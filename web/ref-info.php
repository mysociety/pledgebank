<?php
/*
 * ref-info.php:
 * Information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-info.php,v 1.20 2005-06-29 10:41:53 francis Exp $
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
    err(_("Missing pledge reference"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);

$pin_box = deal_with_pin($p->url_info(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
}

$P = person_if_signed_on();
if ($q_LetMeIn && !is_null($q_email)) {
    /* User wants to log in. Do the signon then redirect back to the normal
     * page, so that they always view it as a GET. */
    $P = person_signon(array(
                    'reason_web' => _('To view announcement messages for a pledge, we need to check you have signed it.'),
                    'reason_email' => _('Then you will be able to view announcement messages.'),
                    'reason_email_subject' => _('View pledge announcements archive on PledgeBank.com'),
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

page_header(_("More information: ") . $p->h_title(), array('ref'=>$p->url_main()) );

/* Brief table of info about the pledge. */
$p->render_box();

?>

<div id="pledgeaction">
<?=_('<h2>General pledge information</h2>') ?>
<table border="0" cellpadding="3" cellspacing="0">
<tr>
    <th><?=_('Creator') ?></th>
    <td><?=$p->h_name_and_identity()?></td>
</tr>
<tr>
    <th><?=_('Created') ?></th>
    <td><?=prettify($p->creationdate())?></td>
</tr>
<tr>
    <th><?= $p->open() ? _('Expires') : _('Expired') ?></th>
    <td><?=prettify($p->date())?></td>
</tr>
<tr>
    <th><?=_('Status') ?></th>
    <td><?
    if ($p->open())
        print _('open for signers; ')
                . ($p->succeeded() ? _('successful') : _('not yet successful'));
    else
        print _('closed; ')
                . ($p->succeeded() ? _('successful') : _('failed'));
    ?></td?
</tr>
<tr>
    <th><?=_('Number of signers') ?></th>
    <td><?= $p->signers() ?> / <?= $p->target() ?>
        <?= sprintf('(%.1f%% of target)', 100. * $p->signers() / $p->target()) ?>
    </td>
</tr>
<tr>
    <th><?=_('Categories') ?></th>
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
<h2 style="clear:both"><?=_('Rate of signups') ?></h3>

<? if ($p->pin()) {
        print p(_('Signup graph not available for private pledges.'));
} else { ?>
<p><img src="/graph.cgi?pledge_id=<?= $p->id() ?>;interval=pledge" alt="<?=_('Graph of signers to this pledge') ?>" width="500" height="300"><br>
<small><?=_('Graph updated once per day') ?></small>
</p>
<?
}

print _('<h2>Messages sent by creator to signers</h2>');

if ($priv & PRIV_SIGNER) {
    $q = db_query('select id, whencreated, fromaddress, emailsubject, emailbody from message where pledge_id = ? and sendtosigners and emailbody is not null', $p->id());
    $n = 0;
    while (list($id, $when, $from, $subject, $body) = db_fetch_row($q)) {
        if ($n++)
            print '<hr>';
        ?>
<table>
    <tr>
        <th><?=_('From') ?></th>
        <td><?= $p->h_name() ?> &lt;<?= htmlspecialchars($p->creator_email()) ?>&gt;<td>
    </tr>
    <tr>
        <th><?=_('Subject') ?></th>
        <td><?= htmlspecialchars($subject) ?></td>
    </tr>
    <tr>
        <th><?=_('Date') ?></th>
        <td><?= prettify(substr($when, 0, 10)) ?></td>
    </tr>
</table>

<div class="message"><?= comments_text_to_html($body) ?></div>
<?
        
    }
    if (!$n) {
        print p(_('The pledge creator has not sent any announcements yet.'));
    }
} else {
    print p(_('<em>The messages can only be shown to people who have signed the pledge. If
you have signed, please give your email address so that we can identify
you:</em>'));
?>
<form class="pledge" name="logIn" method="POST" action="/<?=$q_h_ref?>/info">
<strong><?=_('Email address') ?></strong>
<input type="hidden" name="ref" value="<?=$q_h_ref?>">
<input type="text" name="email" value="">
<input type="submit" name="LetMeIn" value="<?=_('Let me in') ?>">
</p>
</form>

<?
}

page_footer();

?>
