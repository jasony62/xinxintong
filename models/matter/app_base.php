<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 *
 */
abstract class app_base extends base_model {
	/**
	 * 返回进行推送的客服消息格式
	 *
	 * $runningMpid
	 * $id
	 */
	public function &forCustomPush($runningMpid, $id) {
		$app = $this->byId($id);

		if (!empty($app->pic) && stripos($app->pic, 'http') === false) {
			$pic = 'http://' . $_SERVER['HTTP_HOST'] . $app->pic;
		} else {
			$pic = $app->pic;
		}

		$ma[] = array(
			'title' => $app->title,
			'description' => $app->summary,
			'url' => $this->getEntryUrl($runningMpid, $id),
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