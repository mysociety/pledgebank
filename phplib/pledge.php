<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.39 2005-04-11 13:32:15 francis Exp $
 * 
 */

require_once 'db.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/rabx.php';

/* pledge_ab64_encode DATA
 * Return a "almost base64" encoding of DATA (a six-bit encoding of
 * URL-friendly characters; specifically the encoded data match
 * /^[0-9A-Za-z._-]+$/). */
function pledge_ab64_encode($i) {
    $t = base64_encode($i);
    $t = str_replace("+", ".", &$t);
    $t = str_replace("/", "_", &$t);
    $t = str_replace("=", "-", &$t);
    return $t;
}

/* pledge_token_store SCOPE DATA
 * Returns a randomly generated token, suitable for use in URLs. SCOPE is the
 * associated scope. DATA (of arbitrary, non-object type) are serialised and
 * stored in the database associated with that scope and token, for later
 * retrieval with pledge_random_token_retrieve. */
function pledge_token_store($scope, $data) {
    $token = pledge_ab64_encode(random_bytes(12));
    $ser = '';
    rabx_wire_wr($data, $ser);
    db_query('
            insert into token (scope, token, data, created)
            values (?, ?, ?, pb_current_timestamp())', array($scope, $token, $ser));
    return $token;
}

/* pledge_token_retrieve SCOPE TOKEN
 * Given a TOKEN returned by pledge_random_token_store for the given SCOPE,
 * return the DATA associated with it, raising an error if there isn't one. */
function pledge_token_retrieve($scope, $token) {
    $data = db_getOne('
                    select data
                    from token
                    where scope = ? and token = ?', array($scope, $token));

    $pos = 0;
    $res = rabx_wire_rd(&$data, &$pos);
    if (rabx_is_error($res)) {
        $res = unserialize($data);
        if (is_null($res))
            err("Data for scope '$scope', token '$token' are not valid");
    }

    return $res;
}

/* pledge_token_destroy SCOPE TOKEN
 * Delete any data associated with TOKEN in the given SCOPE. */
function pledge_token_destroy($scope, $token) {
    db_query('delete from token where scope = ? and token = ?',
            array($scope, $token));
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
     * long (at some marginal risk to security...).  Base64 uses the character
     * set [A-za-z0-9+/=]. "+", "/" and "=" are poor characters for URLs,
     * because they have special interpretations. So use others instead. */
    $b64 = pledge_ab64_encode(substr(&$h, 0, 12));

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

/* pledge_sentence PLEDGE PARAMS
 * Return a sentence describing what each signer agrees to do ("$pledgecreator
 * will ...  if ...").  PLEDGE is either a pledge id number, or an array of
 * pledge data from the database.  
 * If PARAMS['firstperson'] is true, then the sentence is "I will...", if
 # it is 'includename', says "I, $pledgecreator, will..."
 * If PARAMS['html'] is true, encode entities and add <strong> tags around
 * strategic bits. 
 * If PARAMS['href'] contains a URL, then the main part of the returned
 * sentence will be a link to that URL escaped.
 * XXX i18n -- this won't work at all in other languages */
function pledge_sentence($r, $params = array()) {
    $html = array_key_exists('html', $params) ? $params['html'] : false;
    $firstperson = array_key_exists('firstperson', $params) ? $params['firstperson'] : false;
    
    if (!is_array($r))
        $r = db_getRow('select * from pledges where id = ?', $r);
    if (!$r)
        err(pledge_strerror(PLEDGE_NONE));

    if ($html)
        $r = array_map('htmlspecialchars', $r);
        
    $s = ($firstperson ? "I" : $r['name']);
    if ($firstperson == "includename") {
        $s .= ", " . $r['name'] . ",";
    }
    $s .= " will ";
    if (array_key_exists('href', $params)) {
            $s .= "<a href=\"".urlencode($params['href'])."\">"
            . $r['title'] . "</a>";
    } else {
            $s .= "<strong>" . $r['title'] . "</strong>";
    }
    $s .= " but only if "
            . '<strong>';
    $s .= prettify($r['target'])
            . '</strong>'
            . " ${r['type']} will "
            . ($r['signup'] == 'do the same' ? 'too' : $r['signup'])
            . ".";
    if (!$html or array_key_exists('href', $params))
        $s = preg_replace('#</?strong>#', '', $s);

    // Tidy up
    $s = preg_replace('#\.\.#', '.', $s);

    return $s;
}

/* get_flyer_text PLEDGE [PARAMS]
 * Given a pledge data associated array, returns the text for use on flyer.
 */
function get_flyer_text($r, $params = array()) {
    $html = pledge_sentence($r, array('firstperson'=>'includename', 'html'=>true));
    $html .= '<p>Please support me by signing up, and by encouraging
        other people to do the same. I am using the charitable service
        PledgeBank.com to gather support.</p>
    
        <p>It will only take you a few seconds - sign up free at ';
    $html .= '<strong>www.pledgebank.com/' .  htmlspecialchars($r['ref']) . "</strong>";
    $html .= '<p>Or text <strong>';
    $html .= 'pledge ' . htmlspecialchars($r['ref']);
    $html .= '</strong>  to <strong>' . OPTION_PB_SMS_DISPLAY_NUMBER . '</strong> (cost 50p)';
    $html .= '<p>This pledge closes on ' . prettify($r['date']). '. ';
    $html .= 'Thanks!';
    return $html;
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

/* pledge_dbresult_to_code RESULT
 * Convert a string result from the database (e.g. 'ok', 'none', etc.) into a
 * PLEDGE_... code. */
function pledge_dbresult_to_code($r) {
    $resmap = array(
            'ok' => PLEDGE_OK,
            'none' => PLEDGE_NONE,
            'finished' => PLEDGE_FINISHED,
            'signed' => PLEDGE_SIGNED,
            'full' => PLEDGE_FULL,
        );
    if (array_key_exists($r, $resmap))
        return $resmap[$r];
    else
        err("Bad result $r in pledge_dbresult_to_code");
}

/* pledge_is_valid_to_sign PLEDGE EMAIL MOBILE
 * Return true if EMAIL/MOBILE may validly sign PLEDGE. This function locks
 * rows in pledges and signers with select ... for update / lock tables. */
function pledge_is_valid_to_sign($pledge_id, $email, $mobile = null) {
    return pledge_dbresult_to_code(
                db_getOne('select pledge_is_valid_to_sign(?, ?, ?)',
                    array($pledge_id, $email, $mobile))
            );
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
                set confirmed = true, creationtime = pb_current_timestamp()
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
                    pb_current_timestamp()
                )', array(
                    $id,
                    $pledge_id,
                    $name, $email, $showname ? 't' : 'f')
                ))
        return PLEDGE_ERROR;

    /* Done. */
    return $id;
}

/* check_password REF ACTUAL_PASSWORD
   Checks to see if password submitted is correct, returns true if it
   is and false for wrong or no password.  */
function check_password($ref, $actual) {
    $raw = get_http_var('pw');
    $entered = $raw ? sha1($raw) : $raw;
    if (!$actual) 
        return true;

    if ($entered) {
        if ($entered == $actual) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/* deal_with_password LINK REF ACTUAL_PASSWORD
   Calls check_password and if necessary displays form for entering the password.
   LINK url for password form to post back to
   REF pledge reference
   ACTUAL_PASSWORD actual password
  XXX: Doesn't work with non-index.php pages yet!
*/
function deal_with_password($link, $ref, $actual) {
    if (check_password($ref, $actual)) {
        return true;
    }

    if (get_http_var('pw')) {
        print '<p class="finished">Incorrect password!</p>';
    }

    print '<form class="pledge" name="pledge" action="'.$link.'" method="post"><h2>Password Protected Pledge</h2><p>This pledge is password protected.  Please enter the password to proceed.</p>';
    print '<p><strong>Password:</strong> <input type="password" name="pw" value=""><input type="submit" name="submitpassword" value="Submit"></p>';
    print '</form>';
    return false;
}


/* print_link_with_password
   Prints out a link, normally just using <a href=...>.  Title is for
   the title= attribute, and text is the actual text body of the link.
   If this page has a password, then instead of a link prints a button which
   also transmits the passowrd to the link page.  Text to this function
   should be already escaped, or not need escaping, for display in URLs or
   HTML.*/
function print_link_with_password($link, $title, $text) {
    if (get_http_var('pw')) {
?> 
    <form class="buttonform" name="buttonform" action="<?=$link?>" method="post" title="<?=$title?>">
    <input type="hidden" name="pw" value="<?=htmlspecialchars(get_http_var('pw'))?>">
    <input type="submit" name="submit" value="<?=$text?>">
    </form>
<?
    } else {
?><a href="<?=$link?>" title="<?=$title?>"><?=$text?></a><?
    }
}

