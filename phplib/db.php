<?

function db_connect() {
	if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
		$db = @mysql_connect();
		mysql_select_db('pb');
	} else {
		$db = @mysql_connect('mysql.netweaver.net', 'dracos', '*******');
		mysql_select_db('dracos');
	}
	return $db;
}

function db_query($query) {
	$result = mysql_query($query);
	return $result;
}

function db_fetch_array($q) {
	return mysql_fetch_array($q);
}

function db_fetch_row($q) {
	return mysql_fetch_row($q);
}

function db_num_rows($q) {
	return mysql_num_rows($q);
}

function db_affected_rows($q='') {
	return ($q) ? mysql_affected_rows($q) : mysql_affected_rows();
}

function db_error() {
	return mysql_error();
}

function db_close() {
	mysql_close();
}

function db_data_seek($q, $n) {
	mysql_data_seek($q, $n);
}

?>