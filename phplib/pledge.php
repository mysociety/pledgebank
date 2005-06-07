<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.79 2005-06-07 00:29:03 francis Exp $
 * 
 */

require_once 'db.php';
require_once 'fns.php';
require_once 'person.php';

require_once '../../phplib/utility.php';
require_once '../../phplib/rabx.php';

class Pledge {
    // Associative array of parameters about the pledge, taken from database
    var $data;
    // Escaped ref used for URLs
    var $h_ref;

    // Construct from either:
    // - string, a PledgeBank reference
    // - integer, the internal id from the pledges table
    // - array, a dictionary of data about the pledge
    function Pledge($ref) {
        $main_query_part = "SELECT pledges.*, 
                               pb_current_date() <= pledges.date AS open,
                               (SELECT count(*) FROM signers WHERE 
                                    signers.pledge_id = pledges.id) AS signers,
                               person.email AS email
                           FROM pledges
                           LEFT JOIN person ON person.id = pledges.person_id
                           WHERE confirmed ";
        if (gettype($ref) == "string") {
            $q = db_query("$main_query_part AND ref ILIKE ?", array($ref));
            if (!db_num_rows($q))
                err('PledgeBank reference not known');
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "integer") {
            $q = db_query("$main_query_part AND pledges.id = ?", array($ref));
            if (!db_num_rows($q))
                err('PledgeBank reference not known');
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "array") {
            $this->data = $ref;
        } else {
            err("Unknown type '" . gettype($ref) . "' to Pledge constructor");
        }

        $this->_calc();
    }

    /* lock
     * Lock a pledge in the database using SELECT ... FOR UPDATE. */
    function lock() {
        if (!array_key_exists('id', $this->data))
            err("Pledge is not present in database");
        else {
            /* Now we have to grab the data again, since it may have changed
             * since the constructor was called. */
            $d = db_getRow('
                        select *,
                            (select count(id) from signers
                                where signers.pledge_id = pledges.id)
                                    as signers
                        from pledges
                        where id = ?
                        for update of pledges', $this->data['id']);
            foreach ($d as $k => $v)
                $this->data[$k] = $v;
        }
    }

    // Internal function to calculate some values from data
    function _calc() {
        // Fill in partial pledges (ones being made still)
        if (!array_key_exists('signers', $this->data)) $this->data['signers'] = -1;
        if (!array_key_exists('confirmed', $this->data)) $this->data['confirmed'] = 'f';
        if (!array_key_exists('open', $this->data)) $this->data['open'] = 't';

        // Some calculations 
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
    }

    // Basic data access
    function ref() { return $this->data['ref']; }
    function id() { return $this->data['id']; }
    function open() { return $this->data['open']; } // not gone past the deadline date
    function finished() { return $this->data['finished']; } // can take no more signers, for whatever reason
    function succeeded() {
        return pledge_is_successful($this->id());
    }

    function failed() {
        return $this->finished() && !$this->succeeded();
    }
    
    function exactly() { return ($this->data['comparison'] == 'exactly'); }
    function has_details() { return $this->data['detail'] ? true : false; }

    function target() { return $this->data['target']; }
    function signers() { return $this->data['signers']; }
    function left() { return $this->data['left']; }

    function creator() { return new person($this->data['person_id']); }
    function creator_email() { return $this->data['email']; }
    function creator_name() { return $this->data['name']; }
    function creator_id() { return $this->data['person_id']; }

    function creationtime() { return $this->data['creationtime']; }
    function creationdate() { return substr($this->data['creationtime'], 0, 10); }

    function date() { return $this->data['date']; }

    function pin() { return $this->data['pin']; }

    function title() { return $this->data['title']; }
    function type() { return $this->data['type']; }

    function has_picture() { return array_key_exists('picture', $this->data) && $this->data['picture']; }
    
    function categories() {
        $c = array();
        $q = db_query('select category_id, category.name from pledge_category, category where pledge_id = ? and category_id = category.id', $this->id());
        while ($r = db_fetch_row($q))
            $c[$r[0]] = $r[1];
        return $c;
    }

    // Basic data access for HTML display
    function h_title() { return htmlspecialchars($this->data['title']); }
    function h_name() { return htmlspecialchars($this->data['name']); }
    function h_name_and_identity() {
        return $this->h_name().
                ((isset($this->data['identity']) && $this->data['identity']) ? 
                    ', '. htmlspecialchars($this->data['identity'])
                    : '');
    }
    function h_pretty_date() { return prettify(htmlspecialchars($this->data['date'])); }

    // Links.  The semantics here is that the URLs are all escaped, but didn't
    // need escaping.  They can safely be used in HTML or plain text.
    function url_main() { return "/" . $this->h_ref; }
    function url_email() { return "/" . $this->h_ref . "/email"; }
    function url_ical() { return "/" . $this->h_ref . "/ical"; }
    function url_flyers() { return "/" . $this->h_ref . "/flyers"; }
    function url_flyer($type) { return "/flyers/" . $this->h_ref . "_$type"; }
    function url_comments() { return "/" . $this->h_ref . "#comments"; }
    function url_picture() { return "/" . $this->h_ref . "/picture"; }
    function url_announce() { return "/" . $this->h_ref . "/announce"; }
    function url_info() { return "/" . $this->h_ref . "/info"; }

    // Rendering the pledge in various ways

    // Draws a plaque containing the pledge.  $params is an array, which
    // can contain the following:
    //     showdetails - if present and true, show "details" field
    function render_box($params = array()) {
?>
<div id="pledge">
<p style="margin-top: 0">
<? if ($this->has_picture()) { print "<img class=\"creatorpicture\" src=\"".$this->data['picture']."\" alt=\"\">"; } ?>
&quot;<?=pledge_sentence($this->data, array('firstperson'=>true, 'html'=>true)) ?>&quot;</p>
<p align="right">&mdash; <?=$this->h_name_and_identity() ?></p>
<p>Deadline: <strong><?=$this->h_pretty_date()?></strong>.
<? if ($this->signers() >= 0) { ?>
<i><?=prettify($this->signers()) ?> <?=make_plural($this->signers(), 'person has', 'people have') ?> signed up<?=($this->left()<0?' ('.prettify(-$this->left()).' over target)':', '.prettify($this->left()).' more needed') ?></i>
<? } ?>
</p>
<?
        if (array_key_exists('showdetails', $params) && $params['showdetails'] && isset($this->data['detail']) && $this->data['detail']) {
            $det = htmlspecialchars($this->data['detail']);
            $det = make_clickable($det);
            $det = nl2br($det);
            print '<p align="left"><strong>More details</strong><br>' . $det . '</p>';
        }
?>
</div>
<?
    }

    function sentence($params = array()) {
        return pledge_sentence($this->data, $params);
    }

    function h_sentence($params = array()) {
        $params['html'] = true;
        return pledge_sentence($this->data, $params);
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
 * Return a PLEDGE_... code describing whether EMAIL/MOBILE may validly sign
 * PLEDGE. This function locks rows in pledges and signers with select ... for
 * update / lock tables. */
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

/* check_pin REF ACTUAL_PIN
   Checks to see if PIN submitted is correct, returns true if it is and false
   for wrong or no PIN.  */
function check_pin($ref, $actual) {
    $raw = get_http_var('pin');
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

/* deal_with_pin LINK REF ACTUAL_PIN
   Calls check_pin and if necessary returns HTML form for asking for pin.
   Otherwise returns false.
       LINK url for pin form to post back to
       REF pledge reference
       ACTUAL_PIN actual pin
*/
function deal_with_pin($link, $ref, $actual) {
    if (check_pin($ref, $actual)) {
        return false;
    }

    $html = "";
    if (get_http_var('pin')) {
        $html .= '<p class="finished">Incorrect PIN!</p>';
    }
    $html .= '<form class="pledge" name="pledge" action="'.$link.'" method="post"><h2>PIN Protected Pledge</h2><p>This pledge is protected.  Please enter the PIN to proceed.</p>';
    $html .= '<p><strong>PIN:</strong> <input type="pin" name="pin" value=""><input type="submit" name="submitpin" value="Submit"></p>';
    $html .= '</form>';
    return $html;
}


/* print_link_with_pin
   Prints out a link, normally just using <a href=...>.  Title is for
   the title= attribute, and text is the actual text body of the link.
   If this page has a PIN, then instead of a link prints a button which
   also transmits the passowrd to the link page.  Text to this function
   should be already escaped, or not need escaping, for display in URLs or
   HTML.*/
function print_link_with_pin($link, $title, $text) {
    if (get_http_var('pin')) {
?> 
    <form class="buttonform" name="buttonform" action="<?=$link?>" method="post" title="<?=$title?>">
    <input type="hidden" name="pin" value="<?=htmlspecialchars(get_http_var('pin'))?>">
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

    $email = get_http_var('email');
    $name = get_http_var('name');

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
        if (is_null($name) || !$name)
            $name = $P->name();
    } else {
        // error_log("nobody signed on");
    }

    // error_log("$email $name");
?>
<form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=htmlspecialchars(get_http_var('ref')) ?>/sign" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars(get_http_var('ref')) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('ref')) ?>">
<h2>Sign up now</h2>
<? if (get_http_var('pin')) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin')).'">'; ?>
<p><b>
I, <input onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="name" id="name" value="<?=htmlspecialchars($name)?>">,
sign up to the pledge.<br>Your email: <input type="text" size="30" name="email" value="<?=htmlspecialchars($email) ?>"></b>
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
</p>
<p><input type="checkbox" name="showname" value="1"<?=$showname?>> Show my name on this pledge </p>
<p><input type="submit" name="submit" value="Sign Pledge"> </p>
</form>

<? 

}

/* post_confirm_advertise PLEDGE_ROW
   Print relevant advertising */
function post_confirm_advertise($pledge) {
    $local = pledge_is_local($pledge->data);
    if ($local) {
        post_confirm_advertise_flyers($pledge->data);
    } else {
        post_confirm_advertise_sms($pledge->data);
        view_friends_form($pledge);
    }
}

/* post_confirm_advertise_flyers PLEDGE_ROW
 * Print some stuff advertising flyers for PLEDGE. */
function post_confirm_advertise_flyers($r) {
    $png_flyers8_url = new_url("/flyers/{$r['ref']}_A4_flyers8.png", false);

    ?>
<p class="noprint" align="center"><big><strong>
You will massively increase the chance of this pledge succeeding if you
<?
    if (!$r['pin']) {
        print_this_link("print this page out", "");
        ?>
    (or use <a href="/<?=htmlspecialchars($r['ref']) ?>/flyers">these more attractive PDF and RTF (Word) versions</a>),
<?
   } else {
        // TODO - we don't have the PIN raw here, but really want it on
        // form to pass on for link to flyers page.  Not sure how best to fix
        // this up.
        print_link_with_pin("/".htmlspecialchars($r['ref'])."/flyers", "", "print these pages out");
        print ",";
   }
?>
 cut up the flyers and stick them through your neighbours' letterboxes. We
cannot emphasise this enough &mdash; print them NOW and post them next time you
go out to the shops or your pledge is unlikely to succeed.</strong></big>
</p>
<? 
    // Show inline graphics only for PINless pledges (as PNG doesn't
    // work for the PIN protected ones, you can't POST a PIN
    // into an IMG SRC= link)
    if (!$r['pin']) { ?>
<p align="center"><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="Graphic of flyers for printing"></a></p>
<?  }
    post_confirm_advertise_sms($r);
}

/* post_confirm_advertise_sms PLEDGE_ROW
 * Prints some stuff about SMS for PLEDGE.
 * Only for PINless pledges, since private pledges can't be signed by SMS. */
function post_confirm_advertise_sms($r) {
    if (!$r['pin']) {
?>
<p class="noprint"><strong>Take your Pledge to the pub</strong> &ndash; next time you're out and about,
get your friends to sign up by having them text
<strong>pledge&nbsp;<?=htmlspecialchars($r['ref'])?></strong> to <strong>60022</strong>.
The text costs your normal rate, and we'll keep them updated about progress via their
mobile.</p>

<? # Below not needed as we're currently not charging premium rate
/* <p class="noprint" style="text-align: center;"><small>The small print: operated by
mySociety, a project of UK Citizens Online Democracy. Sign-up message costs
your normal text rate. Further messages from us are free.
Questions about this SMS service? Call us on 08453&nbsp;330&nbsp;160 or
email <a href="mailto:team@pledgebank.com">team@pledgebank.com</a>.</small></p> */
?>
<?   }
}



/* pledge_delete_pledge ID
 * Delete the pledge with the given ID, and all its signers and comments. */
function pledge_delete_pledge($id) {
    db_query('select pb_delete_pledge(?)', $id);
}

/* pledge_delete_signer ID
 * Delete the siger with the given ID. */
function pledge_delete_signer($id) {
    db_query('select pb_delete_signer(?)', $id);
}

/* pledge_delete_comment ID
 * Delete the comment with the given ID. */
function pledge_delete_comment($id) {
    db_query('select pb_delete_comment(?)', $id);
}

/* pledge_is_local R
 * Given pledge data, returns true if local pledge (where flyers
 * are useful), or false if it isn't. */
function pledge_is_local($r) {
    return $r['country'] == 'UK' && $r['postcode'];
}

?>
