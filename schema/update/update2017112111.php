<?php
require_once '../../db.php';

$sel = "select include_apps from xxt_mission_report where include_apps != '' or include_apps != null";
$resut = $mysqli->query($sel);
$row = mysqli_fetch_assoc($resut);
if (strpos($row['include_apps'], 'apps') !== false) {
	echo "end update " . __FILE__ . PHP_EOL;
	die;
}
$sqls = array();
//
$sqls[] = "update xxt_mission_report set include_apps = concat('{" . '"apps"' . ":',include_apps,'}') where include_apps != '' or include_apps != null";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;