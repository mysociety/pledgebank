<?php
/*
 * person.php:
 * An individual user for the purpose of login etc.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: person.php,v 1.1 2005-05-18 12:56:56 chris Exp $
 * 
 */

require_once '../../phplib/error.php';
require_once '../../phplib/utility.php';
require_once '../phplib/stash.php';

class Person {
    /* person ID | EMAIL
     * Given a person ID or EMAIL address, return a person object describing
     * their account. */
    function Person($id) {
        if (preg_match('/@/', $id))
            $this->id = db_getOne('select id from person where email = ? for update', $email);
        else if (preg_match('/^[1-9]\d*$/', $id))
            $this->id = db_getOne('select id from person where id = ? for update', $id);
        else
            err('value passed to person constructor must be person ID or email address');
        if (is_null($this->id))
            err("No such person '$id'");
        $this->email = db_getOne('select email from person where id = ?', $id);
        $this->name = db_getOne('select name from person where id = ?', $id);
    }

    /* id [ID]
     * Get or set the person ID. */
    function id() {
        return $this->id;
    }

    /* email [EMAIL]
     * Get or set the person's EMAIL address. */
    function email($email = null) {
        if (!is_null($email)) {
            db_query('update person set email = ? where id = ?', array($email, $this->id));
            $this->email = $email;
        }
        return $this->email;
    }

    /* name [NAME]
     * Get or set the person's NAME. */
    function name($name = null) {
        if (!is_null($name)) {
            db_query('update person set name = ? where id = ?', array($name, $this->id));
            $this->name = $name;
        }
        return $this->name;
    }

    /* password PASSWORD
     * Set the person's PASSWORD. */
    function password($password) {
        if (is_null($password))
            err("PASSWORD must not be null in password method");
        db_query('update person set password = ? where id = ?', array(crypt($password), $this->id));
    }

    /* check_password PASSWORD
     * Return true if PASSWORD is the person's password, or false otherwise. */
    function check_password($p) {
        $c = db_getOne('select password from person where id = ?', $this->id);
        if (is_null($c))
            return false;
        else if (crypt($p, $c) != $c)
            return false;
        else
            return true;
    }
}

/* person_cookie_token ID
 * Return an opaque version of ID to identify a person in a cookie. */
function person_cookie_token($id) {
    if (!preg_match('/^[1-9]\d*$/', $id))
        err("ID should be a decimal integer, not '$id'");
    $salt = bin2hex(random_bytes(8));
    $sha = sha1("$id/$salt/" . db_secret());
    return sprintf('%d/%s/%s', $id, $salt, $sha);
}

/* person_check_cookie_token TOKEN
 * Given TOKEN, allegedly representing a person, test it and return the
 * associated person ID if it is valid, or null otherwise. On successful
 * return from this function the database row identifying the person will
 * have been locked with SELECT ... FOR UPDATE. */
function person_check_cookie_token($token) {
    $a = array();
    if (!preg_match('#^([1-9]\d*)/([0-9a-f]+)/([0-9a-f]+)$#', $token, $a))
        return null;
    list($x, $id, $salt, $sha) = $a;
    if (sha1("$id/$salt/" . db_secret()) != $sha)
        return null;
    else if (is_null(db_getOne('select id from person where id = ? for update', $id)))
        return null;
    else
        return $id;
}

/* person_signon REASON EMAIL [NAME]
 * Return a record of a person, if necessary requiring them to sign on to an
 * existing account or to create a new one. REASON is the reason why
 * authentication is required, for instance "create the pledge '...'" or
 * "sign the pledge '...'" or */
function person_signon($reason, $email, $name = null) {
    if (array_key_exists('pb_person_id', $_COOKIE)) {
        /* User has a cookie and may be logged in. */
        $id = person_check_cookie_token($_COOKIE['pb_person_id']);
print "id = $id";
        if (!is_null($id))
            return new Person($id);
    }

    if (headers_sent())
        err("Headers have already been sent in person_signon without cookie being present");

    /* No or invalid cookie. We will need to redirect the user via another
     * page, either to log in or to prove their email address. */
    $st = stash_request($reason);
    db_commit();
    header("Location: login.php?stash=$st&email=" . urlencode($email) . "&name=" . urlencode($name));
    exit();
}

/* person_get EMAIL
 * Return a person object for the account with the given EMAIL address, if one
 * exists, or null otherwise. */
function person_get($email) {
    $id = db_getOne('select id from person where email = ? for update', $email);
    if (is_null($id))
        return null;
    else
        return new Person($id);
}

/* person_get_or_create EMAIL NAME
 * If there is an existing account for the given EMAIL address, return the
 * person object describing it. Otherwise, create a new account for EMAIL and
 * NAME, and return the object describing it. */
function person_get_or_create($email, $name) {
    if (is_null($email) || is_null($name))
        err('EMAIL or NAME null in person_get_or_create');
        /* XXX case-insensitivity of email addresses? */
    $id = db_getOne('select id from person where email = ? for update', $email);
    if (is_null($id)) {
        $id = db_getOne("select nextval('person_id_seq')");
        db_query('insert into person (id, email, name) values (?, ?, ?)', array($id, $email, $name));
    }
    return new Person($id);
}

?>
