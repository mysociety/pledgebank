<?php
/*
 * js.php:
 * Shared functions for generating JS boxes
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: js.php,v 1.3 2007-06-13 21:03:50 matthew Exp $
 * 
 */

function js_header($title = '') {
    if ($title)
        $title = " - $title";
    return <<<EOF
<style type="text/css">
.pb_pledgebox { border: solid 1px #522994; font-family: 'Lucida Grande','Lucida Sans Unicode','Lucida Sans',Arial,sans-serif; margin-bottom: 1em; }
.pb_pledgebox p { margin: 0; }
.pb_pledgebox p.pb_gap { margin: 0 0 0.5em; }
.pb_pledgebox a { color: #522994; font-weight: normal; text-decoration: underline; }
.pb_header { background-color: #9c7bbd; color: #ffffff; border-bottom: solid 1px #522994; padding: 2px; }
.pb_header a { text-decoration: none; font-weight: bold; color: #ffffff; }
.pb_header a span { color: #21004a; background-color: #9c7bbd; }
.pb_body { padding: 2px; }
</style>
<div class="pb_pledgebox">
<div class="pb_header">
<a href="http://www.pledgebank.com/">Pledge<span>Bank</span>$title</a>
</div>
<div class="pb_body">
EOF;
}

function js_footer() {
    return '</div></div>';
}

function js_output($s) {
    header('Content-Type: text/javascript; charset=utf-8');
    $s = addslashes($s); /* XXX check this works with UTF-8 and is correct for JS. */
    $s = preg_replace("/\n/s", '\\n', $s);
    print "<!--\ndocument.write(\"$s\");\n//-->\n";
}

?>
