<?php
//++++++++++++++++++++++++++++++++++++++++++++++++++++
//You MUST change this value.
$sqlite = array(
	'path'   => '../sample/sample.sqlite',
);
//++++++++++++++++++++++++++++++++++++++++++++++++++++


//****************************************************
//Connect to Database.
//****************************************************
$sqlite['resource'] = sqlite_open($sqlite['path'],'0600');


if (isset($_GET['page_num'])) {
	//****************************************************
	//Parameters from JavaScript.
	//****************************************************
	$param = array(
		'db_table'     => sqlite_escape_string($_GET['db_table']),
		'page_num'     => sqlite_escape_string($_GET['page_num']),
		'per_page'     => sqlite_escape_string($_GET['per_page']),
		'and_or'       => sqlite_escape_string($_GET['and_or']),
		'order_by'     => sqlite_escape_string($_GET['order_by']),
		'order_field'  => array(),
		'search_field' => array(),
		'q_word'       => array()
	);
	$esc = array('order_field', 'search_field', 'q_word');
	for ($i=0; $i<count($esc); $i++) {
		for ($j=0; $j<count($_GET[$esc[$i]]); $j++) {
			$param[$esc[$i]][$j] = sqlite_escape_string($_GET[$esc[$i]][$j]);
		}
	}


	//****************************************************
	//Create a SQL. (shared by MySQL and SQLite)
	//****************************************************
	//----------------------------------------------------
	// WHERE
	//----------------------------------------------------
	$depth1 = array();
	for($i = 0; $i < count($param['q_word']); $i++){
		$depth2 = array();
		for($j = 0; $j < count($param['search_field']); $j++){
			$depth2[] = "\"{$param['search_field'][$j]}\" LIKE '%{$param['q_word'][$i]}%' ";
		}
		$depth1[] = '(' . join(' OR ', $depth2) . ')';
	}
	$param['where'] = join(" {$param['and_or']} ", $depth1);

	//----------------------------------------------------
	// ORDER BY
	//----------------------------------------------------
	$str = '(CASE ';
	for ($i = 0, $j = 0; $i < count($param['q_word']); $i++) {
		for ($k = 0; $k < count($param['order_field']); $k++) {
			$str .= "WHEN \"{$param['order_field'][$k]}\" LIKE '{$param['q_word'][$i]}' ";
			$str .= "THEN $j ";
			$j++;
			$str .= "WHEN \"{$param['order_field'][$k]}\" LIKE '{$param['q_word'][$i]}%' ";
			$str .= "THEN $j ";
			$j++;
		}
	}
	$param['orderby'] = $str . "ELSE $j END) {$param['order_by']}";

	//----------------------------------------------------
	// OFFSET
	//----------------------------------------------------
	$param['offset']  = ($param['page_num'] - 1) * $param['per_page'];


	$query = sprintf(
		"SELECT * FROM \"%s\" WHERE %s ORDER BY %s LIMIT %s OFFSET %s",
		$param['db_table'],
		$param['where'],
		$param['orderby'],
		$param['per_page'],
		$param['offset']		
	);

	//****************************************************
	//Query database
	//****************************************************
	//In the end, return this array to JavaScript.
	$return = array();

	//----------------------------------------------------
	//Search
	//----------------------------------------------------
	$rows = sqlite_query($sqlite['resource'], $query);
	while ($row = sqlite_fetch_array($rows, SQLITE_ASSOC)) {
		$return['result'][] = $row;
	}
	//----------------------------------------------------
	//Whole count
	//----------------------------------------------------
	$query = "SELECT COUNT(*) FROM \"{$param['db_table']}\" WHERE {$param['where']}";
	$rows = sqlite_query($sqlite['resource'], $query);
	while ($row = sqlite_fetch_array($rows, SQLITE_NUM)) {
		$return['cnt_whole'] = $row[0];
	}
	//****************************************************
	//Return
	//****************************************************
	echo json_encode($return);

} else {
	//****************************************************
	//Parameters from JavaScript.
	//****************************************************
	$param = array(
		'db_table'  => sqlite_escape_string($_GET['db_table']),
		'pkey_name' => sqlite_escape_string($_GET['pkey_name']),
		'pkey_val'  => sqlite_escape_string($_GET['pkey_val'])
	);
	//****************************************************
	//get initialize value
	//****************************************************
	$query = sprintf(
		"SELECT * FROM \"%s\" WHERE \"%s\" = '%s'",
		$param['db_table'],
		$param['pkey_name'],
		$param['pkey_val']
	);
	$rows  = sqlite_query($sqlite['resource'], $query);
	while ($row = sqlite_fetch_array($rows, SQLITE_ASSOC)) echo json_encode($row);
}


//****************************************************
//End
//****************************************************
sqlite_close($sqlite['resource']);
?>
