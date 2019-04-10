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

//天翼云的API服务器
define('OOS_ENDPOINT', 'https://oos-js.ctyunapi.cn/');
//Access Key 在天翼云门户网站-帐户管理-API密钥管理中获取
define('OOS_ACCESS_KEY', '5b79c659ce70ace89ce2');
//Access Secret 在天翼云门户网站-帐户管理-API密钥管理中获取
define('OOS_ACCESS_SECRET', '4493e6cc8c992cb194cfdd8760949becc6029223');
// bucket 容器
define('OOS_BUCKET', 'ctsi-zsbssss1');
// define('OOS_BUCKET', '');

require dirname(__FILE__)."/core/autoload.php";

$browser = new browser_tyyoos();
$browser->action();
