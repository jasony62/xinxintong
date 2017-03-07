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

		if ($template = $this->query_obj_ss($q)) {
			$template->type = 'template';
			if($template->matter_type === 'enroll'){
				//获取版本
				$p = [
					'*',
					'xxt_template_enroll',
					['template_id'=>$tid, 'state' => 1]
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
				//获取指定版本信息，如果未指定就用默认版本
				if ($cascaded === 'Y') {
					//获取当前需要展示页面的版本的id
					if(empty($vid)){
						if(empty($template->pub_version)){
							foreach($template->versions as $v){
								if($v->version === $template->last_version){
									$vid = $v->id;
									$version = $v;
								}
							}
						}else{
							foreach($template->versions as $v){
								if($v->version === $template->pub_version){
									$vid = $v->id;
									$version = $v;
								}
							}
						}
					}else{
						//返回当前预览版本的数据
						foreach($template->versions as $v){
							if($v->id === $vid){
								$version = $v;
							}
						}
					}
					foreach($version as $k=>$v2){
						if($k === 'id'){
							$template->vid = $v2;
						}elseif($k === 'create_at'){
							$template->vcreate_at = $v2;
						}else{
							$template->$k = $v2;
						}
					}
					$modelPage = $this->model('matter\enroll\page');
					$template->pages = $modelPage->byApp('template:'.$vid);
				}
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
	public function &bySite($site, $page = 1, $size = 30){
		$q = [
			'*',
			'xxt_template',
			['siteid'=>$site,'state'=>1]
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
			["matter_id" => $matterId, "matter_type" => $matterType],
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
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'coin' => $matter->coin,
				'visible_scope' => $matter->visible_scope,
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
			];
			$tid = $this->insert('xxt_template', $template, true);
			/*创建版本*/
			if($matter->matter_type === 'enroll'){
				$this->putMatterEnroll($site->id, $tid, $matter->matter_id, $account, $current);
			}

			$template = $this->byId($site->id, $tid);
		} else {
			/* 更新模板 */
			$updated = [
				'title' => $matter->title,
				'pic' => $matter->pic,
				'put_at' => $current,
				'summary' => $matter->summary,
				'coin' => $matter->coin,
				'visible_scope' => $matter->visible_scope,
				'push_home' => isset($matter->push_home) ? $matter->push_home : 'N',
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
			];
			$this->update(
				'xxt_template',
				$updated,
				["id" => $template->id]
			);
			/*创建新的版本*/
			if($matter->matter_type === 'enroll'){
				$this->putMatterEnroll($site->id, $template->id, $matter->matter_id, $account, $current);
			}

			$template = $this->byId($site->id, $template->id);
		}
		// 添加模板接收人
		// if (!empty($matter->acls)) {
		// 	$modelAcl = \TMS_APP::M('template\acl');
		// 	foreach ($matter->acls as $acl) {
		// 		$acl = $modelAcl->add($account, $template, $acl);
		// 	}
		// }

		return $template;
	}
	/**
	 * 创建登记活动模板
	 * @param int $tid template_id
	 * @param string $eid enroll_id
	 */
	private function putMatterEnroll($site, $tid ,$eid, $user, $time = ''){
		$current = empty($time)? time() : $time;
		//创建模板版本号
		$version = $this->getVersion($site, $tid);
		$modelApp = $this->model('matter\enroll');

		$matter = $modelApp->byId($eid);
		$options = [
			'version' => $version,
			'modifier' => $user->id,
			'modifier_name' => $user->name,
			'create_at' => $current,
			'siteid' => $site,
			'template_id' => $tid,
			'scenario_config' => empty($matter->scenario_config) ? '' : $matter->scenario_config,
			'multi_rounds' => $matter->multi_rounds,
			'enrolled_entry_page' => $matter->enrolled_entry_page,
			'open_lastroll' => $matter->open_lastroll,
			'data_schemas' => $matter->data_schemas,
			'pub_status' => 'Y',
		];
		//版本id
		$vid = $this->insert('xxt_template_enroll', $options, true);
		$this->update(
				'xxt_template',
				['pub_version' => $version, 'last_version' => $version],
				['siteid' => $site, 'id' => $tid]
			);

		/*复制页面*/
		if (count($matter->pages)) {
			$modelPage = $this->model('matter\enroll\page');
			$modelCode = $this->model('code\page');
			foreach ($matter->pages as $ep) {
				$newPage = $modelPage->add($user, $site, 'template:'.$vid);
				$rst = $this->update(
					'xxt_enroll_page',
					[
						'title' => $ep->title,
						'name' => $ep->name,
						'type' => $ep->type,
						'data_schemas' => $this->escape($ep->data_schemas),
						'act_schemas' => $this->escape($ep->act_schemas),
						'user_schemas' => $this->escape($ep->user_schemas),
					],
					['aid' => 'template:'.$vid, 'id' => $newPage->id]
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
			["visible_scope" => 'P', "push_home" => 'Y','state' => 1],
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
			["siteid" => $siteId, 'push_home' => 'Y','state' => 1],
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
				'favor' => 'Y'
			);
		$q = [
			'count(*)',
			'xxt_template_order',
			$options
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
		$order->template_version = empty($version)? $template->pub_version : $this->escape($version);
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
	 * 是否站点已经使用模版
	 */
	public function isPurchaseBySite(&$template, $siteId) {
		$options = array(
				'siteid' => $siteId,
				'template_id' => $template->id,
				'purchase' => 'Y'
			);
		$q = [
			'count(*)',
			'xxt_template_order',
			$options
		];
		return 0 < (int) $this->query_val_ss($q);
	}
	/**
	 * 站点使用模版
	 */
	public function purchaseBySite(&$user, &$template, $siteId, $version) {
		if ($this->isPurchaseBySite($template, $siteId)) {
			return true;
		}
		$template = $this->escape($template);
		$order = new \stdClass;
		$order->siteid = $siteId;
		$order->buyer = $user->id;
		$order->buyer_name = $user->name;
		$order->template_id = $template->id;
		$order->template_version = empty($version)? $template->pub_version : $this->escape($version);
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
	/**
	 * 创建模板版本号
	 */
	public function getVersion($site, $tid){
		$options = array(
				'siteid' => $site,
				'template_id' => $tid,
			);
		$q = [
			'max(version)',
			'xxt_template_enroll',
			$options
		];
		$max = $this->query_val_ss($q);
		$seq = empty($max) ? 1 : (int)$max + 1 ;

		return $seq;
	}
}