<?php
namespace site\fe\matter\signin;

include_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动
 */
class main extends base {
	/**
	 *
	 */
	private $modelApp;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->modelApp = $this->model('matter\signin');
	}
	/**
	 * 返回活动页
	 *
	 * @param string $site 因为活动有可能来源于父账号，因此需要指明活动是在哪个公众号中进行的
	 * @param string $app
	 * @param string $round 指定签到轮次
	 * @param $page 要进入活动的哪一页面
	 *
	 */
	public function index_action($site, $app, $round = null, $page = '', $ignoretime = 'N') {
		empty($site) && $this->outputError('没有指定团队ID');
		empty($app) && $this->outputError('签到活动ID为空');
		$app = $this->escape($app);

		$oApp = $this->modelApp->byId($app, ['cascade' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的签到活动不存在，请检查参数是否正确');
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		if ($ignoretime === 'N') {
			$activeRound = $this->model('matter\signin\round')->getActive($site, $oApp->id);
			if (!$activeRound) {
				$this->outputError('签到还没有开始', $oApp->title);
			} else if (!empty($round) && $round !== $activeRound->rid) {
				$this->outputError('您签到的场次或时间不正确', $oApp->title);
			}
		}
		/* 计算打开哪个页面 */
		if (empty($page)) {
			/*没有指定页面*/
			$oPage = $this->_defaultPage($oApp, true, isset($activeRound) ? $activeRound : null);
		} else {
			$oPage = $this->model('matter\signin\page')->byName($oApp->id, $page);
		}
		empty($oPage) && $this->outputError('没有可访问的页面');

		/* 返回签到活动页面 */
		\TPL::assign('title', $oApp->title);
		if ($oPage->type === 'V') {
			\TPL::output('/site/fe/matter/signin/view');
		} elseif ($oPage->type === 'I') {
			\TPL::output('/site/fe/matter/signin/signin');
		}
		exit;
	}
	/**
	 * 当前用户进入的缺省页面
	 */
	private function &_defaultPage($oApp, $redirect = false, $round = null) {
		$page = $this->checkEntryRule($oApp, $redirect, $round);
		$oPage = $this->model('matter\signin\page')->byName($oApp->id, $page);
		if (empty($oPage)) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
				exit;
			}
		}

		return $oPage;
	}
	/**
	 * 返回签到活动定义
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $page page's name
	 *
	 */
	public function get_action($site, $app, $page = null) {
		$params = [];

		// 签到活动定义
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		$params['app'] = &$oApp;

		// 当前访问用户的基本信息
		$oUser = $this->who;

		/* 补充联系人信息 */
		if (isset($oApp->entry_rule->scope) && $oApp->entry_rule->scope === 'member') {
			$modelMem = $this->model('site\user\member');
			if (empty($oUser->unionid)) {
				$aMembers = $modelMem->byUser($oUser->uid);
				if (count($aMembers)) {
					!isset($oUser->members) && $oUser->members = new \stdClass;
					foreach ($aMembers as $oMember) {
						if (isset($oApp->entry_rule->member->{$oMember->schema_id})) {
							$oUser->members->{$oMember->schema_id} = $oMember;
						}
					}
				}
			} else {
				$modelAcnt = $this->model('site\user\account');
				$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
				foreach ($aUnionUsers as $oUnionUser) {
					$aMembers = $modelMem->byUser($oUnionUser->uid);
					if (count($aMembers)) {
						!isset($oUser->members) && $oUser->members = new \stdClass;
						foreach ($aMembers as $oMember) {
							if (isset($oApp->entry_rule->member->{$oMember->schema_id})) {
								$oUser->members->{$oMember->schema_id} = $oMember;
							}
						}
					}
				}
			}
		}
		$params['user'] = $oUser;

		// 当前轮次
		$activeRound = $this->model('matter\signin\round')->getActive($site, $oApp->id);
		$params['activeRound'] = $activeRound;

		// 打开哪个页面？
		if (empty($page)) {
			$oPage = $this->_defaultPage($oApp, false, $activeRound);
		} else {
			$oPage = $this->model('matter\signin\page')->byName($oApp->id, $page);
		}
		if (empty($oPage)) {
			return new \ResponseError('页面不存在');
		}
		$params['page'] = $oPage;

		// 团队页面设置
		if ($oApp->use_site_header === 'Y' || $oApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}
		// 项目页面设置
		if ($oApp->use_mission_header === 'Y' || $oApp->use_mission_footer === 'Y') {
			if ($oApp->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$oApp->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}

		// 签到记录
		$newForm = false;
		if ($oPage->type === 'I') {
			$options = [
				'fields' => '*',
				'cascaded' => 'Y',
			];
			$modelRec = $this->model('matter\signin\record');
			if (false === ($oUserRecord = $modelRec->byUser($oUser, $oApp, $options))) {
				// 如果关联了报名记录，从报名记录中获得登记信息
				if (!empty($oApp->enroll_app_id)) {
					$oUserRecord = $this->_recordByEnroll($oApp, $oUser);
				}
				/* 关联了分组活动 */
				if (!empty($oApp->group_app_id)) {
					$oGrpApp = $this->model('matter\group')->byId($oApp->group_app_id, ['cascaded' => 'N']);
					$oGrpPlayer = $this->model('matter\group\player')->byUser($oGrpApp, $oUser->uid);
					if (count($oGrpPlayer) === 1) {
						if (!empty($oGrpPlayer[0]->data)) {
							if (!empty($oUserRecord)) {
								$oAssocData = json_decode($oGrpPlayer[0]->data);
								$oUserRecord->data->_round_id = $oGrpPlayer[0]->round_id;
								foreach ($oAssocData as $k => $v) {
									$oUserRecord->data->{$k} = $v;
								}
							} else {
								$oUserRecord = new \stdClass;
								$oUserRecord->data = json_decode($oGrpPlayer[0]->data);
								$oUserRecord->data->_round_id = $oGrpPlayer[0]->round_id;
							}
						}
					}
				}
			}
			$params['record'] = $oUserRecord;
		}

		return new \ResponseData($params);
	}
	/**
	 * 从关联的登记活动中获得匹配的数据
	 */
	private function _recordByEnroll(&$signinApp, &$user) {
		$modelEnlRec = $this->model('matter\enroll\record');
		$oAssocApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id, ['cascaded' => 'N']);
		if ($oAssocApp) {
			$records = $modelEnlRec->byUser($oAssocApp, $user);
			if (count($records)) {
				$signinRecord = new \stdClass;
				foreach ($records as $record) {
					if ($record->verified === 'Y') {
						if (is_string($record->data)) {
							$signinRecord->data = json_decode($record->data);
						} else {
							$signinRecord->data = $record->data;
						}
						return $signinRecord;
					}
				}
			}
		}

		return false;
	}
}