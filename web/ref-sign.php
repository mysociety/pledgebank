<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.3 2005-05-20 15:09:07 francis Exp $

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
            array('email',      '/^[^@]+@.+/',     'Please give your email'),
            array('ref',        '/^[a-z0-9-]+$/i', ''),
            array('showname',   '//',              'Please enter showname', 0),
            array('pw',         '//',              '', null)
            );
    if ($q_email=='<Enter your name>') $q_email='';

    $r = db_getRow('select * from pledges where ref ILIKE ?', $q_ref);
    if (!check_password($q_ref, $r['password']))
        err("Permission denied");

    if (!is_null($errors)) {
        return $errors;
    }

    /* The exact mail we send depends on whether we're already signed up to
     * this pledge. */
    $id = db_getOne('select id from signers where pledge_id = ? and email = ?', array($r['id'], $q_email));
    if (isset($id)) {
        $success = pb_send_email_template($q_email, 'signature-confirm-already', $r);
    } else {
        /* Generate a secure URL to send to the user. */
        $data = array('email' => $q_email, 'name' => $q_name, 
                'showname' => $q_showname, 'pledge_id' => $r['id']);
        $token = auth_token_store('signup-web', $data);

        $url = OPTION_BASE_URL . "/I/" . $token;
        $success = pb_send_email_template($q_email, 'signature-confirm-ok',
                array_merge($r, array('url'=>$url)));
    }

    if ($success) {
    ?>
    <p><strong>Now check your email</strong></p>
    <p>We've sent you an email to confirm your address. Please follow the link
    we've sent to you to finish signing this pledge.</p>
    <?
        db_commit();
    } else {
    ?>
    <p>We seem to be having some technical problems. Please could try again in a
    few minutes, making sure that you carefully check the email address you give.
    </p>
    <?
    }
}

?>
