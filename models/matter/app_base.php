<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 *
 */
abstract class app_base extends base_model {
	/**
	 * 新建活动
	 */
	public function create($oUser, $oNewApp) {
		if (empty($oNewApp->id)) {
			$oNewApp->id = uniqid();
		};
		$oNewApp = parent::create($oUser, $oNewApp);

		return $oNewApp;
	}
	/**
	 * 返回进行推送的客服消息格式
	 *
	 * $runningMpid
	 * $id
	 * @param string $ver 为了兼容老版本，迁移后应该去掉
	 */
	public function &forCustomPush($runningMpid, $id, $ver = 'NEW') {
		$app = $this->byId($id);

		if (!empty($app->pic) && stripos($app->pic, 'http') === false) {
			$pic = APP_PROTOCOL . APP_HTTP_HOST . $app->pic;
		} else {
			$pic = $app->pic;
		}

		$ma[] = array(
			'title' => $app->title,
			'description' => $app->summary,
			'url' => $this->getEntryUrl($runningMpid, $id, $ver),
			'picurl' => $pic,
		);
		$msg = array(
			'msgtype' => 'news',
			'news' => array(
				'articles' => $ma,
			),
		);

		return $msg;
	}
}