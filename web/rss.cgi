#!/usr/bin/perl -w
#
# rss.cgi:
# RSS feed of new pledges.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: rss.cgi,v 1.11 2005-07-11 16:55:03 francis Exp $';

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
use PB;

# Hardcoded variables - should be in central conf file.
my %CONF = ( number_of_pledges => 20,
	     base_url => mySociety::Config::get('BASE_URL') . '/'
	     );

# Other modules we need.
use XML::RSS;
use CGI::Carp;
use CGI::Fast qw(-no_xhtml);

# Run as a FCGI.
my $W = new mySociety::WatchUpdate();
our $request;
while ( $request = new CGI::Fast() ) {
    $W->exit_if_changed();
    run();
}

################################################################################

sub run {

    # Get an rss object.
    my $rss = new_rss_object();

    # Get the data from the database.
    my $pledges = get_pledges($request->param('type'));

    # Add the pledges to the RSS.
    foreach my $pledge (@$pledges) {

        # Put together the title and description.
        my $title     = "$$pledge{title} (target $$pledge{target}, deadline $$pledge{date})";
        my $description = "Pledge by $$pledge{name}";
        $description .= "\n\n$$pledge{detail}" if $$pledge{detail};

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

    # Return the RSS.
    print CGI->header( -type => 'application/xml' );
    print $rss->as_string;
}

################################################################################
#
# helper subs

# Get the pledges
sub get_pledges {
    my $type = shift;

    my $query_text = "select id, ref, title, target, date, name, detail, latitude, longitude
           from pledges
           where pin IS NULL 
           AND pb_pledge_prominence(id) <> 'backpage' ";

    if ($type && $type eq 'all') {
        $query_text .= " order by id desc ";
    } else {
        $query_text .= " order by id desc 
               limit $CONF{number_of_pledges}";
    }

    my $query = dbh()->prepare($query_text);

    $query->execute;

    my $arrayref = [];

    while ( my $pledge = $query->fetchrow_hashref ) {
        push @$arrayref, $pledge;
    }

    return $arrayref;
}

# Create an RSS object.
sub new_rss_object {

    # Create the rss object.
    # Using 1.0, because geo tags didn't appear when using XML::RSS to make a 2.0 file.
    my $rss = XML::RSS->new( version => '1.0' );
    $rss->add_module(prefix=>'geo', uri=>'http://www.w3.org/2003/01/geo/wgs84_pos#');

    # Fill in the details needed.
    $rss->channel(
        title       => 'PledgeBank.com',
        link        => $CONF{base_url},
        language    => 'en',
        description => 'Newest pledges from PledgeBank.com',
        ttl         => 5,
    );

    return $rss;
}
