<?php
namespace app\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动
 */
class main extends base {
	/**
	 * 获得当前访问用户的信息
	 *
	 * $mpid
	 * $act
	 * $ooid
	 */
	private function getVisitorInfo($mpid, $act, $ooid = null, $checkAccessControl = false, $askAuth = false) {
		/**
		 * 当前用户在cookie中的记录
		 */
		empty($ooid) && $ooid = $this->getCookieOAuthUser($mpid);
		/**
		 * 确保只有认证过的用户才能提交数据
		 * todo 企业号直接跳过这个限制？
		 */
		$mid = '';
		if ($act->access_control === 'Y' && $checkAccessControl) {
			/**
			 * 仅限注册用户报名，若不是注册用户，先要求进行注册
			 */
			if ($askAuth) {
				$myUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$mpid&aid=$act->id";
				$member = $this->accessControl($mpid, $act->id, $act->authapis, $ooid, $act, $myUrl);
			} else {
				$member = $this->accessControl($mpid, $act->id, $act->authapis, $ooid, $act, false);
			}
			$mid = $member->mid;
		}

		if (empty($ooid) && !empty($mid)) {
			$fan = $this->model('user/fans')->byMid($mid, 'openid');
			$ooid = $fan->openid;
		} else if (!empty($ooid)) {
			$fan = $this->model('user/fans')->byOpenid($mpid, $ooid);
		}

		$vid = $this->getVisitorId($mpid);

		return array($ooid, $mid, $vid, isset($fan) ? $fan->fid : '');
	}
	/**
	 * 获得当前用户的相关信息
	 *
	 * todo 认证用户信息如何体现？
	 */
	private function getUserInfo($mpid, $openid) {
		if ($user = $this->model('user/fans')->byOpenid($mpid, $openid)) {

			$members = $this->model('user/member')->byOpenid($mpid, $openid);
			foreach ($members as &$member) {
				if (!empty($member->depts)) {
					$member->depts = $this->model('user/member')->getDepts($member->mid, $member->depts);
				}

				if (!empty($member->tags)) {
					$member->tags = $this->model('user/member')->getTags($member->mid, $member->tags);
				}

			}

			$user->members = $members;
		}

		return $user;
	}
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
		/**
		 * 判断活动的开始结束时间
		 */
		$enrollModel = $this->model('app\enroll');
		$act = $enrollModel->byId($aid);
		$tipPage = false;
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
			$oPage = $act->pages[$tipPage];
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
		$enrollModel = $this->model('app\enroll');
		$act = $enrollModel->byId($aid);
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->getUser($mpid,
			array(
				'authapis' => $act->authapis,
				'openid' => $openid,
				'matter' => $act,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		//die(json_encode($user));
		/**
		 * 如果没有指定页面，计算应该进入到哪一个状态页
		 * todo 需要避免直接指定page进入的情况
		 */
		if (empty($page)) {
			if ($enrollModel->hasEnrolled($mpid, $act->id, $user->openid) && !empty($act->enrolled_entry_page)) {
				$page = $act->enrolled_entry_page;
			} else {
				if (!$this->getClientSrc() && empty($user->openid)) {
					/**
					 * 非易信、微信公众号打开，无法获得openid
					 */
					if (!empty($act->authapis)) {
						/**
						 * 如果活动限认证用户访问
						 */
						$page = '$authapi_auth';
					} else {
						$page = $act->entry_rule->nonfan->entry;
					}
				} else {
					if (empty($user->fan)) {
						/**
						 * 非关注用户
						 */
						$page = $act->entry_rule->nonfan->entry;
					} else {
						if (isset($user->fan)) {
							/**
							 * 关注用户
							 */
							$page = $act->entry_rule->fan->entry;
						}
						if (isset($user->membersInAcl) && !empty($user->members)) {
							/**
							 * 认证用户不在白名单中
							 */
							$page = $act->entry_rule->member_outacl->entry;

						}
						if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
							/**
							 * 白名单中的认证用户，或者，不限制白名单的认证用户
							 */
							$page = $act->entry_rule->member->entry;
						}
					}
				}
				switch ($page) {
				case '$authapi_outacl':
					$actAuthapis = explode(',', $act->authapis);
					$this->gotoOutAcl($mpid, $actAuthapis[0]);
					break;
				case '$authapi_auth':
					$actAuthapis = explode(',', $act->authapis);
					$this->gotoAuth($mpid, $actAuthapis, $user->openid);
					break;
				case '$mp_follow':
					$this->askFollow($mpid, $openid);
					break;
				}
			}
		}

		empty($act->pages[$page]) && $this->outputError("指定页面[$page]不存在");

		if (isset($user->fan) && $this->getClientSrc() && isset($act->shift2pc) && $act->shift2pc === 'Y') {
			/**
			 * 提示在PC端完成
			 */
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
			\TPL::assign('shift2pcAlert', $pageOfShift2Pc);
		}

		$oPage = $act->pages[$page];
		\TPL::assign('page', $oPage->name);
		!empty($oPage->html) && \TPL::assign('extra_html', $oPage->html);
		!empty($oPage->css) && \TPL::assign('extra_css', $oPage->css);
		!empty($oPage->js) && \TPL::assign('extra_js', $oPage->js);
		!empty($oPage->ext_js) && \TPL::assign('ext_js', $oPage->ext_js);
		!empty($oPage->ext_css) && \TPL::assign('ext_css', $oPage->ext_css);
		/**
		 * 全局设置
		 */
		\TPL::assign('title', $act->title);
		$mpsetting = $this->getMpSetting($mpid);
		\TPL::assign('body_ele', $mpsetting->body_ele);
		\TPL::assign('body_css', $mpsetting->body_css);
		/**
		 * 记录日志，完成前置活动再次进入的情况不算
		 */
		$this->model()->update("update xxt_enroll set read_num=read_num+1 where id='$act->id'");

		$logUser = new \stdClass;
		$logUser->vid = $user->vid;
		$logUser->openid = $user->openid;
		$logUser->nickname = isset($user->fan) ? $user->fan->nickname : '';

		$logMatter = new \stdClass;
		$logMatter->id = $act->id;
		$logMatter->type = 'enroll';
		$logMatter->title = $act->title;

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$search = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->model('log')->writeMatterRead($mpid, $logUser, $logMatter, $logClient, $shareby, $search, $referer);

		$this->view_action('/app/enroll/page');
	}
	/**
	 * 返回活动数据
	 */
	public function get_action($mpid, $aid, $rid = null, $page, $ek = null) {
		$params = array();

		$enrollModel = $this->model('app\enroll');
		$act = $enrollModel->byId($aid);
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
		 * 登记活动管理员
		 */
		$admins = \TMS_APP::model('acl')->enrollReceivers($mpid, $aid);
		$params['admins'] = $admins;
		/**
		 * 页面
		 */
		$newForm = false;
		$oPage = $act->pages[$page];
		if ($page === 'form' || $oPage->type === 'I') {
			if ($act->open_lastroll === 'N' && empty($ek)) {
				$newForm = true;
			}

		}
		$params['page'] = $oPage;
		/**
		 * 设置页面登记数据
		 */
		list($openedek, $record, $statdata) = $this->getPageData($mpid, $act, $rid, $ek, $user->openid, $page, $newForm);
		$params['enrollKey'] = $openedek;
		$params['record'] = $record;
		$params['statdata'] = $statdata;

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
	private function getPageData($mpid, $act, $rid, $ek, $openid, $page, $newForm = false) {
		$modelEnroll = $this->model('app\enroll');
		$openedek = $ek;
		$record = null;
		/**
		 * 打开登记数据页
		 */
		if (empty($openedek)) {
			if (!$newForm) {
				/**
				 * 获得最后一条登记数据
				 */
				$enrollList = $modelEnroll->getRecordList($mpid, $act->id, $openid, $rid);
				if (!empty($enrollList)) {
					$record = $enrollList[0];
					$openedek = $record->enroll_key;
					$record->data = $modelEnroll->getRecordData($openedek);
				}
			}
		} else {
			/**
			 * 打开指定的登记记录
			 */
			$record = $modelEnroll->getRecordById($openedek);
		}

		/**
		 * 互动数据
		 */
		if (!empty($openedek)) {
			/**
			 * 登记人信息
			 */
			if (!empty($record->openid)) {
				$record->enroller = $this->getUserInfo($mpid, $record->openid);
			}

			/**
			 * 评论数据
			 */
			$record->remarks = $modelEnroll->getRecordRemarks($openedek);
		}

		$statdata = $modelEnroll->getStat($act->id);

		return array($openedek, $record, $statdata);
	}
	/**
	 * 登记记录点赞
	 *
	 * $mpid
	 * $ek
	 */
	public function recordScore_action($mpid, $ek) {
		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid', 'xxt_enroll_record', "enroll_key='$ek'");
		$aid = $this->model()->query_val_ss($q);
		$act = $modelEnroll->byId($aid);
		/**
		 * 当前用户
		 */
		list($openid) = $this->getVisitorInfo($mpid, $act);

		if ($modelEnroll->rollPraised($openid, $ek)) {
			/**
			 * 点了赞，再次点击，取消赞
			 */
			$this->model()->delete(
				'xxt_enroll_record_score',
				"enroll_key='$ek' and openid='$openid'"
			);
			$myScore = 0;
		} else {
			/**
			 * 点赞
			 */
			$i = array(
				'openid' => $openid,
				'enroll_key' => $ek,
				'create_at' => time(),
				'score' => 1,
			);
			$this->model()->insert('xxt_enroll_record_score', $i, false);
			$myScore = 1;
		}
		/**
		 * 获得点赞的总数
		 */
		$score = $modelEnroll->rollScore($ek);
		$this->model()->update('xxt_enroll_record', array('score' => $score), "enroll_key='$ek'");

		return new \ResponseData(array($myScore, $score));
	}
	/**
	 * 针对登记记录发表评论
	 *
	 * $mpid
	 * $ek
	 */
	public function recordRemark_action($mpid, $ek) {
		$data = $this->getPostJson();
		if (empty($data->remark)) {
			return new \ResponseError('评论不允许为空！');
		}

		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid,openid', 'xxt_enroll_record', "enroll_key='$ek'");
		$record = $this->model()->query_obj_ss($q);
		$aid = $record->aid;
		$act = $modelEnroll->byId($aid);
		/**
		 * 发表评论的用户
		 */
		list($openid) = $this->getVisitorInfo($mpid, $act, null, true, true);
		if (empty($openid)) {
			return new \ResponseError('无法获得用户身份标识');
		}

		$remarker = $this->model('user/fans')->byOpenid($mpid, $openid);
		if (empty($remarker)) {
			return new \ResponseError('无法获得用户身份信息');
		}

		$remark = array(
			'openid' => $openid,
			'enroll_key' => $ek,
			'create_at' => time(),
			'remark' => $this->model()->escape($data->remark),
		);
		$remark['id'] = $this->model()->insert('xxt_enroll_record_remark', $remark, true);
		$remark['nickname'] = $remarker->nickname;
		$this->model()->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		/**
		 * 通知登记人有评论
		 */
		if ($act->remark_notice === 'Y' && !empty($act->remark_notice_page)) {
			$apis = $this->model('mp\mpaccount')->getApis($mpid);
			if ($apis && $apis->{$apis->mpsrc . '_custom_push'} === 'Y') {
				/**
				 * 发送评论提醒
				 */
				$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$mpid&aid=$aid&ek=$ek&page=$act->remark_notice_page";
				$text = urlencode($remark['nickname'] . '对【');
				$text .= '<a href="' . $url . '">';
				$text .= urlencode($act->title);
				$text .= '</a>';
				$text .= urlencode('】发表了评论：' . $remark['remark']);
				$message = array(
					"msgtype" => "text",
					"text" => array(
						"content" => $text,
					),
				);
				/**
				 * 通知登记人
				 */
				if ($this->model('log')->canReceivePush($mpid, $record->openid)) {
					if ($record->openid !== $remarker->openid) {
						$this->send_to_user($mpid, $record->openid, $message);
					}

				}
				/**
				 * 通知其他发表了评论的用户
				 */
				$others = $modelEnroll->getRecordRemarkers($ek);
				foreach ($others as $other) {
					if ($other->openid === $record->openid || $other->openid === $remarker->openid) {
						continue;
					}

					$this->send_to_user($mpid, $other->openid, $message);
				}
			}
		}

		return new \ResponseData($remark);
	}
	/**
	 * 返回当前用户的报名数据
	 *
	 * $aid
	 */
	public function hasEnrolled_action($aid) {
		$modelEnroll = $this->model('app\enroll');

		$act = $modelEnroll->byId($aid);
		/**
		 * 检查是否为关注用户
		 */
		$ooid = $this->getCookieOAuthUser($act->mpid);

		if ($modelEnroll->hasEnrolled($act->mpid, $aid, $ooid)) {
			return new \ResponseData(true);
		} else {
			return new \ResponseError('没有报名');
		}

	}
	/**
	 * 列出所有的登记记录
	 *
	 * $mpid
	 * $aid
	 * $orderby
	 * $openid
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function records_action($mpid, $aid, $rid = '', $orderby = 'time', $openid = null, $page = 1, $size = 10) {
		$modelEnroll = $this->model('app\enroll');

		$act = $modelEnroll->byId($aid);

		list($ooid) = $this->getVisitorInfo($mpid, $act);

		$options = array(
			'creater' => $openid,
			'visitor' => $ooid,
			'rid' => $rid,
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
		);

		$rst = $modelEnroll->getRecords($mpid, $aid, $options);

		return new \ResponseData($rst);
	}
	/**
	 * 列出当前访问用户所有的登记记录
	 *
	 * $mpid
	 * $aid
	 * $orderby
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function myRecords_action($mpid, $aid, $rid = '', $orderby = 'time', $page = 1, $size = 10) {
		$modelEnroll = $this->model('app\enroll');

		$act = $modelEnroll->byId($aid);

		$user = $this->getUser($mpid, array('authapis' => $act->authapis));
		if (!$this->getClientSrc() && empty($user->openid)) {
			return new \ResponseError('无法获得用户身份信息');
		}

		$options = array(
			'creater' => $user->openid,
			'visitor' => $user->openid,
			'rid' => $rid,
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
		);

		$rst = $modelEnroll->getRecords($mpid, $aid, $options);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $mpid
	 * $aid
	 */
	public function rounds_action($mpid, $aid) {
		$rounds = $this->model('app\enroll')->getRounds($mpid, $aid);

		return new \ResponseData($rounds);
	}
	/**
	 * 获得指定坐标对应的地址名称
	 *
	 * 没有指定位置信息时通过日志获取当前用户最后一次发送的位置
	 */
	public function locationGet_action($mpid, $lat = '', $lng = '') {
		$openid = $this->getCookieOAuthUser($mpid);
		if (empty($openid)) {
			return new \ResponseError('无法获得身份信息');
		}

		$geo = array();
		if (empty($lat) || empty($lat)) {
			$q = array(
				'max(id)',
				'xxt_log_mpreceive',
				"mpid='$mpid' and openid='$openid' and type='event' and data like '%LOCATION%'",
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
}
