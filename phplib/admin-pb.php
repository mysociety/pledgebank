<?php
/*
 * admin-pb.php:
 * PledgeBank admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pb.php,v 1.155 2007-10-01 15:55:01 francis Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/pledge.php";
require_once "../phplib/comments.php";
require_once '../phplib/pbfacebook.php';
require_once "../../phplib/db.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";
require_once "../../phplib/gaze.php";

function facebook_display_name($facebook_id) {
    global $facebook;
    if (!$facebook)
        pbfacebook_init_cron(OPTION_FACEBOOK_ROBOT_ID);
    $facebook_name = pbfacebook_get_user_name($facebook_id);
    if ($facebook_name) {
        $facebook_name = "<Unknown>"; # due to Facebook privacy search settings hiding name from non-friends
    }
    return '<a href="http://www.facebook.com/profile.php?id='.$facebook_id.'">'.htmlspecialchars($facebook_name).'</a> (Facebook)';
}

class ADMIN_PAGE_PB_SUMMARY {
    function ADMIN_PAGE_PB_SUMMARY() {
        $this->id = 'summary';
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
        
        print "Pledges: $pledges<br>$nonbackpage non-backpaged<br>$successful successful, $failed failed, $open open<br>$signatures signatures, $signers signers<br>$local non-global";
    }
}

class ADMIN_PAGE_PB_MAIN {
    function ADMIN_PAGE_PB_MAIN () {
        $this->id = "pb";
        $this->navname = _("Pledges and Signers");
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
            $openness_condition = "'$pb_today' <= date";
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
                $row .= $microsites_list[$r['microsite']] . '<br>';
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
        if ($openness == 'closed') {
            print '<a href="?page=pb">';
            print _('All Open Pledges');
            print '</a>';
            print " | ";
            print _('All Closed Pledges');
            print " (" . count($found) . ")";
            $openness_url = "&amp;o=closed";
         } else {
            print _('All Open Pledges');
            print " (" . count($found) . ")";
            print " | ";
            print '<a href="?page=pb&amp;o=closed">';
            print _('All Closed Pledges');
            print '</a>';
        }
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

    function show_one_pledge($pledge) {
        print '<p><a href="'.$this->self_link.'">' . _('List of all pledges') . '</a></p>';

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
                (SELECT count(*) FROM comment WHERE pledge_id=pledges.id AND NOT ishidden) AS comments
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

        print "<p>Set by: <b>" . htmlspecialchars($pdata['name']) . " &lt;" .  htmlspecialchars($pdata['email']) . "&gt;</b>";
        print "<br>Created: <b>" . prettify($pdata['creationtime']) . "</b>";
        print "<br>Deadline: <b>" . prettify($pdata['date']) . "</b>";
        print "<br>Target: <b>" . $pdata['target'] . " " .  htmlspecialchars($pdata['type']) . "</b>";
        if ($pdata['target_type'] == "byarea") 
            print " (target is byarea)";

        // Microsite
        global $microsites_list;
        print '<form name="micrositeform" method="post" action="'.$this->self_link.'">';
        print '<input type="hidden" name="update_microsite" value="1">';
        print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
        print _('Microsite:') . ' ';
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

        global $langs;
        print '<form name="languageform" method="post" action="'.$this->self_link.'">';
        print '<input type="hidden" name="update_language" value="1">';
        print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
        print _('Language:') . ' ';
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

        if (array_key_exists('country', $pdata)) {
            print '<form name="countryform" method="post" action="'.$this->self_link.'">';
            print '<input type="hidden" name="update_country" value="1">';
            print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
            print _('Country:') . ' ';
            gaze_controls_print_country_choice($pdata['country'], $pdata['state'], array(), array());
            print '<input name="update" type="submit" value="Update">';
            if (array_key_exists('description', $pdata) && $pdata['description'])
                print '<br>Place: <b>' . $pdata['description'].'</b>';
            if ($pdata['longitude']) {
                $coords = round($pdata['longitude'],2).'E ' . round($pdata['latitude'],2).'N';
                print " Longitude/Latitude WGS84: <b>$coords</b> ";
                print '<a href="'.htmlspecialchars($pledge_obj->url_place_map()).'">(google maps)</a>';
            }
            print '</form>';
        }

        // Tags
        print _('Tags:') . " ";
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
        print "<br>";

        // Prominence
        print '<form name="prominenceform" method="post" action="'.$this->self_link.'">';
        print '<input type="hidden" name="update_prom" value="1">';
        print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
        print 'Prominence: ';
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

        print 'Comments: <strong>' . $pdata['comments']. '</strong>';

        if (get_http_var("edit")) {
            print '<h2>Edit pledge text</h2>';
            print '<form name="editform" method="post" action="'.$this->self_link.'">';
            print 'I will <input type="text" name="title" value="'.htmlspecialchars($pdata['title']).'" size="60">';
            print '<br>but only if ' . $pdata['target']. ' <input type="text" name="type" value="'.htmlspecialchars($pdata['type']).'" size="40">';
            print '<br>will <input type="text" name="signup" value="'.htmlspecialchars($pdata['signup']).'" size="60">';
            print '<br>&mdash;<input type="text" name="name" value="'.htmlspecialchars($pdata['name']).'" size="20">, ';
            print '<input type="text" name="identity" value="'.htmlspecialchars($pdata['identity']).'" size="30">';
            print '<br>More details: <textarea type="text" name="detail" cols="70" rows="7">'.htmlspecialchars($pdata['detail']).'</textarea>';
            #cancelled
            #notice
            print '<input type="hidden" name="edit_pledge_text_id" value="' . $pdata['id'] . '">';
            print '<input type="hidden" name="edit_pledge_text" value="1">';
            print '<input type="hidden" name="edit" value="1">';
            print '<br><input type="submit" name="edit_pledge" value="Save updates"> ';
            print ' <a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp">Cancel edit</a>';
            print "</form>";
        } else {
            print '<p><a href="?page=pb&amp;pledge='.$pdata['ref'].'&amp;edit=1">Edit pledge text</a></p>';
        }

        // Signers
        print "<h2>Signers (".$pdata['signers']."/".$pdata['target'].")</h2>";
        $query = 'SELECT signers.name as signname,person.email as signemail,
                         person.mobile as signmobile, person.facebook_id as signfacebook_id,
                         date_trunc(\'second\',signtime) AS signtime,
                         showname, signers.id AS signid,
                         location.description AS location_description
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
                array_push($e, $r['signname']);
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
                print ' (do not press if you are Tom, it will crash your computer :)</p>';
            }
        } else {
            print '<p>Nobody has signed up to this pledge.</p>';
        }
        print '<p>';
        
        // Messages
        print h2(_("Messages"));
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

print '<form name="removepledgepermanentlyform" method="post" action="'.$this->self_link.'" style="clear:both"><strong>Caution!</strong> This really is forever, you probably don\'t want to do it: <input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';

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
        db_query('UPDATE comment set ishidden = ? where id = ?', 
            array(get_http_var('deletecomment_status') ? true : false, $id));
        db_commit();
        print p(_('<em>That comment has been shown/hidden</em>'));
    }

    function update_prominence($pledge_id) {
        db_query('UPDATE pledges set prominence = ? where id = ?', array(get_http_var('prominence'), $pledge_id));
        db_commit();
        print p(_("<em>Change to pledge prominence saved</em>"));
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
        $title = get_http_var('title');
        $type = get_http_var('type');
        $signup = get_http_var('signup');
        $name = get_http_var('name');
        $identity = get_http_var('identity');
        $detail = get_http_var('detail');
        db_query('update pledges set title = ?, type = ?, signup = ?, name = ?, identity = ?, detail = ? where id = ?', $title, $type, $signup, $name, $identity, $detail, $pledge_id);
        db_commit();
        print p(_('<em>Pledge text updated. Check it in the pledge box preview on the right.</em>'));
    }

    function display($self_link) {
        db_connect();

        $pledge = get_http_var('pledge');
        $pledge_id = null;

        // Perform actions
        if (get_http_var('update_prom')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_prominence($pledge_id);
        } elseif (get_http_var('update_microsite')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_microsite($pledge_id);
        } elseif (get_http_var('update_country')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_country($pledge_id);
        } elseif (get_http_var('update_language')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_language($pledge_id);
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
        }

        // Display page
        if ($pledge_id) {
            $pledge = db_getOne('SELECT ref FROM pledges WHERE id = ?', $pledge_id);
        }
        if ($pledge) {
            $this->show_one_pledge($pledge);
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
            foreach ($datas as $data) {
            if (array_key_exists('signtime', $data)) {
                print $this->pledge_link('ref', $data['ref']);
                if ($data['showname'] == 'f')
                    print ' anonymously';
                print ' signed by ';
                print $data['name'];
                if ($data['email']) print ' &lt;'.htmlspecialchars($data['email']).'&gt;';
                if ($data['mobile']) print ' (' . htmlspecialchars($data['mobile']) . ')';
                if ($data['facebook_id']) print ' ' . facebook_display_name($data['facebook_id']);
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

?>
