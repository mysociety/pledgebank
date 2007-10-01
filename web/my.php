<?
// your.php:
// List all your pledges, and offer account options.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: my.php,v 1.3 2007-10-01 17:14:28 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pbperson.php';

require_once '../../phplib/importparams.php';

$P = pb_person_signon(array(
                'reason_web' => _("To view your pledges, we need to check your email address."),
                'reason_email' => _("Then you will be able to view your pledges."),
                'reason_email_subject' => _('View your pledges at PledgeBank.com')
            ));

page_header(_("My Pledges"), array('id'=>"yourpledges"));


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
        print "\n\n" . '<h2><a name="connections">' . _('Suggested pledges') . '</a></h2>' . "\n\n";
        print p(_("People who signed the pledges you created or signed also signed these..."));
        print '<ol>';
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

# Pledges you have created
function show_your_pledges($type) {
    global $pb_today, $P;
    $qdate = ($type == 'open') ? '<=' : '>';
    $qrows = db_query("
                    SELECT pledges.*,
                        (select count(*) from signers where pledge_id = pledges.id) as signers
                    FROM pledges
                    WHERE pledges.person_id = ?
                    AND '$pb_today' $qdate pledges.date
                    ORDER BY creationtime DESC
                ", $P->id());
    if ($type == 'open')
        print h2(_('Open pledges you created'));
    if (db_num_rows($qrows) > 0) {
        if ($type == 'closed')
            print h2(_('Closed pledges you created'));
        while ($r = db_fetch_array($qrows)) {
            $pledge = new Pledge($r);
            $pledge->render_box(array('class' => '', 'href'=>$pledge->url_main(), 'creatorlinks' => true));
        }
    } elseif ($type == 'open') {
        print p(_('You have no open pledges. <a href="/new">Start a new pledge</a>.'));
    }
}

// Pledges you have signed
function show_your_signed_pledges() {
    global $P;
    $qrows = db_query("
                    SELECT pledges.*, signers.done,
                        (select count(*) from signers where pledge_id = pledges.id) as signers
                    FROM pledges, signers
                    WHERE pledges.id = signers.pledge_id
                    AND signers.person_id = ?
                    ORDER BY signtime DESC
                ", $P->id());
    print _("<h2>Pledges you signed</h2>");
    $successful_ever = 0;
    if (db_num_rows($qrows) > 0) {
        print '<ol id="yoursignedpledges">';
        $done = get_http_var('done');
        while ($r = db_fetch_array($qrows)) {
            $pledge = new Pledge($r);
            print '<li id="signed' . $pledge->id() . '"';
            if ($r['done']=='t')
                print ' class="done">';
            elseif (microsites_has_survey())
                print '><form method="post" action="' . $pledge->url_survey() . '">';
            print $pledge->summary(array('html'=>true, 'href'=>$r['ref']));
            if (microsites_has_survey() && $r['done']=='f') {
                print '<p>';
                if ($pledge->failed()) {
                    print _('Have you done this pledge anyway?');
                } else {
                    print _('Have you done this pledge?');
                }
                print ' <input type="submit" value="' . _('Yes') . '"></p>';
                print '<input type="hidden" name="r" value="my"></form>';
            }
            if ($done == $pledge->ref()) {
                print p('<em>' . _("That's great!") . '</em>');
                ?>
<script type="text/javascript">
    highlight_fade('signed<?=$pledge->id()?>');
</script>
<?
            }
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
}

// Display everything
print '<div id="yourconnections">';
change_personal_details(true);
pledges_you_might_like();
print '</div>';

print '<div id="yoursignaturesandpledges">';
show_your_pledges('open');
# XXX: microsites.php!
global $microsite;
if ($microsite != 'o2')
    alert_list_pledges_local($P->id());
alert_list_comments($P->id(), get_http_var('allcomments') ? true : false);
show_your_pledges('closed');
show_your_signed_pledges();
print '</div>';

page_footer();

?>
