<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.13 2005-06-12 22:00:44 chris Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));

$title = 'Signature addition';
page_header($title);
$errors = do_sign();
if (is_array($errors)) {
    print '<div id="errors"><ul><li>';
    print join ('</li><li>', $errors);
    print '</li></ul></div>';
     $p->render_box(array('showdetails'=>false));
    pledge_sign_box();
}
page_footer();

function do_sign() {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pin;
    $errors = importparams(
                array('name',       '/^[a-z]/i',        'Please give your name'),
                array('email',      '/^[^@]+@.+/',      'Please give your email'),
                array('ref',        '/^[a-z0-9-]+$/i',  ''),
                array('showname',   '//',               'Please enter showname', 0),
                array('pin',         '//',              '', null)
            );
    if ($q_email=='<Enter your name>') $q_email='';

    $pledge = new Pledge($q_ref);
    if (!check_pin($q_ref, $pledge->pin()))
        err("Permission denied");

    if (!is_null($errors))
        return $errors;

    /* Get the user to log in. */
    $r = $pledge->data;
    $r['template'] = 'signature-confirm';
    $r['reason'] = 'sign the pledge';
    $P = person_signon($r, $q_email, $q_name);
    
    $R = pledge_is_valid_to_sign($pledge->id(), $P->email());
    $f1 = $pledge->succeeded();

    if (!pledge_is_error($R)) {
        /* All OK, sign pledge. */
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime) values (?, ?, ?, ?, pb_current_timestamp())', array($pledge->id(), $P->name(), $P->id(), $q_showname ? 't' : 'f'));
        db_commit();
        ?>
<p class="noprint" id="loudmessage" align="center">Thanks for signing up to this pledge!</p>
<?

        /* Grab the row again so the check is current. */
        $pledge = new Pledge($q_ref);
        if (!$f1 && $pledge->succeeded())
            print "<p><strong>Your signature has made this pledge reach its target! Woohoo!</strong></p>";

        post_confirm_advertise($pledge);
    } else if ($R == PLEDGE_SIGNED) {
        /* Either has already signer, or is creator. */
        if ($P->id() == $pledge->creator_id()) {
        ?>
<p><strong>You cannot sign your own pledge!</strong></p>
<?
        } else {
        ?>
<p><strong>You've already signed this pledge!</strong></p>
<?
        }
    } else {
        /* Something else has gone wrong. */
        print "<p><strong>Sorry &mdash; it wasn't possible to sign that pledge. "
                . htmlspecialchars(pledge_strerror($R))
                . ".</p></strong>";
    }
}

?>
