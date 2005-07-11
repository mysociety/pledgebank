<?php
/*
 * admin-pb.php:
 * PledgeBank admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pb.php,v 1.83 2005-07-11 15:02:19 francis Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/pledge.php";
require_once "../phplib/comments.php";
require_once "fns.php";
require_once "../../phplib/db.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";

class ADMIN_PAGE_PB_MAIN {
    function ADMIN_PAGE_PB_MAIN () {
        $this->id = "pb";
        $this->navname = _("Pledges and Signers");
    }

    function pledge_header($sort) {
        print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
        $cols = array(
            'r'=>'Ref', 
            'a'=>'Title', 
            't'=>'Target', 
            's'=>'Signers', 
            'd'=>'Deadline', 
            'p'=>'Prominence', 
            'e'=>'Creator', 
            'c'=>'Creation Time', 
            'u'=>'Success Time',
            'o'=>'% complete',
        );
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s) print '<a href="'.$this->self_link.'&amp;s='.$s.'">';
            print $col;
            if ($sort != $s) print '</a>';
            print '</th>';
        }
        print '</tr>';
        print "\n";
    }

    function list_all_pledges() {
        global $open;
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^ratdecspuo]/', $sort)) $sort = 'c';
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

        $q = db_query("
            SELECT pledges.*, person.email,
                date_trunc('second',whensucceeded) as whensucceeded, 
                date_trunc('second',creationtime) AS creationtime, 
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                pb_current_date() <= date AS open
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id" .
            ($order ? ' ORDER BY ' . $order : '') );
        $open = array();
        $closed = array();
        while ($r = db_fetch_array($q)) {
            $r = array_map('htmlspecialchars', $r);
            $row = "";

            $row .= '<td><a href="'.OPTION_BASE_URL . "/" . $r['ref'] .'">'.$r['ref'].'</a>'.
                '<br><a href="'.$this->self_link.'&amp;pledge='.$r['ref'].'">admin</a> |
                <a href="?page=pblatest&amp;ref='.$r['ref'].'">timeline</a>';
            $row .= '</td>';
            $row .= '<td>'.$r['title'].'</td>';
            $row .= '<td>'.$r['target'].' '.$r['type'].'</td>';
            $row .= '<td>'.$r['signers'].'</td>';
            $row .= '<td>'.prettify($r['date']).'</td>';

            $row .= '<td>'.$r['prominence'];
            if ($r['pin']) 
                $row .= '<br><b>private</b> ';
            $row .= '</td>';

            $row .= '<td>'.$r['name'].'<br>'.str_replace('@','@ ',$r['email']).'</td>';
            $row .= '<td>'.prettify($r['creationtime']).'</td>';
            if ($r['whensucceeded']) 
                $row .= '<td>'.prettify($r['whensucceeded']).'</td>';
            else
                $row .= '<td>None</td>';

            $row .= '<td>' . str_replace('.00', '', number_format($r['signers']/$r['target']*100,2)) . '%</td>';

            if ($r['open'] == 't')
                $open[] = $row;
            else
                $closed[] = $row;
        }
        if ($sort=='o') {
            function sort_by_percent($a, $b) {
                global $open;
                preg_match('#<td>([\d\.,]+)%</td>#', $open[$a], $m); $aa = str_replace(',','',$m[1]);
                preg_match('#<td>([\d\.,]+)%</td>#', $open[$b], $m); $bb = str_replace(',','',$m[1]);
                if ($aa==$bb) return 0;
                return ($aa<$bb) ? 1 : -1;
            }
            uksort($open, 'sort_by_percent');
        }
         
        if (count($open)) {
            print h2(_("All Open Pledges"));
            $this->pledge_header($sort);
            $a = 0;
            foreach ($open as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>'."\n";
            }
            print '</table>';
        }
        if (count($closed)) {
            print h2(_("All Closed Pledges"));
            $this->pledge_header($sort);
            $a = 0;
            foreach ($closed as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>'."\n";
            }
            print '</table>';
        }
        print '<p>';
    }

    function show_one_pledge($pledge) {
        print '<p><a href="'.$this->self_link.'">' . _('List of all pledges') . '</a></p>';

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etcn]/', $sort)) $sort = 'e';

        $q = db_query('SELECT pledges.*, person.email FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id WHERE ref ILIKE ?', $pledge);
        $pdata = db_fetch_array($q);

        print "<h2>Pledge '<a href=\"".OPTION_BASE_URL.'/'.$pdata['ref']."\">" . $pdata['ref'] . "</a>'";
        print ' (<a href="?page=pblatest&amp;ref='.$pdata['ref'].'">' . _('timeline') . '</a>)';
        print " &mdash; " .  $pdata['title'] . "</h2>";

        print "<p>Set by: <b>" . $pdata['name'] . " &lt;" .  $pdata['email'] . "&gt;</b>";
        print "<br>Created: <b>" . prettify($pdata['creationtime']) . "</b>";
        print "<br>Deadline: <b>" . prettify($pdata['date']) . "</b>";
        print " Target: <b>" . $pdata['target'] . " " .  $pdata['type'] . "</b>";
        print '<br>Country: <b>' . $pdata['country'] . "</b>";
        if ($pdata['postcode'])
            print ' Postcode: <b>' . $pdata['postcode'].'</b>';
        if ($pdata['longitude'])
            print ' Longitude/Latitude WGS84: <b>' . round($pdata['longitude'],2).'E ' . round($pdata['latitude'],2).'N</b>';
        print "</p>";

        // Prominence
        print '<form name="prominenceform" method="post" action="'.$this->self_link.'">';
        print '<input type="hidden" name="update_prom" value="1">';
        print '<input type="hidden" name="pledge_id" value="'.$pdata['id'].'">';
        print 'Prominence: ';
        if ($pdata['pin'])
            print "<b>private</b> ";
        print '<select name="prominence">';
        print '<option value="normal"' . ($pdata['prominence']=='normal'?' selected':'') . '>normal</option>';
        print '<option value="frontpage"' . ($pdata['prominence']=='frontpage'?' selected':'') . '>frontpage</option>';
        print '<option value="backpage"' . ($pdata['prominence']=='backpage'?' selected':'') . '>backpage</option>';
        print '</select>';
        print '<input name="update" type="submit" value="Update"></form>';

        // Signers
        print "<h2>Signers</h2>";
        $query = 'SELECT signers.name as signname,person.email as signemail,
                         signers.mobile as signmobile,
                         date_trunc(\'second\',signtime) AS signtime,
                         showname, signers.id AS signid 
                   FROM signers 
                   LEFT JOIN person ON person.id = signers.person_id
                   WHERE pledge_id=?';
        if ($sort=='t') $query .= ' ORDER BY signtime DESC';
        elseif ($sort=='n') $query .= ' ORDER BY showname DESC';
        $q = db_query($query, $pdata['id']);
        $out = array();
        while ($r = db_fetch_array($q)) {
            $r = array_map('htmlspecialchars', $r);
            $e = array();
            if ($r['signname'])
                array_push($e, $r['signname']);
            if ($r['signemail'])
                array_push($e, $r['signemail']);
            if ($r['signmobile'])
                array_push($e, $r['signmobile']);
            $e = join("<br>", $e);
            $out[$e] = '<td>'.$e.'</td>';
            $out[$e] .= '<td>'.prettify($r['signtime']).'</td>';

            $out[$e] .= '<td><form name="shownameform" method="post" action="'.$this->self_link.'"><input type="hidden" name="showname_signer_id" value="' . $r['signid'] . '">';
            $out[$e] .= '<select name="showname">';
            $out[$e] .=  '<option value="1"' . ($r['showname'] == 't'?' selected':'') . '>Yes</option>';
            $out[$e] .=  '<option value="0"' . ($r['showname'] == 'f'?' selected':'') . '>No</option>';
            $out[$e] .=  '</select>';
            $out[$e] .= '<input type="submit" name="showname_signer" value="update">';
            $out[$e] .= '</form></td>';

            $out[$e] .= '<td>';
            $out[$e] .= '<form name="removesignerform" method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer permanently"></form>';
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
        } else {
            print '<p>Nobody has signed up to this pledge.</p>';
        }
        print '<p>';
        
        // Messages
        print h2(_("Messages"));
        $q = db_query('select * from message 
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
            print " to be sent from <strong>" . $r['fromaddress'] . "</strong> to <strong>";
            print join(", ", $whom) . "</strong>";
            print "<br>has been queued to evel for ";
            print "<strong>$got_creator_count creators</strong>";
            print " and <strong>$got_signer_count signers</strong>";
            if ($r['sms'])
                print "<br><strong>sms content:</strong> " . $r['sms'];
            if ($r['emailtemplatename'])
                print "<br><strong>email template:</strong> " . $r['emailtemplatename'];
            if ($r['emailsubject'])
                print "<br><strong>email subject:</strong> " . $r['emailsubject'];
            if ($r['emailbody']) {
                ?><br><strong>email body:</strong>
                <div class="message"><?= comments_text_to_html($r['emailbody']) ?></div> <?
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

        print '<h2>Actions</h2>';
        print '<form name="sendannounceform" method="post" action="'.$this->self_link.'"><input type="hidden" name="send_announce_token_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="send_announce_token" value="Send announce URL to creator"></form>';

print '<form name="removepledgepermanentlyform" method="post" action="'.$this->self_link.'"><strong>Caution!</strong> This really is forever, you probably don\'t want to do it: <input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';

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
        print p(_('<em>Show name for signer updated</em>'));
    }

    function update_prominence($pledge_id) {
        db_query('UPDATE pledges set prominence = ? where id = ?', array(get_http_var('prominence'), $pledge_id));
        db_commit();
        print p(_("<em>Changes to pledge prominence saved</em>"));
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

    function display($self_link) {
        db_connect();

        $pledge = get_http_var('pledge');
        $pledge_id = null;

        // Perform actions
        if (get_http_var('update_prom')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_prominence($pledge_id);
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
         } elseif (get_http_var('update_cats')) {
            $pledge_id = get_http_var('pledge_id');
            $this->update_categories($pledge_id);
        } elseif (get_http_var('send_announce_token')) {
            $pledge_id = get_http_var('send_announce_token_pledge_id');
            if (ctype_digit($pledge_id)) {
                send_announce_token($pledge_id);
                print p(_('<em>Announcement permission mail sent</em>'));
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

        if (get_http_var('linelimit')) {
            $this->linelimit = get_http_var('linelimit');
        } else {
            $this->linelimit = 250;
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
        $q = db_query('SELECT signers.name, signer_person.email,
                              signers.mobile, signtime, showname, pledges.title,
                              pledges.ref, pledges.id,
                              extract(epoch from signtime) as epoch
                         FROM pledges, signers
                         LEFT JOIN person AS signer_person ON signer_person.id = signers.person_id
                        WHERE signers.pledge_id = pledges.id
                     ORDER BY signtime DESC');
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
    
        $q = db_query('SELECT pledges.*,extract(epoch from creationtime) as epoch, person.email as email
                         FROM pledges LEFT JOIN person ON person.id = pledges.person_id
                     ORDER BY pledges.id DESC');
        $this->pledgeref = array();
        while ($r = db_fetch_array($q)) {
            if (!$this->ref || $this->ref==$r['id']) {
                if (!get_http_var('onlysigners')) {
                    $time[$r['epoch']][] = $r;
                }
                $this->pledgeref[$r['id']] = $r['ref'];
            }
        }
        if (!get_http_var('onlysigners')) {
            $q = db_query('SELECT *
                             FROM incomingsms
                         ORDER BY whenreceived DESC');
            while ($r = db_fetch_array($q)) {
                $time[$r['whenreceived']][] = $r;
            }
            $q = db_query('SELECT *
                             FROM outgoingsms
                         ORDER BY lastsendattempt DESC LIMIT 10');
            while ($r = db_fetch_array($q)) {
                if (!$this->ref) {
                    $time[$r['lastsendattempt']][] = $r;
                }
            }
            $q = db_query('SELECT whencreated, circumstance, ref,extract(epoch from whencreated) as epoch, pledges.id
                             FROM message, pledges
                            WHERE message.pledge_id = pledges.id
                         ORDER BY whencreated DESC');
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['id']) {
                    $time[$r['epoch']][] = $r;
                }
            }
            $q = db_query('SELECT comment.*, extract(epoch from whenposted) as commentposted,
                                  person.email as author_email
                             FROM comment
                             LEFT JOIN person ON person.id = comment.person_id
                         ORDER BY whenposted DESC');
            while ($r = db_fetch_array($q)) {
                if (!$this->ref || $this->ref==$r['pledge_id']) {
                    $time[$r['commentposted']][] = $r;
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
            $linecount++;
            if ($linecount > $this->linelimit) {
                print '<dt><br><a href="'.$this->self_link.
                        '&linelimit='.htmlspecialchars($this->linelimit + 250).'">Expand timeline...</a></dt>';
                break;
            }
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
                if ($data['email']) print ' &lt;'.$data['email'].'&gt;';
                if ($data['mobile']) print ' (' . $data['mobile'] . ')';
            } elseif (array_key_exists('creationtime', $data)) {
                print "Pledge $data[id], ref <em>$data[ref]</em>, ";
                print $this->pledge_link('ref', $data['ref'], $data['title']) . ' created (confirmed)';
                print " by $data[name] &lt;$data[email]&gt;";
            } elseif (array_key_exists('whenreceived', $data)) {
                print "Incoming SMS from $data[sender] received, sent
                $data[whensent], message $data[message]
                ($data[foreignid] $data[network])";
            } elseif (array_key_exists('whencreated', $data)) {
                print "Message $data[circumstance] queued for pledge " .
                $this->pledge_link('ref', $data['ref']);
            } elseif (array_key_exists('created', $data)) {
                if (array_key_exists('error', $data)) {
                    print '<em>' . $data['error'] . '</em><br>';
                }
                print "$data[scope] token $data[token] created ";
                if (array_key_exists('email', $data)) {
                    print "for $data[name] $data[email] ";
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
                print "SMS sent to $data[recipient], message
                    '$data[message]' status $data[lastsendstatus]";
            } elseif (array_key_exists('commentposted', $data)) {
                $comment_email = $data['email'];
                if (!$comment_email)
                    $comment_email = $data['author_email'];
                print "$data[name] &lt;$comment_email&gt; commented on " .
                    $this->pledge_link('id', $data['pledge_id']) . " saying
                '$data[text]'";
            } else {
                print_r($data);
            }
            print '<br>';
            }
            print "</dd>\n";
        }
        print '</dl>';
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

class ADMIN_PAGE_PB_ABUSEREPORTS {
    function ADMIN_PAGE_PB_ABUSEREPORTS() {
        $this->id = 'pbabusereports';
        $this->navname = _('Abuse reports');
    }

    function display($self_link) {
        db_connect();

        if (array_key_exists('prev_url', $_POST)) {
            $do_discard = false;
            if (get_http_var('discardReports'))
                $do_discard = true;
            foreach ($_POST as $k => $v) {
                if ($do_discard && preg_match('/^ar_([1-9]\d*)$/', $k, $a))
                    db_query('delete from abusereport where id = ?', $a[1]);
                // Don't think delete pledge is safe as a button here
                # if (preg_match('/^delete_(comment|pledge|signer)_([1-9]\d*)$/', $k, $a)) {
                if (preg_match('/^delete_(comment)_([1-9]\d*)$/', $k, $a)) {
                    if ($a[1] == 'comment') {
                        pledge_delete_comment($a[2]);
                    } else if ($a[1] == 'pledge') {
                        // pledge_delete_pledge($a[2]);
                    } else {
                        // pledge_delete_signer($a[2]);
                    }
                    print "<em>Deleted "
                            . htmlspecialchars($a[1])
                            . " #" . htmlspecialchars($a[2]) . "</em><br>";
                }
            }

            db_commit();

        }

        $this->showlist($self_link);
    }

    function showlist($self_link) {
        global $q_what;
        importparams(
                array('what',       '/^(comment|pledge|signer)$/',      '',     'comment')
            );

        print "<p><strong>See reports on:</strong> ";

        $ww = array('comment', 'signer', 'pledge');
        $i = 0;
        foreach ($ww as $w) {
            if ($w != $q_what)
                print "<a href=\"$self_link&amp;what=$w\">";
            print "${w}s ("
                    . db_getOne('select count(id) from abusereport where what = ?', $w)
                    . ")";
            if ($w != $q_what)
                print "</a>";
            if ($i < sizeof($ww) - 1)
                print " | ";
            ++$i;
        }

        $this->do_one_list($self_link, $q_what);
    }

    function do_one_list($self_link, $what) {

        $old_id = null;
        $q = db_query('select id, what_id, reason, ipaddr, extract(epoch from whenreported) as epoch from abusereport where what = ? order by what_id, whenreported desc', $what);

        if (db_num_rows($q) > 0) {

            print '<form name="discardreportsform" method="POST" action="'.$this->self_link.'"><input type="hidden" name="prev_url" value="'
                        . htmlspecialchars($self_link) . '">';
            print '
    <p><input type="submit" name="discardReports" value="Discard selected abuse reports"></p>
    <table class="abusereporttable">
    ';
            while (list($id, $what_id, $reason, $ipaddr, $t) = db_fetch_row($q)) {
                if ($what_id !== $old_id) {
                
                    /* XXX should group by pledge and then by signer/comment, but
                     * can't be arsed... */
                    print '<tr style="background-color: #eee;"><td colspan="4">';

                    if ($what == 'pledge')
                        $pledge_id = $what_id;
                    elseif ($what == 'signer')
                        $pledge_id = db_getRow('select pledge_id from signers where id = ?', $what_id);
                    elseif ($what == 'comment')
                        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $what_id);
                    
                    $pledge = db_getRow('
                                    select *,
                                        extract(epoch from creationtime) as createdepoch,
                                        extract(epoch from date) as deadlineepoch
                                    from pledges
                                    where id = ?', $pledge_id);
                        
                    /* Info on the pledge. Print for all categories. */
                    print '<table>';
                    print '<tr><td><b>Pledge:</b> ';
                    $pledge_obj = new Pledge($pledge);
                    print $pledge_obj->h_sentence(array());
                    print ' <a href="'.$pledge_obj->url_main().'">'.$pledge_obj->ref().'</a> ';
                    print '<a href="?page=pb&amp;pledge='.$pledge_obj->ref().'">(admin)</a> ';
                            
                    /* Print signer/comment details under pledge. */
                    if ($what == 'signer') {
                        $signer = db_getRow('
                                        select signers.*, person.email,
                                            extract(epoch from signtime) as epoch
                                        from signers
                                        left join person on signers.person_id = person.id
                                        where signers.id = ?', $what_id);

                        print '</td></tr>';
                        print '<tr class="break"><td><b>Signer:</b> '
                                . (is_null($signer['name'])
                                        ? "<em>not known</em>"
                                        : htmlspecialchars($signer['name']))
                                . ' ';

                        if (!is_null($signer['email']))
                            print '<a href="mailto:'
                                    . htmlspecialchars($signer['email'])
                                    . '">'
                                    . htmlspecialchars($signer['email'])
                                    . '</a> ';

                        if (!is_null($signer['mobile']))
                            print htmlspecialchars($signer['mobile']);

                        print '<b>Signed at:</b> ' . date('Y-m-d H:m', $signer['epoch']);
                    } elseif ($what == 'comment') {
                        $comment = db_getRow('
                                        select id,
                                            extract(epoch from whenposted)
                                                as whenposted,
                                            text, name, website
                                        from comment
                                        where id = ?', $what_id);

                        print '</td></tr>';
                        print '<tr class="break">';
                        print '<td><b>Comment:</b> ';
                        comments_show_one($comment, true);
                    }

                    if ($what == "comment") {
                        print " <input type=\"submit\" name=\"delete_${what}_${what_id}\" value=\"Delete this $what\">";
                    }
                    print '</td></tr>';
                    print '</table>';
                    $old_id = $what_id;
                }

                print '<tr><td>'
                            . '<input type="checkbox" name="ar_' . $id . '" value="1">'
                        . '</td><td><b>Abuse report:</b> '
                            . date('Y-m-d H:i', $t)
                            . ' from '
                            . $ipaddr
                        . '</td><td><b>Reason: </b>'
                            . $reason
                        . '</td></tr>';
            }

            print '</table>';
            print '<p><input type="submit" name="discardReports" value="' . _('Discard selected abuse reports') . '"></form>';
        } else {
            print '<p>No abuse reports of this type.</p>';
        }
    }
}

?>
