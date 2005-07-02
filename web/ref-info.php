<?php
/*
 * ref-info.php:
 * Information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-info.php,v 1.23 2005-07-02 02:46:58 francis Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/comments.php';
require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$err = importparams( array('ref',        '/./',              ''));

if (!is_null($err))
    err(_("Invalid parameters, please try again"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);

$pin_box = deal_with_pin($p->url_info(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
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
<h2 style="clear:both"><?=_('Rate of signups') ?></h2>

<? if ($p->pin()) {
        print p(_('Signup graph not available for private pledges.'));
} else { ?>
<p><img src="/graph.cgi?pledge_id=<?= $p->id() ?>;interval=pledge" alt="<?=_('Graph of signers to this pledge') ?>" width="500" height="300"></p>
<p><small><?=_('Graph updated once per day') ?></small></p>
<?
}

page_footer();

?>
