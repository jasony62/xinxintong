<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class invoke extends \pl\fe\base {
	/*
	 *
	 */
	public function __construct() {
		$siteId = $_GET['site'];
		if (empty($siteId)) {
			return new \ParameterError('参数不完整');
		}

		$modelSite = $this->model('site');
		if (false === ($oSite = $modelSite->byId($siteId))) {
			return new \ObjectNotFoundError();
		}
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = [];

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/frame');
		exit;
	}
	/*
	 *
	 */
	public function get_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		$invoke = $modelInv->bySite($site);
		if ($invoke === false) {
			//没有就创建
			$invoke = $modelInv->creat($site, $oUser);
		}

		return new \ResponseData($invoke);
	}
	/**
	 *
	 */
	public function update_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		if (false === ($invoke = $modelInv->bySite($site))) {
			return new \ObjectNotFoundError();
		}

		$post = $this->getPostJson();
		if (empty($post)) {
			return new \ResponseData('ok');
		}

		$updata = [];
		foreach ($post as $key => $value) {
			if ($key === 'invokerIp') {
				$userIps = [];
				foreach ($value as $v) {
					if (filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$userIps[] = $v;
					}
				}
				if (!empty($userIps)) {
					$userIp = implode(',', $userIps);
					$updata['invoker_ip'] = $userIp;
				}
			}
		}

		if (empty($updata)) {
			return new \ResponseData('ok');
		}

		$rst = $modelInv->update('xxt_site_invoke', $updata, ['siteid' => $site]);

		/*记录操作日志*/
		$modelSite = $this->model('site');
		$matter = $modelSite->byId($site, ['fields' => 'id,name as title']);
		$matter->type = 'site';
		$this->model('matter\log')->matterOp($site, $oUser, $matter, 'updateInvoke');

		return new \ResponseData($rst);
	}
	/*
	 * 生成sitesecret
	 */
	public function creatSecret_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		$invoke = $modelInv->bySite($site);
		if ($invoke === false) {
			return new \ObjectNotFoundError();
		}

		if (!empty($invoke->secret)) {
			return new \ResponseError('已存在siteSecret，不能重复生成');
		}

		$current = time();
		$secret = md5($site . '-' . $oUser->id . '-' . $current);

		$data = new \stdClass;
		$data->secret = $secret;
		$data->secret_creater = $oUser->id;
		$data->secret_creater_name = $modelInv->escape($oUser->name);
		$data->secret_create_at = $current;

		$ret = $modelInv->update('xxt_site_invoke', $data, ['id' => $invoke->id]);

		return new \ResponseData($rst);
	}
	/*
	 * 重置sitesecret
	 */
	public function resetSecret_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		$invoke = $modelInv->bySite($site);
		if ($invoke === false) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$secret = md5($site . '-' . $oUser->id . '-' . $current);

		$modifyLog = empty($invoke->secret_modify_log)? new \stdClass : json_decode($invoke->secret_modify_log);
		$modifyLog->{$oUser->id} = $current;

		$data = new \stdClass;
		$data->secret = $secret;
		$data->secret_modify_log = json_encode($modifyLog);

		$ret = $modelInv->update('xxt_site_invoke', $data, ['id' => $invoke->id]);

		return new \ResponseData($rst);
	}
}