<?php
namespace template;
/**
 * 应用模板商店
 */
class shop_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["id" => $id],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 */
	public function &getMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["matter_id" => $matterId, "matter_type" => $matterType],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 * @param string $siteId 来源于哪个站点
	 * @param object $matter 共享的素材
	 */
	public function putMatter($siteId, $account, $matter, $options = array()) {
		if (isset($matter->id) && $matter->id) {
			// 更新模板
			$current = time();

			$item = [
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'visible_scope' => $matter->visible_scope,
			];
			$this->update(
				'xxt_shop_matter',
				$item,
				["siteid" => $siteId, "matter_type" => $matter->matter_type, "matter_id" => $matter->matter_id]
			);
		} else {
			// 新建模板
			$current = time();

			$item = [
				'creater' => $account->id,
				'creater_name' => $account->name,
				'put_at' => $current,
				'siteid' => $siteId,
				'matter_type' => $matter->matter_type,
				'matter_id' => $matter->matter_id,
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'visible_scope' => $matter->visible_scope,
			];

			$id = $this->insert('xxt_shop_matter', $item, true);
			$item = $this->byId($id);

			// 添加模板接收人
			if (!empty($matter->acls)) {
				$modelAcl = \TMS_APP::M('template\acl');
				foreach ($matter->acls as $acl) {
					$acl = $modelAcl->add($account, $item, $acl);
				}
			}
		}

		return $item;
	}
	/**
	 * 推送到主页
	 */
	public function pushHome($templateId) {
		$rst = $this->update(
			'xxt_shop_matter',
			['push_home' => 'Y'],
			["id" => $templateId]
		);

		return $rst;
	}
	/**
	 * 推送到主页
	 */
	public function pullHome($templateId) {
		$rst = $this->update(
			'xxt_shop_matter',
			['push_home' => 'N'],
			["id" => $templateId]
		);

		return $rst;
	}
	/**
	 * 主页上的
	 */
	public function &atHome($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["push_home" => 'Y'],
		];

		$items = $this->query_objs_ss($q);

		return $items;
	}
	/**
	 * 站点主页上的
	 */
	public function &atSiteHome($siteId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["siteid" => $siteId, 'visible_scope' => 'A'],
		];

		$items = $this->query_objs_ss($q);

		return $items;
	}
}