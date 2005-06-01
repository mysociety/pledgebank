#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# graph.cgi:
# Draw graphs of pledge progress.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: graph.cgi,v 1.3 2005-06-01 09:39:05 chris Exp $';

use strict;

require 5.8.0;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI;
use CGI::Fast;
use Date::Calc qw(Add_Delta_Days Add_Delta_YM Day_of_Week Delta_Days);
use DateTime::Format::Strptime;
use Digest::SHA1;
use Encode;
use Error qw(:try);
use POSIX;
use Time::HiRes qw(sleep);
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;

sub mkdirp ($;$) {
    my ($dir, $mask) = @_;
    my @dd = split(/\//, $dir);
    for (my $i = 1; $i < @dd; ++$i) {
        my $d = join('/', @dd[0 .. $i]);
        mkdir($d, $mask);
    }
}

# Where we stuff the graphs output.
my $dir_hash_levels = 2;
# XXX produce the hashed directories, if we need to

my ($gnuplot_pid, $gnuplot_pipe, $gnuplot_uses);

my $gnuplot_font = mySociety::Config::get('GNUPLOT_FONT');
die "font for axis labels in gnuplot should be an absolute pathname ending '.ttf', not '$gnuplot_font'"
    unless ($gnuplot_font =~ m#^/.+/[^/]+\.ttf$# && -e $gnuplot_font);
my ($gnuplot_font_dir, $gnuplot_font_face) = ($gnuplot_font =~ m#^/(.+)/([^/]+)\.ttf$#);

# spawn_gnuplot_if_necessary
# Ensure that we have a pipe to gnuplot.
sub spawn_gnuplot_if_necessary () {
    return if (defined($gnuplot_pid));
    
    # Do this here so that any error appears from the parent process.
    my $gnuplot_bin = mySociety::Config::get('GNUPLOT_PATH');

    # Taken from the PoliticalSurvey2005 code.
    $gnuplot_pipe = new IO::Pipe()
        or die "pipe: $!";
    $gnuplot_pid = fork();
    if (!defined($gnuplot_pid)) {
        die "fork: $!";
    } elsif ($gnuplot_pid == 0) {
        # Child process. Various plumbing, then exec gnuplot.
        umask(0022);
        $gnuplot_pipe->reader();
        POSIX::close(0);
        POSIX::close(1);
        POSIX::close(2);
        # stdin from pipe
        POSIX::dup($gnuplot_pipe->fileno());
        $ENV{GDFONTPATH} = $gnuplot_font_dir;
        # stdout/err to /dev/null
        POSIX::open('/dev/null', O_WRONLY);
        POSIX::open('/dev/null', O_WRONLY);
            # NB this means that we won't see any error output
            # from gnuplot, but the alternative is to accept
            # screeds of irrelevant crap from it.
        { exec($gnuplot_bin); }
        exit(1);
    } else {
        warn "gnuplot's PID is $gnuplot_pid; ours is $$";
        $gnuplot_pipe->writer();
        $gnuplot_pipe->autoflush(1);    # don't want print to buffer
        $gnuplot_uses = 0;
    }
}

# g TEXT
# Send TEXT to gnuplot.
sub g ($) {
    die "no gnuplot process available"
        if (!defined($gnuplot_pid));
    $gnuplot_pipe->print($_[0])
        or die "write to gnuplot: $!";
}

while (my $q = new CGI::Fast()) {
    try {
        my $pledge_id = $q->param('pledge_id');

        throw PB::Error("No pledge_id specified")
            if (!defined($pledge_id));
        throw PB::Error("Invalid pledge_id '$pledge_id'")
            if ($pledge_id !~ /^[1-9]\d*$/);

        my $P = dbh()->selectrow_hashref('
                        select *,
                            case
                                when date < pb_current_date() then date
                                else pb_current_date()
                            end as graph_date
                        from pledges
                        where id = ?', {}, $pledge_id);

        throw PB::Error("Unknown pledge_id '$pledge_id'")
            if (!$P);

        my $interval = $q->param('interval');
        $interval = 'pledge'
            if (!defined($interval) || $interval !~ /^(pledge|week|month|year)$/);

        # Compute start and end dates of the plot.
        my $end_date = $P->{graph_date};
        my $start_date;
        if ($interval eq 'pledge') {
            # Extract date part of pledge creation time,
            $start_date = substr($P->{creationtime}, 0, 10);
        } elsif ($interval eq 'week') {
            $start_date = sprintf('%04d-%02d-%02d', Add_Delta_Days(split(/-/, $end_date), -7));
        } elsif ($interval eq 'month') {
            $start_date = sprintf('%04d-%02d-%02d', Add_Delta_YM(split(/-/, $end_date), 0, -1));
        } elsif ($interval eq 'year') {
            $start_date = sprintf('%04d-%02d-%02d', Add_Delta_YM(split(/-/, $end_date), -1, 0));
        }

        # Make sure the graph shows at least a few days.
        if (Delta_Days(split(/-/, $start_date), split(/-/, $end_date)) < 7) {
            $start_date = sprintf('%04d-%02d-%02d', Add_Delta_Days(split(/-/, $end_date), -7));
        }

        my $gparam = join(',', $pledge_id, $start_date, $end_date);
        my $hash = Digest::SHA1::sha1_hex($gparam);

        # See where the graph would go.
        my $filename = join('/', (split(//, $hash))[0 .. ($dir_hash_levels - 1)])
                        . "/$gparam.png";
        if (!-e mySociety::Config::get('PB_GRAPH_DIR') . "/$filename") {
            # Don't have a graph, so create it.
            spawn_gnuplot_if_necessary();

            # We need two sets of data: the cumulative number of signers during
            # the period in question, and a timeseries of the rate of signups.
            # Dump both of these in temporary files so that they can be read
            # with gnuplot.

            # Decide how large the buckets in which the signup rate is
            # displayed should be. Basically we want to have a reasonable
            # number of bars, but not too many or too few.
            my $length = Delta_Days(split(/-/, $start_date), split(/-/, $end_date));
            my $bucket = 'day';
            if ($length > 70) {
                $bucket = 'week';
            } elsif ($length > 500) {
                $bucket = 'month';
            }

            my ($h, $signers_file) = mySociety::Util::named_tempfile();

            # + 1 to account for signer
            my $n = dbh()->selectrow_array('select count(id) from signers where pledge_id = ? and signtime::date < ?', {}, $pledge_id, $start_date) + 1;
            my $n1 = dbh()->selectrow_array('select count(id) from signers where pledge_id = ? and signtime::date <= ?', {}, $pledge_id, $end_date) + 1;

            my $s = dbh()->prepare('
                        select signtime::date from signers
                        where pledge_id = ?
                            and signtime::date >= ? and signtime::date < ?
                        order by signtime');

            $h->printf("%s %d\n", $start_date, $n);
            $s->execute($pledge_id, $start_date, $end_date);

            my %ts = ( );
            while (my ($date) = $s->fetchrow_array()) {
                $h->printf("%s %d\n", $date, ++$n);
                if ($bucket eq 'day') {
                    ++$ts{$date};
                } elsif ($bucket eq 'week') {
                    my ($Y, $m, $d) = split(/-/, $date);
                    my $dow = Day_of_Week($Y, $m, $d) % 7;
                    # 0 = Sunday, 6 = Saturday
                    # rebase this to the middle of the week.
                    ($Y, $m, $d) = Add_Delta_Days($Y, $m, $d, 3 - $dow);
                    ++$ts{sprintf('%04d-%02d-%02d', $Y, $m, $d)};
                } elsif ($bucket eq 'month') {
                    # Rebase this to the middle of the month.
                    my ($Y, $m, $d) = split(/-/, $date);
                    $d = int((Days_in_Month($Y, $m) + 1) / 2);
                    ++$ts{sprintf('%04d-%02d-%02d', $Y, $m, $d)};
                }
            }

            $h->printf("%s %d\n", $end_date, $n1);
            $h->close();

            ($h, my $signuprate_file) = mySociety::Util::named_tempfile();
            $h->print("2000-01-01 0\n");    # avoid "no data point found" error
            foreach (keys %ts) {
                $h->printf("%s %d\n", $_, $ts{$_});
            }
            $h->close();

            my $graphfile = mySociety::Config::get('PB_GRAPH_DIR') . "/$filename";

            my $datefmt = '%d %b';
                # XXX really we should do something more sensible, like
                # labelling the month only when it changes.
            if ($bucket eq 'month') {
                $datefmt = "%b '%y";
            }

my $fontface = 'arial';

            g(<<EOF
reset
set term png enhanced size 500,300 xffffff x000000 xaaaaaa x9c7bbd x522994 x21004a font $gnuplot_font_face 10
set output '$graphfile.new'
set timefmt '%Y-%m-%d'
set xdata time
set xrange ['$start_date':'$end_date']
set yrange [0:*]
set noborder
set noarrow
set nolabel
set nozeroaxis
set nokey
set xtics nomirror
set ytics nomirror
set y2tics
set y2label "total number of signers"
set format x '$datefmt'
set ylabel 'signups per $bucket'
plot "$signuprate_file" using 1:2 with impulses lt 1 lw 15, "$signers_file" using 1:2 axes x1y2 with steps lt -1 lw 1
show output
system "mv $graphfile.new $graphfile"
EOF
                        );

            # Now wait until the file is created.
            my $i = 0;
            while (!-e "$graphfile") {
                sleep(0.1);
                ++$i;
                die "timed out waiting for gnuplot to render graph" if ($i == 20);
            }

            unlink($signuprate_file);
            unlink($signers_file);

            ++$gnuplot_uses;

            # Don't use an individual gnuplot process for too long -- they
            # probably leak memory or some bloody thing.
            if ($gnuplot_uses > 1000) {
                $gnuplot_pipe->close();
                kill(SIGTERM, $gnuplot_pid);
                undef($gnuplot_pid);
                    # child should be automatically reaped
            }
        }

        # Graph already exists, so just redirect to it.
        print $q->redirect(
                    -uri => mySociety::Config::get('PB_GRAPH_URL') . "/$filename",
                    -status => 302
                );
    } catch PB::Error with {
        my $E = shift;
        my $t = $E->text();
        print STDERR "$t\n";
        print $q->header(
                    -status => "500 Internal Error: $t",
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($t) + 1
                ), $t, "\n";
    } otherwise {
        my $E = shift;
        # Try to avoid leaving zombies, but don't actually wait as we don't
        # want to hang on a stuck gnuplot.
        if (defined($gnuplot_pid)) {
            kill(SIGKILL, $gnuplot_pid);
            $gnuplot_pipe->close();
            sleep(0.1);
        }
        $E->throw();
    };
}
