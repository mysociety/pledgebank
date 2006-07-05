<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: your.php,v 1.19 2006-07-05 13:12:23 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../../phplib/person.php';

require_once '../../phplib/importparams.php';

$P = person_signon(array(
                'reason_web' => _("To view your pledges, we need to check your email address."),
                'reason_email' => _("Then you will be able to view your pledges."),
                'reason_email_subject' => _('View your pledges at PledgeBank.com')
            ));

page_header(_("Your Pledges"), array('id'=>"yourpledges"));

// Pledges you might like (people who signed pledges you created/signed also signed these...)
$s = db_query("
        SELECT some_pledges.id, SUM(some_pledges.strength) AS sum FROM
        (
            SELECT pledges.id, strength, date
            FROM pledges, pledge_connection
            WHERE (
                    (b_pledge_id = pledges.id AND
                        (a_pledge_id IN (SELECT pledge_id FROM signers WHERE signers.person_id = ?)
                        OR a_pledge_id IN (SELECT id FROM pledges WHERE pledges.person_id = ?)))
                        )
                AND pledges.id NOT IN (SELECT pledge_id from signers where signers.person_id = ?)
                AND pledges.id NOT IN (SELECT id from pledges where pledges.person_id = ?)
                AND pledges.date >= '$pb_today'
        UNION
            SELECT pledges.id, strength, date
            FROM pledges, pledge_connection
            WHERE (
                    (a_pledge_id = pledges.id AND
                        (b_pledge_id IN (SELECT pledge_id FROM signers WHERE signers.person_id = ?)
                        OR b_pledge_id IN (SELECT id FROM pledges WHERE pledges.person_id = ?)))
                        )
                AND pledges.id NOT IN (SELECT pledge_id from signers where signers.person_id = ?)
                AND pledges.id NOT IN (SELECT id from pledges where pledges.person_id = ?)
                AND pledges.date >= '$pb_today'
        ) AS some_pledges

        GROUP BY some_pledges.id
        ORDER BY sum DESC
        LIMIT 50
        ", 
        array($P->id(), $P->id(), 
              $P->id(), $P->id(), 
              $P->id(), $P->id(), 
              $P->id(), $P->id()));
if (0 != db_num_rows($s)) {
    print "\n\n" . '<div id="yourconnections"><h2><a name="connections">' . _('Suggested pledges') . '</a></h2><ol>' . "\n\n";
    print p(_("People who signed the pledges you created or signed also signed these..."));
    while (list($id, $strength) = db_fetch_row($s)) {
        $p2 = new Pledge(intval($id));
        print '<li>';
        print $p2->summary(array('html'=>true, 'href'=>$p2->url_main()));
        print '</li>';
        print "<!-- strength $strength -->\n";
    }
    print "\n\n";
    print '</ol></div>';
}

# Change/update your personal details
?>
<form action="/your" method="post"><input type="hidden" name="UpdateDetails" value="1">
<h2><?=_('Update your details') ?></h2>
<?

importparams(
#        array('email',          '/./',          '', null),
        array('pw1',            '/[^\s]+/',     '', null),
        array('pw2',            '/[^\s]+/',     '', null),
        array('UpdateDetails',  '/^.+$/',       '', false)
);

$error = null;
if ($q_UpdateDetails) {
    if (is_null($q_pw1) || is_null($q_pw2))
        $error = _("Please type your new password twice");
    elseif (strlen($q_pw1)<5 || strlen($q_pw2)<5)
        $error = _('Your password must be at least 5 characters long');
    elseif ($q_pw1 != $q_pw2)
        $error = _("Please type the same password twice");
    else {
        $P->password($q_pw1);
        db_commit();
        print '<p id="success">' . _('Password successfully updated') . '</p>';
    }
}
if (!is_null($error))
    print "<p id=\"error\">$error</p>";
?>
<p><?=_('If you wish to change your password, you can do so here.') ?></p>
<p>
<?=_('New password:') ?> <input type="password" name="pw1" id="pw1" size="15">
<?=_('New password (again):') ?> <input type="password" name="pw2" id="pw2" size="15">
<input type="submit" value="<?=_('Submit') ?>"></p>
</form>

<?
$made_pledges = false;

// Alerts
alert_list_pledges_local($P->id());

// Open pledges you made
$qrows = db_query("
                SELECT pledges.*
                FROM pledges
                WHERE pledges.person_id = ?
                AND '$pb_today' <= pledges.date
                ORDER BY creationtime DESC
            ", $P->id());
print _("<h2>Open pledges you created</h2>");
if (db_num_rows($qrows) > 0) {
    $made_pledges = true;
    while ($r = db_fetch_array($qrows)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledge = new Pledge($r);
        $pledge->render_box(array('class' => 'pledge-yourcreated', 'href'=>$pledge->url_main()));
    }
} else {
    print p(_('You have no open pledges. <a href="/new">Start a new pledge</a>.'));
}

// Closed pledges you made
$qrows = db_query("
                SELECT pledges.*
                FROM pledges
                WHERE pledges.person_id = ?
                AND '$pb_today' > pledges.date
                ORDER BY creationtime DESC
            ", $P->id());
if (db_num_rows($qrows) > 0) {
    $made_pledges = true;
    print _("<h2>Closed pledges you created</h2>");
    while ($r = db_fetch_array($qrows)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledge = new Pledge($r);
        $pledge->render_box(array('class' => 'pledge-yourcreated', 'href'=>$pledge->url_main()));
    }
} 

// Pledges you have signed
$qrows = db_query("
                SELECT pledges.*
                FROM pledges, signers
                WHERE pledges.id = signers.pledge_id
                AND signers.person_id = ?
                ORDER BY signtime DESC
            ", $P->id());
print '<div id="yoursignedpledges">';
print _("<h2>Pledges you signed</h2>");
$successful_ever = 0;
if (db_num_rows($qrows) > 0) {
    print '<ol>';
    while ($r = db_fetch_array($qrows)) {
        $pledge = new Pledge($r['ref']);
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        print '<li>';
        print $pledge->summary(array('html'=>true, 'href'=>$r['ref']));
        
        print '</li>';
        if ($r['whensucceeded']) 
            $successful_ever = 1;
    }
    print '</ol>';
} else {
    print p(_('You have signed no pledges.'));
}
if ($successful_ever)
    print p(_("Why not <a href=\"mailto:team@pledgebank.com\">send us photos</a> of yourself carrying out successful pledges?"));
print '</div>';

page_footer();

?>
