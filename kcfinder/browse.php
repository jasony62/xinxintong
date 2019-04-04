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

if (isset($_GET['type']) && $_GET['type'] === 'ylylisten') {
	if (!defined('OOS_ENDPOINT')) {
		throw new Exception('天翼云的API服务器');
	}
	if (!defined('OOS_ACCESS_KEY')) {
		throw new Exception('未指定API参数1');
	}
	if (!defined('OOS_ACCESS_SECRET')) {
		throw new Exception('未指定API参数2');
	}

	$browser = new browser_tyoos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
} else if (defined('KCFINDER_STORE_AT') && KCFINDER_STORE_AT === 'local') {
    $browser = new browser();
} else {
    $browser = new browser_alioss();
}
$browser->action();
