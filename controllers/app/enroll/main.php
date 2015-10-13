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
	public function index_action($mpid, $aid, $shareby = '', $page = '', $ek = '', $mocker = '', $code = null) {
		empty($mpid) && $this->outputError('没有指定当前公众号的ID');
		empty($aid) && $this->outputError('登记活动ID为空');

		$modelApp = $this->model('app\enroll');
		$act = $modelApp->byId($aid);

		$tipPage = false;
		/**
		 * 判断活动的开始结束时间
		 */
		$current = time();
		if ($act->start_at != 0 && !empty($act->before_start_page) && $current < $act->start_at) {
			/**
			 * 活动没有开始
			 */
			$tipPage = $act->before_start_page;
		} else if ($act->end_at != 0 && !empty($act->after_end_page) && $current > $act->end_at) {
			/**
			 * 活动已经结束
			 */
			$tipPage = $act->after_end_page;
		}
		if ($tipPage !== false) {
			$mapPages = array();
			foreach ($act->pages as &$p) {
				$mapPages[$p->name] = $p;
			}
			$oPage = $mapPages[$tipPage];
			\TPL::assign('page', $oPage->name);
			!empty($oPage->html) && \TPL::assign('extra_html', $oPage->html);
			!empty($oPage->css) && \TPL::assign('extra_css', $oPage->css);
			!empty($oPage->js) && \TPL::assign('extra_js', $oPage->js);
			!empty($oPage->ext_js) && \TPL::assign('ext_js', $oPage->ext_js);
			!empty($oPage->ext_css) && \TPL::assign('ext_css', $oPage->ext_css);
			\TPL::assign('title', $act->title);
			$mpsetting = $this->getMpSetting($mpid);
			\TPL::assign('body_ele', $mpsetting->body_ele);
			\TPL::assign('body_css', $mpsetting->body_css);
			$this->view_action('/app/enroll/page');
		}
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $aid, $ek, $page, $shareby, $openid);
	}
	/**
	 * 返回活动页面
	 */
	private function afterOAuth($mpid, $aid, $ek, $page, $shareby, $openid = null) {
		$modelApp = $this->model('app\enroll');
		$act = $modelApp->byId($aid);
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->getUser($mpid);
		/* 提示在PC端完成 */
		if (isset($user->fan) && $this->getClientSrc() && isset($act->shift2pc) && $act->shift2pc === 'Y') {
			$fea = $this->model('mp\mpaccount')->getFeatures($mpid, 'shift2pc_page_id');
			$pageOfShift2Pc = $this->model('code/page')->byId($fea->shift2pc_page_id, 'html,css,js');
			/**
			 * 任务码
			 */
			if ($act->can_taskcode && $act->can_taskcode === 'Y') {
				$httpHost = $_SERVER['HTTP_HOST'];
				$httpHost = str_replace('www.', '', $_SERVER['HTTP_HOST']);
				$myUrl = "http://$httpHost" . $_SERVER['REQUEST_URI'];
				$taskCode = $this->model('task')->addTask($mpid, $user->fan->fid, $myUrl);
				$pageOfShift2Pc->html = str_replace('{{taskCode}}', $taskCode, $pageOfShift2Pc->html);
			}
			//\TPL::assign('shift2pcAlert', $pageOfShift2Pc);
		}
		/**
		 * 记录日志，完成前置活动再次进入的情况不算
		 */
		$this->model()->update("update xxt_enroll set read_num=read_num+1 where id='$act->id'");
		$this->logRead($mpid, $user, $act->id, 'enroll', $act->title, $shareby);

		\TPL::assign('title', $act->title);
		\TPL::output('/app/enroll/page');
		exit;
	}
	/**
	 *
	 */
	private function defaultPage($mpid, $act, $user, $hasEnrolled = false) {
		if ($hasEnrolled && !empty($act->enrolled_entry_page)) {
			$page = $act->enrolled_entry_page;
		} else {
			$page = $this->checkEntryRule($mpid, $act, $user);
		}
		return $page;
	}
	/**
	 * 返回活动数据
	 */
	public function get_action($mpid, $aid, $rid = null, $page = null, $ek = null) {
		$params = array();

		$modelApp = $this->model('app\enroll');
		$act = $modelApp->byId($aid);
		$params['enroll'] = $act;
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->getUser($mpid,
			array(
				'authapis' => $act->authapis,
				'matter' => $act,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		$params['user'] = $user;
		/**
		 * 页面
		 */
		$hasEnrolled = $modelApp->hasEnrolled($mpid, $act->id, $user->openid);
		empty($page) && $page = $this->defaultPage($mpid, $act, $user, $hasEnrolled);
		foreach ($act->pages as $p) {
			if ($p->name === $page) {
				$oPage = $p;
				break;
			}
		}
		if (!isset($oPage)) {
			return new \ResponseError('指定的页面[' . $page . ']不存在');
		}
		$params['page'] = $oPage;
		/**
		 * 登记活动管理员
		 */
		$admins = \TMS_APP::model('acl')->enrollReceivers($mpid, $aid);
		$params['admins'] = $admins;
		/* 自动登记 */
		if (!$hasEnrolled && $act->can_autoenroll === 'Y' && $oPage->autoenroll_onenter === 'Y') {
			$modelRec = $this->model('app\enroll\record');
			$modelRec->add($mpid, $act, $user, (empty($posted->referrer) ? '' : $posted->referrer));
		}
		/**
		 * 设置页面登记数据
		 */
		$newForm = false;
		if ($oPage->type === 'I' && empty($ek)) {
			if ($act->open_lastroll === 'N' || (!empty($page) && $page === $oPage->name)) {
				$newForm = true;
			}
		}
		list($openedek, $record, $statdata) = $this->getRecord($mpid, $act, $rid, $ek, $user->openid, $page, $newForm);
		if ($newForm === false) {
			$params['enrollKey'] = $openedek;
			$params['record'] = $record;
		}
		$params['statdata'] = $statdata;
		/**
		 * 公众号信息
		 */
		$mpaccount = $this->getMpSetting($mpid);
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $user_agent)) {
			$modelMpa = $this->model('mp\mpaccount');
			$mpa = $modelMpa->byId($mpid, 'yx_cardname,yx_cardid');
			$mpaccount->yx_cardname = $mpa->yx_cardname;
			$mpaccount->yx_cardid = $mpa->yx_cardid;
		}
		$params['mpaccount'] = $mpaccount;

		return new \ResponseData($params);
	}
	/**
	 *
	 * $mpid
	 * $act
	 * $ek
	 * $openid
	 * $page
	 * $newForm
	 *
	 */
	private function getRecord($mpid, $act, $rid, $ek, $openid, $page, $newForm = false) {
		$openedek = $ek;
		$record = null;
		$modelRec = $this->model('app\enroll\record');
		/**
		 * 登记数据
		 */
		if (empty($openedek)) {
			if (!$newForm) {
				/**
				 * 获得最后一条登记数据
				 */
				$myRecords = $modelRec->byUser($mpid, $act->id, $openid, $rid);
				if (!empty($myRecords)) {
					$record = $myRecords[0];
					$openedek = $record->enroll_key;
					$record->data = $modelRec->dataById($openedek);
				}
			}
		} else {
			/**
			 * 打开指定的登记记录
			 */
			$record = $modelRec->byId($openedek);
		}
		/**
		 * 互动数据
		 */
		if (!empty($openedek)) {
			/**
			 * 登记人信息
			 */
			if (!empty($record->openid)) {
				$options = array(
					'openid' => $record->openid,
					'verbose' => array('fan' => 'Y', 'member' => 'Y'),
				);
				$record->enroller = $this->getUser($mpid, $options);
				if (!empty($record->enroller->fan)) {
					if ($record->nickname !== $record->enroller->fan->nickname) {
						$record->nickname = $record->enroller->fan->nickname;
						$this->model()->update('xxt_enroll_record', array('nickname' => $record->nickname), "enroll_key='$record->enroll_key'");
					}
				}
			}
			/**
			 * 评论数据
			 */
			$record->remarks = $modelRec->remarks($openedek);
		}
		/**
		 * 统计数据
		 */
		$modelEnroll = $this->model('app\enroll');
		$statdata = $modelEnroll->getStat($act->id);

		return array($openedek, $record, $statdata);
	}
	/**
	 * 获得指定坐标对应的地址名称
	 *
	 * 没有指定位置信息时通过日志获取当前用户最后一次发送的位置
	 */
	public function locationGet_action($mpid, $lat = '', $lng = '') {
		$fan = $this->getCookieOAuthUser($mpid);
		if (empty($fan->openid)) {
			return new \ResponseError('无法获得身份信息');
		}

		$geo = array();
		if (empty($lat) || empty($lat)) {
			$q = array(
				'max(id)',
				'xxt_log_mpreceive',
				"mpid='$mpid' and openid='$fan->openid' and type='event' and data like '%LOCATION%'",
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
}