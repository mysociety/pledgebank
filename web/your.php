<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: your.php,v 1.9 2005-07-12 12:15:30 francis Exp $

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
$s = db_query('SELECT pledges.id, SUM(strength) AS sum, max(date) - pb_current_date() AS daysleft
        FROM pledge_connection, pledges
        WHERE (
                (b_pledge_id = pledges.id AND
                    (a_pledge_id IN (SELECT pledge_id FROM signers WHERE signers.person_id = ?)
                    OR a_pledge_id IN (SELECT id FROM pledges WHERE pledges.person_id = ?)))
                OR
                (a_pledge_id = pledges.id AND
                    (b_pledge_id IN (SELECT pledge_id FROM signers WHERE signers.person_id = ?)
                    OR b_pledge_id IN (SELECT id FROM pledges WHERE pledges.person_id = ?)))
            )
            AND pledges.id NOT IN (SELECT pledge_id from signers where signers.person_id = ?)
            AND pledges.id NOT IN (SELECT id from pledges where pledges.person_id = ?)
        GROUP BY pledges.id
        ORDER BY sum DESC
        LIMIT 100
        ', 
        array($P->id(), $P->id(), $P->id(), $P->id(), $P->id(), $P->id()));
if (0 != db_num_rows($s)) {
    print "\n\n" . '<div id="yourconnections"><h2><a name="connections">' . _('Suggested Pledges') . '</a></h2><ol>' . "\n\n";
    print p(_("People who signed the pledges you created or signed also signed these..."));
    while (list($id, $strength, $daysleft) = db_fetch_row($s)) {
        $p2 = new Pledge(intval($id));
        print '<li>';
    #    print '<a href="/' . htmlspecialchars($p2->ref()) . '">' . $p2->h_title() . '</a>';
        $data = $p2->data;
        $data['daysleft' ] = $daysleft;
        print pledge_summary($data, array('html'=>true, 'href'=>$p2->url_main()));
        print '</li>';
        print "<!-- strength $strength\n -->";
    }
    print "\n\n";
    print '</ol></div>';
}

// Pledges you made
$qrows = db_query("
                SELECT pledges.*, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE pledges.person_id = ?
                ORDER BY creationtime DESC
            ", $P->id());
print _("<h2>Pledges You Created</h2>");
if (db_num_rows($qrows) > 0) {
    while ($r = db_fetch_array($qrows)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledge = new Pledge($r);
        $pledge->render_box(array('class' => 'pledge-yourcreated', 'href'=>$pledge->url_main()));
    }
} else {
    print p(_('You have created no pledges.'));
}

// Pledges you have signed
$qrows = db_query("
                SELECT pledges.*, date - pb_current_date() AS daysleft
                FROM pledges, signers
                WHERE pledges.id = signers.pledge_id
                AND signers.person_id = ?
                ORDER BY signtime DESC
            ", $P->id());
print '<div id="yoursignedpledges">';
print _("<h2>Pledges You Signed</h2>");
$successful_ever = 0;
if (db_num_rows($qrows) > 0) {
    print '<ol>';
    while ($r = db_fetch_array($qrows)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        print '<li>';
        print pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
        
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
