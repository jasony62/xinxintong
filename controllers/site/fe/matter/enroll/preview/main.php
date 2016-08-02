<?php
namespace site\fe\matter\enroll\preview;
/**
 * 登记活动预览
 */
class main extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function index_action($openAt = null) {
		if (!empty($openAt)) {
			if ($openAt === 'before') {
				$this->outputError('登记尚未开始');
			}
			if ($openAt === 'after') {
				$this->outputError('登记已经结束');
			}
		}

		\TPL::output('/site/fe/matter/enroll/preview');
		exit;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $siteid
	 * @param string $appid
	 * @param string $rid round's id
	 * @param string $page page's name
	 * @param string $ek record's enroll key
	 * @param string $newRecord
	 */
	public function get_action($site, $app, $rid = null, $page = null, $ek = null, $newRecord = null) {
		$params = array();

		$modelApp = $this->model('matter\enroll');
		/* 登记活动定义 */
		$app = $modelApp->byId($app, array('cascaded' => 'Y'));
		$params['app'] = &$app;
		/*站点页面设置*/
		if ($app->use_site_header === 'Y' || $app->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}
		/*项目页面设置*/
		if ($app->use_mission_header === 'Y' || $app->use_mission_footer === 'Y') {
			if ($app->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$app->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}
		/* 当前访问用户的基本信息 */
		$user = new \stdClass;
		$params['user'] = $user;
		/* 计算打开哪个页面 */
		if (empty($page)) {
			$oPage = $this->_defaultPage($site, $app, $user);
		} else {
			foreach ($app->pages as $p) {
				if ($p->name === $page) {
					$oPage = &$p;
					break;
				}
			}
		}
		if (empty($oPage)) {
			return new \ResponseError('页面不存在');
		}
		$modelPage = $this->model('matter\enroll\page');
		$oPage = $modelPage->byId($app->id, $oPage->id, 'Y');
		$params['page'] = $oPage;
		/* 自动登记 */
		$hasEnrolled = $modelApp->hasEnrolled($site, $app->id, $user);
		if (!$hasEnrolled && $app->can_autoenroll === 'Y' && $oPage->autoenroll_onenter === 'Y') {
			$modelRec = $this->model('matter\enroll\record');
			$options = array(
				'fields' => 'enroll_key,enroll_at',
			);
			$lastRecord = $modelRec->getLast($site, $$app->id, $user, $options);
			if (false === $lastRecord) {
				$modelRec->add($site, $app, $user, (empty($posted->referrer) ? '' : $posted->referrer));
			} else if ($lastRecord->enroll_at === '0') {
				$updated = array(
					'enroll_at' => time(),
				);
				!empty($posted->referrer) && $updated['referrer'] = $posted->referrer;
				$modelRec->update('xxt_enroll_record', $updated, "enroll_key='$lastRecord->enroll_key'");
			}
		}
		if ($app->multi_rounds === 'Y') {
			$params['activeRound'] = $this->model('matter\enroll\round')->getLast($site, $app->id);
		}
		/*登记记录*/
		$newForm = false;
		if ($oPage->type === 'I' || $oPage->type === 'S') {
			if ($newRecord === 'Y') {
				$newForm = true;
			} else if (empty($ek)) {
				if ($app->open_lastroll === 'N') {
					$newForm = true;
				}
			}
			if ($newForm === false) {
				/*获得最后一条登记数据。登记记录有可能未进行过登记*/
				$params['record'] = false;
			}
		}

		return new \ResponseData($params);
	}
	/**
	 * 当前用户的缺省页面
	 */
	private function _defaultPage($site, &$app, $redirect = false) {
		$user = new \stdClass;

		$modelApp = $this->model('matter\enroll');
		$page = $this->checkEntryRule($site, $app, $user, $redirect);
		$oPage = null;
		foreach ($app->pages as $p) {
			if ($p->name === $page) {
				$oPage = $p;
				break;
			}
		}
		if (empty($oPage)) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
				exit;
			}
		}

		return $oPage;
	}
	/**
	 * 检查登记活动进入规则
	 */
	protected function checkEntryRule($site, $app, $user, $redirect = false) {
		$entryRule = $app->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			foreach ($entryRule->member as $schemaId => $rule) {
				$page = $rule->entry;
				break;
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				$page = $rule->entry;
				break;
			}
		} else {
			if (isset($entryRule->otherwise->entry)) {
				$page = $entryRule->otherwise->entry;
			} else {
				$page = null;
			}
		}
		/*内置页面*/
		switch ($page) {
		case '$memberschema':
			$aMemberSchemas = array();
			foreach ($entryRule->member as $schemaId => $rule) {
				$aMemberSchemas[] = $schemaId;
			}
			if ($redirect) {
				/*页面跳转*/
				$this->gotoMember($site, $aMemberSchemas, $user->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($site, $aMemberSchemas, $user->uid, false);
			}
			break;
		case '$mpfollow':
			if (isset($entryRule->sns->wx)) {
				$this->snsFollow($site, 'wx');
			} else if (isset($entryRule->sns->qy)) {
				$this->snsFollow($site, 'qy');
			} else if (isset($entryRule->sns->yx)) {
				$this->snsFollow($site, 'yx');
			}
			break;
		}

		return $page;
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $err);
		\TPL::output('error');
		exit;
	}
}