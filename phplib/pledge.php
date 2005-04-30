<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.51 2005-04-30 15:54:29 francis Exp $
 * 
 */

require_once 'db.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/rabx.php';

class Pledge {
    // Associative array of parameters about the pledge, taken from database
    var $data;
    // Escaped ref used for URLs
    var $h_ref;
    
    // Construct from ref (TODO: probably can overload this for id constructor)
    function Pledge($ref) {
        $q = db_query('SELECT *, 
                           pb_current_date() <= pledges.date AS open,
                           (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
                       FROM pledges
                       WHERE ref ILIKE ?', array($ref));
        if (!db_num_rows($q))
            err('PledgeBank reference not known');
        $this->data = db_fetch_array($q);

        $this->_calc();
    }

    // Internal function to calculate some values from data
    function _calc() {
        $this->data['left'] = $this->data['target'] - $this->data['signers'];
        $this->data['confirmed'] = ($this->data['confirmed'] == 't');
        $this->data['open'] = ($this->data['open'] == 't');
        $this->h_ref = htmlspecialchars($this->data['ref']);

        // "Finished" means closed to new signers
        $finished = false;
        if (!$this->open())
            $finished = true;
        if ($this->left() <= 0)
            if ($this->exactly())
                $finished = true;
        $this->data['finished'] = $finished;

        // Check is confirmed
        if (!$this->data['confirmed'])
            err('PledgeBank reference not known');
    }

    // Basic data access
    function ref() { return $this->data['ref']; }
    function id() { return $this->data['id']; }
    function open() { return $this->data['open']; } // not gone past the deadline date
    function finished() { return $this->data['finished']; } // can take no more signers, for whatever reason
    function exactly() { return ($this->data['comparison'] == 'exactly'); }
    function has_details() { return $this->data['detail'] ? true : false; }

    function target() { return $this->data['target']; }
    function signers() { return $this->data['signers']; }
    function left() { return $this->data['left']; }

    function password() { return $this->data['password']; }

    // Basic data access for HTML display
    function h_title() { return htmlspecialchars($this->data['title']); }
    function h_name() { return htmlspecialchars($this->data['name']); }

    // Links.  The semantics here is that the URLs are all escaped, but didn't
    // need escaping.  They can safely be used in HTML or plain text.
    function url_main() { return "/" . $this->h_ref; }
    function url_email() { return "/" . $this->h_ref . "/email"; }
    function url_ical() { return "/" . $this->h_ref . "/ical"; }
    function url_flyers() { return "/" . $this->h_ref . "/flyers"; }
    function url_flyer($type) { return "/flyers/" . $this->h_ref . "_$type"; }
    function url_comments() { return "/" . $this->h_ref . "#comments"; }

    // Rendering the pledge in various ways

    // Draws a plaque containing the pledge.  $params is an array, which
    // can contain the following:
    //     showdetails - if present, show "details" field
    function render_box($params) {
        pledge_box($this->data, $this->signers(), $this->left(), $params);
    }
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
    if ($firstperson === "includename") {
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

function pledge_box($r, $curr='', $left='', $params = array()) { ?>
<div class="tips">
<p style="margin-top: 0">&quot;<?=pledge_sentence($r, array('firstperson'=>true, 'html'=>true)) ?>&quot;</p>
<p align="right">&mdash; <?=$r['name'].((isset($r['identity']) && $r['identity'])?', '.$r['identity']:'') ?></p>
<p>Deadline: <strong><?=prettify($r['date']) ?></strong>.
<? if ($curr !== '') { ?>
<i><?=prettify($curr) ?> <?=make_plural($curr, 'person has', 'people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target)':', '.prettify($left).' more needed') ?></i>
<? } ?>
</p>
<?
    if (array_key_exists('showdetails', $params) && isset($r['detail']) && $r['detail']) {
        $det = htmlspecialchars($r['detail']);
        # regexs here borrowed from TWFY
        preg_match_all("/((http(s?):\/\/)|(www\.))([a-zA-Z\d\_\.\+\,\;\?\%\~\-\/\#\='\*\$\!\(\)\&]+)([a-zA-Z\d\_\?\%\~\-\/\#\='\*\$\!\(\)\&])/", $det, $matches);
        foreach ($matches[0] as $match) {
            $newmatch = $match;
            if (substr($match,0,3)=='www') $newmatch = "http://$match";
            $det = str_replace($match, '<a href="'.$newmatch.'">'.$match.'</a>', $det);
        }
        $det = preg_replace("/([\w\.]+)(@)([\w\.\-]+)/i", "<a href=\"mailto:$0\">$0</a>", $det);
        $det = nl2br($det);
        print '<p align="left"><strong>More details</strong><br>' . $det . '</p>';
    }
    print '</div>';
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
   Calls check_password and if necessary returns HTML form for asking for password.
   Otherwise returns false.
       LINK url for password form to post back to
       REF pledge reference
       ACTUAL_PASSWORD actual password
*/
function deal_with_password($link, $ref, $actual) {
    if (check_password($ref, $actual)) {
        return false;
    }

    $html = "";
    if (get_http_var('pw')) {
        $html .= '<p class="finished">Incorrect password!</p>';
    }
    $html .= '<form class="pledge" name="pledge" action="'.$link.'" method="post"><h2>Password Protected Pledge</h2><p>This pledge is password protected.  Please enter the password to proceed.</p>';
    $html .= '<p><strong>Password:</strong> <input type="password" name="pw" value=""><input type="submit" name="submitpassword" value="Submit"></p>';
    $html .= '</form>';
    return $html;
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
    <input type="submit" name="submitbuttonform" value="<?=$text?>">
    </form>
<?
    } else {
?><a href="<?=$link?>" title="<?=$title?>"><?=$text?></a><?
    }
}

/* Sends a message to pledge creator with URL containing link
 * to let them make an announcement to all signers. */
function send_announce_token($pledge_id) {
    $max_circumstance = db_getOne("select max(circumstance_count) from message
        where pledge_id = ? and circumstance = ?", array($pledge_id, 'announce-post'));
    db_query("
            insert into message (
                pledge_id, circumstance, circumstance_count,
                sendtocreator, sendtosigners, sendtolatesigners,
                emailtemplatename
            ) values (
                ?, 'announce-post', ?,
                true, false, false,
                'announce-post'
            )", array($pledge_id, $max_circumstance + 1));
    db_commit();
}

/* Display form for pledge signing. */
function pledge_sign_box() {
    if (get_http_var('add_signatory'))
        $showname = get_http_var('showname') ? ' checked' : '';
    else
        $showname = ' checked';
?>
<form accept-charset="utf-8" class="pledgesign" name="pledge" action="/<?=htmlspecialchars(get_http_var('ref')) ?>/sign" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars(get_http_var('ref')) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('ref')) ?>">
<h2>Sign up now</h2>
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<p><b>
I, <input onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="name" id="name" value="<?=htmlspecialchars(get_http_var('name'))?>">,
sign up to the pledge.<br>Your email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>"></b>
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
</p>
<p><input type="checkbox" name="showname" value="1"<?=$showname?>> Show my name on this pledge </p>
<p><input type="submit" name="submit" value="Sign Pledge"> </p>
</form>

<? 

}
