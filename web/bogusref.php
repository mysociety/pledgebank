<?php
/*
 * bogusref.php:
 * Display of 404 page for bogus pledge refs.
 * 
 * This is redirected to by fuzzyref.cgi -- see comments in there for why.
 * 
 * Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: bogusref.php,v 1.6 2006-12-01 15:06:07 chris Exp $
 * 
 */

require_once '../conf/general';
require_once '../phplib/pb.php';
require_once '../phplib/page.php';
require_once '../phplib/pledge.php';
require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/rabx.php';

function oops() {
    header('Location: /');
    exit;
}

/* Only invoked by GET with ser=... param. */
if (!array_key_exists('ser', $_GET))
    oops();

/* Check format and signature of ser */
$ser = $_GET['ser'];
if (!($ser = base64_decode($ser))
    || strlen($ser) < 21)
    oops();

$hash = substr($ser, strlen($ser) - 20, 20);

if (sha1(db_secret() . substr($ser, 0, strlen($ser) - 20)) != bin2hex($hash))
    oops();

$tmp = substr($ser, 0, strlen($ser) - 20);
$data = rabx_unserialise($tmp);
if (rabx_is_error($data))
    oops();

if (!array_key_exists('ref', $data) || !array_key_exists('matches', $data))
    oops();

$ref = $data['ref'];
$matches = $data['matches'];
$moderated = $data['moderated'];

header('HTTP/1.1 404 Not found');

page_header(_("We couldn't find that pledge"));

if (0 == sizeof($matches)) {
    printf(p(_("We couldn't find any pledge with a reference like \"%s\". Try the following: ")), htmlspecialchars($ref) );
} else {
    printf(p(_("We couldn't find the pledge with reference \"%s\". Did you mean one of these pledges?")), htmlspecialchars($ref) );
    print '<dl>';
    foreach ($matches as $id) {
        $p = new Pledge((int)$id);
        print '<dt><a href="/'
                    /* XXX for the moment, just link to pledge index page,
                     * but we should figure out which page the user
                     * actually wanted and link to that instead. */
                    . htmlspecialchars($p->ref()) . '">'
                    . htmlspecialchars($p->ref()) . '</a>'
                . '</dt><dd>'
                . '"' . $p->h_sentence(array('firstperson'=>true)) . '"'
                . " &mdash; " . $p->h_name()
                . '</dd>';
    }
    print '</dl>';
    print p(_('If none of those look like what you want, try the following:'));
}

$li = [
    _('If you typed in the location, check it carefully and try typing it again.'),
    _('Look for the pledge on <a href="/all">the list of all pledges</a>.'),
    _('Search for the pledge you want by entering words below.'),
];
if ($moderated) {
    array_unshift($li, _('If the pledge is new, it may not yet have been moderated.  Please try again later.') );
}
    
print '<ul>';
foreach ($li as $item) {
    printf('<li> %s </li>', $item);
}
print '</ul>';

?>
<form accept-charset="utf-8" action="/search" method="get" class="pledge">
<label for="s"><?=_('Search for a pledge') ?>:</label>
<input type="text" id="s" name="s" size="15" value=""> <input type="submit" value=<?=_('Go') ?>>
</form>
<?

page_footer();

?>
