package PB::PDF;
use strict;

use File::Temp ();
use PDF::API2;
use Text::Autoformat;
use POSIX ();
use Data::Dumper;

sub new {
    my ($class, $vars) = @_;
    my $self = {};
    bless $self, $class;
    $self->{'class'} = $class;
    $self->defaults($vars);
    return $self;
}

sub error {
    my ($self, $error) = @_;
    $error = 'Unknown error' unless $error;
    warn "$self->{'class'} error: $error";
    return undef;
}

sub defaults {
    my ($self, $vars) = @_;
    $self->{'output_dir'} = '/home/ejhp98/projects/pledgebank/output';
    $self->{'fonts_dir'} = '/home/ejhp98/projects/pledgebank/fonts';
    foreach my $key (keys %{$vars}) {
    $self->{'pledge'}{$key} = $vars->{$key};
    }
    return $self;
}

sub get_temp_filename {
    my ($tmp_fh, $tmp_name) = File::Temp::tmpnam();
    return $tmp_name;
}

sub make {
    my ($self,$scaling_size) = @_;

    my $tmp_filename = get_temp_filename();

    my $pdf = PDF::API2->new(-file => $tmp_filename);
    $pdf->preferences(-fitb => 1); # viewer shows full page

    my ($page_width,$page_height,$page_margin) = (595.27,842.89,36);
    my $page_centre = $page_width / 2;
    # warn "page center: " . $page_centre;
    $pdf->mediabox($page_width,$page_height);

    my $page = $pdf->page;

    # todo - put black line (box) around entire page, using A4
    # dimensions to create mediabox.

    my $gfx = $page->gfx();
    $gfx->rect($page_margin,$page_margin,
#           300,100);
           $page_width-(2*$page_margin),$page_height-(2*$page_margin));
    $gfx->stroke;
    $gfx->endpath();

    # todo - work out a better way of doing vertical line spacing;
    # maybe have a stack and peek ahead?

    unless ($self->{'pledge'}{'creator'}) {
        $self->{'pledge'}{'creator'} = 'me';
    }

    my $poster_type = $self->{'pledge'}{'poster-type'};
    my $output_text;
    if ($poster_type eq "original") {
        $output_text = <<OUTPUT;
XY,$page_centre,720
R,40,I will
R,20,
B,50,$self->{'pledge'}{'text'}
#XY,$page_centre,500
R,20,
R,40,...but only if
R,20,
B,40,$self->{'pledge'}{'target'} other people
R,20,
R,40,pledge to do the
R,40,same with
R,20,
B,40,pledgebank.com
R,80,
XY,$page_centre,120
B,20,Go to pledgebank.com/$self->{'pledge'}{'ref'}
B,20,or send an SMS to $self->{'pledge'}{'sms'} (costs 25p)
B,20,to make the same pledge as $self->{'pledge'}{'creator'}
OUTPUT
    } elsif ($poster_type eq "friendly-flyer") {
        $output_text = <<OUTPUT;
XY,$page_centre,720
R,30,I, $self->{'pledge'}{'creator'},
R,15,
B,30,$self->{'pledge'}{'text'}
R,15,
R,30,if $self->{'pledge'}{'target'} $self->{'pledge'}{'people'} $self->{'pledge'}{'people-text'}.
R,80,
XY,$page_centre,325
R,18,Please support me by signing up, and by encouraging other people to do the same. I am using the free service PledgeBank.com to gather support. It will only take you a few seconds - sign up free at 
R,18,
B,18,www.pledgebank.com/wharfmeet
B,18,OR text 'pledge wharfmeet' to $self->{'pledge'}{'sms'} 
R,18,(cost 25p)
R,18,
R,18,This pledge closes on $self->{'pledge'}{'date'}. Thanks!
OUTPUT
    } else {
        $self->error("Unknown poster type '$poster_type'");
        return undef;
    }
    
    # Word wrap lines
    my @lines;
    foreach my $line (split("\n",$output_text)) {
        next if ($line =~ /^\s*\#/);
        next unless ($line =~ /(.),([\d\.]+),(.*)/);

        if (lc($1) eq 'xy' || $3 eq '') {
            push @lines, $line;
            next;
        }

        my $font_size = $2;
        my $remainder = $3;
        my $max_line_length = POSIX::ceil( 750 / $font_size );
        my $text_length = length($remainder);
        my $num_lines = POSIX::ceil( $text_length / $max_line_length );
        if ($num_lines > 1) {
            $font_size = POSIX::ceil( 50 / $num_lines );
        }

        #warn "Reformat line to $num_lines lines, font size $font_size, max length $max_line_length:";
        #warn $line;

        if ($num_lines > 1) {

            my $new_text = autoformat( $remainder, { 'left' => 0, 'right' => $max_line_length } );
            foreach my $new_line (split ("\n", $new_text)) {
                push @lines, "$1,$2,$new_line";
            }
            next;

        } else {
            push @lines, $line;
        }
    }

    my $text = $page->text;
    $text->compress; # CPU cheap, bandwidth not so cheap. Actually, it takes less CPU anyway! - FAI

    # Render wordwrapped lines
    my ($x,$y);
    foreach my $line (@lines) {
        #print "Line: $line\n";
        next unless ($line =~ /(.+),([\d\.]+),(.*)/);

        if (lc($1) eq 'xy') {
            my ($tx,$ty) = ($2,$3);
            if ($tx =~ /^[\d\.]+$/ && $ty =~ /^[\d\.]+$/) {
                ($x,$y) = ($tx,$ty);
                $text->translate($x,$y);
                # warn "XY $x,$y";
                next;
            } else {
                $self->error("Non-integer XY coordinates ($line)");
                next;
            }
        }

        my $weight;
        if ($1 eq 'R') {
            $weight = '';
        } elsif ($1 eq 'B') {
            $weight = '-Bold';
        } elsif ($1 eq 'I') {
            $weight = '-Italic';
        }

        my $font_name = $self->{'pledge'}{'truetype-font'};
        my $font;

        if (exists($self->{'pledge'}{'truetype-font'}) && 
            $self->{'pledge'}{'truetype-font'} =~ /(.+).ttf/) {
            $font = $pdf->ttfont("$1$weight");
        } else {
            $font = $pdf->corefont("Georgia$weight");
        }
        
        my $font_size = $2;
        my $remainder = $3;

        $y -= 5;
        $text->translate($x,$y);

        $text->font($font,$font_size);
        $text->text_center($remainder);

        $y -= $font_size;
        $text->translate($x,$y);

    }

    $pdf->finishobjects($page,$text);
    $pdf->saveas;
    $pdf->end;

    # Now write to file, and do any scaling
    if ($scaling_size =~ /a(\d)/i) {
        my $paper_size = $1;

        my $scaled_filename = get_temp_filename();
        my $new_pdf = PDF::API2->new(-file => $scaled_filename);
        my $new_page = $new_pdf->page;

        unless ($pdf = PDF::API2->open($tmp_filename)) {
            $self->error("Cannot open $tmp_filename");
            return undef;
        }

        my $form = $new_pdf->importPageIntoForm($pdf,1);
        my $new_gfx = $new_page->gfx();

        if ($paper_size < 4) {

            # todo - resize page and scale up to A3, etc. (see below
            # for how to).

        } elsif ($paper_size > 4) {

            # get xoform, transform and apply appropriate number to
            # $new_page

            my ($form_width,$form_height,$per_row,$num_rows,$rotate);

            $num_rows = 2 ** ($paper_size - 5);
            print "rows $num_rows";

            if ($paper_size % 2 == 0) {
                # todo - work out how many copies we need, calculate x,y
                $per_row = $num_rows;
                $rotate = 1;
                $form_width = $form_width / $num_rows;
                $form_height = $form_height / $num_rows;
            } else {
                $per_row = $num_rows / 2;
                $rotate = 0;
                $form_width = $form_height / $num_rows;
                $form_height = $form_width / $per_row;
            }

            unless ($num_rows > 0 && $per_row > 0) {

                $self->error("Unsupported number of rows ($num_rows) or copies per row ($per_row), ignoring pagination.");
                $new_pdf->end;
                unlink ($scaled_filename);
                chmod(0644,$tmp_filename);
                return $tmp_filename;

            } else {

                # todo - resize and rotate xoform accordingly
                warn "Paper size: $paper_size ($num_rows,$per_row). Rotate: $rotate";
                $form->rotate(90) if ($rotate);
                $form->rotate($form_width,$form_height);

                foreach my $row (1 .. $num_rows) {
                    foreach my $col (1 .. $per_row) {
                    warn "Copy: $row,$col";
                    $gfx->formimage($form,
                            ($col-1)*$form_width,
                            ($row-1)*$form_height);
                    }
                }
            }

            $new_pdf->finishobjects($new_page,$gfx);
            $new_pdf->saveas;
            $new_pdf->end;
            chmod(0644,$scaled_filename);
            unlink ($tmp_filename);
            print "scaled one $scaled_filename\n";
            return $scaled_filename;

        }

    } else {
        $self->error("Unsupported scaling size ($scaling_size) - using A4.");
    }
    
    # warn "File is $tmp_filename";
    
    # chmod(0644,$tmp_filename);
    return $tmp_filename;

}


sub get_template {
    my ($self, $ref) = @_;
    unless ($ref) {
    $self->error("No parameters!");
    return undef;
    }
    unless (defined($ref->{'pledge-id'})) {
    $self->error("Template id not found");
    return undef;
    }
    map {
    unless ($ref->{$_}) {
        $self->error("$_ not found");
        return undef;
    }
    } qw (short-name long-name);
    unless ($ref->{'pledge-id'} =~ /^\d+$/) {
    $self->error("Pledge id is non-numeric: $ref->{'pledge-id'}");
    return undef;
    }
    map {
    $self->{$_} = $ref->{$_};
    } qw (pledge-id short-name long-name);

    return undef unless ($self->get_template_metadata());

    if (-d $self->{'fonts_dir'}) {
        PDF::API2::addFontDirs($self->{'fonts_dir'});
      }

    my $template_filename = "$self->{'template_dir'}/$ref->{'pledge-id'}.pdf";
    unless (-e $template_filename) {
    $self->error("Cannot find $template_filename");
    return undef;
    }
    $self->{'pdf'} = PDF::API2->open($template_filename);
    unless ($self->{'pdf'}) {
    $self->error("Cannot open $template_filename");
    return undef;
    }
    if ($self->{'pdf'}->pages == 1) {
    return $self;
    } else {
    $self->error("No pages found in $template_filename");
    return undef;
    }
}

sub get_template_metadata {
    my ($self) = @_;
    my $metadata_filename = "$self->{'metadata_dir'}/$self->{'template-id'}.data";
    unless (-e $metadata_filename) {
    $self->error("Cannot find $metadata_filename");
    return undef;
    }
    unless (open(IN,$metadata_filename)) {
    $self->error("Cannot open $metadata_filename");
    return undef;
    }
    while (<IN>) {
    next if (/^\#/);
    if (/(text-colour|font-file):\s*(.+)/) {
        $self->{'metadata'}{$1} = $2;
    } elsif (/(.*):\s*(\d+),\s*(\d+),\s*(\d+)/) {
        $self->{'text'}{$1}{'x'} = $2;
        $self->{'text'}{$1}{'y'} = $3;
        $self->{'text'}{$1}{'font-size'} = $4;
    }
    }
    unless ($self->{'text-colour'}) {
    $self->{'metadata'}{'text-colour'} = 'black';
    }
    return $self;
}

1;

__END__

=head1 NAME

PB::PDF - create PDF posters and flyers for PledgeBank.

=head1 SYNOPSIS

my $pdf = PB::PDF->new( { 
              'poster-type' => 'original',
              'pledge-id' => 0,
              'target' => 100,
              'ref' => 'whales',
              'text' => 'stop using whale blubber as insulation',
              'sms' => 12345,
              });

$filename = $pdf->make('A4');

=head1 DESCRIPTION

Given salient details of a pledge (long and short names) will produce
nice PDF posters and flyers. Given an A4 input, we can create output
containing A4, A5, A6 or A7 versions of the original on an A4 sheet.

A5/A6/A7 output - we first create the A4 output; then we shrink it and
create a new A4 output with:

A5 - 2 rotated A5 versions (height is sqrt(2)/2 of A4 height)

A6 - 4 non-rotated A6 versions (height is 1/2 of A4 height)

A7 - 8 rotated A7 versions (height is sqrt(2)/8 of the A4 height).

Each A4 input has an associated text file describing the orientation
(portait/landscape), the X1,Y1 and X2,Y2 bounding boxes for the pledge
name (long and short forms), the required text colour (black/white)
and the preferred font name.

=head1 BUGS

The pagination code doesn't work. The algorithm seems fine to me, but
PDF::API2 seems to be having trouble importing pages from existing
PDFs and applying them to a new PDF. It's probably very simple, but I
can't work out what I'm doing wrong. If you find out, please tell me!

US Letter paper - no support for Letter paper sizes (yet).

=head1 CONTACT

Etienne Pollard - mysociety@ejhp.net or etienne.pollard@mckinsey.com
