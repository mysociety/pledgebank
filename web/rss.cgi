#!/usr/bin/perl -w
#
# rss.cgi:
# RSS feed of new pledges.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: rss.cgi,v 1.16 2005-11-04 18:31:54 matthew Exp $';

use strict;
use warnings;

# Horrible boilerplate to set up appropriate library paths.
use FindBin;
use lib "$FindBin::Bin/../perllib";
use lib "$FindBin::Bin/../../perllib";

use mySociety::Config;
BEGIN {
    mySociety::Config::set_file("$FindBin::Bin/../conf/general");
}
use mySociety::DBHandle qw(dbh);
use mySociety::WatchUpdate;
use mySociety::MaPit;
use PB;

# Hardcoded variables - should be in central conf file.
my %CONF = ( number_of_pledges => 20,
         base_url => mySociety::Config::get('BASE_URL') . '/'
         );

# Other modules we need.
use XML::RSS;
use CGI::Carp;
use CGI::Fast qw(-no_xhtml);
use Error qw(:try);

# Run as a FCGI.
my $W = new mySociety::WatchUpdate();
our $request;
while ( $request = new CGI::Fast() ) {
    $W->exit_if_changed();
    run();
}

################################################################################

sub run {

    # Get the parameters.
    my $type     = $request->param('type');
    my $postcode = $request->param('postcode');
    my $query = $request->param('q');

    # Get the data from the database.
    my $pledges = get_pledges( type => $type, postcode => $postcode, query => $query );
    my $title   = '';

    # If there was a postcode add it to title.
    $title = "Pledges near '$postcode'" if $postcode;

    # If pledges is undef then there was an error.
    unless ( defined $pledges ) {
    $pledges = create_error_pledges();
    $title   = 'Error creating RSS';
    }
    
    # Turn the pledges into RSS
    my $rss = create_rss_from_pledges( $pledges, $title );

    # Return the RSS.
    print CGI->header( -type => 'application/xml' );
    print $rss->as_string;
}

################################################################################
#
# helper subs

# Produce a single line which will be an error.
sub create_error_pledges {
    return [{
    title => 'Error',
    description => 'Something went wrong.',
    ref => '',
    }];    
}

# Create rss from the list of pledges.
sub create_rss_from_pledges {
    my $pledges = shift;
    my $title = shift;

    # Get an rss object.
    my $rss = new_rss_object( $title );

    # Add the pledges to the RSS.
    foreach my $pledge (@$pledges) {

        # Put together the title and description.
        my $title     = "$$pledge{title}";
        my $description = $$pledge{description}
    || sprintf("'I will %s but only if %s %s will %s.'", 
           $$pledge{title}, 
           $$pledge{target}, $$pledge{type}, $$pledge{signup})
        . " -- " . $$pledge{name};

        if ($$pledge{identity}) {
            $description .= ", " . $$pledge{identity};
        }

        # Add then to the rss.
        my $params = {
            title       => $title,
            link        => $CONF{base_url} . $$pledge{ref},
            description => $description,
        };
        # Add geocoding (not a privacy leak to give exact coordinates, as they
        # are mean coordinates of a whole partial postcode area)
        if ($$pledge{latitude} && $$pledge{longitude}) {
            $params->{geo} = {
                lat => $$pledge{latitude},
                lon => $$pledge{longitude},
            }
        }
       $rss->add_item(%$params);
    }

    return $rss;
}

# Try to create an SQL query that can be used to search the
# database. If the postcode lookup fails then returns ''.
sub create_postcode_query {
    my %args = @_;
    my $postcode = $args{postcode} || return '';

    # FIXME - could have a cheaper check here to see if the postcode is good.

    # Check that the postcode is well behaved. If it is no return ''.
    my $loc = '';
    try { $loc = mySociety::MaPit::get_location( $postcode, 1 ); }
    catch RABX::Error with { return ''; }
    otherwise { die "Failed to catch an error"; };

    # Just in case the flash code above failed.
    return '' unless ref($loc) eq 'HASH';

    # Put the location into correct format.
    # 50km. XXX Should be indexed with wgs84_lat, wgs84_lon
    my $find_what = join ', ', $$loc{wgs84_lat}, $$loc{wgs84_lon}, 50;

    my $pb_today = dbh()->selectrow_array('SELECT pb_current_date()');
    # Create the SQL (lifted from pb/web/search.php).
    my $sql = "SELECT pledges.*, distance ";
    $sql .= "  FROM pledge_find_nearby( $find_what ) AS nearby ";
    $sql .= "  LEFT JOIN pledges ON nearby.pledge_id = pledges.id ";
    $sql .= "  WHERE ";
    $sql .= "     pin IS NULL AND ";
    $sql .= "     pb_pledge_prominence(pledges.id) <> 'backpage' AND ";
    $sql .= "     '$pb_today' <= pledges.date ";
    $sql .= "  ORDER BY distance";

    return $sql;
}

sub create_normal_query {
    my %args = @_;
    my $type = $args{type};
    my $query = $args{query};

    my $query_text = "select *
           from pledges
           where pin IS NULL 
           AND pb_pledge_prominence(id) <> 'backpage' ";

    if ($query) {
        $query_text .= " AND title ILIKE " . dbh()->quote("%$query%");
    }

    if ($type && $type eq 'all') {
        $query_text .= " order by id desc ";
    } else {
        $query_text .= " order by id desc 
               limit $CONF{number_of_pledges}";
    }

    return $query_text;
}

# Get the pledges - if there was an error returns undef.
sub get_pledges {
    my %args = @_;
    my $query_text = '';
    
    # Create the sql depending on what args we have.
    if ( $args{postcode} ) {
    $query_text = create_postcode_query( %args );
    } else {
    $query_text = create_normal_query( %args );
    }

    # If we did not get a query then return undef.
    return undef unless $query_text;

    # Run the query.
    my $query = dbh()->prepare($query_text);
    $query->execute;

    # Extract the results onto an array
    my @array = ();

    while ( my $pledge = $query->fetchrow_hashref ) {
        push @array, $pledge;
    }

    # Return as an array ref.
    return \@array;
}

# Create an RSS object.
sub new_rss_object {
    my $title = shift;

    # Create the rss object.
    # Using 1.0, because geo tags didn't appear when using XML::RSS to make a 2.0 file.
    my $rss = XML::RSS->new( version => '1.0' );
    $rss->add_module(prefix=>'geo', uri=>'http://www.w3.org/2003/01/geo/wgs84_pos#');

    # Fill in the details needed.
    $rss->channel(
        title       => $title || 'PledgeBank.com',
        link        => $CONF{base_url},
        language    => 'en',
        description => 'Pledges from PledgeBank.com',
        ttl         => 5,
    );

    return $rss;
}

