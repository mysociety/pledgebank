<?
// your.php:
// List all your pledges, and offer account options.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: your.php,v 1.26 2007-05-10 15:52:10 timsk Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pbperson.php';

require_once '../../phplib/importparams.php';

$P = pb_person_signon(array(
                'reason_web' => _("To view your pledges, we need to check your email address."),
                'reason_email' => _("Then you will be able to view your pledges."),
                'reason_email_subject' => _('View your pledges at PledgeBank.com')
            ));

page_header(_("Your Pledges"), array('id'=>"yourpledges"));


// Pledges you might like (people who signed pledges you created/signed also signed these...)
function pledges_you_might_like() {
    global $pb_today, $P;
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
        print "\n\n" . '<h2><a name="connections">' . _('Suggested pledges') . '</a></h2><ol>' . "\n\n";
        print p(_("People who signed the pledges you created or signed also signed these..."));
        while (list($id, $strength) = db_fetch_row($s)) {
            $p2 = new Pledge(intval($id));
            print '<li>';
            print $p2->summary(array('html'=>true, 'href'=>$p2->url_main()));
            print '</li>';
            print "<!-- strength $strength -->\n";
        }
        print "\n\n";
        print '</ol>';
    }
}

// Open pledges you made
function show_your_open_pledges() {
    global $pb_today, $P;
    $qrows = db_query("
                    SELECT pledges.*
                    FROM pledges
                    WHERE pledges.person_id = ?
                    AND '$pb_today' <= pledges.date
                    ORDER BY creationtime DESC
                ", $P->id());
    print _("<h2>Open pledges you created</h2>");
    if (db_num_rows($qrows) > 0) {
        while ($r = db_fetch_array($qrows)) {
            $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
            $pledge = new Pledge($r);
            $pledge->render_box(array('class' => 'pledge-yourcreated', 'href'=>$pledge->url_main()));
        }
    } else {
        print p(_('You have no open pledges. <a href="/new">Start a new pledge</a>.'));
    }
}

// Closed pledges you made
function show_your_closed_pledges() {
    global $pb_today, $P;
    $qrows = db_query("
                    SELECT pledges.*
                    FROM pledges
                    WHERE pledges.person_id = ?
                    AND '$pb_today' > pledges.date
                    ORDER BY creationtime DESC
                ", $P->id());
    if (db_num_rows($qrows) > 0) {
        print _("<h2>Closed pledges you created</h2>");
        while ($r = db_fetch_array($qrows)) {
            $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
            $pledge = new Pledge($r);
            $pledge->render_box(array('class' => 'pledge-yourcreated', 'href'=>$pledge->url_main()));
        }
    } 
}

// Pledges you have signed
function show_your_signed_pledges() {
    global $P;
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
        print p(sprintf(_("Why not <a href=\"mailto:%s\">send us photos</a> of yourself carrying out successful pledges?"), str_replace("@", "&#64;", OPTION_CONTACT_EMAIL)));
    print '</div>';
}

// Display everything
print '<div id="yourconnections">';
change_personal_details(true);
pledges_you_might_like();
alert_list_comments($P->id());
print '</div>';

show_your_open_pledges();
# XXX: microsites.php!
global $microsite;
if ($microsite != 'o2')
    alert_list_pledges_local($P->id());
show_your_closed_pledges();
show_your_signed_pledges();

page_footer();

?>
