<?php
/*
 * ref-survey.php:
 * Record pledge completion by a signatory.
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-survey.php,v 1.1 2007-07-29 22:25:44 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
#require_once '../../phplib/db.php';

#require_once '../phplib/page.php';
#require_once '../phplib/pbperson.php';
#require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$err = importparams(
    array('ref',   '/./',   '')
);
if (!is_null($err))
    err(_("Missing pledge reference"));

page_check_ref(get_http_var('ref'));
$pledge = new Pledge($q_ref);
microsites_redirect($pledge);

$P = pb_person_signon(array(
    "reason_web" => _("Before you can say that you've done a pledge, we need to check that you signed that pledge."),
    "reason_email" => _("Then you will be able to say you've done a pledge."),
    "reason_email_subject" => _("Say you've done a pledge at PledgeBank.com"))
);

$signer = db_getRow('select id, showname from signers where pledge_id=? and person_id=?', $pledge->id(), $P->id());
if (!count($signer)) {
    page_header(_("PledgeBank pledge survey"),
        array('ref'=>$pledge->ref(), 'pref' => $pledge->url_typein()));
    print p(_("You did not sign this pledge, so cannot say that you have done it!"));
    page_footer();
    exit;
}

# Mark pledge as done!
db_query("update signers set done='t' where id=?", $signer['id']);
db_commit();

if (get_http_var('r') == 'pledge')
    $url = '/' . $pledge->ref() . '#signer' . $signer['id'];
else
    $url = '/your?done=' . $pledge->ref() . '#signed' . $pledge->id();

header("Location: $url");
?>
