<?php
require_once '../../db.php';

set_time_limit(0);

//
$sql = "select e.siteid,e.id from xxt_enroll e where not exists (select 1 from xxt_enroll_round r where r.aid=e.id) ";
$db_result = $mysqli->query($sql);
$notRoundApps = array();
while ($obj = $db_result->fetch_object()) {
	$notRoundApps[] = $obj;
}

foreach ($notRoundApps as $notRoundApp) {
	$roundId = uniqid();
	$q = [
            'siteid' => $notRoundApp->siteid,
			'aid' => $notRoundApp->id,
			'rid' => $roundId,
			'mission_rid' => '',
			'creator' => '',
			'create_at' => time(),
			'title' => '填写时段',
			'state' => 1,
			'start_at' => 0,
			'end_at' => 0,
        ];
        $keys = array_keys($q);
        $keys = implode(',', $keys);

        $valus = array_values($q);
        $valus = implode("','", $valus);
        // 插入轮次
        $sql3 = "insert into xxt_enroll_round (". $keys .") values('". $valus ."')";
        if (!$mysqli->query($sql3)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        
        $sql4 = "update xxt_enroll set multi_rounds = 'Y' where id = '{$notRoundApp->id}'";
        if (!$mysqli->query($sql4)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更改用户轮次
        $sql5 = "update xxt_enroll_user set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql5)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更改数据轮次
        $sql6 = "update xxt_enroll_record set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql6)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更改数据轮次2
        $sql7 = "update xxt_enroll_record_data set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql7)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更改事件表轮次
        $sql8 = "update xxt_enroll_trace set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql8)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更新留言表
        $sql9 = "update xxt_enroll_record_remark set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql9)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更新通知表
        $sql10 = "update xxt_enroll_notice set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql10)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更新日志表
        $sql11 = "update xxt_enroll_log set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql11)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
        // 更新统计缓存表
        $sql12 = "update xxt_enroll_record_stat set rid = '{$roundId}' where aid = '{$notRoundApp->id}' and rid = ''";
        if (!$mysqli->query($sql12)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'database error: ' . $mysqli->error;
        }
}

echo "end update " . __FILE__ . PHP_EOL;
