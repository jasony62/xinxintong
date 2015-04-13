<?php
//测试页面
include "SmtpMail.php";

//你用这个类的时候你修改成你自己的信箱就可以了
$smtp = new SmtpMail("mail3.ctsi.com.cn", "25", "yangyue@ctsi.com.cn", "p0o9I8U7", true);
//如果你需要显示会话信息，请将上面的修改成
//$smtp   =   new smtp_mail("smtp.qq.com","25","你的qq.com的帐号","你的密码",true);
$smtp->send(
    "yangyue@ctsi.com.cn",
    "yangyue@chinatelecom.com.cn",
    "你好",
    "<a href='http://www.baidu.com'>百度</a>");
?> 
