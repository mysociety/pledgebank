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

my $rcsid = ''; $rcsid .= '$Id: fuzzyref.cgi,v 1.2 2006-12-01 14:24:40 chris Exp $';

use strict;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI;
use CGI::Fast;
use Digest::SHA1;
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

my $stmt = dbh()->prepare("
                select id, ref from pledges
                    where pin is null and prominence <> 'backpage'");
$stmt->execute();
while (my ($id , $ref) = $stmt->fetchrow_array()) {
    index_pledge($id, $ref);
}
$stmt->finish();

sub urlencode ($) {
    my $t = shift;
    $t =~ s#([^A-Z0-9,./-])#sprintf('%%%02x', ord($1)#;
    return $t;
}

while (my $q = new CGI::Fast()) {
    my $ref = $q->param('ref');
    # only called as a GET and with a ref= param
    if ('GET' ne $q->request_method() || !$ref) {
        print $q->redirect('/');
        next;
    }

    my @parts = split_parts($ref);
    my @res = ( );
    foreach my $part (@parts) {
        next unless (exists($pledge_ref_part{$part}));
        my $sc = 1 / @{$pledge_ref_part{$part}};
        foreach my $id (@{$pledge_ref_part{$part}}) {
            $res[$id] ||= [$id, 0];
            $res[$id]->[1] += $sc;
        }
    }

    # sort by goodness-of-match
    @res = sort { $b->[1] <=> $a->[1] } @res;
    # limit to five results
    @res = @res[0 .. 4] if (@res > 5);
    # send only pledge IDs
    @res = map { $_->[0] } @res;
    my $ser = RABX::serialise({
                    ref => $ref,
                    matches => \@res,
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
