<?
// db.php:
// Interface to database for PledgeBank
// TODO:  Perhaps get rid of this file, as PEAR's DB is good enough alone.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: db.php,v 1.10 2005-02-28 10:18:55 chris Exp $

require_once "DB.php";

/* db_connect
 * Connect a global handle to the database. */
function db_connect() {
    global $pbdb;

	$vars = array('hostspec'=>'HOST', 'port'=>'PORT', 'database'=>'NAME', 'username'=>'USER', 'password'=>'PASS');
	$connstr = array('phptype'=>'pgsql');
	foreach ($vars as $k => $v) {
		if (defined('OPTION_PB_DB_' . $v)) {
			$connstr[$k] = constant('OPTION_PB_DB_' . $v);
		}
	}
    $pbdb = DB::connect($connstr);

    if (DB::isError($pbdb)) {
        die($pbdb->getMessage());
    }
}

/* db_query QUERY PARAMETERS
 * Perform QUERY against the database. Values in the PARAMETERS array are
 * substituted for '?' in the QUERY. Returns a query object or dies on
 * failure. */
function db_query($query, $params = array()) {
    global $pbdb;
    if (!isset($pbdb))
        db_connect();
    $result = $pbdb->query($query, $params);
    if (DB::isError($result)) {
        die($result->getMessage().': "'.$result->getDebugInfo().'"');
    }
    return $result;
}

/* db_getOne QUERY PARAMETERS
 * Execute QUERY and return a single value of a single column. */
function db_getOne($query, $params = array()) {
    global $pbdb;
    if (!isset($pbdb))
        db_connect();
    $result = $pbdb->getOne($query, $params);
    if (DB::isError($result)) {
        die($result->getMessage().': "'.$result->getDebugInfo().'"');
    }
    return $result;
}

/* db_fetch_array QUERY
 * Fetch values of the next row from QUERY as an associative array from column
 * name to value. */
function db_fetch_array($q) {
    return $q->fetchRow(DB_FETCHMODE_ASSOC);
}

/* db_fetch_row QUERY
 * Fetch values of the next row from QUERY as an array. */
function db_fetch_row($q) {
    return $q->fetchRow(DB_FETCHMODE_ORDERED);
}

/* db_num_rows QUERY
 * Return the number of rows returned by QUERY. */
function db_num_rows($q) {
    return $q->numRows();
}

/* db_affected_rows QUERY
 * Return the number of rows affected by the most recent query. */
function db_affected_rows() {
    global $pbdb;
    if (!isset($pbdb))
        die("db_affected_rows called before any query made");
    return $pbdb->affectedRows();
}

?>
