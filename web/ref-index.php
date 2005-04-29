<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.1 2005-04-29 15:14:12 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

$ref = get_http_var('ref'); 
$h_ref = htmlspecialchars($ref);
$q = db_query('SELECT *, pb_current_date() <= date as open FROM pledges WHERE ref ILIKE ?', array($ref));
if (!db_num_rows($q))
    err('PledgeBank reference not known');

$r = db_fetch_array($q);
$confirmed = ($r['confirmed'] == 't');
if (!$confirmed)
    err('PledgeBank reference not known');

$password_box = deal_with_password("/$h_ref", $ref, $r['password']);
if ($password_box) {
    page_header("Enter Password"); 
    print $password_box;
    page_footer();
    exit;
}

$pledge_id = $r['id'];

$title = "'I will " . $r['title'] . "'";
page_header($title);

$q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($pledge_id));
$curr = db_num_rows($q);
$left = $r['target'] - $curr;

$finished = 0;
if ($r['open'] == 'f') {
    $finished = 1;
    print '<p class="finished">This pledge is now closed, as its deadline has passed.</p>';
}
if ($left <= 0) {
    if ($r['comparison'] == 'exactly') {
        $finished = 1;
        print '<p class="finished">This pledge is now closed, as its target has been reached.</p>';
    } else {
        print '<p class="success">This pledge has been successful!';
        if (!$finished) {
            print '<br><strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.';
        }
        print '</p>';
    }
}

$png_flyers1_url = new_url("../flyers/{$ref}_A7_flyers1.png", false);
pledge_box($r, $curr, $left);

if (!$finished) { 
    pledge_sign_box(); 
}

?>
<div id="flyeremail">
<?    if (!$finished) { ?>
<h2>Spread the word</h2>
<ul id="spread">
<li> <? print_link_with_password("./$h_ref/email", "", "Email pledge to your friends") ?></li>
<li> <? print_link_with_password("$h_ref/ical", "", "Add deadline to your calendar") ?> </li>
<li> <? print_link_with_password("./$h_ref/flyers", "Stick them places!", "Print out customised flyers") ?>
<!-- <a href="/<?=$h_ref ?>/flyers"><img border="0" vspace="5" align="right" src="<?=$png_flyers1_url ?>" width="298" height="211" alt="PDF flyers to download"></a> -->
</li>
</ul>
<br clear="all">
<?
} ?>
</div>

<div id="signatories">
<h2><a name="signers">Current signatories</a></h2><?

$out = '<li>'
        . htmlspecialchars($r['name'])
        . ' (Pledge Author)</li>';
$anon = 0;
$unknownname = 0;
while ($r = db_fetch_array($q)) {
    $showname = ($r['showname'] == 't');
    if ($showname) {
        if (isset($r['name'])) {
            $out .= '<li>'
                    . htmlspecialchars($r['name'])
                    .' <small>(<a href="/abuse/?what=signer&id='
                        . htmlspecialchars($r['id'])
                    . '">Is this signature suspicious?</a>)</small>'
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
comments_show($pledge_id); 
comments_form($pledge_id, 1);
print '</div>';

page_footer();
?>

