<?php
/*
 * ref-progress.js.php:
 * Produce includable Javascript for showing pledge status on another web page.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-progress.js.php,v 1.7 2007-06-13 16:53:21 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));
microsites_redirect($p);
if (defined($p->pin()) && $p->pin() != '')
    err("Permission denied");

$sentence = $p->h_sentence(array('firstperson'=>true));
$name = $p->h_name_and_identity();
$signers = prettify($p->signers());
$target = prettify($p->target());
$url = $p->url_typein();

$html = <<<EOF
<div class="pledgebank_pledgebox" style="border:solid 1px #522994;font-family:'Lucida Grande','Lucida Sans Unicode','Lucida Sans',Arial,sans-serif;width:17em;font-size:83%;margin-bottom:1em">
<div style="font-weight:bold;background-color:#9c7bbd;color:#ffffff;border-bottom:solid 1px #522994;padding:2px">
<a href="http://www.pledgebank.com/" style="text-decoration:none">
<span style="color:#ffffff;background-color:#9c7bbd;">Pledge</span><span
 style="color:#21004a;background-color:#9c7bbd;">Bank</span></a>
</div>
<ul class="pledgebank_blurb" style="margin:0;padding:2px 2px 2px 1.5em">
<li class="pledgebank_pledgetext"><p style="margin:0"><a style="color:#522994;"
 href="$url">$sentence</a></p>
 <p style="margin:0" align="right">&mdash; $name</p></li>
<li class="pledgebank_progress">
EOF;
if ($p->finished())
    $html .= sprintf(ngettext('%s person signed up', '%s people signed up', $p->signers()), prettify_num($p->signers()));
else
    $html .= sprintf(ngettext('%s person has signed up', '%s people have signed up', $p->signers()), prettify_num($p->signers()));
if ($p->left() <= 0) {
    $html .= ' ' . sprintf('(%s over target)', prettify_num(-$p->left()));
    $html .= ' &mdash; success!';
} else {
    $html .= ', ';
    if ($p->finished())
        $html .= sprintf(ngettext('%d more was needed', '%d more were needed', $p->left()), $p->left());
    else
        $html .= sprintf(ngettext('%d more needed', '%d more needed', $p->left()), $p->left());
}

$html .= "</li>";

if ($p->open()) {
    $html .= '<li class="pledgebank_open">Open until ' . $p->h_pretty_date();
    $html .= ' &mdash; <a style="color:#522994;" href="' . $url . '">Sign this pledge &raquo;</a></li>';
} else {
    $html .= '<li class="pledgebank_closed">Closed on ' . $p->h_pretty_date() . '</li>';
}
$html .= '</ul>';
$html .= '</div>';

header('Content-Type: text/javascript; charset=utf-8');
$html = addslashes($html);  /* XXX check this works with UTF-8 and is correct
                             * for JS. */
$html = preg_replace("/\n/s", '\\n', $html);
print "<!--\ndocument.write(\"$html\");\n//-->\n";

?>
