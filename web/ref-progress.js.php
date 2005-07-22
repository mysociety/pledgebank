<?php
/*
 * ref-progress.js.php:
 * Produce includable Javascript for showing pledge status on another web page.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-progress.js.php,v 1.3 2005-07-22 11:48:59 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));
if (defined($p->pin()) && $p->pin() != '')
    err("Permission denied");

$html = <<<EOF
<div class="pledgebank_pledgebox">
<ul class="pledgebank_blurb">
<li class="pledgebank_pledgetext">
EOF;
$html .= $p->h_sentence() . "</li>";
$signers = $p->signers();
$target = $p->target();
$html .= <<<EOF
<li class="pledgebank_progress">
$signers have signed out of $target needed
</li>
EOF;
if ($p->succeeded())
    $html .= '<li class="pledgebank_success">Pledge is successful!</li>';
else if ($p->failed())
    $html .= '<li class="pledgebank_failure">Pledge has failed</li>';
if ($p->open())
    $html .= '<li class="pledgebank_open">Pledge is open until ' . prettify($p->date()) . '</li>';
else
    $html .= '<li class="pledgebank_closed">Pledge closed on ' . prettify($p->date()) . '</li>';
$html .= <<<EOF
</ul>
</div>
EOF;

header('Content-Type: text/javascript; charset="utf-8"');
$html = addslashes($html);  /* XXX check this works with UTF-8 and is correct
                             * for JS. */
$html = preg_replace("/\n/s", '\\n', $html);
print "<!--\ndocument.write(\"$html\");\n//-->\n";

?>
