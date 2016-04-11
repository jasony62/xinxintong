<?php
require_once '../db.php';
//
$sqls = array();
//
$sqls[] = 'truncate xxt_call_text_yx';
$sqls[] = 'truncate xxt_call_text_wx';
$sqls[] = 'truncate xxt_call_text_qy';
$sqls[] = 'truncate xxt_call_menu_yx';
$sqls[] = 'truncate xxt_call_menu_wx';
$sqls[] = 'truncate xxt_call_menu_qy';
$sqls[] = 'truncate xxt_call_qrcode_yx';
$sqls[] = 'truncate xxt_call_qrcode_wx';
$sqls[] = 'truncate xxt_call_other_yx';
$sqls[] = 'truncate xxt_call_other_wx';
$sqls[] = 'truncate xxt_call_other_qy';
$sqls[] = 'truncate xxt_call_relay_yx';
$sqls[] = 'truncate xxt_call_relay_wx';
$sqls[] = 'truncate xxt_call_relay_qy';
//
$sqls[] = 'truncate xxt_site';
$sqls[] = 'truncate xxt_site_admin';
$sqls[] = 'truncate xxt_site_yx';
$sqls[] = 'truncate xxt_site_wx';
$sqls[] = 'truncate xxt_site_qy';
$sqls[] = 'truncate xxt_site_yxfan';
$sqls[] = 'truncate xxt_site_yxfangroup';
$sqls[] = 'truncate xxt_site_wxfan';
$sqls[] = 'truncate xxt_site_wxfangroup';
//
$sqls[] = 'truncate xxt_site_member_schema';
$sqls[] = 'truncate xxt_site_member';
$sqls[] = 'truncate xxt_site_member_department';
$sqls[] = 'truncate xxt_site_member_tag';
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(truncate): ' . $mysqli->error;
	}
}
//
$sqls = array();
$sqls[] = "insert into xxt_call_text_yx(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_call_text_wx(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_call_text_qy(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_call_text): ' . $mysqli->error;
	}
}
//
$sqls = array();
$sqls[] = "insert into xxt_call_menu_yx(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_call_menu_wx(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_call_menu_qy(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_call_menu): ' . $mysqli->error;
	}
}
//
$sqls = array();
$sqls[] = "insert into xxt_call_qrcode_yx(siteid,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_call_qrcode where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_call_qrcode_wx(siteid,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_call_qrcode where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_call_qrcode): ' . $mysqli->error;
	}
}
//
$sqls = array();
$sqls[] = "insert into xxt_call_other_yx(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_call_other_wx(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_call_other_qy(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_call_other): ' . $mysqli->error;
	}
}
//xxt_mprelay
$sqls = array();
$sqls[] = "insert into xxt_call_relay_yx(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_call_relay_wx(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_call_relay_qy(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_mprelay): ' . $mysqli->error;
	}
}
//xxt_mpaccount,xxt_mpsetting
$sqls = array();
$sqls[] = "insert into xxt_site(id,name,creater,create_at,state) select mpid,name,creater,create_at,state from xxt_mpaccount";
$sqls[] = "insert into xxt_site_admin(siteid,uid,creater,create_at) select mpid,uid,creater,create_at from xxt_mpadministrator";
$sqls[] = "insert into xxt_site_yx(siteid,creater,create_at,qrcode,public_id,token,appid,appsecret,cardname,cardid,joined,access_token,access_token_expire_at) select mpid,creater,create_at,qrcode,public_id,token,yx_appid,yx_appsecret,yx_cardname,yx_cardid,yx_joined,yx_token,yx_token_expire_at from xxt_mpaccount where mpsrc='yx'";
$sqls[] = "insert into xxt_site_wx(siteid,creater,create_at,qrcode,public_id,token,appid,appsecret,cardname,cardid,joined,access_token,access_token_expire_at,jsapi_ticket,jsapi_ticket_expire_at) select mpid,creater,create_at,qrcode,public_id,token,wx_appid,wx_appsecret,wx_cardname,wx_cardid,wx_joined,wx_token,wx_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at from xxt_mpaccount where mpsrc='wx'";
$sqls[] = "insert into xxt_site_qy(siteid,creater,create_at,qrcode,public_id,token,corpid,secret,encodingaeskey,agentid,joined,access_token,access_token_expire_at,jsapi_ticket,jsapi_ticket_expire_at) select mpid,creater,create_at,qrcode,public_id,token,qy_corpid,qy_secret,qy_encodingaeskey,qy_agentid,qy_joined,qy_token,qy_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at from xxt_mpaccount where mpsrc='qy'";
$sqls[] = "update xxt_site_yx s,xxt_mpsetting m set s.can_menu=m.yx_menu,s.can_group_push=m.yx_group_push,s.can_custom_push=m.yx_custom_push,s.can_fans=m.yx_fans,s.can_fansgroup=m.yx_fansgroup,s.can_qrcode=m.yx_qrcode,s.can_oauth=m.yx_oauth,s.can_p2p=m.yx_p2p,s.can_checkmobile=m.yx_checkmobile,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
$sqls[] = "update xxt_site_wx s,xxt_mpsetting m set s.can_menu=m.wx_menu,s.can_group_push=m.wx_group_push,s.can_custom_push=m.wx_custom_push,s.can_fans=m.wx_fans,s.can_fansgroup=m.wx_fansgroup,s.can_qrcode=m.wx_qrcode,s.can_oauth=m.wx_oauth,s.can_pay=m.wx_pay,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
$sqls[] = "update xxt_site_qy s,xxt_mpsetting m set s.can_updateab=m.qy_updateab,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "database error($sql): " . $mysqli->error;
	}
}
//xxt_fans,xxt_fansgroup
$sqls = array();
$sqls[] = "insert into xxt_site_yxfan(siteid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden) select mpid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden from xxt_fans where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_site_wxfan(siteid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden) select mpid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden from xxt_fans where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_site_yxfangroup(id,siteid,name) select id,mpid,name from xxt_fansgroup where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_site_wxfangroup(id,siteid,name) select id,mpid,name from xxt_fansgroup where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "database error($sql): " . $mysqli->error;
	}
}
//xxt_member_authapi,xxt_member,xxt_member_department,xxt_member_tag
$sqls = array();
$sqls[] = "insert into xxt_site_member_schema(id,siteid,title,creater,create_at,type,valid,used,url,validity,attr_mobile,attr_email,attr_name,extattr,code_id,entry_statement,acl_statement,notpass_statement) select authid,mpid,name,creater,create_at,type,valid,used,'/rest/site/fe/user/member',validity,attr_mobile,attr_email,attr_name,extattr,auth_code_id,entry_statement,acl_statement,notpass_statement from xxt_member_authapi";
$sqls[] = "insert into xxt_site_member(siteid,schema_id,create_at,identity,sync_at,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden) select mpid,authapi_id,create_at,authed_identity,sync_at,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden from xxt_member";
$sqls[] = "insert into xxt_site_member_department(id,siteid,schema_id,pid,seq,sync_at,name,fullpath,extattr) select id,mpid,authapi_id,pid,seq,sync_at,name,fullpath,extattr from xxt_member_department";
$sqls[] = "insert into xxt_site_member_tag(id,siteid,schema_id,sync_at,name,type,extattr) select id,mpid,authapi_id,sync_at,name,type,extattr from xxt_member_tag";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error(xxt_member): ' . $mysqli->error;
	}
}
//
$sqls = array();
//$sqls[] = "update xxt_log set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_log_mpa set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_log_mpreceive set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_log_mpsend set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_matter_read set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_matter_share set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_massmsg set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_tmplmsg set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_user_action set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_user_matter set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_log_matter_action set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_log_timer set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_contribute set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_contribute_user set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_enroll set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_enroll_page set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_enroll_record set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_enroll_signin_log set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_enroll_round set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_enroll_receiver set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_lottery set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_lottery_task set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_lottery_award set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_lottery_plate set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_lottery_log set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_article set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_article_remark set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_article_score set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_article_review_log set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_link set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_link_param set siteid=mpid  where mpid<>''";
$sqls[] = "update xxt_text set siteid=mpid,title=content where mpid<>''";
$sqls[] = "update xxt_news set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_channel set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_inner set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_tmplmsg set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_tmplmsg_param set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_tmplmsg_mapping set siteid=mpid where mpid<>''";
//
//$sqls[] = "update xxt_wall set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_wall_page set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_wall_enroll set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_wall_log set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_mission set siteid=mpid where mpid<>''";
$sqls[] = "update xxt_mission_matter set siteid=mpid where mpid<>''";
//
$sqls[] = "update xxt_matter_acl set siteid=mpid where mpid<>''";
//
//$sqls[] = "update xxt_shop_matter set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_tag set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_article_tag set siteid=mpid where mpid<>''";
//$sqls[] = "update xxt_task set siteid=mpid where mpid<>''";
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo "database error($sql): " . $mysqli->error;
	}
}