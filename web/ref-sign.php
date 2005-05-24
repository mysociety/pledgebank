<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.4 2005-05-24 14:56:07 chris Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

$ref = get_http_var('ref'); 
$h_ref = htmlspecialchars($ref);
$q = db_query('SELECT *, pb_current_date() <= date as open FROM pledges WHERE ref ILIKE ?', array($ref));
if (!db_num_rows($q)) {
    err('PledgeBank reference not known');
} 

$title = 'Signature addition';
page_header($title);
$errors = do_sign();
if (is_array($errors)) {
    print '<div id="errors"><ul><li>';
    print join ('</li><li>', $errors);
    print '</li></ul></div>';
    pledge_sign_box();
}
page_footer();

function do_sign() {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pw;
    $errors = importparams(
            array('name',       '/^[a-z]/i',        'Please give your name'),
            array('email',      '/^[^@]+@.+/',      'Please give your email'),
            array('ref',        '/^[a-z0-9-]+$/i',  ''),
            array('showname',   '//',               'Please enter showname', 0),
            array('pw',         '//',               '', null)
            );
    if ($q_email=='<Enter your name>') $q_email='';

    $r = db_getRow('select * from pledges where ref ILIKE ?', $q_ref);
    if (!check_password($q_ref, $r['password']))
        err("Permission denied");

    if (!is_null($errors)) {
        return $errors;
    }

    /* Get the user to log in. */
    $r['template'] = 'signature-confirm';
    $r['reason'] = 'sign the pledge';
    $P = person_signon($r, $q_email, $q_name);

    if ($P->id() == $r['person_id']) {
        ?>
<p><strong>You cannot sign your own pledge!</strong></p>
<?
        return;
    }
   
    $id = db_getOne('select id from signers where pledge_id = ? and person_id = ?', array($r['id'], $P->id()));

    if (isset($id)) {
        ?>
<p><strong>You've already signed this pledge!</strong></p>
<?
        return;
    } else {
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime) values (?, ?, ?, ?, pb_current_timestamp())', array($r['id'], $P->name(), $P->id(), $q_showname));
        db_commit();

        ?>
<p><strong>Congratulations! You've just signed this pledge</strong></p>
<?
    }
}

?>
