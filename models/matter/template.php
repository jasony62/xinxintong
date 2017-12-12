<?php
namespace matter;
/**
 *
 */
class template_model extends \TMS_MODEL {
	/**
	 *返回一个模板
	 */
	public function &byId($tid, $vid = null, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = [
			$fields,
			'xxt_template',
			["id" => $tid, 'state' => 1],
		];

		if (false === ($template = $this->query_obj_ss($q))) {
			return $template;
		}
		$template->type = 'template';
		//查询分享人
		$v = [
			'*',
			'xxt_template_acl',
			["template_id" => $tid],
		];
		$template->acl = $this->query_objs_ss($v);

		//登记活动
		if (isset($template->matter_type) && $template->matter_type === 'enroll') {
			//获取版本
			$p = [
				'*',
				'xxt_template_enroll',
				['template_id' => $tid, 'state' => 1],
			];
			$p2['o'] = "order by create_at desc";
			$template->versions = $this->query_objs_ss($p, $p2);
			if (isset($template->scenario_config)) {
				if (!empty($template->scenario_config)) {
					$template->scenarioConfig = json_decode($template->scenario_config);
				} else {
					$template->scenarioConfig = new \stdClass;
				}
			}
			//获取指定版本信息及页面，如果未指定就用默认版本
			if ($cascaded === 'Y') {
				//获取当前需要展示页面的版本的id
				if (empty($vid)) {
					if (empty($template->pub_version)) {
						foreach ($template->versions as $v) {
							if ($v->version == $template->last_version) {
								$vid = $v->id;
								$version = $v;
								break;
							}
						}
					} else {
						foreach ($template->versions as $v) {
							if ($v->version == $template->pub_version) {
								$vid = $v->id;
								$version = $v;
								break;
							}
						}
					}
				} else {
					//返回当前预览版本的数据
					foreach ($template->versions as $v) {
						if ($v->id == $vid) {
							$version = $v;
							break;
						}
					}
				}
				if (isset($version)) {
					foreach ($version as $k => $v2) {
						if ($k === 'id') {
							$template->vid = $v2;
						} elseif ($k === 'create_at') {
							$template->vcreate_at = $v2;
						} else {
							$template->$k = $v2;
						}
					}
				}
				$modelPage = $this->model('matter\enroll\page');
				$template->pages = $modelPage->byApp('template:' . $vid);
			}
		}

		return $template;
	}
	/**
	 * [获取模板列表]
	 * @param  [type]  $site [description]
	 * @param  integer $page [description]
	 * @param  integer $size [description]
	 * @return [type]        [description]
	 */
	public function &bySite($site, $page = 1, $size = 30) {
		$q = [
			'*',
			'xxt_template',
			['siteid' => $site, 'state' => 1],
		];
		$q2['o'] = 'put_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $this->query_objs_ss($q, $q2)) {
			$result['apps'] = $a;
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result['total'] = $total;
		}

		return $result;
	}
	/**
	 * [byVersion description]
	 * @param  [type] $site    [description]
	 * @param  [type] $tid     [description]
	 * @param  [type] $vid     [版本id]
	 * @param  [type] $version [版本号]
	 * @return [type]          [description]
	 */
	public function byVersion($site, $matterType, $tid = null, $vid = null, $version = null, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array();
		$q[0] = $fields;
		switch ($matterType) {
		case 'enroll':
			$q[1] = 'xxt_template_enroll';
			break;
		}
		//如果vid为空根据版本号和template_id获取版本信息
		if (empty($vid)) {
			if (empty($tid) || empty($version)) {
				return false;
			}
			$q[2] = [
				'siteid' => $site,
				'template_id' => $tid,
				'version' => $version,
				'state' => 1,
			];
		} else {
			$q[2] = [
				'siteid' => $site,
				'id' => $vid,
				'state' => 1,
			];
		}

		$version = $this->query_obj_ss($q);

		return $version;
	}
	/**
	 * 获得素材对应的模版
	 *
	 * @param string $matterId 素材ID
	 * @param string $matterType 素材类型
	 *
	 */
	public function &byMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_template',
			["matter_id" => $matterId, "matter_type" => $matterType, 'state' => 1],
		];

		$templates = $this->query_objs_ss($q);
		if (count($templates) > 1) {
			die('数据错误：存在多条数据，请检查');
		} else if (count($templates) === 1) {
			$template = $templates[0];
			$template->type = 'template';
		} else {
			$template = false;
		}

		return $template;
	}
	/**
	 * 创建或更新素材的模版
	 *
	 * @param string $siteId 来源于哪个站点
	 * @param object $matter 共享的素材
	 *
	 */
	public function putMatter(&$site, &$account, &$matter, $template = null) {
		$current = time();
		if (empty($template->id)) {
			/* 新建模板 */
			$template = [
				'creater' => $account->id,
				'creater_name' => $account->name,
				'create_at' => $current,
				'put_at' => $current,
				'siteid' => $site->id,
				'site_name' => $site->name,
				'matter_type' => $matter->matter_type,
				'matter_id' => isset($matter->matter_id) ? $matter->matter_id : '',
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
				'title' => $this->escape($matter->title),
				'pic' => $matter->pic,
				'summary' => $this->escape($matter->summary),
				'coin' => isset($matter->coin) ? $matter->coin : 0,
				'visible_scope' => isset($matter->visible_scope) ? $matter->visible_scope : 'S',
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
			];
			$tid = $this->insert('xxt_template', $template, true);
			/*创建版本*/
			if ($matter->matter_type === 'enroll') {
				$matter = $this->model('matter\enroll')->byId($matter->matter_id);
				$version = $this->model('matter\template\enroll')->createNewVersion($site->id, $tid, $matter, $account, $current, 'N');
				$this->update(
					'xxt_template',
					['last_version' => $version->version],
					['id' => $tid]
				);
			}

			$template = $this->byId($tid, $version->id);
		} else {
			/* 更新模板 */
			$updated = [
				'title' => $this->escape($matter->title),
				'pic' => $matter->pic,
				'put_at' => $current,
				'summary' => $this->escape($matter->summary),
				'coin' => isset($matter->coin) ? $matter->coin : 0,
				'visible_scope' => isset($matter->visible_scope) ? $matter->visible_scope : 'S',
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
			];
			$this->update(
				'xxt_template',
				$updated,
				["id" => $template->id]
			);
			/*创建新的版本*/
			if ($matter->matter_type === 'enroll') {
				$matter = $this->model('matter\enroll')->byId($matter->matter_id);
				$version = $this->model('matter\template\enroll')->createNewVersion($site->id, $template->id, $matter, $account, $current, 'Y');
				$this->update(
					'xxt_template',
					['pub_version' => $version->version, 'last_version' => $version->version],
					["id" => $template->id]
				);
			}

			$template = $this->byId($template->id, $version->id);
		}

		return $template;
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
			["visible_scope" => 'P', "push_home" => 'Y', 'state' => 1],
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
			["siteid" => $siteId, 'push_home' => 'Y', 'state' => 1],
		];

		$templates = $this->query_objs_ss($q);

		return $templates;
	}
	/**
	 * 是否站点已经收藏模版
	 */
	public function isFavorBySite(&$template, $siteId) {
		$options = array(
			'siteid' => $siteId,
			'template_id' => $template->id,
			'favor' => 'Y',
		);
		$q = [
			'count(*)',
			'xxt_template_order',
			$options,
		];
		return 0 < (int) $this->query_val_ss($q);
	}
	/**
	 * 站点收藏模版
	 */
	public function favorBySite(&$user, &$template, $siteId, $version) {
		if ($this->isFavorBySite($template, $siteId)) {
			return true;
		}
		$template = $this->escape($template);
		$order = new \stdClass;
		$order->siteid = $siteId;
		$order->buyer = $user->id;
		$order->buyer_name = $user->name;
		$order->template_id = $template->id;
		$order->template_version = empty($version) ? $template->pub_version : $this->escape($version);
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
		$q = $this->update("update xxt_template set favor_num = favor_num+1 where id = " . $template->id);

		return $order;
	}
	/**
	 * 取消站点收藏模版
	 */
	public function unfavorBySite($tid, $siteId) {
		$options = array(
			'siteid' => $siteId,
			'template_id' => $tid,
			'favor' => 'Y',
		);
		$rst = $this->delete(
			'xxt_template_order',
			$options
		);

		$this->update("update xxt_template set favor_num = favor_num - 1 where id = " . $tid . " and favor_num > 0");

		return $rst;
	}
	/**
	 * 是否站点已经使用模版
	 */
	public function isPurchaseBySite(&$template, $siteId) {
		$options = array(
			'siteid' => $siteId,
			'template_id' => $template->id,
			'purchase' => 'Y',
		);
		$q = [
			'count(*)',
			'xxt_template_order',
			$options,
		];
		return 0 < (int) $this->query_val_ss($q);
	}
	/**
	 * 站点使用模版
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
		$order->template_version = $template->version;
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

		$this->update("update xxt_template set copied_num=copied_num+1 where id = " . $template->id);

		return $order;
	}
	/**
	 * 创建模板版本号
	 */
	public function getVersionNum($site, $tid, $matterType) {
		$options = array(
			'siteid' => $site,
			'template_id' => $tid,
		);
		$q = [
			'max(version)',
			'',
			$options,
		];
		switch ($matterType) {
		case 'enroll':
			$q[1] = 'xxt_template_enroll';
			break;
		}
		$max = $this->query_val_ss($q);
		$seq = empty($max) ? 1 : (int) $max + 1;

		return $seq;
	}
	/**
	 *
	 */
	public function getEntryUrl($site, $tid, $vid = null) {
		$url = "http://" . APP_HTTP_HOST;

		if ($site === 'platform') {
			$app = $this->byId($tid, ['cascaded' => 'N']);
			$url .= "/rest/site/fe/matter/template";
			$url .= "?site={$app->siteid}&tid=" . $tid;
		} else {
			$url .= "/rest/site/fe/matter/enroll";
			$url .= "?site={$site}&tid=" . $tid;
		}

		if (!empty($vid)) {
			$url .= "&vid=" . $vid;
		}
		return $url;
	}

}