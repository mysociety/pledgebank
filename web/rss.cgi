#!/usr/bin/perl -w
#
# rss.cgi:
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: rss.cgi,v 1.1 2005-04-13 16:18:24 chris Exp $';

use strict;

#!/usr/bin/perl

use strict;
use warnings;

# Might need to look somewhere for modules.
# use lib ('');

# Might need to set the config file.
# use mySociety::Config;
# mySociety::Config::set_file('../conf/general');

# Get dbh sub for connecting to db (use one of following lines).
use PB;
# use DBI; sub dbh { DBI->connect( 'dbi:Pg:dbname=pb' ) }; # EvdB's machine

# Hardcoded variables - should be in central conf file.
my %CONF = ( number_of_pledges => 20,
	     base_url => 'http://www.pledgebank.com/'
	     );

# Other modules we need.
use XML::RSS;
use CGI;
use FCGI;

# Run as a FCGI.
my $request = FCGI::Request();
while ( $request->Accept() >= 0 ) {
    run();
}

################################################################################

sub run {

    # Get an rss object.
    my $rss = new_rss_object();

    # Get the data from the database.
    my $pledges = get_pledges();
    my $signers = get_signers();

    # Add the pledges to the RSS.
    foreach my $pledge (@$pledges) {

        # Put together the title and description.
        my $signed_up = $$signers{ $$pledge{id} } || 0;
        my $title     = "$$pledge{title} - $signed_up of $$pledge{target}";

        my $description = "Pledge by '$$pledge{name}'";
        $description .= "\n\n$$pledge{detail}" if $$pledge{detail};

        # Add then to the rss.
        $rss->add_item(
            title       => $title,
            link        => $CONF{base_url} . $$pledge{ref},
            description => $description,
        );

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

    my $query =
      dbh()
      ->prepare( 
"select id, ref, title, target, date, name, detail
   from pledges
   order by id desc 
   limit $CONF{number_of_pledges}"
);

    $query->execute;

    my $arrayref = [];

    while ( my $pledge = $query->fetchrow_hashref ) {
        push @$arrayref, $pledge;
    }

    return $arrayref;
}

# Get signers.
sub get_signers {
    my $query =
      dbh()
      ->prepare( 
"select pledge_id, count(id) as count
  from signers
  where pledge_id in
    ( select id from pledges order by id desc limit $CONF{number_of_pledges})
  group by pledge_id " );

    $query->execute;

    my $hashref = {};

    while ( my $row = $query->fetchrow_hashref ) {
        $$hashref{ $$row{pledge_id} } = $$row{count} || 0;
    }

    return $hashref;
}

# Create an RSS object.
sub new_rss_object {

    # Create the rss object.
    my $rss = XML::RSS->new( version => '2.0' );

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
