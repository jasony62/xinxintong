<?php
/**
 * inside a mp.
 *
 * 在seesion中保留一些公众号的基本数据
 * 要考虑只能用那些不会变的属性
 */
$view['template'] = '/mp/frame';
$view['params']['layout-top'] = '/global/title-bar';
$view['params']['layout-left'] = '/mp/nav';

if (isset($_GET['mpid']) && ($mpid = $_GET['mpid'])) {
	$_SESSION['mpid'] = $mpid;
	if (!isset($_SESSION['mpaccount']) || !($mp = $_SESSION['mpaccount']) || ($mp->mpid != $mpid)) {
		$_SESSION['mpaccount'] = TMS_APP::M('mp\mpaccount')->byId($mpid, 'name,mpid,mpsrc,asparent,parent_mpid,yx_joined,wx_joined,qy_joined');
		$_SESSION['authapis'] = TMS_APP::M('user/authapi')->byMpid($mpid, 'Y');
	}
}