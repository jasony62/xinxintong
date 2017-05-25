<?php
require_once '../../db.php';
/*
 * 登记活动相关数据类型迁移
 */
$tables = array('xxt_enroll', 'xxt_group', 'xxt_signin', 'xxt_wall');

foreach ($tables as $table) {
    try {
        $sql    = "select id,data_schemas from " . $table . " where data_schemas LIKE '%type\":\"name%' OR data_schemas LIKE '%type\":\"mobile%' OR data_schemas LIKE '%type\":\"email%' OR data_schemas LIKE '%number\":\"Y%' OR data_schemas LIKE '%number\":\"N%' ";

        $result = $mysqli->query($sql);

        while ($row = $result->fetch_object()) {
            if (!empty($row->data_schemas)) {
                $row->data_schemas = str_replace(
                    ['"type":"name"', '"type":"mobile"', '"type":"email"', '"number":"N"', '"number":"Y"'],
                    ['"type":"shorttext","fastSelect":"name"', '"type":"shorttext","fastSelect":"mobile"',
                        '"type":"shorttext","fastSelect":"email"', '"fastSelect":""', '"fastSelect":"number"'],
                    $row->data_schemas);
                $mysqli->query("update $table set data_schemas='$row->data_schemas' where id='$row->id'");
            }
        }
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        echo $e->getMessage() . '<br/> database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;
