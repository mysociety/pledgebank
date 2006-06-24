<?php
/*
 * ref-plain.php:
 * Plain text information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-plain.php,v 1.4 2006-06-24 22:17:23 matthew Exp $
 * 
 */

require_once '../conf/general';
require_once '../phplib/page.php';
require_once '../../phplib/conditional.php';
require_once '../../phplib/db.php';

/* Short-circuit the conditional GET as soon as possible -- parsing the rest of
 * the includes is costly. */
page_send_vary_header();
if (array_key_exists('ref', $_GET)
    && ($id = db_getOne('select id from pledges where ref = ?', $_GET['ref']))
    && cond_maybe_respond(intval(db_getOne('select extract(epoch from pledge_last_change_time(?))', $id))))
    exit();

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/rabx.php';

$err = importparams(
    array('ref', '/./', ''),
    array('output', '/^(xml|rabx)$/', '', 'xml')
);
if (!is_null($err))
    err(_("Invalid parameters, please try again"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);

/* Do this again because it's possible we'll reach here with a non-canonical
 * ref (e.g. different case from that entered by the creator). */
if (cond_maybe_respond($p->last_change_time()))
    exit();

$pin_box = deal_with_pin($p->url_info(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
}

header("Content-Type: text/plain");

locale_push($p->lang());
$title = _('I will') . ' ' . $p->h_title();
locale_pop();

$a = array_values($p->categories());
if (sizeof($a) == 0)
    $cats = '';
else {
    sort($a);
    $cats = implode('; ', array_map('htmlspecialchars', array_map('_', $a)));
}

$out = array(
    'data' => array(
        'url'=> $p->url_main(),
        'ref' => $p->ref(),
        'sentence_first' => pledge_sentence($p->data, array('firstperson'=>true)),
        'sentence_first_withname' => pledge_sentence($p->data, array('firstperson'=>'includename')),
        'sentence_third' => pledge_sentence($p->data, array('firstperson'=>false)),
        'title' => $title,
        'target' => $p->target(),
        'deadline' => $p->date() . 'T24:00:00Z',
        'detail' => $p->data['detail'],
        'creator_name' => $p->creator_name(),
        'creator_identity' => $p->data['identity'],
        'creation_date' => $p->creationdate(),
        'lang' => $p->lang(),
        'categories' => $cats,
        'country_code' => $p->h_country(),
        'local_type' => $p->h_local_type(),
        'description' => $p->h_description(),
        'latitude' => $p->data['latitude'],
        'longitude' => $p->data['longitude'],
        'open' => $p->open(),
        'finished' => $p->finished(),
        'succeeded' => $p->succeeded(),
        'failed' => $p->failed(),
        'signers' => $p->signers(),
        'left' => $p->left(),
        'probable_will_reach' => $p->probable_will_reach(),
        'last_change_time' => gmdate('Y-m-d\TH:i:s\Z', $p->last_change_time()),
        'cancelled' => $p->data['cancelled'],
        'notice' => $p->data['notice'],
        'cached_prominence' => $p->data['cached_prominence'],
    )
);

if ($q_output == 'xml') {
    print "<pledge>\n";
} elseif ($q_output == 'rabx') {
    $rabx = '';
}

foreach ($out['data'] as $k => $v) {
    $v = str_replace("\r", "", $v);
    if ($q_output == 'xml') {
        print "<$k>" . htmlspecialchars($v) . "</$k>\n";
    }
}

# XXX: Not perfect, in XML list of signers should probably be with number of signers above?
# And should all the location stuff be together?
add_signatories($p);

if ($q_output == 'xml')
    print "</pledge>\n";
elseif ($q_output == 'rabx') {
    rabx_wire_wr($out, $rabx);
    print $rabx;
}

function add_signatories($p) {
    global $q_output;
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    if ($nsigners == 0) {
        return;
    }
    $names = array();
    $anon = 0;
    $unknownname = 0;

    $query = "SELECT * FROM signers WHERE pledge_id = ? ORDER BY id";
    $q = db_query($query, $p->id());
    while ($r = db_fetch_array($q)) {
        $showname = ($r['showname'] == 't');
        if ($showname) {
            if (isset($r['name'])) {
                $names[] = $r['name'];
            } else {
                ++$unknownname;
            }
        } else {
            $anon++;
        }
    }
    if ($q_output == 'xml') {
        print "<signerslist>\n";
        print "  <signer>" . join("</signer>\n  <signer>", array_map('htmlspecialchars', $names)) . "</signer>\n";;
        print "</signerslist>\n";
        print "<anonymous_signers>$anon</anonymous_signers>\n";
        print "<mobile_signers>$unknownname</mobile_signers>\n";
    } elseif ($q_output == 'rabx') {
        global $out;
        $out['signers']['list'] = $names;
        $out['data']['anonymous_signers'] = $anon;
        $out['data']['mobile_signers'] = $unknownname;
    }
}

?>
