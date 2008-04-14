#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# fuzzyref.cgi:
# Bogus pledge ref fuzzy match.
#
# This is nasty. All the pledge display code is in PHP but we can't really do
# the fuzzy match stuff there because it needs persistent storage (it could go
# in the database but that was really slow). So do the search here and redirect
# back to a PHP page which actually displays it. Avoid the obvious XSS bug by
# not being incompetent.
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: fuzzyref.cgi,v 1.11 2008-04-14 08:24:04 francis Exp $';

use strict;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI;
use mySociety::CGIFast;
use Digest::SHA1 qw(sha1);
use MIME::Base64;
use POSIX;
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;
use RABX;

my @pledges;
my %pledge_ref_part;

sub split_parts ($) {
    return ( ) if (length($_[0]) < 3);
    
    my %res = ( );
    for (my $i = 0; $i < length($_[0]) - 2; ++$i) {
        ++$res{lc(substr($_[0], $i, 3))};
    }
    return (keys %res);
}

sub index_pledge ($$) {
    my ($id, $ref) = @_;
    $pledges[$id] = $ref;
    foreach my $part (split_parts($ref)) {
        push(@{$pledge_ref_part{$part}}, $id);
    }
}

sub do_index() {
    @pledges = ( );
    %pledge_ref_part = ( );
    my $stmt = dbh()->prepare("
                    select id, ref from pledges
                        where pin is null and cached_prominence <> 'backpage'");
    $stmt->execute();
    while (my ($id , $ref) = $stmt->fetchrow_array()) {
        index_pledge($id, $ref);
    }
    $stmt->finish();
}

my $last_indexed = 0;

while (my $q = new mySociety::CGIFast()) {
    my $ref = $q->param('ref');
    # only called as a GET and with a ref= param
    if ('GET' ne $q->request_method() || !$ref) {
        print $q->redirect('/');
        next;
    }

    if ($last_indexed < time() - 600) {
        do_index();
        $last_indexed = time();
    }

    my $n = length($ref) < 20 ? length($ref) : 20;
    my @parts = split_parts(substr($ref, 0, $n));
    my %res = ( );
    foreach my $part (@parts) {
        next unless (exists($pledge_ref_part{$part}));
        my $sc = 1 / @{$pledge_ref_part{$part}};
        foreach my $id (@{$pledge_ref_part{$part}}) {
            $res{$id} += $sc;
        }
    }

    # sort by goodness-of-match
    my @matches = sort { $res{$b} <=> $res{$a} } keys(%res);
    # limit to five results
    @matches = @matches[0 .. 4] if (@matches > 5);
    my $ser = RABX::serialise({
                    ref => $ref,
                    matches => \@matches,
                    salt => int(rand(0xffffffff))
                });

    # 
    # XSS hole here is pretty limited (would just allow the attacker to force a
    # set of existing pledges into the 404 page) but let's handle it properly
    # anyway.
    # 
    $ser .= sha1(PB::DB::secret() . $ser);
    $ser = encode_base64($ser, '');
    $ser =~ s/=+$//;

    print $q->redirect("/bogusref?ser=$ser");
}
