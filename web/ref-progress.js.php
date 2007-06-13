<?php
/*
 * ref-progress.js.php:
 * Produce includable Javascript for showing pledge status on another web page.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-progress.js.php,v 1.8 2007-06-13 20:38:47 matthew Exp $
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
<style type="text/css">
.pb_pledgebox { border: solid 1px #522994; font-family: 'Lucida Grande','Lucida Sans Unicode','Lucida Sans',Arial,sans-serif; font-size: 83%; margin-bottom: 1em; }
.pb_pledgebox p { margin: 0; }
.pb_pledgebox p.pb_gap { margin: 0 0 0.5em; }
.pb_pledgebox a { color: #522994; font-weight: normal; text-decoration: underline; }
.pb_header { font-weight: bold; background-color: #9c7bbd; color: #ffffff; border-bottom: solid 1px #522994; padding: 2px; }
.pb_header a { text-decoration: none; }
.pb_header span { background-color: #9c7bbd; }
</style>
<div class="pb_pledgebox">
<div class="pb_header">
<a href="http://www.pledgebank.com/"><span style="color:#ffffff;">Pledge</span><span style="color:#21004a;">Bank</span></a>
</div>
<p class="pb_pledgetext"><a href="$url">$sentence</a></p>
<p class="pb_gap" align="right">&mdash; $name</p>
<p class="pb_progress pb_gap">
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

$html .= "</p>";

if ($p->open()) {
    $html .= '<p class="pb_open">Open until ' . $p->h_pretty_date();
    $html .= ' &mdash; <a href="' . $url . '">Sign this pledge &raquo;</a></p>';
} else {
    $html .= '<p class="pb_closed">Closed on ' . $p->h_pretty_date() . '</p>';
}
$html .= '</div>';

header('Content-Type: text/javascript; charset=utf-8');
$html = addslashes($html);  /* XXX check this works with UTF-8 and is correct
                             * for JS. */
$html = preg_replace("/\n/s", '\\n', $html);
print "<!--\ndocument.write(\"$html\");\n//-->\n";

?>
