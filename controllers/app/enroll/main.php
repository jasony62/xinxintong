<?php
namespace app\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动
 */
class main extends base {
	/**
	 * 返回活动页
	 *
	 * 活动是否只向会员开放，如果是要求先成为会员，否则允许直接
	 * 如果已经报过名如何判断？
	 * 如果已经是会员，则可以查看和会员的关联
	 * 如果不是会员，临时分配一个key，保存在cookie中，允许重新报名
	 *
	 * $mpid 因为活动有可能来源于父账号，因此需要指明活动是在哪个公众号中进行的
	 * $aid
	 * $page 要进入活动的哪一页
	 * $ek 登记记录的id
	 * $shareid 谁进行的分享
	 * $mocker 用于测试，模拟访问用户
	 * $code OAuth返回的code
	 *
	 */
	public function index_action($mpid, $aid, $shareby = '', $page = '', $ek = '', $mocker = '', $code = null, $ignoretime = 'N') {
		empty($mpid) && $this->outputError('没有指定当前公众号的ID');
		empty($aid) && $this->outputError('登记活动ID为空');

		$modelApp = $this->model('app\enroll');
		$modelPage = $this->model('app\enroll\page');
		$app = $modelApp->byId($aid, array('cascaded' => 'N'));
		$app->pages = $modelPage->byApp($aid, array('cascaded' => 'N'));
		/** 判断活动的开始结束时间 */
		if ($ignoretime === 'N') {
			$tipPage = false;
			$current = time();
			if ($app->start_at != 0 && !empty($app->before_start_page) && $current < $app->start_at) {
				$tipPage = $app->before_start_page;
			} else if ($app->end_at != 0 && !empty($app->after_end_page) && $current > $app->end_at) {
				$tipPage = $app->after_end_page;
			}
			if ($tipPage !== false) {
				$mapPages = array();
				foreach ($app->pages as &$p) {
					$mapPages[$p->name] = $p;
				}
				$oPage = $mapPages[$tipPage];
				$oPage = $modelPage->byId($aid, $oPage->id);
				!empty($oPage->html) && \TPL::assign('body', $oPage->html);
				!empty($oPage->css) && \TPL::assign('css', $oPage->css);
				!empty($oPage->js) && \TPL::assign('js', $oPage->js);
				\TPL::assign('title', $app->title);
				\TPL::output('info');
				exit;
			}
		}
		/*获得当前访问用户的信息*/
		$openid = $this->doAuth($mpid, $code, $mocker);
		$options = array('verbose' => array('fan' => 'Y'));
		if (!empty($openid)) {
			$options['openid'] = $openid;
		} else if (!empty($mocker)) {
			$options['openid'] = $mocker;
		}
		if (!empty($app->authapis)) {
			$options['authapis'] = $app->authapis;
			$options['matter'] = $app;
			$options['verbose']['member'] = 'Y';
		}
		/*提示用户在PC端完成操作*/
		if ($this->getClientSrc() && isset($app->shift2pc) && $app->shift2pc === 'Y') {
			if (isset($user->fan)) {
				$fea = $this->model('mp\mpaccount')->getFeature($mpid, 'shift2pc_page_id');
				$pageOfShift2Pc = $this->model('code\page')->byId($fea->shift2pc_page_id, 'html,css,js');
				/*任务码*/
				if ($app->can_taskcode && $app->can_taskcode === 'Y') {
					$httpHost = $_SERVER['HTTP_HOST'];
					$httpHost = str_replace('www.', '', $_SERVER['HTTP_HOST']);
					$myUrl = "http://$httpHost" . $_SERVER['REQUEST_URI'];
					$taskCode = $this->model('task')->addTask($mpid, $user->fan->fid, $myUrl);
					$pageOfShift2Pc->html = str_replace('{{taskCode}}', $taskCode, $pageOfShift2Pc->html);
				}
				//\TPL::assign('shift2pcAlert', $pageOfShift2Pc);
			}
		} else {
			$user = $this->getUser($mpid, $options);
		}
		/**记录日志，完成前置活动再次进入的情况不算 */
		$this->model()->update("update xxt_enroll set read_num=read_num+1 where id='$app->id'");
		$this->logRead($mpid, $user, $app->id, 'enroll', $app->title, $shareby);
		/*根据要打开的页面确定使用的模板*/
		$oPage = null;
		$hasEnrolled = $modelApp->hasEnrolled($mpid, $aid, $user);
		empty($page) && $page = $this->_defaultPage($mpid, $app, $user, $hasEnrolled, true);
		foreach ($app->pages as $p) {
			if ($p->name === $page) {
				$oPage = $p;
				break;
			}
		}
		if (empty($oPage)) {
			$this->outputError('指定的页面[' . $page . ']不存在');
			exit;
		}
		\TPL::assign('title', $app->title);
		if ($oPage->type === 'I') {
			\TPL::output('/app/enroll/input');
		} else {
			\TPL::output('/app/enroll/page');
		}
		exit;
	}
	/**
	 * 当前用户的缺省页面
	 */
	private function _defaultPage($mpid, $app, $user, $hasEnrolled = false, $redirect = false) {
		if ($hasEnrolled && !empty($app->enrolled_entry_page)) {
			$page = $app->enrolled_entry_page;
		} else {
			$page = $this->checkEntryRule($mpid, $app, $user, $redirect);
		}
		return $page;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $mpid
	 * @param string $aid
	 * @param string $rid round's id
	 * @param string $page page's name
	 * @param string $ek record's enroll key
	 * @param string $newRecord
	 */
	public function get_action($mpid, $aid, $rid = null, $page = null, $ek = null, $newRecord = null) {
		$params = array();

		$modelApp = $this->model('app\enroll');
		$modelPage = $this->model('app\enroll\page');
		$app = $modelApp->byId($aid, array('cascaded' => 'N'));
		/*登记活动定义*/
		$params['app'] = $app;
		/*当前访问用户的基本信息*/
		$user = $this->getUser($mpid,
			array(
				'authapis' => $app->authapis,
				'matter' => $app,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		$params['user'] = $user;
		/*打开页面*/
		$app->pages = $modelPage->byApp($aid, array('cascaded' => 'N'));
		$hasEnrolled = $modelApp->hasEnrolled($mpid, $app->id, $user);
		empty($page) && $page = $this->_defaultPage($mpid, $app, $user, $hasEnrolled);
		foreach ($app->pages as $p) {
			if ($p->name === $page) {
				$oPage = $p;
				break;
			}
		}
		if (!isset($oPage)) {
			return new \ResponseError('指定的页面[' . $page . ']不存在');
		}
		$oPage = $modelPage->byId($aid, $oPage->id);
		$params['page'] = $oPage;
		/* 自动登记 */
		if (!$hasEnrolled && $app->can_autoenroll === 'Y' && $oPage->autoenroll_onenter === 'Y') {
			$modelRec = $this->model('app\enroll\record');
			$options = array(
				'fields' => 'enroll_key,enroll_at',
			);
			$lastRecord = $modelRec->getLast($mpid, $aid, $user, $options);
			if (false === $lastRecord) {
				$modelRec->add($mpid, $app, $user, (empty($posted->referrer) ? '' : $posted->referrer));
			} else if ($lastRecord->enroll_at === '0') {
				$updated = array(
					'enroll_at' => time(),
				);
				!empty($posted->referrer) && $updated['referrer'] = $posted->referrer;
				$modelRec->update('xxt_enroll_record', $updated, "enroll_key='$lastRecord->enroll_key'");
			}
		}
		if ($app->multi_rounds === 'Y') {
			$params['activeRound'] = $this->model('app\enroll\round')->getLast($mpid, $aid);
		}
		/*登记记录*/
		$newForm = false;
		if ($oPage->type === 'I') {
			if ($newRecord === 'Y') {
				$newForm = true;
			} else if (empty($ek)) {
				if ($app->open_lastroll === 'N') {
					$newForm = true;
				}
			}
			if ($newForm === false) {
				/*获得最后一条登记数据。登记记录有可能未进行过登记*/
				$options = array(
					'fields' => '*',
				);
				$modelRec = $this->model('app\enroll\record');
				$lastRecord = $modelRec->getLast($mpid, $aid, $user, $options);
				if ($lastRecord) {
					if ($lastRecord->enroll_at) {
						$lastRecord->data = $modelRec->dataById($lastRecord->enroll_key);
					}
				}
				$params['record'] = $lastRecord;
				/*数据定义*/
				$schema = $this->model('app\enroll\page')->schemaByApp($aid);
				$params['schema'] = $schema;
			}
		}

		return new \ResponseData($params);
	}
	/**
	 * 获得指定坐标对应的地址名称
	 *
	 * 没有指定位置信息时通过日志获取当前用户最后一次发送的位置
	 */
	public function locationGet_action($mpid, $lat = '', $lng = '') {
		$geo = array();
		if (empty($lat) || empty($lat)) {
			$user = $this->getUser($mpid);
			if (empty($user->openid)) {
				return new \ResponseError('无法获得身份信息');
			}
			$q = array(
				'max(id)',
				'xxt_log_mpreceive',
				"mpid='$mpid' and openid='$user->openid' and type='event' and data like '%LOCATION%'",
			);
			if ($lastid = $this->model()->query_val_ss($q)) {
				$q = array(
					'data',
					'xxt_log_mpreceive',
					"id=$lastid",
				);
				$data = $this->model()->query_val_ss($q);
				$data = json_decode($data);
				$lat = $data[1];
				$lng = $data[2];
			} else {
				return new \ResponseError('无法获取位置信息');
			}
		}

		$url = "http://apis.map.qq.com/ws/geocoder/v1/";
		$url .= "?location=$lat,$lng";
		$url .= "&key=JUXBZ-JL3RW-UYYR2-O3QGA-CDBSZ-QBBYK";
		$rsp = file_get_contents($url);
		$rsp = json_decode($rsp);
		if ($rsp->status !== 0) {
			return new \ResponseError($rsp->message);
		}
		$geo['address'] = $rsp->result->address;

		return new \ResponseData($geo);
	}
	/**
	 * 根据邀请用户数的排名
	 */
	public function rankByFollower_action($mpid, $aid) {
		$modelApp = $this->model('app\enroll');
		$user = $this->getUser($mpid);
		$rank = $modelApp->rankByFollower($mpid, $aid, $user->openid);

		return new \ResponseData(array('rank' => $rank));
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计radio/checkbox类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function statGet_action($mpid, $aid, $fromCache = 'N', $interval = 600) {
		if ($fromCache === 'Y') {
			$current = time();
			$model = $this->model();
			$q = array(
				'create_at,id,title,v,l,c',
				'xxt_enroll_record_stat',
				"aid='$aid'",
			);
			$cached = $model->query_objs_ss($q);
			if (count($cached) && $cached[0]->create_at >= $current - $interval) {
				/*从缓存中获取统计数据*/
				$result = array();
				foreach ($cached as $data) {
					if (isset($result[$data->id])) {
						$item = &$result[$data->id];
					} else {
						$item = array(
							'id' => $data->id,
							'title' => $data->title,
							'ops' => array(),
						);
						$result[$data->id] = &$item;
					}
					$op = array(
						'v' => $data->v,
						'l' => $data->l,
						'c' => $data->c,
					);
					$item['ops'][] = $op;
				}
			} else {
				$result = $this->model('app\enroll')->getStat($aid);
				/*更新缓存的统计数据*/
				$model->delete('xxt_enroll_record_stat', "aid='$aid'");
				foreach ($result as $id => $stat) {
					foreach ($stat['ops'] as $op) {
						$r = array(
							'aid' => $aid,
							'create_at' => $current,
							'id' => $id,
							'title' => $stat['title'],
							'v' => $op['v'],
							'l' => $op['l'],
							'c' => $op['c'],
						);
						$model->insert('xxt_enroll_record_stat', $r);
					}
				}
			}
		} else {
			/*直接获取统计数据*/
			$result = $this->model('app\enroll')->getStat($aid);
		}

		return new \ResponseData($result);
	}
}