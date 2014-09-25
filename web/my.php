<?
// your.php:
// List all your pledges, and offer account options.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: my.php,v 1.9 2009-08-08 20:35:10 timsk Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pbperson.php';

require_once '../commonlib/phplib/importparams.php';

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
        print '<ul class="search_results">';
        while (list($id, $strength) = db_fetch_row($s)) {
            $p2 = new Pledge(intval($id));
            print '<li>';
            print $p2->new_summary(array('firstperson'=>true,'html'=>true, 'href'=>$p2->url_main()));
            print '</li>';
            print "<!-- strength $strength -->\n";
        }
        print "\n\n";
        print '</ul>';
    }
}

# Pledges you have created
function show_your_pledges($type) {
    global $pb_today, $P;
    $qdate = ($type != 'closed') ? '<=' : '>';

    $moderation_condition = '';
    if (OPTION_MODERATE_PLEDGES) {
        if ($type == 'unmoderated') {
            $moderation_condition = ' AND pledges.moderated_time IS NULL ';
        }
        else {
            $moderation_condition = ' AND NOT pledges.ishidden ';
        }
    }

    $qrows = db_query("
                    SELECT pledges.*,
                        (select count(*) from signers where pledge_id = pledges.id) as signers
                    FROM pledges
                    WHERE pledges.person_id = ?
                    AND '$pb_today' $qdate pledges.date
                        $moderation_condition
                    ORDER BY creationtime DESC
                ", $P->id());

    if ($type == 'open')
        print h2(_('Open pledges you created'));

    if (db_num_rows($qrows) > 0) {

        if ($type == 'unmoderated')
            print h2(_('Pledges you created that are pending moderation'));
        if ($type == 'closed')
            print h2(_('Closed pledges you created'));

        echo '<ul class="search_results">';
        while ($r = db_fetch_array($qrows)) {
            $pledge = new Pledge($r);
            print '<li>' . $pledge->new_summary(array('firstperson'=>true, 'creatorlinks' => true));
        }
        echo '</ul>';
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
    print _("<h2>Pledges you have signed</h2>");
    $successful_ever = 0;
    if (db_num_rows($qrows) > 0) {
        print '<ol id="yoursignedpledges">';
        $done = get_http_var('done');
        while ($r = db_fetch_array($qrows)) {
            $pledge = new Pledge($r);
            print '<li id="signed' . $pledge->id() . '"';
            if ($r['done']=='t')
                print ' class="done"';
            print '>';
            if (microsites_has_survey())
                print '<form method="post" action="' . $pledge->url_survey() . '">';
            print $pledge->summary(array('html'=>true, 'href'=>$r['ref']));
            if (microsites_has_survey()) {
                print '<p>';
                if ($r['done']=='f') {
                    if ($pledge->failed()) {
                        print _('Have you now done what you pledged anyway?');
                    } else {
                        print _('Have you now done what you pledged?');
                    }
                    print ' <input type="submit" value="' . _('Yes') . '"></p>';
                }
                if ($done == $pledge->ref() && $r['done']=='t') {
                    print p('<em>' . _("That's great!") . '</em>');
                    ?>
<script type="text/javascript">
    highlight_fade('signed<?=$pledge->id()?>');
</script>
<?
                }
                if ($r['done']=='t') {
                    print '<p>' . _('If, in fact, you have NOT yet done your bit for this pledge, you need to click this button:');
                    print ' <input type="submit" value="' . _('I HAVE NOT DONE THIS YET!') . '"></p>';
                    print '<input type="hidden" name="undopledge" value="1">';
                }
                print '<input type="hidden" name="r" value="my"></form>';
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
alert_list_comments($P->id(), get_http_var('allcomments') ? true : false);
pledges_you_might_like();
print '</div>';

print '<div id="yoursignaturesandpledges">';
if (OPTION_MODERATE_PLEDGES) {
    show_your_pledges('unmoderated');
}
show_your_pledges('open');
if (microsites_local_alerts())
    alert_list_pledges_local($P->id());
show_your_signed_pledges();
show_your_pledges('closed');
print '</div>';

page_footer();

?>
