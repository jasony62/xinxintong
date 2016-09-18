<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 应用模版商店
 */
class shop extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 注册用户模板管理界面
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/template/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action($matterType, $matterId) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = [
			's.*',
			"xxt_shop_matter s",
			["s.matter_type" => $matterType, "s.matter_id" => $matterId],
		];
		if ($item = $model->query_obj_ss($q)) {
			if ($item->visible_scope === 'S') {
				$modelAcl = $this->model('template\acl');
				$item->acls = $modelAcl->byMatter($matterId, $matterType);
				if (!empty($item->acls)) {
					$modelAcnt = $this->model('account');
					foreach ($item->acls as &$acl) {
						$account = $modelAcnt->byId($acl->receiver, ['fields' => 'nickname']);
						$acl->account = $account;
					}
				}
			}
		}

		return new \ResponseData($item);
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 */
	public function list_action($matterType, $scope = 'A', $page = 1, $size = 20) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$matterType = $model->escape($matterType);

		$q = [
			's.*',
			"xxt_shop_matter s",
		];
		if ($scope === 'A') {
			$q[] = "s.matter_type='$matterType' and visible_scope='A'";
		} else if ($scope === 'U') {
			$q[] = "s.matter_type='$matterType' and creater='{$loginUser->id}'";
		} else if ($scope === 'S') {
			// 指定分享的
			$where = "s.matter_type='$matterType' and s.visible_scope='S'";
			$where .= " and exists(select 1 from xxt_shop_matter_acl acl";
			$where .= " where acl.shop_matter_id=s.id and acl.receiver='{$loginUser->id}'";
			$where .= ")";
			$q[] = $where;
		}
		$q2 = [
			'o' => 'put_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if ($items = $model->query_objs_ss($q, $q2)) {
			$q[0] = "count(*)";
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(['templates' => $items, 'total' => $total]);
	}
	/**
	 * 素材上架
	 *
	 * @param string $site
	 * @param string $scope [U|A]
	 */
	public function put_action($site) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->getPostJson();

		$item = $this->model('template\shop')->putMatter($site, $loginUser, $matter);

		return new \ResponseData($item);
	}
	/**
	 * @todo 如何检查当前用户是否有权限？
	 */
	public function update_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = $this->model()->update('xxt_shop_matter', $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}