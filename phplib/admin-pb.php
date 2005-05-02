<?php
/*
 * admin-pb.php:
 * PledgeBank admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pb.php,v 1.36 2005-05-02 22:27:43 matthew Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/pledge.php";
require_once "../phplib/comments.php";
require_once "fns.php";
require_once "db.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";

class ADMIN_PAGE_PB_MAIN {
    function ADMIN_PAGE_PB_MAIN () {
        $this->id = "pb";
        $this->navname = "Pledges and Signers";
    }

    function pledge_header($sort) {
        print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
        $cols = array('r'=>'Ref', 'a'=>'Title', 't'=>'Target', 's'=>'Signers', 'd'=>'Deadline', 'e'=>'Creator', 'c'=>'Creation Time', 'f'=>'Front Page');
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
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^ratdecsf]/', $sort)) $sort = 'd';
        if ($sort=='r') $order = 'ref';
        elseif ($sort=='a') $order = 'title';
        elseif ($sort=='t') $order = 'target';
        elseif ($sort=='d') $order = 'date';
        elseif ($sort=='e') $order = 'email';
        elseif ($sort=='c') $order = 'creationtime';
        elseif ($sort=='f') $order = 'frontpage desc';
        elseif ($sort=='s') $order = 'signers';

        $q = db_query("
            SELECT id,ref,title,type,target,signup,date,name,email,confirmed,
                date_trunc('second',creationtime) AS creationtime, 
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                pb_current_date() <= date AS open,
                (SELECT count(*) FROM frontpage_pledges WHERE pledge_id=pledges.id) AS frontpage
            FROM pledges ORDER BY " . $order);
        $open = array();
        $closed = array();
        while ($r = db_fetch_array($q)) {
            $r = array_map('htmlspecialchars', $r);
            $row = '<td><a href="'.OPTION_BASE_URL . "/" . $r['ref'] .'">'.$r['ref'].'</a></td>';
            $row .= '<td><a href="'.$this->self_link.'&amp;pledge='.$r['ref'].'">'.
            $r['title'].'</a>';
            if ($r['confirmed'] == 'f') {
                $row .= "<br><b>not confirmed</b>";
            }
            $row .= '</td>';
            $row .= '<td>'.$r['target'].' '.$r['type'].'</td>';
            $row .= '<td>'.$r['signers'].'</td>';
            $row .= '<td>'.prettify($r['date']).'</td>';
            $row .= '<td>'.$r['name'].'<br>'.$r['email'].'</td>';
            $row .= '<td>'.$r['creationtime'].'</td>';
            $row .= '<td><input type="checkbox" name="frontpage['.$r['id'].']" '
                    . ($r['frontpage'] ? 'checked' : ''). ' value="1"></td>';
            if ($r['open'] == 't')
                $open[] = $row;
            else
                $closed[] = $row;
        }
         
        print '<form method="post" action="'.$this->self_link.'">'."\n";
        print '<input type="hidden" name="s" value="' . get_http_var('s') . '">';
        if (count($open)) {
            print "<h2>All Open Pledges</h2>\n";
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
            print "<h2>All Closed Pledges</h2>\n";
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
        print '<input type="submit" name="update" value="Save changes">';
        print '</form>';
    }

    function show_one_pledge($pledge) {
        print '<p><a href="'.$this->self_link.'">List of all pledges</a></p>';

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etcn]/', $sort)) $sort = 'e';

        $q = db_query('SELECT * FROM pledges where ref ILIKE ?', $pledge);
        $pdata = db_fetch_array($q);

        print "<h2>Pledge '" . $pdata['ref'] . "' &mdash; " .  $pdata['title'] . "</h2>";

        if ($pdata['confirmed'] == 'f') {
            print "<p><i>Pledge creator's email address not confirmed</b></i>";
        }
        
        print "<p>Set by: <b>" . $pdata['name'] . " &lt;" .  $pdata['email'] . "&gt;</b>";
        print "<br>Created: <b>" . prettify($pdata['creationtime']) . "</b>";
        print "<br>Deadline: <b>" . prettify($pdata['date']) . "</b>";
        print " Target: <b>" . $pdata['target'] . " " .  $pdata['type'] . "</b>";
        print "</p>";

        $query = 'SELECT name as signname,email as signemail,mobile as signmobile,
        date_trunc(\'second\',signtime) AS signtime,showname, signers.id AS signid FROM signers 
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
            $out[$e] .= '<td align="center">'.($r['showname']?'Yes':'No').'</td>';
            $out[$e] .= '<td><form method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer permanently"></form></td>';
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
        }
        print '<p><form method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_pledge_id" value="' . $pdata['id'] . '"><input type="submit" name="remove_pledge" value="Remove pledge permanently"></form>';
    }

    function remove_pledge($id) {
        db_query('DELETE FROM message_signer_recipient WHERE signer_id IN
            (SELECT id FROM signers WHERE pledge_id = ?)', array($id));
        db_query('DELETE FROM message_creator_recipient WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM message WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM smssubscription WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM smssubscription WHERE signer_id IN
            (SELECT id FROM signers WHERE pledge_id = ?)', array($id));
        db_query('DELETE FROM signers WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM comment WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM frontpage_pledges WHERE pledge_id = ?', array($id));
        db_query('DELETE FROM pledges WHERE id = ?', array($id));
        db_commit();
        print '<p><em>That pledge has been successfully removed, along with all its signatories.</em></p>';
    }

    function remove_signer($id) {
        db_query('DELETE FROM message_signer_recipient WHERE signer_id = ?', array($id));
        db_query('DELETE FROM smssubscription WHERE signer_id = ?', array($id));
        db_query('DELETE FROM signers WHERE id = ?', array($id));
        db_commit();
        print '<p><em>That signer has been successfully removed.</em></p>';
    }

    function update_changes() {
        db_query('DELETE FROM frontpage_pledges');
        if (array_key_exists('frontpage', $_POST)) {
            foreach (array_keys($_POST['frontpage']) as $ref) {
                db_query('INSERT INTO frontpage_pledges (pledge_id) values (?)', array($ref));
            }
        }
        db_commit();
        print "<p><i>Changes to front page pledges saved</i></p>";
    }

    function display($self_link) {
        db_connect();

        $pledge = get_http_var('pledge');

        if (get_http_var('remove_pledge_id')) {
            $id = get_http_var('remove_pledge_id');
            if (ctype_digit($id))
                $this->remove_pledge($id);
            $this->list_all_pledges();
        } elseif (get_http_var('remove_signer_id')) {
            $id = get_http_var('remove_signer_id');
            if (ctype_digit($id)) {
                $pledge_id = db_getOne("SELECT pledge_id FROM signers WHERE id = $id");
                $this->remove_signer($id);
                $pledge = db_getOne("SELECT ref FROM pledges WHERE id = $pledge_id");
            }
            $this->show_one_pledge($pledge);
        } elseif ($pledge) {
            $this->show_one_pledge($pledge);
        } else {
            if (get_http_var('update')) {
                $this->update_changes();
            }
            $this->list_all_pledges();
        }
    }
}

class ADMIN_PAGE_PB_LATEST {
    function ADMIN_PAGE_PB_LATEST() {
        $this->id = 'pblatest';
        $this->navname = 'Timeline';
    }

    # pledges use creationtime
    # signers use signtime
    function show_latest_changes() {
        $q = db_query('SELECT signers.name, signers.email,
                              signers.mobile, signtime, showname, pledges.title,
                              pledges.ref, pledges.id
                              extract(epoch from signtime) as epoch
                         FROM signers, pledges
                        WHERE signers.pledge_id = pledges.id
                     ORDER BY signtime DESC');
        while ($r = db_fetch_array($q)) {
            $time[$r['epoch']][] = $r;
        }
        $q = db_query('SELECT *,extract(epoch from created) as epoch
                         FROM token
                     ORDER BY created DESC');
        while ($r = db_fetch_array($q)) {
            $time[$r['epoch']][] = $r;
        }
        if (!get_http_var('onlysigners')) {
            $q = db_query('SELECT *,extract(epoch from creationtime) as epoch
                             FROM pledges
                         ORDER BY id DESC');
            $this->pledgeref = array();
            while ($r = db_fetch_array($q)) {
                $time[$r['epoch']][] = $r;
                $this->pledgeref[$r['id']] = $r['ref'];
            }
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
                $time[$r['lastsendattempt']][] = $r;
            }
            $q = db_query('SELECT whencreated, circumstance, ref,extract(epoch from whencreated) as epoch
                             FROM message, pledges
                            WHERE message.pledge_id = pledges.id
                         ORDER BY whencreated DESC');
            while ($r = db_fetch_array($q)) {
                $time[$r['epoch']][] = $r;
            }
            $q = db_query('SELECT *, extract(epoch from whenposted) as commentposted
                             FROM comment
                         ORDER BY whenposted DESC');
            while ($r = db_fetch_array($q)) {
                $time[$r['commentposted']][] = $r;
            }
        }
        krsort($time);
        print '<a href="'.$this->self_link.'">Full log</a> | <a
        href="'.$this->self_link.'&amp;onlysigners=1">Only
        signatures</a>'; ?>
<style type="text/css">
dt {   
    clear: left;
    float: left;
    font-weight: bold;
}
dd {
    margin-left: 6em;
}
</style>
<?
        print '<dl>';
        $date = ''; # $signed = array();
        foreach ($time as $epoch => $datas) {
            $curdate = date('jS F Y', $epoch);
            if ($date != $curdate) {
                print '</dl> <h2>'. $curdate . '</h2> <dl>';
                $date = $curdate;
            }
            print '<dt><b>' . date('H:i:s', $epoch) . ':</b></dt> <dd>';
            foreach ($datas as $data) {
            if (array_key_exists('signtime', $data)) {
                print $data['name'];
                if ($data['email']) print ' &lt;'.$data['email'].'&gt;';
                if ($data['mobile']) print ' (' . $data['mobile'] . ')';
                print ' signed up to ' .
                $this->pledge_link('ref', $data['ref']);
                if ($data['showname'] == 'f') {
                    print ' (anonymously)';
                }
#                if ($data['email']) {
#                    $signed[$data['id']][$data['email']] = 1;
#                }
            } elseif (array_key_exists('creationtime', $data)) {
                print "Pledge $data[id], ref <em>$data[ref]</em>, ";
                if ($data['confirmed']=='f') {
                    print '<em>' . htmlspecialchars($data['title']) . '</em> created (unconfirmed)';
                } else {
                    print $this->pledge_link('ref', $data['ref'], $data['title']) . ' created (confirmed)';
                }
                print " by $data[name] &lt;$data[email]&gt;";
            } elseif (array_key_exists('whenreceived', $data)) {
                print "Incoming SMS from $data[sender] received, sent
                $data[whensent], message $data[message]
                ($data[foreignid] $data[network])";
            } elseif (array_key_exists('whencreated', $data)) {
                print "Message $data[circumstance] queued for pledge " .
                $this->pledge_link('ref', $data['ref']);
            } elseif (array_key_exists('created', $data)) {
                $stuff = $data['data'];
                $pos = 0;
                $res = rabx_wire_rd(&$stuff, &$pos);
                if (rabx_is_error($res)) {
                    print '<em>RABX Error: ' . $res->text . '</em><br>';
                }
                print "$data[scope] token $data[token] created ";
                if (array_key_exists('email', $res)) {
                    print "for $res[name] $res[email], pledge " .
                    $this->pledge_link('id', $res['pledge_id']);
#                    if ($signed[$res['pledge_id']][$res['email']]) {
#                        print ' - confirmed';
#                    }
                } elseif (array_key_exists('circumstance', $res)) {
                    print "for pledge " . $this->pledge_link('id', $res['pledge_id']);
                }
            } elseif (array_key_exists('lastsendattempt', $data)) {
                if ($data['ispremium'] == 't') print 'Premium ';
                print "SMS sent to $data[recipient], message
                '$data[message]' status $data[lastsendstatus]";
            } elseif (array_key_exists('commentposted', $data)) {
                print "$data[name] &lt;$data[email]&gt; commented on " .
                    $this->pledge_link('id', $data['pledge_id']) . " saying
                '$data[text]'";
            } else {
                print_r($data);
            }
            print '<br>';
            }
            print "</dd>\n";
        }
        print '</ul>';
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
        return '<a href="' . OPTION_BASE_URL . '/' . $ref . '">' .
        htmlspecialchars($title) . '</a>';
    }

    function display($self_link) {
        db_connect();
        $this->show_latest_changes();
    }
}

class ADMIN_PAGE_PB_ABUSEREPORTS {
    function ADMIN_PAGE_PB_ABUSEREPORTS() {
        $this->id = 'pbabusereports';
        $this->navname = 'Abuse reports';
    }

    function display($self_link) {
        db_connect();

        
        if (array_key_exists('prev_url', $_POST)) {
            $do_discard = false;
            if (get_http_var('discardReports'))
                $do_discard = true;
            foreach ($_POST as $k => $v) {
                $m = array();
                if ($do_discard && preg_match('/^ar_([1-9]\d*)$/', $k, $a))
                    db_query('delete from abusereport where id = ?', $a[1]);
                if (preg_match('/^delete_(comment|pledge|signer)_([1-9]\d*)$/', $k, $a)) {
                    /* XXX delete thing. */
                }
            }

            db_commit();

            print '<a href="' . htmlspecialchars(get_http_var('prev_url')) . '">Return to report list</a>';
            return;
        }

        
        ?>
<style type="text/css">
table.abusereporttable th { background-color: black; color: white; font-weight: bold; }
//table.abusereporttable { padding-left: 1.5em; }
table.abusereporttable tr.thing { background-color: #eee; padding-left: 0em; }
table.abusereporttable tr.thing table { width: 100%; }
table.abusereporttable tr.thing th { background-color: #00b; text-align: right; vertical-align: top; }
table.abusereporttable tr.break { border-top: 1px solid white; }
</style>
<?
        
        $this->showlist($self_link);
    }

    function showlist($self_link) {
        global $q_what;
        importparams(
                array('what',       '/^(comment|pledge|signer)$/',      '',     'pledge')
            );

        print "<p><strong>See reports on:</strong> ";

        $ww = array('pledge', 'signer', 'comment');
        $i = 0;
        foreach ($ww as $w) {
            if ($w != $q_what)
                print "<a href=\"$self_link&what=$w\">";
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

        print <<<EOF
<table class="abusereporttable">
    <tr>
        <th style="width: 2em;"></th>
        <th width="15%">Time</th>
        <th width="15%">Reporting IP address</th>
        <th>Reason</th>
    </tr>
EOF;

        $old_id = null;
        $q = db_query('select id, what_id, reason, ipaddr, extract(epoch from whenreported) as epoch from abusereport where what = ? order by what_id, whenreported desc', $what);

        while (list($id, $what_id, $reason, $ipaddr, $t) = db_fetch_row($q)) {
            if ($what_id !== $old_id) {
                print '<form method="POST"><input type="hidden" name="prev_url" value="' . htmlspecialchars($self_link) . '">';
            
                /* XXX should group by pledge and then by signer/comment, but
                 * can't be arsed... */
                print '<tr class="thing"><td colspan="4">';

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
                print '<table><tr><th colspan="2" style="text-align: center;">Pledge '
                            . htmlspecialchars($pledge['title'])
                        . '</th></tr><tr><th width="15%">Creator</th><td>'
                            . htmlspecialchars($pledge['name'])
                        . '</td></tr><tr><th>Created</th><td>'
                            . date('Y-m-d H:i', $pledge['createdepoch'])
                        . '</td></tr><tr><th>Deadline</th><td>'
                            . date('Y-m-d', $pledge['deadlineepoch'])
                        . '</td></tr>';
                        
                /* Print signer/comment details under pledge. */
                if ($what == 'signer') {
                    $signer = db_getRow('
                                    select *,
                                        extract(epoch from signtime) as epoch
                                    from signers
                                    where id = ?', $what_id);

                    print '<tr class="break"><th>Signer name</th><td>'
                            . (is_null($signer['name'])
                                    ? "<em>not known</em>"
                                    : htmlspecialchars($signer['name']))
                            . '</td></tr><tr><th>Contact details</th><td>';

                    if (!is_null($signer['email']))
                        print '<a href="mailto:'
                                . htmlspecialchars($signer['email'])
                                . '">'
                                . htmlspecialchars($signer['email'])
                                . '</a> ';

                    if (!is_null($signer['mobile']))
                        print htmlspecialchars($signer['mobile']);

                    print '</td></tr>'
                            . '<tr><th>Signed at</th><td>'
                            . date('Y-m-d H:M', $signer['epoch'])
                            . '</td></tr>';
                } elseif ($what == 'comment') {
                    $comment = db_getRow('
                                    select id,
                                        extract(epoch from whenposted)
                                            as whenposted,
                                        text, name, website
                                    from comment
                                    where id = ?', $what_id);

                    print '<tr class="break"><th>Comment</th><td>';
                    comments_show_one($comment, true);
                    print '</td></tr>';
                }

                print '</td></tr><tr><td></td><td>'
                        . "<input type=\"submit\" name=\"delete_${what}_${what_id}\" value=\"Delete this $what\">"
                        . '</td></tr></table>';
                $old_id = $what_id;
            }

            print '<tr><td>'
                        . '<input type="checkbox" name="ar_' . $id . '" value="1">'
                    . '</td><td>'
                        . date('Y-m-d H:i', $t)
                    . '</td><td>'
                        . $ipaddr
                    . '</td><td>'
                        . $reason
                    . '</td></tr>';
        }

        print '</table>'
                . '<input type="submit" name="discardReports" value="Discard selected abuse reports"></form>';
    }
}

?>
