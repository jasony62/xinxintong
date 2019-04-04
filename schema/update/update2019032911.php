<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/db.php';
require_once dirname(dirname(dirname(__FILE__))) . '/tms/tms_model.php';

set_time_limit(0);

$model = TMS_MODEL::model();

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_channel_matter add seq int not null default 10000";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
		die;
	}
}

// 查询所有的有置顶或置低图文的频道
$channels = $model->query_objs_ss(['id,top_type,top_id,bottom_type,bottom_id', 'xxt_channel', "state = 1 and (top_type <> '' or bottom_type <> '')"]);
foreach ($channels as $channel) {
	if (!empty($channel->top_type)) {
		$model->update('xxt_channel_matter', ['seq' => 1], ['channel_id' => $channel->id, 'matter_type' => $channel->top_type, 'matter_id' => $channel->top_id]);
	}
	if (!empty($channel->bottom_type)) {
		$model->update('xxt_channel_matter', ['seq' => 20001], ['channel_id' => $channel->id, 'matter_type' => $channel->bottom_type, 'matter_id' => $channel->bottom_id]);
	}
}
echo "end update " . __FILE__ . PHP_EOL;