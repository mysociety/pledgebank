<?

function db_connect() {
	$vars = array('host'=>'HOST', 'port'=>'PORT', 'dbname'=>'NAME', 'user'=>'USER', 'password'=>'PASS');
	$connstr = array();
	foreach ($vars as $k => $v) {
		if ($c = constant('OPTION_PB_DB_' . $v)) {
			$connstr[] = $k . '=' . $c;
		}
	}
	$connstr = join(' ',$connstr);
	return pg_connect($connstr);
}

function db_query($query) {
	$result = pg_query($query);
	return $result;
}

function db_fetch_array($q) {
	return pg_fetch_array($q);
}

function db_fetch_row($q) {
	return pg_fetch_row($q);
}

function db_num_rows($q) {
	return pg_num_rows($q);
}

function db_affected_rows($q) {
	return pg_affected_rows($q);
}

function db_error() {
	return pg_last_error(); # TODO: Should do pg_result_error and so on instead
}

function db_close() {
	return pg_close();
}

function db_data_seek($q, $n) {
	pg_result_seek($q, $n);
}

?>
