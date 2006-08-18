#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# byarea-map.cgi:
#
# Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

# Country shape data files from Centers for Disease Control and Prevention (CDC): 
# http://www.cdc.gov/epiinfo/shape.htm

# TODO: Polyline simplification to speed up Canada
#       http://geometryalgorithms.com/Archive/algorithm_0205/
# Cope with wrap around - US shouldn't be rendered everywhere

my $rcsid = ''; $rcsid .= '$Id: byarea-map.cgi,v 1.2 2006-08-18 21:27:26 francis Exp $';

my $bitmap_size = 500;
my $margin_extra = 0.05;

use strict;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI::Fast;
use Data::Dumper;
use Geo::ShapeFile;
use Cairo;
use POSIX;
use Math::Trig qw(pi);
use Math::Round;
use File::stat;
use Error qw(:try);
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;

my $graph_dir = mySociety::Config::get('PB_GRAPH_DIR');
die "graph output directory '$graph_dir' does not exist and cannot be created"
    unless (-d $graph_dir || mkdir($graph_dir, 0755));

# Transform latitude to mercator projection y coordinate
sub mercator {
    my $lat = shift;
    my $val = Math::Trig::tan(
            Math::Trig::deg2rad($lat)) + Math::Trig::sec(Math::Trig::deg2rad($lat));
    # XXX not really sure what the best thing to do here is
    # this happens only with World, not Europe - presumably artic
    if ($val == 0) {
        return $lat;
    }
    return Math::Trig::rad2deg(log($val));
}

sub r {
    my $num = shift;
#    return Math::Round::round($num * 10) / 10;
    return $num;
}

# Load in country boundary data from shape file
my $countries;
my $country_extents;
sub load_countries {
    my $cn = shift;
    my $shape_filename = "/home/francis/fiddle/cdc/$cn";
    my $sh = new Geo::ShapeFile($shape_filename);
    if (!$sh) {
        warn "$_: $!\n";
        next;
    }
    # loop through shapes
    my $linec;
    for (my $i = 1; $i <= $sh->shapes(); ++$i) {
        #print STDERR ".";
        my $S = $sh->get_shp_record($i);
        my $D = $sh->get_dbf_record($i);
        #print STDERR "$i:\n";
        #print STDERR $D->{CNTRY_NAME} . "\n";
        #next if $D->{CNTRY_NAME} ne 'United Kingdom';
        #foreach (keys %$D) {
        #    print STDERR "  '$_' -> '$D->{$_}'\n";
        #}
        #print STDERR "$i: number of parts: ", $S->num_parts(), "\n";
        #print STDERR $S->dump(), "\n\n";

        # Loop through parts of shape
        my $shape;
        my $extents;
        for(1 .. $S->num_parts) {
            my @part = $S->get_part($_);
                            
            my $first = 0;
            my $part;
            my ($p_x, $p_y);
            foreach my $point (@part) {
                my $x = $point->X;
                my $y = mercator($point->Y);
                push @$part, [$x, $y];
                if (defined($p_x) && defined($p_y)) {
                    $linec->{pack('ffff', r($p_x), r($p_y), r($x), r($y))}++;
                    #warn "$p_x $p_y $x $y got";
                    $extents->{x_min} = $x if (!$extents->{x_min} || $x < $extents->{x_min});
                    $extents->{x_max} = $x if (!$extents->{x_max} || $x > $extents->{x_max});
                    $extents->{y_min} = $y if (!$extents->{y_min} || $y < $extents->{y_min});
                    $extents->{y_max} = $y if (!$extents->{y_max} || $y > $extents->{y_max});
                }
                $p_x = $x;
                $p_y = $y;
            }
            #warn "new part";
            push @$shape, $part;
        }
        $countries->{$D->{CNTRY_NAME}} = $shape;
        $country_extents->{$D->{CNTRY_NAME}} = $extents;
    }
    #print Dumper($countries);
    #print Dumper($country_extents);
    return $countries;
}

# Expand range to fit this country fully in view
my ($x_min, $x_max, $y_min, $y_max);
sub include_extents {
    my ($country) = @_;

    # Loop through shapes
    foreach my $part (@$country) {
        foreach my $line (@$part) {
            my $x = $line->[0];
            my $y = $line->[1];
            $x_min = $x if (!$x_min || $x < $x_min);
            $x_max = $x if (!$x_max || $x > $x_max);
            $y_min = $y if (!$y_min || $y < $y_min);
            $y_max = $y if (!$y_max || $y > $y_max);
        }
    }
}


# Draw the map, making a PNG file
sub plot_country {
    my ($cr, $country, $main) = @_;

    # Loop through shapes
    foreach my $part (@$country) {
        my $first = 1;
        my ($p_x, $p_y);
        foreach my $line (@$part) {
            if ($first) {
                $cr->move_to($line->[0], $line->[1]);
                $first = 0;
            } else {
                #my $rep_count = $linec->{pack('ffff', r($p_x), r($p_y), r($line->[0]), r($line->[1]))};
                my $rep_count = 1;
                #warn "$p_x $p_y " . $line->[0] . " " . $line->[1] . " draw";
                if (!$rep_count) {
                    warn "failed to find linec: $p_x $p_y " . $line->[0] . " " . $line->[1];
                } else {
                    if ($rep_count > 1) {
                        #print STDERR "> 1";
                        $cr->stroke();
                        $cr->move_to($line->[0], $line->[1]);
                    } else {
                        #print STDERR "= 1";
                        $cr->line_to($line->[0], $line->[1]);
                    }
                }
            }
            $p_x = $line->[0];
            $p_y = $line->[1];
        }
        if ($main) {
            $cr->set_source_rgb(1.0, 0.94, 0.87);
        } else {
            $cr->set_source_rgb(0.94, 0.86, 0.72);
        }
        $cr->fill_preserve();
        $cr->set_source_rgb(0, 0.71, 0.93);
        $cr->stroke();
    }
}

sub plot_pins {
    my ($cr, $pins, $sscale) = @_;
    foreach my $pin (@$pins) {
        my ($lat, $lon, $succ, $count) = @$pin;
        if ($succ) {
            $cr->set_source_rgb(0, 1, 0);
        } else {
            $cr->set_source_rgb(1, 0, 0);
        }
        $cr->arc($lon, mercator($lat), 3*sqrt($count)/$sscale, 0, 2*pi);
        #print "ll ", $lon, " ", $lat, "\n";
        $cr->fill_preserve();
        $cr->stroke();
        #print "$lat $lon $succ $count\n";
    }
}
    
sub create_image {
    my ($output, $pledge, $pins) = @_;

    #load_countries('europe');
    load_countries('cntry00');

    my $main_country = "Canada";
    include_extents($countries->{$main_country});

    # Calculate scale and margins
    my $bitmap_w = $bitmap_size;
    my $bitmap_h = $bitmap_size;
    my $all_left = $x_min;
    my $all_top = $y_min;
    my $all_width = ($x_max - $x_min);
    my $all_height = ($y_max - $y_min);
    my $margin_x = $all_width * $margin_extra;
    my $margin_y = $all_height * $margin_extra;
    $all_width += $margin_x * 2;
    $all_height += $margin_y * 2;
    $all_left -= $margin_x;
    $all_top -= $margin_y;
    my $sscale;
    if ($all_width > $all_height) {
        $sscale = $bitmap_w / $all_width;
    } else {
        $sscale = $bitmap_h / $all_height;
    }
    $bitmap_w = $sscale * $all_width;
    $bitmap_h = $sscale * $all_height;
    #warn "all $all_width $all_height - bitmap $bitmap_w $bitmap_h";

    # Create a new image
    my $surface = Cairo::ImageSurface->create('argb32', $bitmap_w, $bitmap_h);
    my $cr = Cairo::Context->create ($surface);
    #$cr->set_antialias('none');

    # Set up scale
    $cr->set_line_width(1/$sscale);
    $cr->translate(0, $bitmap_h);
    $cr->scale(1, -1);
    $cr->scale($sscale, $sscale);
    $cr->translate(-$all_left, -$all_top);

    # Draw the sea
    $cr->set_source_rgb(0.78, 0.91, 0.94);
    #warn "rect: " . ($x_min - $margin_x) . " ". ($y_min - $margin_y) . " $all_width  $all_height";
    $cr->rectangle($x_min - $margin_x, $y_min - $margin_y, $all_width, $all_height);
    $cr->fill_preserve();
    $cr->stroke();

    # Plot stuff
    foreach my $country (keys %$countries) {
        my $extents = $country_extents->{$country};
        if (!($extents->{x_max} < $x_min) && !($x_max < $extents->{x_min}) &&
            !($extents->{y_max} < $y_min) && !($y_max < $extents->{y_min}))
        {
            warn "overlaps: " . $country . "\n";
            plot_country($cr, $countries->{$country}, ($country eq $main_country) ? 1 : 0);
        }
    }

    plot_pins($cr, $pins, $sscale);
    $cr->show_page;
    $surface->write_to_png($output) or die "failed to write_to_png";
}

# Main FastCGI loop
while (my $q = new CGI::Fast()) {
    try {
    my $pledge_id = $q->param('pledge_id');
    $pledge_id = 2042;

    throw PB::Error("No pledge_id specified")
        if (!defined($pledge_id));
    throw PB::Error("Invalid pledge_id '$pledge_id'")
        if ($pledge_id !~ /^[1-9]\d*$/);

    my $P = dbh()->selectrow_hashref('
                    select *,
                        case
                            when date < ms_current_date() then date
                            else ms_current_date()
                        end as graph_date
                    from pledges
                    where id = ?', {}, $pledge_id);

    throw PB::Error("Unknown pledge_id '$pledge_id'") 
    if (!$P);
    if (defined($P->{pin})) {
        # Don't bother producing the graph: it's only of use as an inline
        # image, and we don't want to expose the PIN in that.
        # XXX if you remove this, then add an actual check for the PIN as
        # well!
        throw PB::Error("Permission denied");
    }
    throw PB::Error("Pledge '$pledge_id' is not byarea pledge") 
        if ($P->{'target_type'} ne 'byarea');

    my $pins = dbh()->selectall_arrayref('
        select max(latitude) as lat, max(longitude) as lon, max(whensucceeded) as succeeded, count(*) as count
        from byarea_location 
        left join location on byarea_location.byarea_location_id = location.id
        left join signers on byarea_location.byarea_location_id = signers.byarea_location_id
        where byarea_location.pledge_id = ?
        group by byarea_location.byarea_location_id', {}, $pledge_id);

    my $filename = "out.png";
    my $f = new IO::File("$graph_dir/$filename", O_RDONLY);
    $f = new IO::File("nonexistent", O_RDONLY);
    
    if (!$f && $!{ENOENT}) {
        create_image("$graph_dir/$filename", $P, $pins);
        $f = new IO::File("$graph_dir/$filename", O_RDONLY)
                or die "$graph_dir/$filename: $! (after drawing map)";
    } elsif (!$f) {
        die "$graph_dir/$filename: $!";
    }

    # Map already exists, so emit it. We can't redirect as we may be
    # running on >1 server.
    my $st = stat($f);

    print $q->header(
                -type => 'image/png',
                -content_length => $st->size(),
                -expires => '+20m'
            );
    binmode(STDOUT, ':bytes');
    binmode($f, ':bytes');
    my $buf;
    my $n = 0;
    while ($n < $st->size()) {
        my $m = $f->read($buf, 65536, 0);
        if (!defined($m)) {
            die "$graph_dir/$filename: $!";
        } elsif ($m == 0) {
            last;
        } else {
            $n += $m;
        }
        print $buf;
    }
    $f->close();
    } catch PB::Error with {
        my $E = shift;
        my $t = $E->text();
        print STDERR "$t\n";
        print $q->header(
                    -status => "500 Internal Error: $t",
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($t) + 1
                ), $t, "\n";
    }
} 


