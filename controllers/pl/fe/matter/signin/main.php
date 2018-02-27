<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 签到活动主控制器
 */
class main extends \pl\fe\matter\main_base {
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 返回一个签到活动
	 */
	public function get_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\signin')->byId($id);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/*关联登记活动*/
		if ($oApp->enroll_app_id) {
			$oApp->enrollApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
		}
		/*关联分组活动*/
		if ($oApp->group_app_id) {
			$oApp->groupApp = $this->model('matter\group')->byId($oApp->group_app_id);
		}
		/*所属项目*/
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回签到活动列表
	 * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
	 *
	 */
	public function list_action($site = null, $mission = null, $page = null, $size = null, $cascaded = '', $onlySns = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelSig = $this->model('matter\signin');
		$oOptions = [];
		if (!empty($oPosted->byTitle)) {
			$oOptions['byTitle'] = $oPosted->byTitle;
		}
		if (empty($mission)) {
			$site = $modelSig->escape($site);
			if (!empty($oPosted->byTags)) {
				$oOptions['byTags'] = $oPosted->byTags;
			}
			if (!empty($oPosted->byStar) && $oPosted->byStar === 'Y') {
				$oOptions['byStar'] = $oUser->id;
			}
			$oOptions['user'] = $oUser;
			$result = $modelSig->bySite($site, $page, $size, $onlySns, $oOptions);
		} else {
			$result = $modelSig->byMission($mission, $oOptions, $page, $size);
		}

		if (strlen($cascaded) && count($result->apps)) {
			$cascaded = explode(',', $cascaded);
			$modelSigRnd = $this->model('matter\signin\round');
			foreach ($result->apps as &$oApp) {
				if (in_array('round', $cascaded)) {
					/* 轮次 */
					$oApp->rounds = $modelSigRnd->byApp($oApp->id, ['fields' => 'id,rid,title,start_at,end_at,late_at']);
				}
				if (in_array('opData', $cascaded)) {
					$oApp->opData = $modelSig->opData($oApp, true);
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 返回和指定登记活动关联的签到活动列表
	 *
	 */
	public function listByEnroll_action($site, $enroll) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSig = $this->model('matter\signin');
		$result = $modelSig->byEnrollApp($enroll);

		return new \ResponseData($result);
	}
	/**
	 * 创建一个空的签到活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function create_action($site, $mission = null, $template = 'basic') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		if (empty($mission)) {
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		}
		$oCustomConfig = $this->getPostJson();

		$modelApp = $this->model('matter\signin')->setOnlyWriteDbConn(true);
		$oNewApp = $modelApp->createByTemplate($oUser, $oSite, $oCustomConfig, $oMission, $template);

		return new \ResponseData($oNewApp);
	}
	/**
	 *
	 * 复制一个登记活动
	 *
	 * @param string $site
	 * @param string $app
	 * @param int $mission
	 *
	 */
	public function copy_action($site, $app, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelApp = $this->model('matter\signin')->setOnlyWriteDbConn(true);
		$oCopied = $modelApp->byId($app);
		if (false === $oCopied) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$modelCode = $this->model('code\page');
		/**
		 * 获得的基本信息
		 */
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $site;
		$oNewApp->start_at = $current;
		$oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
		$oNewApp->pic = $oCopied->pic;
		$oNewApp->summary = $modelApp->escape($oCopied->summary);
		$oNewApp->data_schemas = $modelApp->escape($oCopied->data_schemas);
		$oNewApp->entry_rule = json_encode($oCopied->entryRule);
		if (!empty($mission)) {
			$oNewApp->mission_id = $mission;
		}

		$oNewApp = $modelApp->create($oUser, $oNewApp);
		/**
		 * 复制自定义页面
		 */
		if (count($oCopied->pages)) {
			$modelPage = $this->model('matter\signin\page');
			foreach ($oCopied->pages as $ep) {
				$newPage = $modelPage->add($oUser, $site, $oNewApp->id);
				$rst = $modelPage->update(
					'xxt_signin_page',
					[
						'title' => $ep->title,
						'name' => $ep->name,
						'type' => $ep->type,
						'data_schemas' => $modelApp->escape($ep->data_schemas),
						'act_schemas' => $modelApp->escape($ep->act_schemas),
						'user_schemas' => $modelApp->escape($ep->user_schemas),
					],
					"aid='{$oNewApp->id}' and id=$newPage->id"
				);
				$data = [
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				];
				$modelCode->modify($newPage->code_id, $data);
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $oNewApp, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\signin');
		$oApp = $modelApp->byId($app, ['fields' => 'id,title,summary,pic,start_at,end_at,mission_id,absent_cause', 'cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 处理数据
		 */
		$posted = $this->getPostJson();
		$oUpdated = new \stdClass;
		foreach ($posted as $n => $v) {
			if (in_array($n, ['data_schemas', 'recycle_schemas'])) {
				$oUpdated->{$n} = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'entryRule') {
				$oUpdated->entry_rule = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'assignedNickname') {
				$oUpdated->assigned_nickname = $modelApp->escape($modelApp->toJson($v));
			} else if (in_array($n, ['title', 'summary'])) {
				$oUpdated->{$n} = $modelApp->escape($v);
			} else if ($n === 'absent_cause') {
				$absentCause = !empty($oApp->absent_cause) ? $oApp->absent_cause : new \stdClass;
				foreach ($v as $uid => $val) {
					$absentCause->{$uid} = $val;
				}
				$oUpdated->{$n} = $modelApp->escape($modelApp->toJson($absentCause));
			} else {
				$oUpdated->{$n} = $v;
			}
		}

		if ($oApp = $modelApp->modify($oUser, $oApp, $oUpdated)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oApp, 'U');
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $app
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$oUser, $siteId, &$app, &$templateConfig) {
		$pages = $templateConfig->pages;
		if (empty($pages)) {
			return false;
		}
		/* 创建页面 */
		$templateDir = TMS_APP_TEMPLATE . $templateConfig->path;
		$modelPage = $this->model('matter\signin\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($oUser, $siteId, $app->id, $page);
			$data = [
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			];
			$modelCode->modify($ap->code_id, $data);
			/*页面关联的定义*/
			$pageSchemas = [];
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_signin_page',
				$pageSchemas,
				"aid='{$app->id}' and id={$ap->id}"
			);
		}

		return $pages;
	}
	/**
	 * 添加第一个轮次
	 *
	 * @param string $app
	 */
	private function &_addFirstRound(&$oUser, $siteId, &$app) {
		$modelRnd = $this->model('matter\signin\round');

		$roundId = uniqid();
		$round = [
			'siteid' => $siteId,
			'aid' => $app->id,
			'rid' => $roundId,
			'creater' => $oUser->id,
			'create_at' => time(),
			'title' => '第1轮',
			'state' => 1,
		];

		$modelRnd->insert('xxt_signin_round', $round, false);

		$round = (object) $round;

		return $round;
	}
	/**
	 * 应用的微信二维码
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $round
	 *
	 */
	public function wxQrcode_action($site, $app, $round = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\wx\call\qrcode');

		if (empty($round)) {
			$qrcodes = $modelQrcode->byMatter('signin', $app);
		} else {
			$params = new \stdClass;
			$params->round = $round;
			$params = \TMS_MODEL::toJson($params);
			$qrcodes = $modelQrcode->byMatter('signin', $app, $params);
		}

		return new \ResponseData($qrcodes);
	}
	/**
	 * 应用的易信二维码
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function yxQrcode_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\yx\call\qrcode');

		$qrcode = $modelQrcode->byMatter('signin', $app);

		return new \ResponseData($qrcode);
	}
	/**
	 * 删除一个活动
	 *
	 * 如果没有登记数据，就将活动彻底删除
	 * 否则只是打标记
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSig = $this->model('matter\signin');
		$oApp = $modelSig->byId($app, ['fields' => 'siteid,id,title,summary,pic,mission_id,creater', 'cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			if (!$this->model('site')->isAdmin($oApp->siteid, $oUser->id)) {
				return new \ResponseError('没有删除数据的权限');
			}
			$rst = $modelApp->remove($oUser, $oApp, 'Recycle');
		} else {

			$q = [
				'count(*)',
				'xxt_signin_record',
				["aid" => $oApp->id],
			];
			if ((int) $modelSig->query_val_ss($q) > 0) {
				$rst = $modelSig->remove($oUser, $oApp, 'Recycle');
			} else {
				$modelSig->delete(
					'xxt_signin_log',
					["aid" => $oApp->id]
				);
				$modelSig->delete(
					'xxt_signin_round',
					["aid" => $oApp->id]
				);
				$modelSig->delete(
					'xxt_code_page',
					"id in (select code_id from xxt_signin_page where aid='" . $modelSig->escape($oApp->id) . "')"
				);
				$modelSig->delete(
					'xxt_signin_page',
					["aid" => $oApp->id]
				);
				$rst = $modelSig->remove($oUser, $oApp, 'D');
			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function opData_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelApp = $this->model('matter\signin');
		$oApp = new \stdClass;
		$oApp->siteid = $site;
		$oApp->id = $app;
		$opData = $mdoelApp->opData($oApp);

		return new \ResponseData($opData);
	}
}