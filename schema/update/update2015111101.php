<?php
require_once '../../db.php';

$sqls = array();
$sql = 'create table if not exists xxt_merchant_product_gensku_log(';
$sql .= 'id int not null auto_increment';
$sql .= ',mpid varchar(32) not null';
$sql .= ',sid int not null'; // shop id
$sql .= ',cate_id int not null'; // 所属分类ID
$sql .= ',prod_id int not null'; // 所属商品
$sql .= ',creater varchar(40) not null';
$sql .= ",create_at int not null";
$sql .= ",begin_at int not null default 0";
$sql .= ",end_at int not null default 0";
$sql .= ',primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$sqls[] = $sql;

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;