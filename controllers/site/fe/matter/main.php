<?php
namespace site\fe\matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 返回访问的素材页面
 */
class main extends \site\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 * @param string $id
	 * @param string $type
	 * @param string $shareby
	 */
	public function index_action($site, $id, $type, $shareby = '') {
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}
		/* 返回页面 */
		switch ($type) {
		case 'article':
		case 'custom':
			$modelArticle = $this->model('matter\article');
			$article = $modelArticle->byId($id, 'title');
			if ($article) {
				\TPL::assign('title', $article->title);
				if ($type === 'article') {
					\TPL::output('site/fe/matter/article/main');
				} else {
					\TPL::output('site/fe/matter/custom/main');
				}
			} else {
				$this->outputInfo('指定的对象不存在');
			}
			break;
		case 'news':
			\TPL::output('site/fe/matter/news/main');
			break;
		case 'channel':
			$modelChn = $this->model('matter\channel');
			$channel = $modelChn->byId($id, 'title');
			if ($channel) {
				\TPL::assign('title', $channel->title);
				\TPL::output('site/fe/matter/channel/main');
			} else {
				$this->outputInfo('指定的对象不存在');
			}
			break;
		}
		exit;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 记录访问日志
	 */
	public function logAccess_action($site, $id, $type, $title = '', $shareby = '') {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');
		//header('Access-Control-Allow-Methods:POST');
		//header('Access-Control-Allow-Headers:Content-Type');
		//if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		//	exit;
		//}

		$user = $this->who;
		$model = $this->model();
		$site = $model->escape($site);
		$id = $model->escape($id);
		$type = $model->escape($type);
		$shareby = $model->escape($shareby);

		$post = $this->getPostJson();
		if ($type === 'enroll') {
			$userRid = !empty($post->rid) ? $post->rid : '';
			if (empty($post->assignedNickname)) {
				$oApp = $this->model('matter\enroll')->byId($id, ['fields' => 'siteid,id,round_cron,multi_rounds,assigned_nickname', 'cascaded' => 'N']);
				if ((isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y') && isset($oApp->assignedNickname->schema->id)) {
					$options = [];
					$options['fields'] = 'nickname'; 
					$options['assignRid'] = $userRid;
					$userRec = $this->model('matter\enroll\record')->lastByUser($oApp, $user, $options);
					if ($userRec) {
						$assignedNickname = $userRec->nickname;
					}
				}
			} else {
				$assignedNickname = $post->assignedNickname;
			}
		}

		if (defined('TMS_PHP_RESQUE') && TMS_PHP_RESQUE === 'Y' && defined('TMS_PHP_RESQUE_REDIS') && strlen(TMS_PHP_RESQUE_REDIS)) {
			require_once TMS_APP_DIR . '/vendor/chrisboulton/php-resque/lib/Resque.php';

			\Resque::setBackend(TMS_PHP_RESQUE_REDIS);

			$args = [
				'site' => $site,
				'id' => $id,
				'title' => $model->escape($title),
				'type' => $type,
				'user_uid' => $user->uid,
				'user_nickname' => (!empty($assignedNickname)) ? $assignedNickname : $user->nickname,
				'clientIp' => $this->client_ip(),
				'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
				'QUERY_STRING' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '',
				'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
			];
			isset($userRid) && $args['rid'] = $userRid;
			\Resque::enqueue('default', 'job\log\site\fe\matter\access', $args);
		} else {
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

			!empty($assignedNickname) && $user->nickname = $assignedNickname;
			$options = [];
			isset($userRid) && $options['rid'] = $userRid;
			$logid = $this->logRead($site, $user, $id, $type, $title, $shareby, $options);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 记录访问日志
	 */
	protected function logRead($siteId, $user, $id, $type, $title, $shareby = '', $options = []) {
		$logUser = new \stdClass;
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $title;

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$search = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $this->model('matter\log')->addMatterRead($siteId, $logUser, $logMatter, $logClient, $shareby, $search, $referer, $options);
		/**
		 * coin log
		 */
		if ($type === 'article') {
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->setOnlyWriteDbConn(true);
			$matter = $this->model('matter\article')->byId($id);
			$modelCoin->award($matter, $user, 'site.matter.article.read');
		} else if ($type === 'enroll') {
			$matter = $this->model('matter\enroll')->byId($id, ['cascaded' => 'N']);
			$modelMat = $this->model('matter\enroll\coin');
			$modelMat->setOnlyWriteDbConn(true);
			$rules = $modelMat->rulesByMatter('site.matter.enroll.read', $matter);
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->setOnlyWriteDbConn(true);
			$modelCoin->award($matter, $user, 'site.matter.enroll.read', $rules);

			/* 更新活动用户总数据 */
			$modelUsr = $this->model('matter\enroll\user');
			$modelUsr->setOnlyWriteDbConn(true);
			$oEnrollUsrALL = $modelUsr->byId($matter, $user->uid, ['fields' => 'id,nickname,user_total_coin', 'rid' => 'ALL']);
			if (false === $oEnrollUsrALL) {
				$inDataALL = [];
				$inDataALL['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inDataALL['user_total_coin'] = $inDataALL['user_total_coin'] + (int) $rule->actor_delta;
				}
				$inDataALL['rid'] = 'ALL';
				$modelUsr->add($matter, $user, $inDataALL);
			} else {
				$upDataALL = [];
				$upDataALL['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
				foreach ($rules as $rule) {
					$upDataALL['user_total_coin'] = $upDataALL['user_total_coin'] + (int) $rule->actor_delta;
				}
				if ($upDataALL['user_total_coin'] !== (int) $oEnrollUsrALL->user_total_coin) {
					$modelUsr->update('xxt_enroll_user', $upDataALL, ['id' => $oEnrollUsrALL->id]);
				}
			}

			/* 修改所属轮次的数据 */
			$modelRun = $this->model('matter\enroll\round');
			if ($activeRound = $modelRun->getActive($matter)) {
				$rid = $activeRound->rid;
			} else {
				$rid = '';
			}
			/* 更新活动用户数据 */
			$oEnrollUsr = $modelUsr->byId($matter, $user->uid, ['fields' => 'id,nickname,user_total_coin', 'rid' => $rid]);
			if (false === $oEnrollUsr) {
				$inData = [];
				$inData['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $rule->actor_delta;
				}

				$inData['rid'] = $rid;
				$modelUsr->add($matter, $user, $inData);
			} else {
				$upData = [];
				$upData['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
				foreach ($rules as $rule) {
					$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $rule->actor_delta;
				}
				if ($upData['user_total_coin'] !== (int) $oEnrollUsr->user_total_coin) {
					$modelUsr->update('xxt_enroll_user', $upData, ['id' => $oEnrollUsr->id]);
				}
			}
		}

		return $logid;
	}
	/**
	 * 记录分享动作
	 *
	 * $shareid
	 * $site 公众号ID，是当前用户
	 * $id 分享的素材ID
	 * $type 分享的素材类型
	 * $share_to  分享给好友或朋友圈
	 * $shareby 谁分享的当前素材ID
	 *
	 */
	public function logShare_action($shareid, $site, $id, $type, $title, $shareto, $shareby = '') {
		//header('Access-Control-Allow-Origin:*');

		$model = $this->model();
		$shareid = $model->escape($shareid);
		$site = $model->escape($site);
		$id = $model->escape($id);
		$type = $model->escape($type);
		$shareto = $model->escape($shareto);
		$shareby = $model->escape($shareby);
		/* 检查请求是否由客户端发起 */
		if ($type === 'lottery') {
			if (!$this->_isAgentEnter($id)) {
				return new \ResponseError('请从指定客户端发起请求');
			}
		}

		switch ($type) {
		case 'article':
			$table = 'xxt_article';
			break;
		case 'news':
			$table = 'xxt_news';
			break;
		case 'channel':
			$table = 'xxt_channel';
			break;
		case 'enroll':
			$table = 'xxt_enroll';
			break;
		case 'lottery':
			$table = 'xxt_lottery';
			break;
		default:
			return new \ResponseError('不支持的类型');
		}

		if ($shareto === 'F') {
			$model->update("update $table set share_friend_num=share_friend_num+1 where id='$id'");
		} else if ($shareto === 'T') {
			$model->update("update $table set share_timeline_num=share_timeline_num+1 where id='$id'");
		}

		$user = $this->who;

		$logUser = new \stdClass;
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $model->escape($title);

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$this->model('matter\log')->addShareAction($site, $shareid, $shareto, $shareby, $logUser, $logMatter, $logClient, $referer);

		/**
		 * coin log
		 */
		if ($type === 'article') {
			$modelCoin = $this->model('site\coin\log');
			$matter = $this->model('matter\article')->byId($id);
			$modelCoin->award($matter, $user, 'site.matter.article.share.' . ['F' => 'friend', 'T' => 'timeline'][$shareto]);
		} else if ($type === 'enroll') {
			$matter = $this->model('matter\enroll')->byId($id);
			$modelMat = $this->model('matter\enroll\coin');
			$rules = $modelMat->rulesByMatter('site.matter.enroll.share.' . ['F' => 'friend', 'T' => 'timeline'][$shareto], $matter);
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($matter, $user, 'site.matter.enroll.share.' . ['F' => 'friend', 'T' => 'timeline'][$shareto], $rules);

			/* 获得所属轮次 */
			$modelRun = $this->model('matter\enroll\round');
			if ($activeRound = $modelRun->getActive($matter)) {
				$rid = $activeRound->rid;
			} else {
				$rid = '';
			}
			/* 更新活动用户轮次数据 */
			$modelUsr = $this->model('matter\enroll\user');
			$modelUsr->setOnlyWriteDbConn(true);
			$oEnrollUsr = $modelUsr->byId($matter, $user->uid, ['fields' => 'id,nickname,user_total_coin', 'rid' => $rid]);
			if (false === $oEnrollUsr) {
				$inData = ['last_enroll_at' => time()];
				$inData['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $rule->actor_delta;
				}
				$inData['rid'] = $rid;
				$modelUsr->add($matter, $user, $inData);
			} else {
				if (count($rules)) {
					$upData = [];
					$upData['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
					foreach ($rules as $rule) {
						$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $rule->actor_delta;
					}
					if ($upData['user_total_coin'] !== (int) $oEnrollUsr->user_total_coin) {
						$modelUsr->update('xxt_enroll_user', $upData, ['id' => $oEnrollUsr->id]);
					}
				}
			}

			/* 更新活动用户总数据 */
			$oEnrollUsrALL = $modelUsr->byId($matter, $user->uid, ['fields' => 'id,nickname,user_total_coin', 'rid' => 'ALL']);
			if (false === $oEnrollUsrALL) {
				$inDataALL = [];
				$inDataALL['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inDataALL['user_total_coin'] = $inDataALL['user_total_coin'] + (int) $rule->actor_delta;
				}

				$inDataALL['rid'] = 'ALL';
				$modelUsr->add($matter, $user, $inDataALL);
			} else {
				if (count($rules)) {
					$upDataALL = [];
					$upDataALL['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
					foreach ($rules as $rule) {
						$upDataALL['user_total_coin'] = $upDataALL['user_total_coin'] + (int) $rule->actor_delta;
					}
					if ($upDataALL['user_total_coin'] !== (int) $oEnrollUsrALL->user_total_coin) {
						$modelUsr->update('xxt_enroll_user', $upDataALL, ['id' => $oEnrollUsrALL->id]);
					}
				}
			}
		}

		return new \ResponseData('ok');
	}
}