<?php
/*
 * ref-progress.js.php:
 * Produce includable Javascript for showing pledge status on another web page.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-progress.js.php,v 1.10 2007-08-15 12:51:00 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';
require_once '../phplib/js.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));
microsites_redirect($p);
if (defined($p->pin()) && $p->pin() != '')
    err("Permission denied", E_USER_NOTICE);

$sentence = $p->h_sentence(array('firstperson'=>true));
$name = $p->h_name_and_identity();
$signers = prettify($p->signers());
$target = prettify($p->target());
$url = $p->url_typein();

$html = js_header();
$html .= <<<EOF
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

$html .= js_footer();

js_output($html);

?>
