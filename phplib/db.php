<?
// db.php:
// Interface to database for PledgeBank
// TODO:  Perhaps get rid of this file, as PEAR's DB is good enough alone.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: db.php,v 1.9 2005-02-22 09:20:56 francis Exp $

require_once "DB.php";

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

function db_query($query, $params = array()) {
    global $pbdb;
    $result = $pbdb->query($query, $params);
    if (DB::isError($result)) {
        die($result->getMessage().': "'.$result->getDebugInfo().'"');
    }
    return $result;
}

function db_getOne($query, $params = array()) {
    global $pbdb;
    $result = $pbdb->getOne($query, $params);
    if (DB::isError($result)) {
        die($result->getMessage().': "'.$result->getDebugInfo().'"');
    }
    return $result;
}

function db_fetch_array($q) {
	return $q->fetchRow(DB_FETCHMODE_ASSOC);
}

function db_fetch_row($q) {
	return $q->fetchRow(DB_FETCHMODE_ORDERED);
}

function db_num_rows($q) {
	return $q->numRows();
}

function db_affected_rows() {
    global $pbdb;
    return $pbdb->affectedRows();
}

?>
