<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.4 2005-05-18 11:17:27 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

$p  = new Pledge(get_http_var('ref'));
$password_box = deal_with_password($p->url_main(), $p->ref(), $p->password());
if ($password_box) {
    page_header("Enter Password"); 
    print $password_box;
    page_footer();
    exit;
}

page_header("'I will " . $p->h_title() . "'");

if (!$p->open()) {
    print '<p class="finished">This pledge is now closed, as its deadline has passed.</p>';
}
if ($p->left() <= 0) {
    if ($p->exactly()) {
        print '<p class="finished">This pledge is now closed, as its target has been reached.</p>';
    } else {
        print '<p class="success">This pledge has been successful!';
        if (!$p->finished()) {
            print '<br><strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.';
        }
        print '</p>';
    }
}

$p->render_box(array('showdetails' => true));

if (!$p->finished()) { 
    pledge_sign_box(); 
}

?>
<div id="flyeremail">
<?    if (!$p->finished()) { ?>
<h2>Spread the word</h2>
<ul id="spread">
<li> <? print_link_with_password($p->url_email(), "", "Email pledge to your friends") ?></li>
<li> <? print_link_with_password($p->url_ical(), "", "Add deadline to your calendar") ?> </li>
<li> <? print_link_with_password($p->url_flyers(), "Stick them places!", "Print out customised flyers") ?>
</li>
</ul>
<br clear="all">
<?
} ?>
</div>

<div id="signatories">
<h2><a name="signers">Current signatories</a></h2><?

$out = '<li>' . $p->h_name() . ' (Pledge Author)</li>';
$anon = 0;
$unknownname = 0;
$q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($p->id()));
while ($r = db_fetch_array($q)) {
    $showname = ($r['showname'] == 't');
    if ($showname) {
        if (isset($r['name'])) {
            $out .= '<li>'
                    . htmlspecialchars($r['name'])
                    .' <small>(<a href="/abuse?what=signer&amp;id='
                        . htmlspecialchars($r['id'])
                    . '">suspicious signer?</a>)</small>'
                    . '</li>';
        } else {
            ++$unknownname;
        }
    } else {
        $anon++;
    }
}
print '<ul>'.$out;
if ($anon || $unknownname) {
    /* XXX i18n-a-go-go */
    $extra = '';
    if ($anon)
        $extra .= "Plus $anon "
                    . make_plural($anon, 'other')
                    . ' who did not want to give their '
                    . make_plural($anon, 'name');
    if ($unknownname)
        $extra .= ($anon ? ', and' : 'Plus')
                    . " $unknownname "
                    . make_plural($unknownname, 'other')
                    . ' whose '
                    . make_plural($unknownname, 'name')
                    . " we don't know.";
    print "<li>$extra</li>";
}
print '</ul>';
print '</div>';

print '<div id="comments"><h2><a name="comments">Comments on this pledge</a></h2>';
comments_show($p->id()); 
comments_form($p->id(), 1);
print '</div>';

page_footer();
?>

