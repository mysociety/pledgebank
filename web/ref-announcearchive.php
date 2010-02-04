<?php
/*
 * ref-info.php:
 * Show old announcement messages to signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-announcearchive.php,v 1.10 2007-08-09 16:56:16 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/comments.php';
require_once '../phplib/page.php';
require_once '../phplib/pbperson.php';
require_once '../commonlib/phplib/utility.php';
require_once '../phplib/pledge.php';

require_once '../commonlib/phplib/importparams.php';

$err = importparams( array('ref',        '/./',              ''));

if (!is_null($err))
    err(_("Invalid parameters, please try again"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);
microsites_redirect($p);
deal_with_pin($p->url_info(), $p->ref(), $p->pin());

$P = pb_person_signon(array(
                     'reason_web' => _('To view announcement messages for a pledge, we need to check you have signed it.'),
                     'reason_email' => _('Then you will be able to view announcement messages.'),
                     'reason_email_subject' => _('View pledge announcements archive on PledgeBank.com'),
                 ));

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

page_header(_("Messages sent by creator to signers: ") . $p->h_title(), array('ref'=>$p->ref(),'pref'=>$p->url_typein()) );

$p->render_box();

print '<div id="oldannounce">';

print h2(_('Messages sent by creator to signers'));

if ($priv & PRIV_SIGNER) {
    $q = db_query('select id, whencreated, fromaddress, emailsubject, emailbody from message where pledge_id = ? and sendtosigners and emailbody is not null order by id desc', $p->id());
    if (db_num_rows($q) > 0) {
        while (list($id, $when, $from, $subject, $body) = db_fetch_row($q)) {
            print '<hr>';
            ?>
            <p><strong><?=_('From') ?></strong>:
            <?= $p->h_name() ?> &lt;<?= htmlspecialchars($p->creator_email()) ?>&gt;
            <br><strong><?=_('Subject') ?></strong>:
            <?= htmlspecialchars($subject) ?>
            <br><strong><?=_('Date') ?></strong>:
            <?= prettify(substr($when, 0, 10)) ?>
            </p>

    <div class="message"><?= comments_text_to_html($body) ?></div>
    <?
            
        }
        print "<hr>";
    } else {
        print p(_('The pledge creator has not sent any announcements yet.'));
    }
} else {
    print p(_('The messages can only be shown to people who have signed the
    pledge.'));
}

print "</div>";

page_footer();

?>
