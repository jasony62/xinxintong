<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "insert into xxt_call_text(mpid,keyword,match_mode,matter_type,matter_id,access_control,authapis) select mpid,keyword,match_mode,matter_type,matter_id,access_control,authapis from xxt_text_call_reply";
$sqls[] = "insert into xxt_call_menu(mpid,version,published,menu_key,pversion,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview,access_control,authapis) select mpid,version,published,menu_key,pversion,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview,access_control,authapis from xxt_menu_reply";
$sqls[] = "insert into xxt_call_qrcode(mpid,src,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,src,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_qrcode_call_reply";
$sqls[] = "insert into xxt_call_other(mpid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_other_call_reply";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;