<?php
namespace job\log\site\fe\matter;
/**
 * 调用model需要引用的文件
 */
require_once TMS_APP_DIR . '/tms/db.php';
require_once TMS_APP_DIR . '/tms/tms_model.php';
/**
 * 素材访问日志
 */
class access extends \TMS_MODEL {
	/**
	 * 执行任务
	 */
	public function perform() {
		$siteId = $this->args['site'];
		$type = $this->args['type'];
		$id = $this->args['id'];
		$title = $this->args['title'];
		$shareby = isset($this->args['shareby']) ? $this->args['shareby'] : '';
		//
		$oUser = new \stdClass;
		$oUser->uid = $this->args['user_uid'];
		$oUser->nickname = $this->args['user_nickname'];
		//
		$options = [];
		isset($this->args['rid']) && $options['rid'] = $this->args['rid'];
		//
		$clientIp = $this->args['clientIp'];
		$HTTP_USER_AGENT = isset($this->args['HTTP_USER_AGENT']) ? $this->args['HTTP_USER_AGENT'] : '';
		$QUERY_STRING = $this->args['search'];
		$HTTP_REFERER = $this->args['referer'];

		$model = $this->model();
		switch ($type) {
		case 'article':
			$model->update("update xxt_article set read_num=read_num+1 where id='$id'");
			break;
		case 'channel':
			$model->update("update xxt_channel set read_num=read_num+1 where id='$id'");
			break;
		case 'news':
			$model->update("update xxt_news set read_num=read_num+1 where id='$id'");
			break;
		case 'enroll':
			$model->update("update xxt_enroll set read_num=read_num+1 where id='$id'");
		}

		$logUser = new \stdClass;
		$logUser->userid = $oUser->uid;
		$logUser->nickname = $oUser->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $title;

		$logClient = new \stdClass;
		$logClient->ip = $clientIp;
		$logClient->agent = $HTTP_USER_AGENT;

		// 登记活动的专题页和讨论页和共享页需要单独记录
		if ($type === 'enroll') {
			$targets = ['topic', 'repos', 'cowork'];
			if (!empty($this->args['target_type']) && in_array($this->args['target_type'], $targets) && !empty($this->args['target_id'])) {
				$logMatter->id = $this->args['target_id'];
				$logMatter->type = 'enroll.' . $this->args['target_type'];
			}
		}

		$logid = $this->model('matter\log')->addMatterRead($siteId, $logUser, $logMatter, $logClient, $shareby, $QUERY_STRING, $HTTP_REFERER, $options);
		/**
		 * coin log
		 */
		if ($type === 'plan') {
			$oApp = $this->model('matter\plan')->byId($id);
			$modelPUser = $this->model('matter\plan\user')->setOnlyWriteDbConn(true);
			$modelPUser->createOrUpdate($oApp, $oUser);
		}
	}
}