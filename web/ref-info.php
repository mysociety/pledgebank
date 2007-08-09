<?php
/*
 * ref-info.php:
 * Information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-info.php,v 1.45 2007-08-09 16:56:16 matthew Exp $
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

require_once '../phplib/page.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

$err = importparams( array('ref',        '/./',              ''));

if (!is_null($err))
    err(_("Invalid parameters, please try again"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);
microsites_redirect($p);

/* Do this again because it's possible we'll reach here with a non-canonical
 * ref (e.g. different case from that entered by the creator). */
if (cond_maybe_respond($p->last_change_time()))
    exit();

deal_with_pin($p->url_info(), $p->ref(), $p->pin());

page_header(_("More information: ") . $p->h_title(), array(
            'ref'=>$p->ref(),
            'pref' => $p->url_typein(),
            'last-modified' => $p->last_change_time()
        ));

debug_timestamp(true, "retrieved pledge");

/* Brief table of info about the pledge. */
$p->render_box();

debug_timestamp(true, "pledge info box");

?>

<div id="pledgeaction">
<?=_('<h2>General pledge information</h2>') ?>
<table border="0" cellpadding="3" cellspacing="0">
<tr>
    <th><?=_('Creator') ?></th>
    <td><?=$p->h_name_and_identity()?></td>
</tr>
<tr>
    <th><?=_('Date created') ?></th>
    <td><?=prettify($p->creationdate())?></td>
</tr>
<tr>
    <th><?= $p->open() ? _('Date closes') : _('Date closed') ?></th>
    <td><?=prettify($p->date())?></td>
</tr>
<tr>
    <th><?=_('Status') ?></th>
    <td><?
    if ($p->byarea()) {
        print sprintf(ngettext('successful in %d area', 'successful in %d areas', $p->byarea_successes()),
            $p->byarea_successes());
    } elseif ($p->open())
        print _('open for signers; ')
	        # TRANS: "successful" is only ever used in singular context
                . ($p->succeeded() ? _('successful') : _('not yet successful'));
    else
        print _('closed; ')
                . ($p->succeeded() ? _('successful') : _('failed'));
    debug_timestamp(true);
    ?></td?
</tr>
<tr>
    <th><?=_('Number of signers') ?></th>
    <td><?= $p->signers() ?> 
        <? if (!$p->byarea()) { ?>
        / <?= $p->target() ?>
        <?= sprintf(_('(%.1f%% of target)'), 100. * $p->signers() / $p->target()) ?>
        <? } ?>
    </td>
</tr>
<? debug_timestamp(true); ?>
<tr>
    <th><?=_('Estimated signers by deadline') ?></th>
    <td><?= $p->probable_will_reach() ?> 
        <? if (!$p->byarea()) { ?>
        <?= sprintf(_('(%.1f%% of target)<br><small>if signup rate continues as in last week</small>'), 100. * $p->probable_will_reach() / $p->target()) ?>
        <? } ?>
    </td>
</tr>
<? debug_timestamp(true); ?>
<? if (microsites_categories_allowed()) { ?>
<tr>
    <th><?=_('Categories') ?></th>
    <td><?
    $a = array_values($p->categories());
    if (sizeof($a) == 0)
        print '&mdash;';
    else {
        sort($a);
        print implode('; ', array_map('htmlspecialchars', array_map('_', $a)));
    }
    ?></td>
</tr>
<? } ?>
</table>
</div>
<?

/* Pretty graph of pledge signup, and some notes about how quickly the pledge
 * is progressing. */
?>
<h2 style="clear:both"><?=_('Rate of signups') ?></h2>

<? if ($p->pin()) {
        print p(_('Signup graph not available for private pledges.'));
} else { ?>
<p><img src="/graph.cgi?pledge_id=<?= $p->id() ?>;interval=pledge" alt="<?=_('Graph of signers to this pledge') ?>" width="500" height="300"></p>
<p><small><?=_('Graph updated every 30 minutes') ?></small></p>
<?
}

debug_timestamp(true);

page_footer();

?>
