<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class account extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 * 团队下的所有访客用户
	 */
	public function list_action($site, $page = 1, $size = 30) {
		$modelSite = $this->model('site');
		if (false === ($oSite = $modelSite->byId($site))) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

		$q = [
			'uid,reg_time,last_active,nickname,headimgurl,ufrom,coin,unionid,is_reg_primary,wx_openid,read_num,favor_num',
			'xxt_site_account',
			['siteid' => $oSite->id],
		];
		if (!empty($oPosted->nickname)) {
			$q[2]['nickname'] = (object) ['op' => 'like', 'pat' => '%' . $this->escape($oPosted->nickname) . '%'];
		}
		if (isset($oPosted->onlyWxfan) && $oPosted->onlyWxfan === true) {
			$q[2]['wx_openid'] = (object) ['op' => '<>', 'pat' => ''];
		}

		$q2['o'] = 'reg_time desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$oResult = new \stdClass;
		$users = $modelSite->query_objs_ss($q, $q2);
		if (count($users)) {
			$modelWx = $this->model('sns\wx\fan');
			foreach ($users as $oUser) {
				if (!empty($oUser->wx_openid)) {
					$oUser->wxfan = $modelWx->byOpenid($oSite->id, $oUser->wx_openid, 'nickname,headimgurl,subscribe_at,unsubscribe_at');
				}
			}
			$oResult->users = $users;
		}
		$q[0] = 'count(*)';
		$total = (int) $modelSite->query_val_ss($q);
		$oResult->total = $total;

		return new \ResponseData($oResult);
	}
}