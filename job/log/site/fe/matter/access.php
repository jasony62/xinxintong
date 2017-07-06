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
		$user = new \stdClass;
		$user->uid = $this->args['user_uid'];
		$user->nickname = $this->args['user_nickname'];
		//
		$clientIp = $this->args['clientIp'];
		$HTTP_USER_AGENT = isset($this->args['HTTP_USER_AGENT']) ? $this->args['HTTP_USER_AGENT'] : '';
		$QUERY_STRING = isset($this->args['QUERY_STRING']) ? $this->args['QUERY_STRING'] : '';
		$HTTP_REFERER = isset($this->args['HTTP_REFERER']) ? $this->args['HTTP_REFERER'] : '';

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
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $title;

		$logClient = new \stdClass;
		$logClient->ip = $clientIp;
		$logClient->agent = $HTTP_USER_AGENT;

		$logid = $this->model('matter\log')->addMatterRead($siteId, $logUser, $logMatter, $logClient, $shareby, $QUERY_STRING, $HTTP_REFERER);
		/**
		 * coin log
		 */
		if ($type === 'article') {
			$modelCoin = $this->model('site\coin\log');
			if ($matter = $this->model('matter\article2')->byId($id)) {
				$modelCoin->award($matter, $user, 'site.matter.article.read');
			}
		} else if ($type === 'enroll') {
			$matter = $this->model('matter\enroll')->byId($id);
			$modelMat = $this->model('matter\enroll\coin');
			$rules = $modelMat->rulesByMatter('site.matter.enroll.read', $matter);
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($matter, $user, 'site.matter.enroll.read', $rules);
			/* 更新活动用户数据 */
			$modelUsr = $this->model('matter\enroll\user');
			$oEnrollUsr = $modelUsr->byId($matter, $user->uid, ['fields' => 'id,nickname,last_enroll_at,enroll_num,user_total_coin']);
			if (false === $oEnrollUsr) {
				$inData = ['last_enroll_at' => time(), 'enroll_num' => 1];
				$inData['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $rule->actor_delta;
				}
				
				$modelUsr->add($matter, $user, $inData);
			} else {
				$upData = ['last_enroll_at' => time(), 'enroll_num' => (int) $oEnrollUsr->enroll_num + 1];
				$upData['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
				foreach ($rules as $rule) {
					$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $rule->actor_delta;
				}
				
				$modelUsr->update('xxt_enroll_user', $upData, ['id' => $oEnrollUsr->id]);
			}
		}
	}
}