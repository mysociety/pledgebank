<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.14 2005-03-11 17:00:41 matthew Exp $
 * 
 */

require_once 'db.php';
require_once '../../phplib/utility.php';

/* pledge_ab64_encode DATA
 * Return a "almost base64" encoding of DATA (a six-bit encoding of
 * URL-friendly characters; specifically the encoded data match
 * /^[0-9A-Za-z.,-]+$/). */
function pledge_ab64_encode($i) {
    $t = base64_encode($i);
    $t = str_replace("+", ".", &$t);
    $t = str_replace("/", ",", &$t);
    $t = str_replace("=", "-", &$t);
    return $t;
}

/* pledge_email_token ADDRESS PLEDGE [SALT]
 * Return a token encoding ADDRESS and PLEDGE. SALT should be either null, in
 * which case a random salt will be generated; or the result of a previous call
 * to this function, in which case the same salt as was used then will be
 * re-used, so that a comparison can be made. The returned token is of the
 * form SALT_TOKEN, where _ is a literal underscore and SALT and TOKEN are
 * almost-base64-encoded data. */
function pledge_email_token($email, $pledge, $salt = null) {
    if (is_null($salt)) {
        $salt = pledge_ab64_encode(random_bytes(3));
    } else {
        /* Salt is anything up to first "_" in token. */
        $a = split("_", $salt);
        $salt = $a[0];
    }

    $h = pack('H*', sha1($salt . $email . $pledge . db_secret()));
    /* Don't send the full SHA1 hash, because we don't want our URLs to be too
     * long (at some marginal risk to security...). */
    $b64 = pledge_ab64_encode(substr(&$h, 0, 12));

    /* Base64 uses the character set [A-za-z0-9+/=]. "+", "/" and "=" are poor
     * characters for URLs, because they have special interpretations. So use
     * others instead. */
    return $salt . '_' . $b64;
}

/* PLEDGE_...
 * Various codes for things which can happen to pledges. All such error codes
 * must be nonpositive. */
define('PLEDGE_OK',          0);
define('PLEDGE_NONE',       -1);    /* Can't find that pledge */
define('PLEDGE_FINISHED',   -2);    /* Pledge has expired */
define('PLEDGE_FULL',       -3);    /* All places taken */
define('PLEDGE_SIGNED',     -4);    /* Email address is already on pledge */
define('PLEDGE_DENIED',     -5);    /* Permission denied */

    /* codes <= -100 represent temporary errors */
define('PLEDGE_ERROR',    -100);    /* Some sort of nonspecific error. */

/* pledge_is_error RESULT
 * Does RESULT indicate an error? */
function pledge_is_error($res) {
    return (is_int($res) && $res < 0);
}

/* pledge_strerror CODE
 * Return a description of the error CODE. */
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

/* pledge_is_permanent_error CODE
 * Return true if CODE represents a permanent error (i.e. one which won't go
 * away by itself). */
function pledge_is_permanent_error($e) {
    return ($e > PLEDGE_ERROR);
}

/* pledge_sentence PLEDGE FIRSTPERSON [HTML]
 * Return a sentence describing what each signer agrees to do ("$pledgecreator
 * will ...  if ..."). If FIRSTPERSON is true, then the sentence is "I
 * will...". If HTML is true, encode entities and add <strong> tags around
 * strategic bits.
 * XXX i18n -- this won't work at all in other languages */
function pledge_sentence($pledge_id = false, $firstperson = false, $html = false, $r = array()) {
    if (!$r)
        $r = db_getRow('select * from pledges where id = ?', $pledge_id);
    if (!$r)
        err(pledge_strerror(PLEDGE_NONE));

    if ($html)
        $r = array_map('htmlspecialchars', $r);
        
    $s = ($firstperson ? "I" : $r['name'])
            . " will <strong>${r['title']}</strong> if "
            . '<strong>';
    if (isset($r['comparison']))
        $s .= ($r['comparison'] == 'exactly' ? 'exactly' : 'at least');
    $s .= ' ' . prettify($r['target'])
            . '</strong>'
            . " ${r['type']} "
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
                    where id = ?
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
    $resmap = array(
            'ok' => PLEDGE_OK,
            'none' => PLEDGE_NONE,
            'finished' => PLEDGE_FINISHED,
            'signed' => PLEDGE_SIGNED,
            'full' => PLEDGE_FULL,
        );
    $r = db_getOne('select pledge_is_valid_to_sign(?, ?, null)', array($pledge_id, $email));
    if (array_key_exists($r, $resmap))
        return $resmap[$r];
    else
        err("Bad result $r from pledge_is_valid_to_sign");
}

/* pledge_confirm TOKEN
 * If TOKEN confirms any outstanding pledge, confirm that pledge and return
 * its ID. */
function pledge_confirm($token) {
    $pledge_id = db_getOne('
                        select id from pledges
                        where token = ?', $token);
                    /* NB do not need "for update" because this function is
                     * idempotent. */
    if (!isset($pledge_id))
        return PLEDGE_NONE;
    else {
        db_query('
                update pledges
                set confirmed = true, creationtime = current_timestamp
                where id = ? and not confirmed',
                    $pledge_id);
        return $pledge_id;
    }
}

/* pledge_sign PLEDGE SIGNER SHOWNAME EMAIL [CONVERTS]
 * Add the named SIGNER to the PLEDGE. SHOWNAME indicates whether their name
 * should be displayed publically; EMAIL is their email address. It is the
 * caller's responsibility to check that the signer is authorised to sign a
 * private pledge (by supplying a password, presumably). If CONVERTS is not
 * null, it gives the ID of an existing signer whose signature will be
 * replaced by the new signature on confirmation. This is used to convert SMS
 * subscriptions into email subscriptions. On success returns the new signer
 * ID; on failure, return an error code. */
function pledge_sign($pledge_id, $name, $showname, $email, $converts = null) {
    $e = pledge_is_valid_to_sign($pledge_id, $email);
    if (pledge_is_error($e))
        return $e;

    $id = db_getOne("select nextval('signers_id_seq')");
    if (!isset($id))
        return PLEDGE_ERROR;

    if (!db_query('
                insert into signers (
                    id,
                    pledge_id,
                    name, email, showname,
                    signtime
                ) values (
                    ?,
                    ?,
                    ?, ?, ?,
                    current_timestamp
                )', array(
                    $id,
                    $pledge_id,
                    $name, $email, $showname ? 't' : 'f')
                ))
        return PLEDGE_ERROR;

    /* Done. */
    return $id;
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

/* deal_with_password(form input name of variable used to pass pledge reference (e.g. pdf for the pdf page),
        pledge reference, actual password)
  XXX: Doesn't work with non-index.php pages yet!
 */
function deal_with_password($type, $ref, $actual) {
    $h_ref = htmlspecialchars($ref);
    $entered = get_http_var('pw');
    if (!$actual) return true;
    if ($entered) {
        if ($entered != $actual) {
            print '<p class="finished">Incorrect password!</p>';
            print '<form class="pledge" name="pledge" action="./" method="post"><input type="hidden" name="' . $type . '" value="' . $h_ref . '"><h2>Password Protected Pledge</h2><p>This pledge is password protected: please enter the password to proceed:</p>';
            print '<p><input type="password" name="pw" value=""><input type="submit" name="submit" value="Submit"></p>';
            print '</form>';
            return false;
        }
    } else {
        print '<form class="pledge" name="pledge" action="./" method="post"><input type="hidden" name="' . $type . '" value="' . $h_ref . '"><h2>Password Protected Pledge</h2><p>This pledge is password protected: please enter the password to proceed:</p>';
        print '<p><input type="password" name="pw" value=""><input type="submit" name="submit" value="Submit"></p>';
        print '</form>';
        return false;
    }
    return true;
}
    
?>
