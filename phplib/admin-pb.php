<?php
/*
 * PledgeBank admin page.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-pb.php,v 1.1 2005-01-07 16:43:04 francis Exp $
 * 
 */

require_once "fns.php";
require_once "db.php";
require_once "../../phplib/utility.php";

class ADMIN_PAGE_PB {
    function ADMIN_PAGE_PB () {
        $this->id = "pb";
        $this->name = "Pledges and Signers";
        $this->navname = "Pledges and Signers";
    }

    function list_all_pledges() {
        print "<h2>All the pledges</h2>";
        $q = db_query('SELECT ref,title,target,date,name,email FROM pledges');
        print '<table border=1
        width=100%><tr><th>Ref</th><th>Title</th><th>Target</th><th>Deadline</th><th>Setter</th></tr>';
        while ($r = db_fetch_array($q)) {
            $r = array_map('htmlspecialchars', $r);
            print '<tr>';
            print '<td>'.$r['ref'].'</td>';
            print '<td><a href="./?pledge='.$r['ref'].'">'.$r['title'].'</a></td>';
            print '<td>'.$r['target'].'</td>';
            print '<td>'.prettify($r['date']).'</td>';
            print '<td>'.$r['name'].'<br>'.$r['email'].'</td>';
            print '</tr>';
        }
        print '</table>';
    }

    function show_one_pledge($pledge) {
        print '<p><a href="./">List of all pledges</a></p>';

        $q = db_query('SELECT * FROM pledges where ref=?', $pledge);
        $pdata = db_fetch_array($q);

        print "<h2>Pledge '" . $pdata['ref'] . "' &mdash; " .  $pdata['title'] . "</h2>";

        if (!$pdata['confirmed'] == 1) {
            print "<p><b>Email address not confirmed</b></p>";
        }
        
        print "<p>Set by: <b>" . $pdata['name'] . " &lt;" .  $pdata['email'] . "&gt;</b>";
        print "<br/>Created: <b>" . prettify($pdata['creationtime']) . "</b>";
        print "<br/>Deadline: <b>" . prettify($pdata['date']) . "</b>";
        print "</p>";

        $q = db_query('SELECT * FROM signers where pledge_id=?', $pdata['id']);
        print '<table border=1 width=100%><tr>
        <th>Signee</th> <th>Time</th> <th>Show name?</th> <th>Confirmed</th> </tr>';
        while ($r = db_fetch_array($q)) {
            $r = array_map('htmlspecialchars', $r);
            print '<tr>';
            print '<td>'.$r['signname'].'<br>'.$r['signemail'].'</td>';
            print '<td>'.prettify($r['signtime']).'</td>';
            print '<td>'.$r['showname'].'</td>';
            print '<td>'.$r['confirmed'].'</td>';
            print '</tr>';
        }
        print '</table>';
    }


    function display($self_link) {
        db_connect();

        $pledge = get_http_var('pledge');
        if ($pledge) {
            $this->show_one_pledge($pledge);
        } else {
            $this->list_all_pledges();
        }
    }
}

?>
