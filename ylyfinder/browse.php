<?php
/** This file is part of KCFinder project
 *
 *      @desc Browser calling script
 *   @package KCFinder
 *   @version 2.51
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010, 2011 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @link http://kcfinder.sunhater.com
 */

file_exists(dirname(__FILE__).'/cus/config.php') && include_once dirname(__FILE__).'/cus/config.php';

require dirname(__FILE__)."/core/autoload.php";

if (!defined('OOS_ENDPOINT')) {
	die('未指定天翼云API服务器');
}
if (!defined('OOS_ACCESS_KEY')) {
	die('未指定API参数1');
}
if (!defined('OOS_ACCESS_SECRET')) {
	die('未指定API参数2');
}
if (empty($_GET['cms']) || $_GET['cms'] !== 'drupal') {
	session_start();
}
if (!isset($_GET['type']) || $_GET['type'] !== 'tyyoos') {
	die('未知类型');
}
if (!isset($_GET['act'])) {
	die('参数错误');
}

// 查询用户权限
if (empty($_GET['siteid']) || empty($_GET['mpid'])) {
	if (empty($_SESSION['siteid']) || empty($_SESSION['mpid'])) {
		die('参数错误2');
	} else {
		$siteid = $_SESSION['siteid'];
		$mpid = $_SESSION['mpid'];
	}
} else {
	$siteid = $_GET['siteid'];
	$mpid = $_GET['mpid'];
}
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/tms/db.php';
require_once dirname(dirname(__FILE__)) . '/tms/tms_model.php';
$model = TMS_MODEL::model();

$cookiekey = md5('platform');
$cookiename = G_COOKIE_PREFIX . "_site_user_login";
if (!isset($_COOKIE[$cookiename])) {
	die('未登录');
}
$oCookieRegUser = $model->encrypt($_COOKIE[$cookiename], 'DECODE', $cookiekey);
$oCookieRegUser = json_decode($oCookieRegUser);
// 根据用户id查询用户所在团队权限
$q = [
	'unionid',
	'xxt_account_third_user',
	['openid' => $mpid]
];
$unid = $model->query_val_ss($q);
if (empty($unid)) {
	die('没有权限');
}

if ($unid !== $oCookieRegUser->unionid) {
	die('没有权限2');
}

$browser = new browser_tyyoos();
$browser->action();
