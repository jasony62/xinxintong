<?php
require_once '../db.php';
//
$sqls = array();
$sqls[] = 'truncate xxt_call_text_yx';
$sqls[] = 'truncate xxt_call_text_wx';
$sqls[] = 'truncate xxt_call_text_wx';
$sqls[] = 'truncate xxt_call_menu_yx';
$sqls[] = 'truncate xxt_call_menu_wx';
$sqls[] = 'truncate xxt_call_menu_qy';
$sqls[] = 'truncate xxt_call_qrcode_yx';
$sqls[] = 'truncate xxt_call_qrcode_wx';
$sqls[] = 'truncate xxt_call_qrcode_qy';
$sqls[] = 'truncate xxt_call_other_yx';
$sqls[] = 'truncate xxt_call_other_wx';
$sqls[] = 'truncate xxt_call_other_qy';
$sqls[] = 'truncate xxt_call_relay_yx';
$sqls[] = 'truncate xxt_call_relay_wx';
$sqls[] = 'truncate xxt_call_relay_qx';
//
$sqls[] = 'truncate xxt_site';
$sqls[] = 'truncate xxt_site_admin';
$sqls[] = 'truncate xxt_site_yx';
$sqls[] = 'truncate xxt_site_wx';
$sqls[] = 'truncate xxt_site_qy';
$sqls[] = 'truncate xxt_site_yxfan';
$sqls[] = 'truncate xxt_site_yxfangroup';
$sqls[] = 'truncate xxt_site_wxfan';
$sqls[] = 'truncate xxt_site_yxfangroup';
//
$sqls[] = 'truncate xxt_site_member_schema';
$sqls[] = 'truncate xxt_site_member';
$sqls[] = 'truncate xxt_site_member_department';
$sqls[] = 'truncate xxt_site_member_tag';
//
$sqls = array();
$sql = "insert into xxt_call_text_yx(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_text_wx(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_text_wx(siteid,keyword,match_mode,matter_type,matter_id) select mpid,keyword,match_mode,matter_type,matter_id from xxt_call_text where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
$sqls[] = $sql;
//
$sqls = array();
$sql = "insert into xxt_call_menu_yx(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_menu_wx(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_menu_qy(siteid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview) select mpid,version,published,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview from xxt_call_menu where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
$sqls[] = $sql;
//
$sqls = array();
$sql = "insert into xxt_call_qrcode_yx(siteid,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_call_qrcode where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_qrcode_wx(siteid,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_call_qrcode where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_qrcode_qy(siteid,scene_id,expire_at,name,pic,matter_type,matter_id) select mpid,scene_id,expire_at,name,pic,matter_type,matter_id from xxt_call_qrcode where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
$sqls[] = $sql;
//
$sqls = array();
$sql = "insert into xxt_call_other_yx(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_other_wx(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = $sql;
$sql = "insert into xxt_call_other_qy(siteid,name,title,matter_type,matter_id) select mpid,name,title,matter_type,matter_id from xxt_call_other where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
$sqls[] = $sql;
//xxt_mprelay
$sqls = array();
$sql = "insert into xxt_call_relay_yx(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sql = "insert into xxt_call_relay_wx(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sql = "insert into xxt_call_relay_qy(siteid,title,url,state) select mpid,title,url,state from xxt_mprelay where mpid in (select mpid from xxt_mpaccount where mpsrc='qy')";
//xxt_mpaccount,xxt_mpsetting
$sqls = array();
$sqls[] = "insert into xxt_site(id,name,creater,create_at,state) select mpid,name,creater,create_at,state from xxt_mpaccount";
$sqls[] = "insert into xxt_site_admin(siteid,uid,creater,create_at) select mpid,uid,creater,create_at from xxt_mpadministrator";
$sqls[] = "insert into xxt_site_yx(siteid,creater,create_at,qrcode,public_id,token,appid,appsecret,cardname,cardid,joined,access_token,access_token_expire_at) select mpid,creater,create_at,qrcode,public_id,token,yx_appid,yx_appsecret,yx_cardname,yx_cardid,yx_joined,yx_token,yx_token_expire_at from xxt_mpaccount where mpscr='yx'";
$sqls[] = "insert into xxt_site_wx(siteid,creater,create_at,qrcode,public_id,token,appid,appsecret,cardname,cardid,joined,access_token,access_token_expire_at,jsapi_ticket,jsapi_ticket_expire_at) select mpid,creater,create_at,qrcode,public_id,token,wx_appid,wx_appsecret,wx_cardname,wx_cardid,wx_joined,wx_token,wx_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at from xxt_mpaccount where mpscr='wx'";
$sqls[] = "insert into xxt_site_qy(siteid,creater,create_at,qrcode,public_id,token,corpid,secret,encodingaeskey,agentid,joined,access_token,access_token_expire_at,jsapi_ticket,jsapi_ticket_expire_at) select mpid,creater,create_at,qrcode,public_id,qy_corpid,qy_secret,qy_encodingaeskey,qy_agentid,qy_joined,qy_token,qy_token_expire_at,wx_jsapi_ticket,wx_jsapi_ticket_expire_at from xxt_mpaccount where mpscr='qy'";
$sqls[] = "update xxt_site_yx s,xxt_mpsetting m set m.can_menu=s.yx_menu,m.can_group_push=s.yx_group_push,m.can_custom_push=s.yx_custom_push,m.can_fans=s.yx_fans,m.fansgroup=s.yx_fansgroup,m.can_qrcode=s.yx_qrcode,m.can_oatuh=s.yx_oauth,m.can_p2p2=s.yx_p2p,m.can_checkmobile=s.yx_checkmobile,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
$sqls[] = "update xxt_site_wx s,xxt_mpsetting m set m.can_menu=s.wx_menu,m.can_group_push=s.wx_group_push,m.can_custom_push=s.wx_custom_push,m.can_fans=s.wx_fans,m.fansgroup=s.wx_fansgroup,m.can_qrcode=s.wx_qrcode,m.can_oatuh=s.wx_oauth,m.can_pay=s.wx_pay,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
$sqls[] = "update xxt_site_wx s,xxt_mpsetting m set m.can_updateab=s.qy_updateab,s.follow_page_id=m.follow_page_id where s.siteid=m.mpid";
//xxt_fans,xxt_fansgroup
$sqls = array();
$sqls[] = "insert into xxt_site_yxfan(siteid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden) select mpid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden from xxt_fans where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_site_wxfan(siteid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden) select mpid,openid,groupid,subscribe_at,unsubscribe_at,sync_at,headimgurl,nickname,sex,city,province,country,forbidden from xxt_fans where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
$sqls[] = "insert into xxt_site_yxfangroup(id,siteid,name) select id,mpid,name from xxt_fansgroup where mpid in (select mpid from xxt_mpaccount where mpsrc='yx')";
$sqls[] = "insert into xxt_site_wxfangroup(id,siteid,name) select id,mpid,name from xxt_fansgroup where mpid in (select mpid from xxt_mpaccount where mpsrc='wx')";
//xxt_member_authapi,xxt_member,xxt_member_department,xxt_member_tag
$sqls = array();
$sqls[] = "insert into xxt_site_member_schema(id,siteid,title,creater,create_at,type,valid,used,url,validity,attr_mobile,attr_email,attr_name,extattr,code_id,entry_statement,acl_statement,notpass_statement) select authid,mpid,name,creater,create_at,type,valid,used,'/rest/site/fe/user/member',validity,attr_mobile,attr_email,attr_name,extattr,auth_code_id,entry_statement,acl_statement,notpass_statement from xxt_member_authapi";
$sqls[] = "insert into xxt_site_member(siteid,schema_id,create_at,identity,sync_at,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden) select mpid,authapi_id,create_at,authed_identity,sync_at,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden from xxt_member";
$sqls[] = "insert into xxt_site_member_department(id,siteid,schema_id,pid,seq,sync_at,name,fullpath,extattr) select id,mpid,authapi_id,pid,seq,sync_at,name,fullpath,extattr from xxt_member_department";
$sqls[] = "insert into xxt_site_member_tag(id,siteid,schema_id,sync_at,name,type,extattr) select id,mpid,authapi_id,sync_at,name,type,extattr from xxt_member_tag";
//
$sqls = array();
//$sqls[] = "update set siteid=mpid from xxt_log where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_log_mpa where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_log_mpreceive where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_log_mpsend where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_matter_read where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_matter_share where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_massmsg where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_tmplmsg where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_user_action where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_user_matter where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_log_matter_action where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_log_timer where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_contribute where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_contribute_user where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_enroll where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_enroll_page where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_enroll_record where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_enroll_signin_log where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_enroll_round where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_enroll_receiver where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_lottery where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_lottery_task where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_lottery_award where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_lottery_plate where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_lottery_log where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_article where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_article_remark where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_article_score where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_article_review_log where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_link where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_link_param";
$sqls[] = "update set siteid=mpid,title=content from xxt_text where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_news where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_channel where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_inner where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_tmplmsg where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_tmplmsg_param where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_tmplmsg_mapping where mpid<>''";
//
//$sqls[] = "update set siteid=mpid from xxt_wall where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_wall_page where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_wall_enroll where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_wall_log where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_mission where mpid<>''";
$sqls[] = "update set siteid=mpid from xxt_mission_matter where mpid<>''";
//
$sqls[] = "update set siteid=mpid from xxt_matter_acl where mpid<>''";
//
//$sqls[] = "update set siteid=mpid from xxt_shop_matter where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_tag where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_article_tag where mpid<>''";
//$sqls[] = "update set siteid=mpid from xxt_task where mpid<>''";
