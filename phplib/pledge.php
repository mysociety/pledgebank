<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.5 2005-03-08 15:45:02 chris Exp $
 * 
 */

require_once 'db.php';
require_once '../../phplib/utility.php';

/* pledge_email_token ADDRESS PLEDGE [SALT]
 * Return a token encoding ADDRESS and PLEDGE. SALT should be either null, in
 * which case a random salt will be generated; or the result of a previous call
 * to this function, in which case the same salt as was used then will be
 * re-used, so that a comparison can be made. The returned token is of the
 * form SALT_TOKEN, where _ is a literal underscore and SALT and TOKEN are
 * base64-encoded data (matching /^[0-9A-Za-z+/=]+$/). */
function pledge_email_token($email, $pledge, $salt = null) {
    if (is_null($salt))
        $salt = base64_encode(random_bytes(3));
    else {
        /* Salt is anything up to first "_" in token. */
        $a = split("_", $salt);
        $salt = $a[0];
    }

    $h = pack('H*', sha1($salt . $email . $pledge . db_secret()));
    /* Don't send the full SHA1 hash, because we don't want our URLs to be too
     * long (at some marginal risk to security...). */
    return $salt . "_" . base64_encode(substr(&$h, 0, 12));
}

/* PLEDGE_...
 * Various codes for things which can happen to pledges. */
define('PLEDGE_OK',          0);
define('PLEDGE_NONE',        1);    /* Can't find that pledge */
define('PLEDGE_FINISHED',    2);    /* Pledge has expired */
define('PLEDGE_FULL',        3);    /* All places taken */
define('PLEDGE_SIGNED',      4);    /* Email address is already on pledge */
define('PLEDGE_DENIED',      5);    /* Permission denied */

    /* codes >= 100 represent temporary errors */
define('PLEDGE_ERROR',     100);    /* Some sort of nonspecific error. */

function pledge_strerror($e) {
    switch ($e) {
    case PLEDGE_OK:
        return "Success";

    case PLEDGE_FINISHED:
        return "That pledge has already finished";

    case PLEDGE_FULL:
        return "That pledge is already full";

    case PLEDGE_SIGNED:
        return "You've already signed that pledge";

    case PLEDGE_DENIED:
        return "Permission denied";

    case PLEDGE_ERROR:
    default:
        return "Something went wrong unexpectedly";
    }
}

function pledge_permanent_error($e) {
    return ($e < 100);
}

/* pledge_sentence PLEDGE [HTML]
 * Return a sentence describing what each signer agrees to do ("I will ...
 * if ..."). If HTML is true, encode entities and add <strong> tags around
 * strategic bits. */
function pledge_sentence($pledge_id, $html = false) {
    $r = db_getOne('select * from pledges where pledge_id = ?', $pledge_id);
    if (!$r)
        err(pledge_strerror(PLEDGE_NONE));
    if ($html)
        $r = array_map('htmlspecialchars', $r);
        
    $s = "I will <strong>$r['title']</strong> if "
            . '<strong>'
                . ($r['comparison'] == 'exactly' ? 'exactly' : 'at least')
                . " $r['target']"
                . '</strong>'
            . " other $r['type'] "
            . ($r['signup'] == 'sign up' ? 'will too' : $r['signup'])
            . ".";
    if (!$html)
        $s = preg_replace('#</?strong>#', '', $s);

    return $s;
}

/* pledge_is_successful PLEDGE
 * Has PLEDGE completed successfully? This function is not reliable. */
function pledge_is_successful($pledge_id) {
    $target = db_getOne('
                    select target
                    from pledges
                    where pledge_id = ?
                    for update', $pledge_id);
    $num = db_getOne('
                    select count(id)
                    from signers
                    where pledge_id = ?', $pledge_id);

    return $num >= $target;
}

/* pledge_is_valid_to_sign PLEDGE EMAIL
 * Return true if EMAIL may validly sign PLEDGE. This function locks rows in
 * pledges and signers with select ... for update / lock tables. */
function pledge_is_valid_to_sign($pledge_id, $email) {
    $r = db_getRow('
                select comparison, target, date, email
                from pledges
                where id = ? and confirmed for update', array($pledge));
    if (is_null($r))
        return PLEDGE_NONE;
    else if ($r['date'] < today())
        return PLEDGE_FINISHED;
    else if ($email == $r['email'])
        return PLEDGE_SIGNED;
    
    if ($comparison == 'exactly') {
        db_query('lock table signers in row shared mode');
        $num = db_getOne('select count(id) from signers where pledge_id = ?', $pledge);
        if ($num >= $r['target'])
            return PLEDGE_FULL;
    }

    if (isset(db_getOne('select id from signers where pledge_id = ? and email = ? for update', array($pledge_id, $email))))
        return PLEDGE_SIGNED;

    return PLEDGE_OK;
}

/* pledge_sign PLEDGE SIGNER SHOWNAME EMAIL [CONVERTS]
 * Add the named SIGNER to the PLEDGE. SHOWNAME indicates whether their name
 * should be displayed publically; EMAIL is their email address. It is the
 * caller's responsibility to check that the signer is authorised to sign a
 * private pledge (by supplying a password, presumably). If CONVERTS is not
 * null, it gives the ID of an existing signer whose signature will be
 * replaced by the new signature on confirmation. This is used to convert SMS
 * subscriptions into email subscriptions. On success returns the signer ID and
 * confirmation token; on failure returns an error message. This function does
 * not commit its changes.
 * XXX we should return a descriptive error code, because some of these error
 * conditions must not be shown to the user. */
function pledge_sign($pledge_id, $name, $showname, $email, $converts = null) {
    $e = pledge_is_valid_to_sign($pledge_id, $email);
    if ($e != PLEDGE_OK)
        return $e;

    $id = db_getOne("select nextval('signers_id_seq')");
    if (!isset($id))
        return PLEDGE_ERROR;

    if (!db_query('
                insert into signers (
                    id,
                    pledge_id,
                    converts_signer_id,
                    name, email, showname,
                    signtime,
                    token, confirmed
                ) values (
                    ?,
                    ?,
                    ?,
                    ?, ?, ?,
                    current_timestamp,
                    ?, false
                )', array(
                    $id,
                    $pledge_id,
                    $converts,
                    $name, $email, $showname ? 't' : 'f',
                    #
                    $token)))
        return "Unable to insert signer into database"; /* XXX should get an error message, somehow */

    /* Done. */
    return array($id, $token);
}

/* pledge_sign_confirm TOKEN
 * Confirm a signature on a pledge with the given TOKEN. If the signer is
 * converting from an earlier (e.g. SMS) subscription, then drop the old
 * subscription after updating the new subscription with any missing details.
 * Returns the ID of the pledge for which the TOKEN confirmed a signatory, or
 * null if the token was not valid. This function does not commit its
 * changes. */
function pledge_sign_confirm($token) {
    $r = db_getOne('
                select id, converts_signer_id, pledge_id
                from signers
                where token = ?
                for update
            ', $token);
    if (is_null($r))
        return null;

    $id = $r['id'];
    $converts = $r['converts_signer_id'];
    
    db_query('update signers set confirmed = true where id = ?', $id);

    /* Handle conversion. For the moment assume that the converted-from signer
     * only specifies a mobile number. */
    if (!is_null($converts)) {
        db_query('
                update signers
                set mobile = (select mobile from signers where id = ?)
                where id = ?
            ', array($converts, $id));
        db_query('delete from outgoingsms_signers where signer_id = ?', $converts);
        db_query('delete from signers where id = ?', $converts);
    }

    return $r['pledge_id'];
}

?>
