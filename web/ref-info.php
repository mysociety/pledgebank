<?php
/*
 * ref-info.php:
 * Information about an individual pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-info.php,v 1.1 2005-06-01 14:53:54 chris Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err("Missing pledge reference");

$p = new Pledge($q_ref);
$P = person_if_signed_on();

/* What is this user allowed to see? */
define('PRIV_SIGNER', '1');
define('PRIV_CREATOR', '2');
$priv = 0;

if (!is_null($P)) {
    if (db_getOne('select id from signer where person_id = ? and pledge_id = ?', array($P->id(), $p->id())))
        $priv |= PRIV_SIGNER;
    if ($p->creator_id() == $P->id())
        $priv |= PRIV_CREATOR | PRIV_SIGNER;
}

page_header("More information: " . $p->h_title());

/* Brief table of info about the pledge. */
$p->render_box();

?>

<h2>General pledge information</h2>
<table>
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
<?

/* Pretty graph of pledge signup, and some notes about how quickly the pledge
 * is progressing. */
?>
<h3>Rate of signups</h3>

<img src="/graph.cgi?pledge_id=<?= $p->id() ?>;interval=pledge" alt="Graph of signers to this pledge" width="500" height="300">
<?

page_footer();

?>
