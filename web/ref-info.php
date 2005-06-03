<?php
/*
 * ref-info.php:
 * Information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-info.php,v 1.4 2005-06-03 16:52:57 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/comments.php';
require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$err = importparams(
            array('ref',        '/./',              ''),
            array('LetMeIn',    '/./',              '', false),
            array('email',      '/^[^@]+@[^@]+$/',  '', null)
        );

if (!is_null($err))
    err("Missing pledge reference");

$p = new Pledge($q_ref);

$pin_box = deal_with_pin($p->url_flyers(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header("Enter PIN"); 
    print $pin_box;
    page_footer();
    exit;
}

$P = person_if_signed_on();
if ($q_LetMeIn && !is_null($q_email)) {
    /* User wants to log in. Do the signon then redirect back to the normal
     * page, so that they always view it as a GET. */
    $P = person_signon(array(
                    'reason' => "log into PledgeBank",
                    'template' => 'generic-confirm'
                ), $q_email);
    if (!$pin_box) {
        header("Location: /$q_ref/info");
        exit();
    }
}

/* What is this user allowed to see? */
define('PRIV_SIGNER', '1');
define('PRIV_CREATOR', '2');
$priv = 0;

if (!is_null($P)) {
    if (db_getOne('select id from signers where person_id = ? and pledge_id = ?', array($P->id(), $p->id())))
        $priv |= PRIV_SIGNER;
    if ($p->creator_id() == $P->id())
        $priv |= PRIV_CREATOR | PRIV_SIGNER;
}

page_header("More information: " . $p->h_title(), array('ref'=>$p->url_main()) );

/* Brief table of info about the pledge. */
$p->render_box();

?>

<div id="pledgeaction">
<h2>General pledge information</h2>
<table border="0" cellpadding="3" cellspacing="0">
<tr>
    <th>Creator</th>
    <td><?=$p->h_name_and_identity()?></td>
</tr>
<tr>
    <th>Created</th>
    <td><?=prettify($p->creationdate())?></td>
</tr>
<tr>
    <th><?= $p->open() ? 'Expires' : 'Expired' ?></th>
    <td><?=prettify($p->date())?></td>
</tr>
<tr>
    <th>Status</th>
    <td><?
    if ($p->open())
        print 'open for signers; '
                . ($p->succeeded() ? 'successful' : 'not yet successful');
    else
        print 'closed; '
                . ($p->succeeded() ? 'successful' : 'failed');
    ?></td?
</tr>
<tr>
    <th>Number of signers</th>
    <td><?= $p->signers() ?> / <?= $p->target() ?>
        (<?= sprintf('%.1f%%', 100. * $p->signers() / $p->target()) ?>
        of target)
    </td>
</tr>
<tr>
    <th>Categories</th>
    <td><?
    $a = array_values($p->categories());
    if (sizeof($a) == 0)
        print '&mdash;';
    else {
        sort($a);
        print implode('; ', array_map('htmlspecialchars', $a));
    }
    ?></td>
</table>
</div>
<?

/* Pretty graph of pledge signup, and some notes about how quickly the pledge
 * is progressing. */
?>
<h3 style="clear:both">Rate of signups</h3>

<img src="/graph.cgi?pledge_id=<?= $p->id() ?>;interval=pledge" alt="Graph of signers to this pledge" width="500" height="300">
<?

?>
<h2>Messages sent by creator to signers</h2>
<?

if ($priv & PRIV_SIGNER) {
    $q = db_query('select id, whencreated, fromaddress, emailsubject, emailbody from message where pledge_id = ? and sendtosigners and emailbody is not null', $p->id());
    $n = 0;
    while (list($id, $when, $from, $subject, $body) = db_fetch_row($q)) {
        if ($n++)
            print '<hr>';
        ?>
<table>
    <tr>
        <th>From</th>
        <td><?= $p->h_name() ?> &lt;<?= htmlspecialchars($p->creator_email()) ?>&gt;<td>
    </tr>
    <tr>
        <th>Subject</th>
        <td><?= htmlspecialchars($subject) ?></td>
    </tr>
    <tr>
        <th>Date</th>
        <td><?= prettify(substr($when, 0, 10)) ?></td>
    </tr>
</table>

<div class="message"><?= comments_text_to_html($body) ?></div>
<?
        
    }
} else {
    ?>
<p><em>The messages can only be shown to people who have signed the pledge. If
you have signed, please give your email address so that we can identify
you:</em></p>

<form method="POST">
<p>Email address:
<input type="hidden" name="ref" value="<?=$q_h_ref?>">
<input type="text" name="email" value="">
<input type="submit" name="LetMeIn" value="Let me in!">
</p>
</form>

<?
}

page_footer();

?>
