<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.3 2005-03-07 16:24:14 chris Exp $
 * 
 */

require_once 'db.php';
require_once '../../phplib/utility.php';

/* pledge_confirmation_token
 * Return a random confirmation token. */
function pledge_confirmation_token() {
    return implode("-", array_map('bin2hex', array(random_bytes(4), random_bytes(4))));
}

/* pledge_sign PLEDGE SIGNER SHOWNAME EMAIL
 * Add the named SIGNER to the PLEDGE. SHOWNAME indicates whether their name
 * should be displayed publically; EMAIL is their email address. It is the
 * caller's responsibility to check that the signer is authorised to sign a
 * private pledge (by supplying a password, presumably). On success returns the
 * signer ID and confirmation token; on failure returns an error message. This
 * function does not commit its changes. */
function pledge_sign($pledge_id, $name, $showname, $email) {
    $r = db_getRow('
            select id, target, title, email, confirmed, date, comparison
            from pledges
            where id = ?
            for update
        ', $pledge_id);
    if (!$r)
        return "No pledge with ID '$pledge_id'";
    else if ($r['confirmed'] != 't')
        return "Pledge $pledge_id has not been confirmed by its creator";
    else if ($email == $r['email'])
        return "'$email' is the creator of the pledge, and cannot sign it again";
    else if ($r['date'] < date('Y-m-d'))
        return "Pledge $pledge_id is closed";

    if ($r['comparison'] == 'exactly') {
        /* Need to count people in the signers table. We can't do
         *  select count(*) from signers where ... for update
         * so lock the whole table instead, using some PG-specific syntax. I
         * love standards -- there are so many to choose from. */
        db_query('lock table signers in row share mode');
        $num = db_getOne('
                select count(id)
                from signers
                where pledge_id = ? and confirmed
            ', $pledge_id);
        if ($num >= $r['target'])
            return "Pledge $pledge_id has already reached its target";
    }

    if (!is_null(db_getOne('
                select email from signers
                where pledge_id = ? and email = ?
            ', array($pledge_id, $email))))
        return "'$email' has already signed pledge $pledge_id";

    $token = pledge_confirmation_token();

    $id = db_getOne("select nextval('signers_id_seq')");
    if (!isset($id))
        return "Database error getting new signer ID";

    if (!db_query('
                insert into signers (
                    id,
                    pledge_id,
                    name, email, showname,
                    signtime,
                    token, confirmed
                ) values (
                    ?,
                    ?,
                    ?, ?, ?,
                    current_timestamp,
                    ?, false
                )', array(
                    $id,
                    $pledge_id,
                    $name, $email, $showname ? 't' : 'f',
                    #
                    $token)))
        return "Unable to insert signer into database"; /* XXX should get an error message, somehow */

    /* Done. */
    return array($id, $token);
}

?>
