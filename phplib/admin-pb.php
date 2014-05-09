<?php
/*
 * admin-pb.php:
 * PledgeBank admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pb.php,v 1.167 2008-09-18 16:59:27 francis Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/pledge.php";
require_once "../phplib/microsites.php";
require_once "../phplib/comments.php";
require_once '../phplib/pbfacebook.php';
require_once "../commonlib/phplib/db.php";
require_once "../commonlib/phplib/utility.php";
require_once "../commonlib/phplib/importparams.php";
require_once "../commonlib/phplib/gaze.php";

function get_prohibited_list () {
    $option = user_is_remote() ? 
        OPTION_ADMIN_LOCKED_FIELDS_SUBADMIN
        : OPTION_ADMIN_LOCKED_FIELDS;
    return preg_split("/\s*,\s*/", strtolower($option), -1, PREG_SPLIT_NO_EMPTY);
}

function remote_user_name () {
    return $_SERVER['REDIRECT_REMOTE_USER'] ? $_SERVER['REDIRECT_REMOTE_USER'] : $_SERVER['REMOTE_USER'];
}

function user_is_remote () {
    if (OPTION_ADMIN_REMOTE_SUBADMIN) {
        $username = remote_user_name();
        # local users are of the form 'hakim'
        # remote users are of the form 'joe.bloggs@rbwm.gov.uk'
        if (preg_match('/\@/', $username)) {
            return true;
        }
    }
    return false;
}

function get_admin_user () {
    $username = remote_user_name();
    $email = user_is_remote() ? $username : $username . '@mysociety.org';
    $P = person_get_or_create($email, $username);

    return $P;
}

/* admin_allow()
 * returns false if the item is not explicitly prohibitted to this site's admin
 */
function admin_allow($item) {
    return ! in_array($item, get_prohibited_list());
}

function divOddEven($n){
    return "<div class='admin-" . ($n % 2? "even":"odd") . "'>\n";
}

function facebook_display_name($facebook_id) {
    global $facebook;
    if (!$facebook)
        pbfacebook_init_cron(OPTION_FACEBOOK_ROBOT_ID);
    $facebook_name = pbfacebook_get_user_name($facebook_id);
    if (!$facebook_name) {
        $facebook_name = "<Unknown $facebook_id>"; # due to Facebook privacy search settings hiding name from non-friends
    }
    return '<a href="http://www.facebook.com/profile.php?id='.$facebook_id.'">'.htmlspecialchars($facebook_name).'</a> (Facebook)';
}

class ADMIN_PAGE_PB_SUMMARY {
    function ADMIN_PAGE_PB_SUMMARY() {
        $this->id = 'summary';
        $this->navname = _("Summary");
    }
    function display() {
        global $pb_today;

        $pledges = db_getOne('SELECT COUNT(*) FROM pledges');
        $nonbackpage = db_getOne("SELECT COUNT(*) FROM pledges WHERE cached_prominence <> 'backpage'");
        $successful = db_getOne('SELECT COUNT(*) FROM pledges WHERE whensucceeded IS NOT NULL');
        $failed = db_getOne("SELECT COUNT(*) FROM pledges WHERE '$pb_today' > date AND whensucceeded IS NULL");
        $open = db_getOne("SELECT COUNT(*) FROM pledges WHERE '$pb_today' <= date AND whensucceeded IS NULL");
        $signatures = db_getOne('SELECT COUNT(*) FROM signers');
        $signers = db_getOne('SELECT COUNT(DISTINCT person_id) FROM signers');
        $local = db_getOne('SELECT COUNT(*) FROM pledges WHERE location_id is not null');

                print <<< HTML
        <ul style="list-style: none; line-height: 1.666em;margin-bottom:3em;">
            <li>Pledges: $pledges</li>
            <li>$nonbackpage non-backpaged</li>
            <li>$successful successful, $failed failed, $open open</li>
            <li>$signatures signatures, $signers signers</li>
            <li>$local non-global</li>
        </ul>
HTML;
    }
}

class ADMIN_PAGE_PB_MAIN {
    function ADMIN_PAGE_PB_MAIN () {
        $this->id = "pb";
        $this->navname = _("Main Admin");
    }

    function search_people() {
        $this->show_menu();

        print '<p><form name="editperson" method="post" action="'.$this->self_link.'&amp;people=1">';
        print 'Search: <input type="text" name="q" value="'.htmlspecialchars(get_http_var('q')).'" size="40">';
        print '<input type="hidden" name="person_search" value="1">';
        print '<input type="submit" name="search" value="Search"> (by substring of name, email and mobile)';
        print "</form></p>";

        if (!get_http_var('q'))
            return;

        $q = db_query("SELECT person.*,
                (SELECT count(*) FROM signers WHERE person_id=person.id) AS signers,
                (SELECT count(*) FROM comment WHERE person_id=person.id AND NOT ishidden) AS comments,
                (SELECT count(*) FROM pledges WHERE person_id=person.id) AS pledges
            FROM person 
            WHERE name ilike '%'||?||'%'
            or email ilike '%'||?||'%'
            or mobile ilike '%'||?||'%'
            ", get_http_var('q'), get_http_var('q'), get_http_var('q'));

        print '<p><table border="1" cellpadding="3" cellspacing="0"><tr>';
        print '<th>Name</th><th>Email</th><th>Signings</th><th>Pledges</th><th>Comments</th>';
        print '</tr>';
        $a = 0;
        while ($r = db_fetch_array($q)) {
            $row = "";
            $row .= '<td><a href="?page=pb&amp;person=' . $r['id'] .'">' . htmlspecialchars($r['name']) . "</a></td>";
            $row .= '<td>'.htmlspecialchars($r['email']).'</td>';
            $row .= '<td>'.htmlspecialchars($r['signers']).'</td>';
            $row .= '<td>'.htmlspecialchars($r['pledges']).'</td>';
            $row .= '<td>'.htmlspecialchars($r['comments']).'</td>';

            print '<tr'.($a++%2==0?' class="v"':'').'>';
            print $row;
            print '</tr>'."\n";
        }
        print '</table></p>';
    }

    function pledge_header($sort, $openness_url) {
        print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
        $cols = array(
            'z'=>'Surge (day)',
            'r'=>'Ref', 
            'a'=>'Title', 
            's'=>'Signers', 
            'o'=>'%',
            'd'=>'Deadline', 
            'p'=>'Promin.', 
            'l'=>'Microsite<br>Place',
            'g'=>'Lang',
                'e'=>'Creator', 
                'c'=>'Creation Time', 
                'u'=>'Success Time',
            );
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s) print '<a href="'.$this->self_link.'&amp;s='.$s.$openness_url.'">';
            print $col;
            if ($sort != $s) print '</a>';
            print '</th>';
        }
        print '</tr>';
        print "\n";
    }

    function list_all_pledges() {
        $this->show_menu();

        global $found, $pb_today;
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^ratdecspuolgz]/', $sort)) $sort = 'c';
        $order = '';
        if ($sort=='r') $order = 'ref';
        elseif ($sort=='a') $order = 'title';
        elseif ($sort=='t') $order = 'target';
        elseif ($sort=='d') $order = 'date desc';
        elseif ($sort=='e') $order = 'email';
        elseif ($sort=='c') $order = 'pledges.creationtime desc';
        elseif ($sort=='u') $order = 'pledges.whensucceeded desc';
        elseif ($sort=='p') $order = 'prominence desc';
        elseif ($sort=='s') $order = 'signers desc';
        elseif ($sort=='l') $order = 'country, description';
        elseif ($sort=='g') $order = 'lang';
        elseif ($sort=='z') $order = 'surge desc';

        $openness = get_http_var('o');
        if ($openness == 'closed') {
            $openness_condition = "'$pb_today' > date";
         } else {
            $openness = 'open';
            $openness_condition = "'$pb_today' <= date";
        }

        $mode = $openness;

        $moderation_condition = '';

        if (OPTION_MODERATE_PLEDGES) {
            $moderation_status = get_http_var('m') || 0;
            if ($openness != 'closed') {
                $moderation_condition = sprintf(' AND pledges.moderated_time IS %s NULL ',
                    $moderation_status ? 'NOT' : '' );
                if ($moderation_status) {
                    $hidden_status = get_http_var('h');
                    $moderation_condition .= sprintf(' AND %s pledges.ishidden ', $hidden_status ? '' : 'NOT');
                }
                $mode = $moderation_status ? ($hidden_status ? 'bad' : 'good') : 'unmoderated';
            }
        }

        $q = db_query("
            SELECT pledges.*, person.email,
                date_trunc('second',whensucceeded) as whensucceeded, 
                date_trunc('second',creationtime) AS creationtime, 
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                pledges.cached_prominence as calculated_prominence,
                country, description,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id AND signtime > ms_current_timestamp() - interval '1 day') AS surge
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id
            LEFT JOIN location ON location.id = pledges.location_id
            WHERE $openness_condition
                  $moderation_condition
            " .  ($order ? ' ORDER BY ' . $order : '') );
        $found = array();
        while ($r = db_fetch_array($q)) {
            $row = "";

            $row .= '<td>'.$r['surge'].'</td>';
            $row .= '<td><a href="'.
                pb_domain_url(array('path'=>"/".$r['ref'], 'lang'=>$r['lang'], 'country'=>$r['country'], 'microsite'=>$r['microsite'])) .
                '">'.$r['ref'].'</a>'.
                '<br><a href="'.$this->self_link.'&amp;pledge='.$r['ref'].'">admin</a> |
                <a href="?page=pblatest&amp;ref='.$r['ref'].'">timeline</a>';
            $row .= '</td>';
            $row .= '<td>'.trim_characters(htmlspecialchars($r['title']),0,100).'</td>';
            $row .= '<td>'.htmlspecialchars($r['signers']) . ' / '.htmlspecialchars($r['target']).
            ' '.htmlspecialchars($r['target_type']).'</td>';
            $row .= '<td>' . str_replace('.00', '', round($r['signers']/$r['target']*100,0)) . '%</td>';
            $row .= '<td>'.$r['date'].'</td>';

            $row .= '<td>'.$r['prominence'];
            if ($r['calculated_prominence'] <> $r['prominence'])
                $row .= '<br>('.$r['calculated_prominence'].')';
            if ($r['pin']) 
                $row .= '<br><b>private</b> ';
            $row .= '</td>';

            $row .= '<td>';
            if ($r['microsite']) {
                global $microsites_list;
                $row .= $r['microsite'] . '<br>';
            }
            if ($r['country']) {
                global $countries_code_to_name;
                if (array_key_exists('country', $countries_code_to_name))
                    $dcountry = htmlspecialchars($countries_code_to_name[$r['country']]);
                else
                    $dcountry = htmlspecialchars($r['country']);
                $row .= $dcountry . ($r['description'] ? (" (<span title=\"".htmlspecialchars($r['description'])."\">" . substr(htmlspecialchars($r['description']),0,20).(strlen(htmlspecialchars($r['description'])) > 20 ? "..." : "")."</span>)") : '');
            } else
                $row .= 'Global';
            $row .= '</td>';
            $row .= '<td>' . htmlspecialchars($r['lang']) . '</td>';

            $row .= '<td><a href="mailto:'.htmlspecialchars($r['email']).'">'.
                htmlspecialchars($r['name']).'</a></td>';
            $row .= '<td>'.$r['creationtime'].'</td>';
            if ($r['whensucceeded']) 
                $row .= '<td>'.$r['whensucceeded'].'</td>';
            else
                $row .= '<td>None</td>';

            $found[] = $row;
        }
        if ($sort=='o') {
            function sort_by_percent($a, $b) {
                global $found;
                preg_match('#<td>([\d\.,]+)%</td>#', $found[$a], $m); $aa = str_replace(',','',$m[1]);
                preg_match('#<td>([\d\.,]+)%</td>#', $found[$b], $m); $bb = str_replace(',','',$m[1]);
                if ($aa==$bb) return 0;
                return ($aa<$bb) ? 1 : -1;
            }
            uksort($found, 'sort_by_percent');
        }

        print "<p>";
        $openness_url = "";

        if (OPTION_MODERATE_PLEDGES) {
            print admin_moderation_styles();
            $tabs = [
                [ 
                  'mode' => 'unmoderated',
                  'page' => '?page=pb&m=0',
                  'title' => 'All Unmoderated Pledges' 
                ],
                [ 
                  'mode' => 'good',
                  'page' => '?page=pb&m=1&h=0',
                  'title' => 'All Good Pledges' 
                ],
                [ 
                  'mode' => 'bad',
                  'page' => '?page=pb&m=1&h=1',
                  'title' => 'All Bad Pledges' 
                ],
                [ 
                  'mode' => 'closed',
                  'page' => '?page=pb&o=closed',
                  'title' => 'All Closed Pledges'
                ],
            ];
        } else {
            $tabs = [
                [ 
                  'mode' => 'open',
                  'page' => '?page=pb',
                  'title' => 'All Open Pledges' 
                ],
                [ 
                  'mode' => 'closed',
                  'page' => '?page=pb&o=closed',
                  'title' => 'All Closed Pledges'
                ],
            ];
        }

        $format_tab = function ($tab) use ($mode, $found) {
            if ($mode == $tab['mode']) {
                return sprintf('%s (%d)', _($tab['title']), count($found));
            } else {
                return sprintf('<a href="%s"> %s </a>', htmlspecialchars($tab['page']), _($tab['title']));
            }
        };

        $display_tabs = array_map(
            $format_tab, 
            $tabs
        );
        print join(' | ', $display_tabs);

        print "</p>";
          
        $this->pledge_header($sort, $openness_url);
        $a = 0;
        foreach ($found as $row) {
            print '<tr'.($a++%2==0?' class="v"':'').'>';
            print $row;
            print '</tr>'."\n";
        }
        print '</table>';
        print '<p>';
    }

    function show_menu() {
        print '<p>';
        if (false /*!get_http_var('people')*/) {
            print _('Pledges');
        } else {
            print '<a href="'.$this->self_link.'">' . _('Pledges') . '</a>';
        }
        print ' | ';
        if (get_http_var('people')) {
            print _('People');
        } else {
            print '<a href="'.$this->self_link.'&amp;people=1">' . _('People') . '</a>';
        }
        print '</p>';
    }

    function show_one_person($person_id) {
        $person_id = intval($person_id);
        $this->show_menu();

        $person = new Person($person_id);

        $q = db_query('SELECT person.*,
                (SELECT count(*) FROM signers WHERE person_id=person.id) AS signers,
                (SELECT count(*) FROM comment WHERE person_id=person.id AND NOT ishidden) AS comments,
                (SELECT count(*) FROM pledges WHERE person_id=person.id) AS pledges
            FROM person 
            WHERE id = ?', $person_id);
        $pdata = db_fetch_array($q);
        
        print "<h2>Person '" . htmlspecialchars($person->name_or_blank()) . "'</h2>";
        
        $parity=0;
        print divOddEven($parity++);
        print "<div class='admin-name'>Name:</div><div class='admin-value'>";
        print ($person->has_name() ? htmlspecialchars($person->name()) : "<unknown>");
        print "</div></div>";
        
        print divOddEven($parity++);
        print "<div class='admin-name'>Email:</div><div class='admin-value'>";
        print "<a href=\"mailto:" . htmlspecialchars($person->email()) . "\">" . htmlspecialchars($person->email()) . '</a>';
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Mobile:</div><div class='admin-value'>";
        print htmlspecialchars($pdata['mobile']);
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Facebook:</div><div class='admin-value'>";
        print htmlspecialchars($pdata['facebook_id']);
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Has password:</div><div class='admin-value'>";
        print $person->has_password() ? "yes" : "no";
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Number of logins:</div><div class='admin-value'>";        
        print htmlspecialchars($person->numlogins());
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Website:</div><div class='admin-value'>";        
        print '<a href="'.htmlspecialchars($person->website_or_blank()).'">' . htmlspecialchars($person->website_or_blank()) . "</a>";
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Activity:</div><div class='admin-value'>";        
        print $pdata['signers'] . " " . make_plural($pdata['signers'], "signature"); 
        print ", " . $pdata['pledges'] . " " . make_plural($pdata['pledges'], "pledge");
        print ", " . $pdata['comments'] . " " . make_plural($pdata['comments'], "comment");
        print "</div></div>";


        print "<h2>Pledges created</h2>";
        $nRecords = 0;
        $q = db_query('SELECT * from pledges where person_id = ?', $person_id);
        while ($r = db_fetch_array($q)) {
            print '<a href="' . OPTION_BASE_URL . '/' . $r['ref'] . '">' .
                htmlspecialchars($r['ref']) . '</a>';
            print' (<a href="?page=pb&amp;pledge='.$r['ref'].'">admin</a>) ';
            $nRecords++;
        }
        if ($nRecords == 0)
            print "none";

        print "<h2>Pledges signed</h2>";
        $nRecords = 0;
        $q = db_query('SELECT * from pledges where id in (select pledge_id from signers where person_id = ?)', $person_id);
        while ($r = db_fetch_array($q)) {
            print '<a href="' . OPTION_BASE_URL . '/' . $r['ref'] . '">' .
                htmlspecialchars($r['ref']) . '</a>';
            print' (<a href="?page=pb&amp;pledge='.$r['ref'].'">admin</a>) ';
            $nRecords++;
        }
        if ($nRecords == 0)
            print "none";
        
        print "<h2>Pledges commented on</h2>";
        $nRecords = 0;
        $q = db_query('SELECT * from pledges where id in (select pledge_id from comment where person_id = ?)', $person_id);
        while ($r = db_fetch_array($q)) {
            print '<a href="' . OPTION_BASE_URL . '/' . $r['ref'] . '">' .
                htmlspecialchars($r['ref']) . '</a>';
            print' (<a href="?page=pb&amp;pledge='.$r['ref'].'">admin</a>) ';
            $nRecords++;
        }
        if ($nRecords == 0)
            print "none";

         print "<h2>Edit person</h2>";

        print '<form name="editperson" method="post" action="'.$this->self_link.'">';
        print 'Email: <input type="text" name="email" value="'.htmlspecialchars($person->email).'" size="40">';
        print '<input type="hidden" name="edit_person_id" value="' . $person_id . '">';
        print '<input type="hidden" name="edit_person" value="1">';
        print '<input type="hidden" name="edit" value="1">';
        print '<input type="submit" name="edit_person" value="Save updates"> ';
        print "</form>";
     }

    function show_one_pledge($pledge) {
        $this->show_menu();

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etcn]/', $sort)) $sort = 't';
        $list_limit = get_http_var('l');
        if ($list_limit) {
            $list_limit = intval($list_limit);
            if ($list_limit == -1)
                $list_limit = null;
        }
        else
            $list_limit = 100;

        $q = db_query('SELECT pledges.*, person.email,
                pledges.cached_prominence as calculated_prominence,
                location.country, location.state, location.description,
                location.longitude, location.latitude, location.method,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                (SELECT count(*) FROM comment WHERE pledge_id=pledges.id AND NOT comment.ishidden) AS comments,
                person.id as person_id
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id 
            LEFT JOIN location ON location.id = pledges.location_id
            WHERE lower(ref) = ?', strtolower($pledge));
        $pdata = db_fetch_array($q);
        if (!$pdata) {
            print sprintf("Pledge '%s' not found", htmlspecialchars($pledge));
            return;
        }
        $pledge_obj = new Pledge($pdata);

        $pledge_obj->render_box(array('showdetails' => true));

        print "<h2>Pledge '<a href=\"".
                pb_domain_url(array('path'=>"/".$pledge_obj->ref(), 'lang'=>$pledge_obj->lang(), 'country'=>$pledge_obj->country_code(), 'microsite'=>$pledge_obj->microsite())) .
                "\">" . $pdata['ref'] . "</a>'";
        print ' (<a href="?page=pblatest&amp;ref='.$pdata['ref'].'">' . _('timeline') . '</a>)';
        print "</h2>";

        $parity=0;

        $pledge_types =  microsites_get_custom_pledge_types();
        if ($pledge_types == null) {
          print "<!-->Custom pledge types not enabled.</-->";
        } else {
            print divOddEven($parity++);
            print "<div class='admin-name'>Type:</div>";
            print '<div class="admin-value">';
            print '<form name="pledge_typeform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_pledge_type" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            print '<select id="pledge_type" name="pledge_type">';
            print ' <option value="0">(none)</option>';
            foreach ($pledge_types as $p_type) {
                $sel = '';
                if ($p_type == $pledge_obj->pledge_type())
                    $sel = ' selected';
                print " <option $sel value='$p_type'>$p_type</option>";
            }
            print '</select>';
            print '<input name="update" type="submit" value="Update">';
            print '</form>';
            print "</div>";
            print "</div>";
            
            $is_valid_pledge_type = in_array($pledge_obj->pledge_type(), $pledge_types);
            # report if the actual type isn't one we know about, just in case
            if ($pledge_obj->pledge_type() and ! $is_valid_pledge_type) {
                print divOddEven($parity++);            
                print "<div class='admin-name'>Actual Type:</div>";
                print '<div class="admin-value">'. $pledge_obj->pledge_type() . ' (not found!)</div>';
                print "</div>";                
            }

            print divOddEven($parity++);
            print "<div class='admin-name'>Ref in Type:</div>";
            print '<div class="admin-value">';
            if (! $is_valid_pledge_type && (get_http_var("edit_ref_in_pledge") || $pdata['ref_in_pledge_type'])) {
                print "Warning: ref in type won't be used until the pledge has a valid pledge type!" . '<br/>';
            }
            if (get_http_var("edit_ref_in_pledge")) {
                print '<form name="ref_in_pledge_typeform" method="post" action="'.$this->self_link.'">';
                print '<input type="hidden" name="update_ref_in_pledge_type" value="1">';
                print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
                print '<input type="text" name="ref_in_pledge_type" id="ref_in_pledge_type" value="' . htmlspecialchars($pdata['ref_in_pledge_type']) . '">';
                print '<input name="update" type="submit" value="Update">';
                print '</form>';
            } else {
                print htmlspecialchars($pdata['ref_in_pledge_type']);
                if ($pdata['ref_in_pledge_type'])
                    print "<br/>";
                print '<a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp;edit_ref_in_pledge=1">Edit ref</a>';
            }
            print '</div>';
            print "</div>";
        }

        if (OPTION_MODERATE_PLEDGES) {
            print admin_moderation_styles(); # hack
            print divOddEven($parity++);
            print "<div class='admin-name'>Moderation:</div>";
            print '<div class="admin-value">';
            print '<form name="moderationform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_moderation" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';

            $is_moderated = $pdata['moderated_time'] ? true : false;

            if ($is_moderated) {
                $bad = $pledge_obj->ishidden();
                printf('Moderated <span class="moderated_%s">%s</span> by %s at %s',
                    $bad ? 'bad' : 'good',
                    $bad ? 'BAD' : 'GOOD',
                    $pledge_obj->moderator()->name(),
                    $pdata['moderated_time']
                );
            }

            printf('<br />Comment: <input type="text" name="moderated_comment" value="%s">',
                htmlspecialchars( $pdata['moderated_comment']) );

            if ($is_moderated) {
                print "<br />Remoderate as:";
                if ($bad) {
                    print '<input name="moderate_good" type="submit" value="Good">';
                }
                else {
                    print '<input name="moderate_bad" type="submit" value="Bad">';
                }
            }
            else {
                print "Not yet moderated. Moderate as:";
                print '<input name="moderate_good" type="submit" value="Good">';
                print '<input name="moderate_bad" type="submit" value="Bad">';
            }

            printf('<input type="checkbox" name="send_moderation_email" %s> (send email)',
                $is_moderated ? '' : 'checked');

            print "</form>";
            print "</div>";
        }
        
        print divOddEven($parity++);
        print "<div class='admin-name'>Set by:</div>";
        print '<div class="admin-value"><a href="?page=pb&amp;person=' . $pdata['person_id'] .'">' . htmlspecialchars($pdata['name']) . "</a>" .
            " &lt;" .  htmlspecialchars($pdata['email']) . "&gt;</div>";
        print "</div>";
            
        print divOddEven($parity++);
        print "<div class='admin-name'>Created:</div>";
        print '<div class="admin-value">' . prettify($pdata['creationtime']) . "</div>";
        print "</div>";
        
        print divOddEven($parity++);
        print "<div class='admin-name'>Deadline:</div>";
        print '<div class="admin-value">' . prettify($pdata['date']) . "</div>";
        print "</div>";
        
        print divOddEven($parity++);
        print "<div class='admin-name'>Target:</div>";
        print '<div class="admin-value">' . $pdata['target'] . " " .  htmlspecialchars($pdata['type']);
        if ($pdata['target_type'] == "byarea") 
            print " (target is byarea)";
        print "</div></div>";
        
        // Microsite
        print divOddEven($parity++);
        print "<div class='admin-name'>Microsite:</div>";
        print '<div class="admin-value">';
        if (admin_allow('microsite')) {
            global $microsites_list;
            print '<form name="micrositeform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_microsite" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            print '<select id="microsite" name="microsite">';
            print ' <option value="(none)">(none)</option>';
            foreach ($microsites_list as $ms => $ms_name) {
                $sel = '';
                if ($ms == $pledge_obj->microsite())
                    $sel = ' selected';
                print ' <option value="'.$ms.'"'.$sel.'>'.str_replace('<em>', '', str_replace('</em>', '', $ms_name))
                    .'</option>';
            }
            print '</select>';
            print '<input name="update" type="submit" value="Update">';
            print '</form>';
        } else {
            if ($pledge_obj->microsite()) {
                print $pledge_obj->microsite();
            } else {
                print "&lt;none&gt;";
            }
        }
        print "</div></div>";

        print divOddEven($parity++);
        print "<div class='admin-name'>Language:</div>";
        print '<div class="admin-value">';
        if (admin_allow('lang')) {
            global $langs;
            print '<form name="languageform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_language" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            print '<select id="lang" name="lang">';
            print ' <option value="(unknown)">(unknown)</option>';
            foreach ($langs as $lang_code => $lang_name) {
                $sel = '';
                if ($lang_code == $pdata['lang'])
                    $sel = ' selected';
                print ' <option value="'.$lang_code.'"'.$sel.'>'.$lang_name.'</option>'; // lang_name already in HTML
            }
            print '</select>';
            print '<input name="update" type="submit" value="Update">';
            print '</form>';
        } else {
            print $pdata['lang'];
        }
        print "</div></div>";

        if (array_key_exists('country', $pdata)) {
            print divOddEven($parity++);
            print "<div class='admin-name'>Country:</div>";
            print '<div class="admin-value">';
            if (admin_allow('country')) {
                print '<form name="countryform" method="post" action="'.$this->self_link.'">';
                print '<input type="hidden" name="update_country" value="1">';
                print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
                gaze_controls_print_country_choice($pdata['country'], $pdata['state'], array(), array());
                print '<input name="update" type="submit" value="Update">';
                print '</form>';
            } else {
                print $pdata['country'];
            }
            print "</div></div>";
            if (array_key_exists('description', $pdata) && $pdata['description']){
                print divOddEven($parity++);
                print "<div class='admin-name'>Place:</div>";
                print '<div class="admin-value">' . $pdata['description'].'</div>';
                print "</div>";
            }
            if ($pdata['longitude']) {
                $coords = round($pdata['longitude'],2).'E ' . round($pdata['latitude'],2).'N';
                print divOddEven($parity++);
                print "<div class='admin-name'>Long/Lat:</div>";
                print '<div class="admin-value">WGS84' . $coords;
                print ' <a href="'.htmlspecialchars($pledge_obj->url_place_map()).'">(google maps)</a>';
                print "</div></div>";
            }
        }

        // Tags        
        print divOddEven($parity++);
        print "<div class='admin-name'>Tags:</div>";
        print '<div class="admin-value">';
        $tags = $pledge_obj->tags();
        if ($tags) {
            print "<strong>";
            foreach ($tags as $tag) {
                print $tag . " ";
            }
            print "</strong>";
        } else {
            print _('none');
        }
        print "</div></div>";

        // Prominence
        print divOddEven($parity++);
        print "<div class='admin-name'>Prominence:</div>";
        print '<div class="admin-value">';        
        if (admin_allow('prominence')) {
            print '<form name="prominenceform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_prom" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            if ($pdata['pin'])
                print "<b>private</b> ";
            print '<select name="prominence">';
            print '<option value="calculated"' . ($pdata['prominence']=='calculated'?' selected':'') . '>calculated</option>';
            print '<option value="normal"' . ($pdata['prominence']=='normal'?' selected':'') . '>normal</option>';
            print '<option value="frontpage"' . ($pdata['prominence']=='frontpage'?' selected':'') . '>frontpage</option>';
            print '<option value="backpage"' . ($pdata['prominence']=='backpage'?' selected':'') . '>backpage</option>';
            print '</select>';
            print '<input name="update" type="submit" value="Update">';
            if ($pdata['calculated_prominence'] <> $pdata['prominence']) {
                print " calculated to: ". $pdata['calculated_prominence'];
            }
            print '</form>';
        } else {
            print $pdata['prominence'];
            if ($pdata['calculated_prominence'] <> $pdata['prominence']) {
                print " (calculated to: ". $pdata['calculated_prominence'] . ")";
            }
        }
        print "</div></div>";

        // Prominence
        print divOddEven($parity++);
        print "<div class='admin-name'>Comments:</div>";
        print '<div class="admin-value">'. $pdata['comments'] . '</div>';
        print '</div>';

        print divOddEven($parity++);
        print "<div class='admin-name'>Picture:</div>";
        print '<div class="admin-value">';
        $picture_edit_verb = $pledge_obj->has_picture()? "Change" : "Add";
        if (admin_allow('picture') && get_http_var("edit_picture")) {
            print '<h2>' . $picture_edit_verb . ' picture</h2>';
            print '<form name="editpicform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_picture" value="1">';
            print '<label for="picture_url">Picture URL: </label>';
            print '<input type="text" name="picture_url" value="' . htmlspecialchars($pdata['picture']) . '" size="64">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            $preloaded_images = microsite_preloaded_images('exists', $pdata['microsite']);
            if (count($preloaded_images)>0){
                print '<br/><label for="preloaded_image">Or choose a preloaded image: </label>';
                print microsite_preloaded_image_select($pdata['microsite']);
            }
            print '<br/><input name="update" type="submit" value="Update picture">';
            print '</form><br/>';
        } else {
            if ($pledge_obj->has_picture()) {
                $pretty_picture = $pdata['picture'];
                if (strlen($pretty_picture)>40) {
                    $pretty_picture="..." . substr($pretty_picture, -36);
                }
                print "<a href='" . $pdata['picture'] . "' title='". $pdata['picture'] . "'>$pretty_picture</a><br>";
            } else {
                print 'none: ';
            }
            if (admin_allow('picture')) {
                print '<a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp;edit_picture=1">'. $picture_edit_verb . ' picture</a>';
            }
        }
        if (admin_allow('picture') && $pledge_obj->has_picture()) {
            print '<form name="delpicform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="remove_picture" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            print '<input name="update" type="submit" value="Remove picture">';
            print '</form>';
        }
        print "</div></div>";
        
        if (admin_allow('techy')) {
            print divOddEven($parity++);
            print "<div class='admin-name'>Pledge text:</div>";
            print '<div class="admin-value">';
            if (get_http_var("edit")) {
                print '<h2>Edit pledge text</h2>';
                print '<form name="editform" method="post" action="'.$this->self_link.'">';
                print 'I will <input type="text" name="title" value="'.htmlspecialchars($pdata['title']).'" size="60">';
                print '<br>but only if <input type="text" name="target" value="'.htmlspecialchars($pdata['target']).'" size="4">';
                print ' <input type="text" name="type" value="'.htmlspecialchars($pdata['type']).'" size="40">';
                print '<br>will <input type="text" name="signup" value="'.htmlspecialchars($pdata['signup']).'" size="60">';
                print '<br>&mdash;<input type="text" name="name" value="'.htmlspecialchars($pdata['name']).'" size="20">, ';
                print '<input type="text" name="identity" value="'.htmlspecialchars($pdata['identity']).'" size="30">';
                print '<br>More details:<br/> <textarea type="text" name="detail" cols="70" rows="7">'.htmlspecialchars($pdata['detail']).'</textarea>';
                print '<br>Notice: <input type="text" name="notice" value="'.htmlspecialchars($pdata['notice']).'" size="60">';
                print '<br>Cancelled text (also cancels pledge): <input type="text" name="cancelled" value="'.htmlspecialchars($pdata['cancelled']).'" size="60">';
                print '<input type="hidden" name="edit_pledge_text_id" value="' . $pdata['id'] . '">';
                print '<input type="hidden" name="edit_pledge_text" value="1">';
                print '<input type="hidden" name="edit" value="1">';
                print '<br><input type="submit" name="edit_pledge" value="Save updates"> ';
                print ' <a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp">Cancel edit</a>';
                print "</form>";
            } else {
                print '<a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp;edit=1">Edit pledge text</a>';
            }
            print "</div></div>";
        }
        
        // Signers
        print "<h2>Signers (".$pdata['signers']."/".$pdata['target'].")</h2>";
        $query = 'SELECT signers.name as signname,person.email as signemail,
                         person.mobile as signmobile, person.facebook_id as signfacebook_id,
                         date_trunc(\'second\',signtime) AS signtime,
                         showname, signers.id AS signid,
                         location.description AS location_description,
                         person.id as signperson_id
                   FROM signers 
                   LEFT JOIN person ON person.id = signers.person_id
                   LEFT JOIN location ON location.id = signers.byarea_location_id
                   WHERE pledge_id=?';
        if ($sort=='t') $query .= ' ORDER BY signtime DESC';
        elseif ($sort=='n') $query .= ' ORDER BY showname DESC';
        else $query .= ' ORDER BY signname DESC';
        if ($list_limit) 
            $query .= " LIMIT $list_limit";
        $q = db_query($query, $pdata['id']);
        $out = array();
        $c = 0;
        while ($r = db_fetch_array($q)) {
            $c++;
            $r = array_map('htmlspecialchars', $r);
            $e = array();
            if ($r['signname'])
                array_push($e, '<a href="?page=pb&amp;person=' . $r['signperson_id'] .'">' . $r['signname'] . "</a>");
            if ($r['signemail'])
                array_push($e, $r['signemail']);
            if ($r['signmobile'])
                array_push($e, $r['signmobile']);
            if ($r['signfacebook_id']) {
                array_push($e, facebook_display_name($r['signfacebook_id']));
            }
            if ($r['location_description'])
                array_push($e, $r['location_description']);
            $e = join("<br>", $e);
            $out[$e] = '<td>'.$e.'</td>';
            $out[$e] .= '<td>'.prettify($r['signtime']).'</td>';

            $out[$e] .= '<td><form name="shownameform'.$c.'" method="post" action="'.$this->self_link.'"><input type="hidden" name="showname_signer_id" value="' . $r['signid'] . '">';
            $out[$e] .= '<select name="showname">';
            $out[$e] .=  '<option value="1"' . ($r['showname'] == 't'?' selected':'') . '>Yes</option>';
            $out[$e] .=  '<option value="0"' . ($r['showname'] == 'f'?' selected':'') . '>No</option>';
            $out[$e] .=  '</select>';
            $out[$e] .= '<input type="submit" name="showname_signer" value="update">';
            $out[$e] .= '</form></td>';

            $out[$e] .= '<td>';
            $out[$e] .= '<form name="removesignerform'.$c.'" method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer permanently"></form>';
            $out[$e] .= '</td>';
        }
        if ($sort == 'e') {
            function sort_by_domain($a, $b) {
                $aa = stristr($a, '@');
                $bb = stristr($b, '@');
                if ($aa==$bb) return 0;
                return ($aa>$bb) ? 1 : -1;
            }
            uksort($out, 'sort_by_domain');
        }
        if (count($out)) {
            print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
            $cols = array('e'=>'Signer', 't'=>'Time', 'n'=>'Show name?');
            foreach ($cols as $s => $col) {
                print '<th>';
                if ($sort != $s) print '<a href="'.$this->self_link.'&amp;pledge='.$pledge.'&amp;s='.$s.'">';
                print $col;
                if ($sort != $s) print '</a>';
                print '</th>';
            }
            print '<th>Action</th>';
            print '</tr>';
            $a = 0;
            foreach ($out as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>';
            }
            print '</table>';
            if ($list_limit && $c >= $list_limit) {
                print "<p>... only $list_limit signers shown, "; 
                print '<a href="'.$this->self_link.'&amp;pledge='.$pledge.'&amp;l=-1">show all</a>';
                print '</p>';
            }
        } else {
            print '<p>Nobody has signed up to this pledge.</p>';
        }
        print '<p>';
        
        // Messages
        print h2(_("Messages"));
        
        if (microsites_admin_announce_link($pdata['microsite'])) {
            print '<p><a href="' . $pledge_obj->url_announce() .'" title="Pledge creator can send">' . _('Send message to signers') . '</a></p>';
        }
        
        $q = db_query('select message.*, location.description as location_description from message 
                left join location on location.id = message.byarea_location_id
                where pledge_id = ? order by whencreated', $pdata['id']);

        $n = 0;
        while ($r = db_fetch_array($q)) {
            if ($n++)
                print '<hr>';

            $got_creator_count = db_getOne('select count(*) from message_creator_recipient where message_id = ?', $r['id']);
            $got_signer_count = db_getOne('select count(*) from message_signer_recipient where message_id = ?', $r['id']);

            $whom = array();
            if ($r['sendtocreator'] == 't') { $whom[] = 'creator'; }
            if ($r['sendtosigners'] == 't') { $whom[] = 'signers'; }
            if ($r['sendtolatesigners'] == 't') { $whom[] = 'late signers'; }

            print "<p>";
            print "<strong>". $r['circumstance'] . ' ' . $r['circumstance_count'] . '</strong>';
            print " created on ". prettify(substr($r['whencreated'], 0, 19));
            print " to be sent from <strong>" . $r['fromaddress'] . "</strong> to ";
            print "<strong>";
            print join(", ", $whom) . "</strong>";
            if ($r['byarea_location_id']) 
                print " for " . $r['location_description'] . " ";
            print "<br>has been queued to evel for ";
            print "<strong>$got_creator_count creators</strong>";
            print " and <strong>$got_signer_count signers</strong>";
            if ($r['sms'])
                print "<br><strong>sms content:</strong> " . $r['sms'];
            if ($r['emailtemplatename'])
                print "<br><strong>email template:</strong> " . $r['emailtemplatename'];
            if ($r['emailsubject'])
                print "<br><strong>email subject:</strong> " . htmlspecialchars($r['emailsubject']);
            if ($r['emailbody']) {
                print '<br><strong>email body:</strong>
                <div class="message">.'.comments_text_to_html($r['emailbody'])."</div>";
            }

        }
        if ($n == 0) {
            print "No messages yet.";
        }

        // Category setting
        $cats = array();
        $q = db_query('select category_id from pledge_category where pledge_id = '.$pdata['id']);
        while ($r = db_fetch_array($q)) {
            $cats[$r['category_id']] = 1;
        }
        print '<form name="categoriesform" method="post" action="'.$this->self_link.'">
            <input type="hidden" name="pledge_id" value="'.$pdata['id'].'">
            <input type="hidden" name="update_cats" value="1">
            <h2>Categories</h2>
            <p><select name="categories[]" multiple>';
        $s = db_query('select id, parent_category_id, name from category 
            where parent_category_id is null
            order by id');
        while ($a = db_fetch_row($s)) {
            list($id, $parent_id, $name) = $a;
            print '<option';
            if (array_key_exists($id, $cats)) print ' selected';
            print ' value="' . $id . '">' .
                (is_null($parent_id) ? '' : '&nbsp;-&nbsp;') . 
                 htmlspecialchars($name) . ' </option>';
        }
        print '</select> <input type="submit" value="Update"></p></form>';

        print '<h2>Comments</h2>';
        comments_show_admin($pledge_obj->id(), $list_limit); 
        if ($list_limit && $c >= $list_limit) {
            print "<p>... only $list_limit comments shown, "; 
            print '<a href="'.$this->self_link.'&amp;pledge='.$pledge.'&amp;l=-1">show all</a>';
            print ' (do not press if you are Tom, it will crash your computer :)</p>';
        }

        print '<h2>Actions</h2>';
        print '<form name="sendannounceform" method="post" action="'.$this->self_link.'"><input type="hidden" name="send_announce_token_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="send_announce_token" value="Send announce URL to creator"></form>';

        if (admin_allow('techy')) {
print '<form name="removepledgepermanentlyform" method="post" action="'.$this->self_link.'" style="clear:both;margin-top:1em;"><strong>Caution!</strong> This really is forever, you probably don\'t want to do it: <input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';
        }

    }

    function remove_pledge($id) {
        pledge_delete_pledge($id);
        db_commit();
        print p(_('<em>That pledge has been successfully removed, along with all its signatories.</em>'));
    }

    function remove_signer($id) {
        pledge_delete_signer($id);
        db_commit();
        print p(_('<em>That signer has been successfully removed.</em>'));
    }

    function showname_signer($id) {
        db_query('UPDATE signers set showname = ? where id = ?', 
            array(get_http_var('showname') ? true : false, $id));
        db_commit();
        # TRANS: http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000078.html
        print p(_('<em>Show name for signer updated</em>'));
    }

    function deletecomment($id) {
        $admin_user = get_admin_user();
        db_query("UPDATE comment set
            ishidden = ?,
            moderated_time = ms_current_timestamp(),
            moderated_by = ?,
            moderated_comment = 'admin panel'
            where id = ?",
            array(get_http_var('deletecomment_status') ? true : false,
                $admin_user->id,
                $id
            )
        );
        db_commit();
        print p(_('<em>That comment has been shown/hidden</em>'));
    }

    function update_prominence($pledge_id) {
        db_query('UPDATE pledges set prominence = ? where id = ?', array(get_http_var('prominence'), $pledge_id));
        db_commit();
        print p(_("<em>Change to pledge prominence saved</em>"));
    }

    function update_pledge_type($pledge_id) {
        $new_pledge_type = get_http_var('pledge_type');
        if (!$new_pledge_type || $new_pledge_type == '(none)') {
            db_query('UPDATE pledges set pledge_type = null where id = ?', array($pledge_id));
        } else {
            $pledge_types = microsites_get_custom_pledge_types();
            if ($pledge_types == null || ! in_array($new_pledge_type, $pledge_types)) {
                err('Unknown pledge_type: ' . htmlspecialchars($new_pledge_type));
            }
            db_query('UPDATE pledges set pledge_type = ? where id = ?', array($new_pledge_type, $pledge_id));
        }
        db_commit();
        print p(_("<em>Change to pledge type saved</em>"));
    }
    
    function update_ref_in_pledge_type($pledge_id) {
        $new_ref_in_pledge_type = get_http_var('ref_in_pledge_type');
        db_query('UPDATE pledges set ref_in_pledge_type = ? where id = ?', array($new_ref_in_pledge_type, $pledge_id));
        db_commit();
        print p(_("<em>Change to ref in pledge type saved</em>"));
    }

    function update_microsite($pledge_id) {
        global $microsites_list;
        $new_microsite = get_http_var('microsite');
        if (!$new_microsite || $new_microsite == '(none)') {
            db_query('UPDATE pledges set microsite = null where id = ?', array($pledge_id));
        } else {
            if (!array_key_exists($new_microsite, $microsites_list)) {
                err('Unknown microsite code: ' . htmlspecialchars($new_microsite));
            }
            db_query('UPDATE pledges set microsite = ? where id = ?', array($new_microsite, $pledge_id));
        }
        db_commit();
        print p(_("<em>Change to pledge microsite saved</em>"));
    }

    function update_moderation($pledge_id) {
        # NOTE that update_moderation will override prominence also. This is
        # requested behaviour for RBWM and seems like a reasonable default for
        # other users of moderation in future.

        $ishidden = null;
        if (get_http_var('moderate_good')) $ishidden = false;
        if (get_http_var('moderate_bad'))  $ishidden = true;

        if (! isset($ishidden)) {
            die("Unexpected error in moderation!");
        }

        $send_moderation_email = get_http_var('send_moderation_email');
        $moderated_comment = get_http_var('moderated_comment');

        db_query('UPDATE pledges SET
            ishidden = ?,
            moderated_time = ms_current_timestamp(),
            moderated_by = ?,
            moderated_comment = ?,
            prominence = ?,
            cached_prominence = ?
            WHERE id = ?',
            [
                $ishidden,
                get_admin_user()->id,
                $moderated_comment,
                $ishidden ? 'backpage' : 'frontpage',
                $ishidden ? 'backpage' : 'frontpage',
                $pledge_id
            ]);

        db_commit();
        print p(_("<em>Pledge has been moderated</em>"));

        if ($send_moderation_email) {
            $pledge = new Pledge($pledge_id);

            $q_name = $pledge->creator_name();
            $q_email = $pledge->creator_email();

            $template_name = $ishidden ? 'moderated-bad' : 'moderated-good';

            $template_data = [
                'id' => $pledge_id,
                'ref' => $pledge->ref(),
                'pledge_url_microsite' => $pledge->url_typein($pledge->microsite()),
                'title' => $pledge->title(),
                'moderated_comment_clause' => $moderated_comment ?
                    _("The reason was") . ": \n\n\t"  . $moderated_comment
                    : '',
            ];

            pb_send_email_template($q_name ? [[ $q_email, $q_name ]] : $q_email,
                $template_name,
                $template_data);

            print p(_("<em>Email sent to pledge creator</em>."));
        }
    }

    function update_country($pledge_id) {
        $country = get_http_var('country');
        $state = null;
        if ($country) {
            $a = array();
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
        }
        global $countries_code_to_name;
        if (!$country || $country == 'Global') {
            db_query('UPDATE pledges set location_id = NULL where id = ?', array($pledge_id));
        } elseif (array_key_exists($country, $countries_code_to_name)) {
            db_query("
                    insert into location
                        (country, state, method, input, latitude, longitude, description)
                    values (?, ?, ?, ?, ?, ?, ?)", array(
                        $country, $state,
                        NULL, NULL,
                        NULL, NULL,
                        NULL
                    ));
            db_query('UPDATE pledges set location_id = (select currval(\'location_id_seq\')) where id = ?', array($pledge_id));
        } else {
            print p(sprintf(_("<em>Unknown country %s</em>"), htmlspecialchars($country)));
            return;
        }
        db_commit();
        print p(_("<em>Change to pledge country saved</em>"));
    }

    function update_language($pledge_id) {
        global $langs;
        $new_lang = get_http_var('lang');
        if (!array_key_exists($new_lang, $langs)) {
            err('Unknown language code: ' . htmlspecialchars($new_lang));
        }
        db_query('UPDATE pledges set lang = ? where id = ?', array($new_lang, $pledge_id));
        db_commit();
        print p(_("<em>Change to pledge language saved</em>"));
    }

    function update_picture($pledge_id) {
        $new_picture = get_http_var('picture_url');
        $preloaded_image = get_http_var('preloaded_image');
        if ($preloaded_image) {
            $new_picture = microsite_preloaded_image_url($preloaded_image);
        }
        # if $preloaded_image, turn it into url and use it if it's not null
        db_query('UPDATE pledges set picture = ? where id = ?', array($new_picture, $pledge_id));
        db_commit();
        print p(_("<em>Change to pledge picture saved</em>"));
    }
    
    function remove_picture($pledge_id) {
        db_query('UPDATE pledges set picture = null where id = ?', $pledge_id);
        db_commit();
        print p(_("<em>Pledge picture removed</em>"));        
    }

    function update_categories($pledge_id) {
        $cats = get_http_var('categories');
        db_query('delete from pledge_category where pledge_id = ?', $pledge_id);
        if (is_array($cats)) {
            foreach ($cats as $id) {
                db_query('insert into pledge_category (pledge_id, category_id) VALUES (?, ?)', array($pledge_id, $id));
            }
        }
        db_commit();
        print p(_('<em>Categories updated.</em>'));
    }

    function edit_pledge_text($pledge_id) {
        $pledge = new Pledge($pledge_id);

        $title = get_http_var('title');
        $type = get_http_var('type');
        $signup = get_http_var('signup');
        $name = get_http_var('name');
        $identity = get_http_var('identity');
        $detail = get_http_var('detail');
        $notice = get_http_var('notice');
        if (!$notice) $notice = null;
        $cancelled = get_http_var('cancelled');
        if (!$cancelled) $cancelled = null;
        $target = intval(get_http_var('target'));
        if ($pledge->target() > $pledge->signers()) {
            if ($target <= $pledge->signers()) {
                print p(_('<em>Pick a target larger than the number of signers please!</em>'));
                return;
            }
        } else {
            if ($target > $pledge->signers()) {
                print p(_('<em>Pick a target smaller than or equal to the number of signers please!</em>'));
                return;
            }
        }
        db_query('update pledges set title = ?, type = ?, signup = ?, name = ?,
            identity = ?, detail = ?, target = ?, notice = ?, cancelled = ?,
            changetime = ms_current_timestamp()
            where id = ?', $title, $type, $signup, $name, $identity, $detail, $target, $notice, $cancelled, $pledge_id);
        db_commit();
        print p(_('<em>Pledge text updated. Check it in the pledge box preview on the right.</em>'));
    }

    function edit_person($person_id) {
        $person = new Person($person_id);

        $email = get_http_var('email');

        db_query('update person set email = ? where id = ?', $email, $person_id);
        db_commit();
        print p(_('<em>Person updated.</em>'));
    }


    function display($self_link) {
        db_connect();

        $pledge = get_http_var('pledge');
        $pledge_id = null;
        $person_id = get_http_var('person');

        // Perform actions
        if (get_http_var('update_prom')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_prominence($pledge_id);
        } elseif (get_http_var('update_pledge_type')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_pledge_type($pledge_id); 
        } elseif (get_http_var('update_ref_in_pledge_type')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_ref_in_pledge_type($pledge_id);
        } elseif (get_http_var('update_moderation')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_moderation($pledge_id);
        } elseif (get_http_var('update_microsite')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_microsite($pledge_id);
        } elseif (get_http_var('update_country')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_country($pledge_id);
        } elseif (get_http_var('update_language')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_language($pledge_id);
        } elseif (get_http_var('remove_picture')) {
            $pledge_id = get_http_var('pledge_id');
            $this->remove_picture($pledge_id);
        } elseif (get_http_var('update_picture')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_picture($pledge_id);
        } elseif (get_http_var('remove_pledge_id')) {
            $remove_id = get_http_var('remove_pledge_id');
            if (ctype_digit($remove_id))
                $this->remove_pledge($remove_id);
        } elseif (get_http_var('remove_signer_id')) {
            $signer_id = get_http_var('remove_signer_id');
            if (ctype_digit($signer_id)) {
                $pledge_id = db_getOne("SELECT pledge_id FROM signers WHERE id = $signer_id");
                $this->remove_signer($signer_id);
            }
        } elseif (get_http_var('showname_signer_id')) {
            $signer_id = get_http_var('showname_signer_id');
            if (ctype_digit($signer_id)) {
                $pledge_id = db_getOne("SELECT pledge_id FROM signers WHERE id = $signer_id");
                $this->showname_signer($signer_id);
            }
        } elseif (get_http_var('deletecomment_comment_id')) {
            $pledge_id = get_http_var('pledge_id');
            $comment_id = get_http_var('deletecomment_comment_id');
            if (ctype_digit($comment_id)) {
                $this->deletecomment($comment_id);
            }
        } elseif (get_http_var('update_cats')) {
            $pledge_id = get_http_var('pledge_id');
            if (ctype_digit($pledge_id)) {
                $this->update_categories($pledge_id);
            }
        } elseif (get_http_var('send_announce_token')) {
            $pledge_id = get_http_var('send_announce_token_pledge_id');
            if (ctype_digit($pledge_id)) {
                send_announce_token($pledge_id);
                # TRANS: This is an admin message, printed when someone has pressed the button to send an email to a pledge creator letting them send an announcement message. (Matthew Somerville,  http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000092.html)
                print p(_('<em>Announcement permission mail sent</em>'));
            }
        } elseif (get_http_var('edit_pledge_text')) {
            $pledge_id = get_http_var('edit_pledge_text_id');
            if (ctype_digit($pledge_id)) {
                $this->edit_pledge_text($pledge_id);
            }
        } elseif (get_http_var('edit_person')) {
            $person_id = get_http_var('edit_person_id');
            if (ctype_digit($person_id)) {
                $this->edit_person($person_id);
            }
        }

        // Display page
        if ($pledge_id) {
            $pledge = db_getOne('SELECT ref FROM pledges WHERE id = ?', $pledge_id);
        }
        if ($pledge) {
            $this->show_one_pledge($pledge);
        } elseif ($person_id) {
            $this->show_one_person($person_id);
        } elseif (get_http_var('people')) {
            $this->search_people();
        } else {
            $this->list_all_pledges();
        }
    }
}

class ADMIN_PAGE_PB_LATEST {
    function ADMIN_PAGE_PB_LATEST() {
        $this->id = 'pblatest';
        $this->navname = 'Timeline';

        if (get_http_var('daylimit')) {
            $this->daylimit = get_http_var('daylimit');
        } else {
            $this->daylimit = 7;
        }

        $this->ref = null;
        if ($ref = get_http_var('ref')) {
            $this->ref = db_getOne('select id from pledges where ref=?', $ref);
        }
        $this->ignore = null;
        if ($ignore = get_http_var('ignore')) {
            $this->ignore = db_getOne('select id from pledges where ref=?', $ignore);
        }
    }

    # pledges use creationtime
    # signers use signtime
    function show_latest_changes() {
        
        print admin_moderation_styles(); # hack
        $time = array();

        global $pb_time;
        $backto_unix = $pb_time - 60*60*24*$this->daylimit; 
        $backto_iso = strftime("%Y-%m-%d", $backto_unix);

        # Get all pledge ids to refs for pledge_link function
        $q = db_query("SELECT pledges.* FROM pledges ORDER BY pledges.id DESC");
        $this->pledgeref = array();
        while ($r = db_fetch_array($q)) {
            if (!$this->ref || $this->ref==$r['id']) {
                $this->pledgeref[$r['id']] = $r['ref'];
            }
        }
 
        $q = db_query("SELECT signers.name, signer_person.email,
                              signer_person.mobile as mobile, 
                              signer_person.facebook_id as facebook_id, 
                              signtime, showname, pledges.title,
                              pledges.ref, pledges.id,
                              extract(epoch from signtime) as epoch
                         FROM pledges, signers
                         LEFT JOIN person AS signer_person ON signer_person.id = signers.person_id
                        WHERE signers.pledge_id = pledges.id AND signtime >= '$backto_iso'
                     ORDER BY signtime DESC");
        while ($r = db_fetch_array($q)) {
            if (!$this->ref || $this->ref==$r['id']) {
                $signed[$r['id']][$r['email']] = 1;
                $time[$r['epoch']][] = $r;
            }
        }

        // Token display not so useful, and wastes too much space
        // (what would be useful is unused tokens)
        /*
        $q = db_query('SELECT *,extract(epoch from created) as epoch
                         FROM token
                     ORDER BY created DESC');
        while ($r = db_fetch_array($q)) {
            $stuff = $r['data'];
            $pos = 0;
            $res = rabx_wire_rd(&$stuff, &$pos);
            if (rabx_is_error($res)) {
                $r['error'] = 'RABX Error: ' . $res->text;
            }
            if ($r['scope'] == "login") {
                $stash_data = db_getRow('select * from requeststash where key = ?', $res['stash']);
                # TODO: Could extract data from post_data here for display if it were useful to do so
                $time[$r['epoch']][] = array_merge(array_merge($r, $res), $stash_data);
            } else {
                if (!isset($signed[$res['pledge_id']]) || 
                    !isset($res['email']) || 
                    !isset($signed[$res['pledge_id']][$res['email']])) {
                        $time[$r['epoch']][] = array_merge($r, $res);
                }
            }
        }
        */

       
        $q = db_query("SELECT pledges.*,extract(epoch from creationtime) as epoch, person.email as email
                         FROM pledges LEFT JOIN person ON person.id = pledges.person_id
                         WHERE creationtime >= '$backto_iso'
                     ORDER BY pledges.id DESC");
        while ($r = db_fetch_array($q)) {
            if (!$this->ref || $this->ref==$r['id']) {
                if (!get_http_var('onlysigners')) {
                    $time[$r['epoch']][] = $r;
                }
            }
        }
        if (!get_http_var('onlysigners')) {
            $q = db_query("SELECT *
                             FROM incomingsms WHERE whenreceived >= $backto_unix
                         ORDER BY whenreceived DESC");
            while ($r = db_fetch_array($q)) {
                $time[$r['whenreceived']][] = $r;
            }
            $q = db_query("SELECT *
                             FROM outgoingsms WHERE lastsendattempt >= $backto_unix
                         ORDER BY lastsendattempt DESC LIMIT 10");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref) {
                    $time[$r['lastsendattempt']][] = $r;
                }
            }
            $q = db_query("SELECT whencreated, circumstance, ref,extract(epoch from whencreated) as epoch, pledges.id
                             FROM message, pledges
                            WHERE message.pledge_id = pledges.id AND whencreated >= '$backto_iso'
                         ORDER BY whencreated DESC");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['id']) {
                    $time[$r['epoch']][] = $r;
                }
            }
            $q = db_query("SELECT comment.*, extract(epoch from whenposted) as commentposted,
                                  person.email as author_email
                             FROM comment
                             LEFT JOIN person ON person.id = comment.person_id
                             WHERE not ishidden AND whenposted >= '$backto_iso'
                         ORDER BY whenposted DESC");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['pledge_id']) {
                    $time[$r['commentposted']][] = $r;
                }
            }
            $q = db_query("SELECT location.description as alertdescription, 
                                    extract(epoch from whenqueued) as whenqueued,
                                  person.email as email, person.name as name,
                                  pledges.ref as ref, pledges.id as pledge_id
                             FROM alert_sent
                             LEFT JOIN alert ON alert.id = alert_sent.alert_id
                             LEFT JOIN person ON person.id = alert.person_id
                             LEFT JOIN pledges ON alert_sent.pledge_id = pledges.id
                             LEFT JOIN location ON alert.location_id = location.id
                             WHERE event_code = 'pledges/local'
                             AND whenqueued >= '$backto_iso'
                         ORDER BY whenqueued DESC");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['pledge_id']) {
                    $time[$r['whenqueued']][] = $r;
                }
            }
        }

        if (OPTION_MODERATE_PLEDGES) {
            # Pledge Moderations
            $q = db_query("SELECT 
                            pledges.*,
                            extract(epoch from pledges.moderated_time) as epoch,
                            moderator.name as moderator_name,
                            moderator.email as moderator_name,
                            creator.name as creator_name,
                            creator.email as creator_email
                           FROM pledges
                           JOIN person moderator ON pledges.moderated_by = moderator.id
                           JOIN person creator ON pledges.person_id = creator.id

                           WHERE moderated_time >= '$backto_iso'
                         ORDER BY moderated_time DESC");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['id']) {
                    $time[$r['epoch']][] = $r;
                }
            }

            # Comment Moderations
            $q = db_query("SELECT 
                            comment.*,
                            pledges.id as pledge_id,
                            pledges.ref as ref,
                            pledges.title as title,
                            extract(epoch from comment.moderated_time) as epoch,
                            moderator.name as moderator_name,
                            moderator.email as moderator_name,
                            commenter.name as commenter_name,
                            commenter.email as commenter_email
                           FROM pledges
                           JOIN comment ON comment.pledge_id = pledges.id
                           JOIN person moderator ON comment.moderated_by = moderator.id
                           JOIN person commenter ON comment.person_id = commenter.id

                           WHERE comment.moderated_time >= '$backto_iso'
                         ORDER BY comment.moderated_time DESC");
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['pledge_id']) {
                    $time[$r['epoch']][] = $r;
                }
            }
        }

        krsort($time);

        print '<a href="'.$this->self_link.'">Full log</a>';
        if ($this->ref) {
            print ' | <em>Viewing only pledge "'.$this->pledgeref[$this->ref].'"</em> (<a href="?page=pb&amp;pledge='.$this->pledgeref[$this->ref].'">admin</a>)';
        } elseif ($this->ignore) {
            print ' | <em>Ignoring pledge "'.$this->pledgeref[$this->ignore].'"</em> (<a href="?page=pb&amp;pledge='.$this->pledgeref[$this->ignore].'">admin</a>)';
        } else {
            print ' | <a href="'.$this->self_link.'&amp;onlysigners=1">Only signatures</a>';
        }
        $date = ''; 
        $linecount = 0;
        print "<div class=\"timeline\">";
        foreach ($time as $epoch => $datas) {
            $curdate = date('l, jS F Y', $epoch);
            if ($date != $curdate) {
                if ($date <> "")
                    print '</dl>';
                print '<h2>'. $curdate . '</h2> <dl>';
                $date = $curdate;
            }
            print '<dt><b>' . date('H:i:s', $epoch) . ':</b></dt> <dd>';

            # Iterate every type of timeline event.
            foreach ($datas as $data) {

            ## Signatures 
            if (array_key_exists('signtime', $data)) {
                print $this->pledge_link('ref', $data['ref']);
                if ($data['showname'] == 'f')
                    print ' anonymously';
                print ' signed by ';
                print $data['name'];
                if ($data['email']) print ' &lt;'.htmlspecialchars($data['email']).'&gt;';
                if ($data['mobile']) print ' (' . htmlspecialchars($data['mobile']) . ')';
                if ($data['facebook_id']) print ' ' . facebook_display_name($data['facebook_id']);

            ## Comment Moderation
            } elseif (array_key_exists('commenter_name', $data)) {

                $bad = ($data['ishidden'] == 't'); # silly PHP retrieves false as 'f', which is true.

                printf("<span class='%s'>Comment</span> ",
                    $bad ? 'moderated_bad' : 'moderated_good');
                printf("'%s'", htmlspecialchars($data['text']));
                printf(" by %s &lt;%s&gt;",
                    htmlspecialchars($data['commenter_name']),
                    $data['commenter_email']);
                print " on ";
                print $this->pledge_link('ref', $data['ref'], $data['title']);
                printf('<br />Moderated %s by %s %s at %s',
                    $bad ? 'bad' : 'good',
                    $data['moderator_name'],
                    htmlspecialchars(sprintf('<%s>', $data['moderator_name'])),
                    $data['moderated_time']
                );
            ## Pledge Moderation
            } elseif (array_key_exists('moderator_name', $data)) {

                $bad = ($data['ishidden'] == 't'); # silly PHP retrieves false as 'f', which is true.

                printf("<span class='%s'> Pledge $data[id]</span>, ref <em>$data[ref]</em>, ",
                    $bad ? 'moderated_bad' : 'moderated_good');
                print $this->pledge_link('ref', $data['ref'], $data['title']);
                print " by ".htmlspecialchars($data['creator_name']);
                print " &lt;".htmlspecialchars($data['creator_email'])."&gt;";

                printf('<br />Moderated %s by %s %s at %s',
                    $bad ? 'bad' : 'good',
                    $data['moderator_name'],
                    htmlspecialchars(sprintf('<%s>', $data['moderator_name'])),
                    $data['moderated_time']
                );
            } elseif (array_key_exists('creationtime', $data)) {
                print "Pledge $data[id], ref <em>$data[ref]</em>, ";
                print $this->pledge_link('ref', $data['ref'], $data['title']) . ' created (confirmed)';
                print " by ".htmlspecialchars($data['name'])." &lt;".htmlspecialchars($data['email'])."&gt;";
            } elseif (array_key_exists('whenreceived', $data)) {
                print "Incoming SMS from ".htmlspecialchars($data['sender'])." received, sent
                $data[whensent], message ".htmlspecialchars($data['message'])."
                (".htmlspecialchars($data['foreignid'])." ".htmlspecialchars($data['network']);
            } elseif (array_key_exists('whencreated', $data)) {
                print "Message $data[circumstance] queued for pledge " .
                $this->pledge_link('ref', $data['ref']);
            } elseif (array_key_exists('created', $data)) {
                if (array_key_exists('error', $data)) {
                    print '<em>' . $data['error'] . '</em><br>';
                }
                print "$data[scope] token $data[token] created ";
                if (array_key_exists('email', $data)) {
                    print "for ".htmlspecialchars($data['name'])." ".htmlspecialchars($data['email'])." ";
                    if (array_key_exists('pledge_id', $data)) {
                        print " pledge " . $this->pledge_link('id', $data['pledge_id']);
                    }
                } elseif (array_key_exists('circumstance', $res)) {
                    print "for pledge " . $this->pledge_link('id', $res['pledge_id']);
                }
                if ($data['scope'] == "login") {
                    if (!array_key_exists('method', $data)) {
                        print "<em>Stash expired</em>";
                    } else {
                        print " " . $data['method'] . " to " . $data['url'];
                    }
                }
            } elseif (array_key_exists('lastsendattempt', $data)) {
                if ($data['ispremium'] == 't') 
                    print 'Premium ';
                print "SMS sent to ".htmlspecialchars($data['recipient']).", message
                    '".htmlspecialchars($data['message'])."' status $data[lastsendstatus]";
            } elseif (array_key_exists('commentposted', $data)) {
                $comment_email = $data['email'];
                if (!$comment_email)
                    $comment_email = $data['author_email'];
                print htmlspecialchars($data['name'])." &lt;".htmlspecialchars($comment_email)."&gt; commented on " .
                    $this->pledge_link('id', $data['pledge_id']) . " saying
                '".htmlspecialchars($data['text'])."'";
            } elseif (array_key_exists('whenqueued', $data)) {
                print "Local alert to ". htmlspecialchars($data['email']) .
                  " " . htmlspecialchars($data['alertdescription']) . " " .
                  " for pledge " . $this->pledge_link('id', $data['pledge_id']);
            } else {
                print_r($data);
            }
            print '<br>';
            }
            print "</dd>\n";
        }
        if (!$date)
            print "<dl>";
        print "<dt>";
        if (count($time) < 1) {
            print(_('No events have happened yet.') . " ");
        }
        print '<br><a href="'.$this->self_link.
                '&amp;daylimit='.htmlspecialchars($this->daylimit + 7).'">Expand timeline by a week...</a> (currently '.htmlspecialchars($this->daylimit).' days)';
        print '</dt></dl>';
        print "</div>";
    }

    function pledge_link($type, $data, $title='') {
        if ($type == 'id') {
            if (!array_key_exists($data, $this->pledgeref)) {
                return "DELETED";
            }
            $ref = $this->pledgeref[$data];
        }
        else 
            $ref = $data;
        if (!$title) 
            $title = $ref;
        $str = '<a href="' . OPTION_BASE_URL . '/' . $ref . '">' .
            htmlspecialchars($title) . '</a>';
        if (!$this->ref)
            $str .= ' (<a href="?page=pb&amp;pledge='.$ref.'">admin</a>' .  ' | ' . ' <a href="?page=pblatest&amp;ref='.$ref.'">timeline</a>'. ')';
        return $str;
    }

    function display($self_link) {
        db_connect();        
        $this->show_latest_changes();
    }
}

class ADMIN_PAGE_PB_STATS {
    function ADMIN_PAGE_PB_STATS() {
        $this->id = 'pbstats';
        $this->navname = _('Statistics');
    }

    function display($self_link) {
        db_connect();

        print h2(_("Local alert summary"));
        $r = db_getRow('select 
            count(case when whendisabled is null then 1 else null end) as active, 
            count(whendisabled) as disabled
            from alert
            where event_code = \'pledges/local\'');
        print p("Total subscribers: " . $r['active'] . " Unsubscribed: " . $r['disabled']);

        print h2(_("Alerts followed by signatures"));
        $q = db_query('
            select alert.location_id, ref, signers.name, person.email,
                date_trunc(\'second\', whenqueued) as whenqueued, 
                date_trunc(\'second\', signtime) as signtime,
                date_trunc(\'second\', signtime - whenqueued) as timegap
            from alert, alert_sent, signers, pledges, person
            where 
                alert.id = alert_sent.alert_id
                and event_code = \'pledges/local\'
                and signers.pledge_id = alert_sent.pledge_id
                and signers.person_id = alert.person_id
                and pledges.id = alert_sent.pledge_id
                and signers.person_id = person.id
                order by ref, signtime
        ');
        print p(sprintf(_("%d cases where somebody signed a pledge they had previously been alerted about. Time gap between alert and signing is displayed for each case."), db_num_rows($q)));
        $last_ref = '';
        while ($r = db_fetch_array($q)) {
            if ($r['ref'] != $last_ref) {
                if ($last_ref != "")
                    print "<br>";
                print "<strong>" . $r['ref'] . "</strong>";
                print " (<a href=\"?page=pblatest&ref=" . $r['ref'] . "\">timeline</a>)";
                print ": ";
            }
            print htmlspecialchars($r['email']) . " " . prettify($r['timegap']) . ', ';
            $last_ref = $r['ref'];
        }
        print '</table>';

        print h2(_("Local alerts by country"));
        $q = db_query('select country, state,
            count(case when whendisabled is null then 1 else null end) as active, 
            count(whendisabled) as disabled, 
            date(min(whensubscribed)) as t1, date(max(whensubscribed)) as t2,
            date(min(whendisabled)) as d1, date(max(whendisabled)) as d2
            from alert 
                left join location on location.id = alert.location_id 
            where event_code = \'pledges/local\' 
            group by country, state
            order by country, state
            ');

        print '<table border="1" cellpadding="3" cellspacing="0">';
        print '<tr><th>Country</th> <th>State</th>
            <th>Signups<br>(still active)</th>
            <th>Signups<br>(now unsubscribed)</th>
            <th>Signup date range</th>
            <th>Unsubscribe date range</th>
            </tr>';
        $n = 0;
        $us_active = 0; $us_disabled = 0; $us_started = false;
        while ($r = db_fetch_array($q)) {
            if ($r['country'] == 'US') {
                $us_active += $r['active'];
                $us_disabled += $r['disabled'];
                $us_started = true;
            }
            if ($r['country'] != 'US' && $us_started) {
                if ($n++%2)
                    print '<tr>';
                else 
                    print '<tr class="v">';
                print '<td>US</td>';
                print '<td>Total</td>';
                print "<td>$us_active</td> <td>$us_disabled</td>";
                print "</tr>\n";
                $us_started = false;
            }
            if ($n++%2)
                print '<tr>';
            else 
                print '<tr class="v">';
            print '<td>'.htmlspecialchars($r['country']) . '</td>';
            print '<td>'.htmlspecialchars($r['state']) . '</td>';
            print '<td>'.htmlspecialchars($r['active']) . '</td>';
            print '<td>'.htmlspecialchars($r['disabled']) . '</td>';
            print '<td>'.htmlspecialchars($r['t1']) . ' to '. htmlspecialchars($r['t2']) . '</td>';
            if (!$r['d1'] && !$r['d2']) {
                print '<td>n/a</td>';
            } else {
                print '<td>'.htmlspecialchars($r['d1']) . ' to '. htmlspecialchars($r['d2']) . '</td>';
            }
            print "</tr>\n";
        }
        print '</table>';

        print h2(_("Local alert signups"));
        $q = db_query('select date(whensubscribed) as date, 
                count(case when whendisabled is null then 1 else null end) as active, 
                count(whendisabled) as disabled
            from alert 
                left join location on location.id = alert.location_id 
            where event_code = \'pledges/local\' 
            group by date(whensubscribed)
            order by date(whensubscribed) desc
            ');

        print '<table border="1" cellpadding="3" cellspacing="0">';
        print '<tr><th>Day</th><th>Signups<br>(still active)</th><th>Signups<br>(now unsubscribed)</th></tr>';
        $n = 0;
        while ($r = db_fetch_array($q)) {
            if ($n++%2)
                print '<tr>';
            else 
                print '<tr class="v">';
            print '<td>'.htmlspecialchars($r['date']) . '</td>';
            print '<td>'.htmlspecialchars($r['active']) . '</td>';
            print '<td>'.htmlspecialchars($r['disabled']) . '</td>';
            print "</tr>\n";
        }
        print '</table>';
    }
}

function admin_moderation_styles () {
    # it seems currently hard to put this anywhere sane (requires editing the header
    # in commonlib/phplib, so generating a snippet here, to be refactored later.)
    return 
        '<style>
            .moderated_good { background-color: #aaffaa; }
            .moderated_bad { background-color: #ffaaaa; }
        </style>';
}

?>
