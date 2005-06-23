<?
// ref-flyers.php:
// Flyers pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-flyers.php,v 1.8 2005-06-23 23:32:32 matthew Exp $

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
page_header($title, array('ref' => $p->url_main()) );

$pdf_flyers8_url = $p->url_flyer("A4_flyers8.pdf");
$pdf_flyers1_url = $p->url_flyer("A4_flyers1.pdf");
$rtf_flyers1_url = $p->url_flyer("A4_flyers1.rtf");
$png_flyers8_url = $p->url_flyer("A4_flyers8.png");

?>
<div class="noprint">
<h2>Customised Flyers</h2>
<p>Here you can get <acronym title="Portable Document Format">PDF</acronym>s or editable <acronym title="Rich Text File">RTF</acronym>s (Word compatible) containing your pledge data, to print out, display, hand out, or whatever.</p>
<ul>
<li><? print_link_with_pin($pdf_flyers8_url, "", "Flyers for handing out, 8 per page (A4, PDF" . (get_http_var("pin") ? "" : ", like picture below") . ")") ?> </li>
<li><? print_link_with_pin($pdf_flyers1_url, "", "A4 PDF poster" . 
($p->has_details() ? ', including more details' : '') ) ?> </li>
<li><? print_link_with_pin($rtf_flyers1_url, "", "A4 editable poster (RTF)" . 
($p->has_details() ? ', including more details' : '') ) ?> </li>
</ul>
</div>
<?
// Show inline graphics only for PINless pledges (as PNG doesn't
// work for the PIN protected ones, you can't POST a PIN
// into an IMG SRC= link)
if (!get_http_var('pin')) {
?>
<p class="noprint">Alternatively, simply 
<?print_this_link("print this page out", "")?>
to get these flyers.
</p>

<p><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="<?=_('Graphic of flyers for printing') ?>"></a></p>
<?  
}

page_footer();

?>
