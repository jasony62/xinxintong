<?php
namespace matter;
/**
 *
 */
class template_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_template',
			["id" => $id],
		];

		if ($template = $this->query_obj_ss($q)) {
			$template->type = 'template';
		}

		return $template;
	}
	/**
	 *
	 */
	public function &byMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_template',
			["matter_id" => $matterId, "matter_type" => $matterType],
		];

		if ($template = $this->query_obj_ss($q)) {
			$template->type = 'template';
		}

		return $template;
	}
	/**
	 *
	 * @param string $siteId 来源于哪个站点
	 * @param object $matter 共享的素材
	 */
	public function putMatter(&$site, &$account, &$matter) {
		if (isset($matter->id) && $matter->id) {
			// 更新模板
			$current = time();
			$item = [
				'title' => $matter->title,
				'pic' => $matter->pic,
				'put_at' => $current,
				'summary' => $matter->summary,
				'visible_scope' => $matter->visible_scope,
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
			];
			$this->update(
				'xxt_template',
				$item,
				["siteid" => $site->id, "matter_type" => $matter->matter_type, "matter_id" => $matter->matter_id]
			);
			$item = $this->byMatter($matter->matter_id, $matter->matter_type);
		} else {
			// 新建模板
			$current = time();

			$item = [
				'creater' => $account->id,
				'creater_name' => $account->name,
				'put_at' => $current,
				'siteid' => $site->id,
				'site_name' => $site->name,
				'matter_type' => $matter->matter_type,
				'matter_id' => $matter->matter_id,
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'visible_scope' => $matter->visible_scope,
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
			];

			$id = $this->insert('xxt_template', $item, true);

			// 添加模板接收人
			// if (!empty($matter->acls)) {
			// 	$modelAcl = \TMS_APP::M('template\acl');
			// 	foreach ($matter->acls as $acl) {
			// 		$acl = $modelAcl->add($account, $item, $acl);
			// 	}
			// }
			$item = $this->byId($id);
		}

		return $item;
	}
	/**
	 * 推送到主页
	 */
	public function pushHome($templateId) {
		$rst = $this->update(
			'xxt_template',
			['push_home' => 'Y'],
			["id" => $templateId]
		);

		return $rst;
	}
	/**
	 * 取消推送到主页
	 */
	public function pullHome($templateId) {
		$rst = $this->update(
			'xxt_template',
			['push_home' => 'N'],
			["id" => $templateId]
		);

		return $rst;
	}
	/**
	 * 平台主页上的模版
	 */
	public function &atHome($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_template',
			["visible_scope" => 'P', "push_home" => 'Y'],
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
			'xxt_template',
			["siteid" => $siteId, 'push_home' => 'Y'],
		];

		$templates = $this->query_objs_ss($q);

		return $templates;
	}
	/**
	 * 是否站点已经收藏模版
	 */
	public function isFavorBySite(&$template, $siteId) {
		$q = [
			'count(*)',
			'xxt_template_order',
			"siteid='{$siteId}' and template_id='{$template->id}' and favor='Y'",
		];
		return 0 < (int) $this->query_val_ss($q);
	}
	/**
	 * 站点收藏模版
	 */
	public function favorBySite(&$user, &$template, $siteId) {
		if ($this->isFavorBySite($template, $siteId)) {
			return true;
		}
		$template = $this->escape($template);
		$order = new \stdClass;
		$order->siteid = $siteId;
		$order->buyer = $user->id;
		$order->buyer_name = $user->name;
		$order->template_id = $template->id;
		$order->from_siteid = $template->siteid;
		$order->from_site_name = $template->site_name;
		$order->matter_id = $template->matter_id;
		$order->matter_type = $template->matter_type;
		$order->scenario = $template->scenario;
		$order->title = $template->title;
		$order->pic = $template->pic;
		$order->summary = $template->summary;
		$order->favor = 'Y';
		$order->favor_at = time();

		$order->id = $this->insert('xxt_template_order', $order, true);

		return $order;
	}
	/**
	 * 取消站点收藏模版
	 */
	public function unfavorBySite(&$user, &$template, $siteId) {
		$rst = $this->delete(
			'xxt_template_order',
			"siteid='{$siteId}' and template_id='{$template->id}'"
		);

		return $rst;
	}
	/**
	 * 是否站点已经收藏模版
	 */
	public function isPurchaseBySite(&$template, $siteId) {
		$q = [
			'count(*)',
			'xxt_template_order',
			"siteid='{$siteId}' and template_id='{$template->id}' and purchase='Y'",
		];
		return 0 < (int) $this->query_val_ss($q);
	}
	/**
	 * 站点收藏模版
	 */
	public function purchaseBySite(&$user, &$template, $siteId) {
		if ($this->isPurchaseBySite($template, $siteId)) {
			return true;
		}
		$template = $this->escape($template);
		$order = new \stdClass;
		$order->siteid = $siteId;
		$order->buyer = $user->id;
		$order->buyer_name = $user->name;
		$order->template_id = $template->id;
		$order->from_siteid = $template->siteid;
		$order->from_site_name = $template->site_name;
		$order->matter_id = $template->matter_id;
		$order->matter_type = $template->matter_type;
		$order->scenario = $template->scenario;
		$order->title = $template->title;
		$order->pic = $template->pic;
		$order->summary = $template->summary;
		$order->purchase = 'Y';
		$order->purchase_at = time();

		$order->id = $this->insert('xxt_template_order', $order, true);

		return $order;
	}
}