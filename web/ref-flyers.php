<?
// ref-flyers.php:
// Flyers pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-flyers.php,v 1.16 2005-12-09 12:37:39 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));

$pin_box = deal_with_pin($p->url_flyers(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
}

$title = _("Flyers");
page_header($title, array('ref' => $p->url_typein(), 'noprint' => true));

// We show both letter and A4 in these countries, only A4 in others
// See http://www.cl.cam.ac.uk/~mgk25/iso-paper.html
$letter_paper_countries = array('US', 'CA', 'MX');

$pdf_a4_flyers8_url = $p->url_flyer("A4_flyers8.pdf");
$pdf_letter_flyers8_url = $p->url_flyer("letter_flyers8.pdf");
$pdf_a4_flyers1_url = $p->url_flyer("A4_flyers1.pdf");
$pdf_letter_flyers1_url = $p->url_flyer("letter_flyers1.pdf");
$rtf_a4_flyers1_url = $p->url_flyer("A4_flyers1.rtf");
$rtf_letter_flyers1_url = $p->url_flyer("letter_flyers1.rtf");
$png_flyers8_url = $p->url_flyer("A4_flyers8.png");

?>
<div class="noprint">
<?  print _('<h2>Customised Flyers</h2>');
print p(_('Here you can get <acronym title="Portable Document Format">PDF</acronym>s or editable <acronym title="Rich Text File">RTF</acronym>s (Word compatible) containing your pledge data, to print out, display, hand out, or whatever.'));
?>
<? if (in_array($site_country, $letter_paper_countries)) { ?>
<ul>
<li><? print_link_with_pin($pdf_letter_flyers8_url, "", _("Letter-sized flyers for handing out, 8 per page (PDF") . (get_http_var("pin", true) ? "" : _(", like picture below")) . ")") ?> </li>
<li><? print_link_with_pin($pdf_letter_flyers1_url, "", _("Letter-sized PDF poster") . 
($p->has_details() ? _(', including more details') : '') ) ?> </li>
<li><? print_link_with_pin($rtf_letter_flyers1_url, "", _("Letter-sized editable poster (RTF)") . 
($p->has_details() ? _(', including more details') : '') ) ?> </li>
</ul>
<? } ?>
<ul>
<li><? print_link_with_pin($pdf_a4_flyers8_url, "", _("A4 flyers for handing out, 8 per page (PDF") . (get_http_var("pin", true) ? "" : _(", like picture below")) . ")") ?> </li>
<li><? print_link_with_pin($pdf_a4_flyers1_url, "", _("A4 PDF poster") . 
($p->has_details() ? _(', including more details') : '') ) ?> </li>
<li><? print_link_with_pin($rtf_a4_flyers1_url, "", _("A4 editable poster (RTF)") . 
($p->has_details() ? _(', including more details') : '') ) ?> </li>
</ul>
</div>
<?
// Show inline graphics only for PINless pledges (as PNG doesn't
// work for the PIN protected ones, you can't POST a PIN
// into an IMG SRC= link)
if (!get_http_var('pin', true)) {
    print '<p class="noprint">';
    printf(_('Alternatively, simply %s to get these flyers.'), print_this_link(_("print this page out"), "") );
    print '</p>';
?>
<p><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="<?=_('Graphic of flyers for printing') ?>"></a></p>
<?  
}

page_footer();

?>
