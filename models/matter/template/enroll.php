<?php
namespace matter\template;
/**
 *
 */
class enroll_model extends \TMS_MODEL {
	/**
	 * 创建模板
	 */
	public function create(&$site, $user, &$data) {
		$current = time();
		/* 新建模板 */
		$template = [
			'creater' => $user->id,
			'creater_name' => $user->name,
			'create_at' => $current,
			'siteid' => $site->id,
			'site_name' => $site->name,
			'matter_type' => $data->matter_type,
			'scenario' => isset($data->scenario) ? $data->scenario : 'common',
			'title' => isset($data->title) ? $data->title : '新模板(' . $data->matter_type . ')',
			'pic' => isset($data->pic) ? $data->pic : '',
			'summary' => isset($data->summary) ? $this->escape($data->summary) : '',
			'coin' => isset($data->coin) ? $data->coin : 0,
			'visible_scope' => isset($data->visible_scope) ? $data->visible_scope : 'S',
			'push_home' => isset($data->push_home) ? $data->push_home : 'N',
		];
		$template['id'] = $this->insert('xxt_template', $template, true);

		//新建版本
		$versionNum = $this->model('matter\template')->getVersionNum($site->id, $template['id'], $data->matter_type);
		$options = [
			'version' => $versionNum,
			'modifier' => $user->id,
			'modifier_name' => $user->name,
			'create_at' => $current,
			'siteid' => $site->id,
			'template_id' => $template['id'],
			'scenario_config' => isset($data->scenario_config) ? $data->scenario_config : '',
			'enrolled_entry_page' => isset($data->enrolled_entry_page) ? $data->enrolled_entry_page : '',
			'open_lastroll' => isset($data->open_lastroll) ? $data->open_lastroll : 'Y',
			'data_schemas' => isset($data->data_schemas) ? $data->data_schemas : '',
		];
		$options['id'] = $this->insert('xxt_template_enroll', $options, true);
		$this->update(
			'xxt_template',
			['last_version' => $versionNum],
			['id' => $template['id']]
		);

		$app = (object) $template;
		$app->last_version = $versionNum;
		$app->version = (object) $options;
		return $app;
	}
	/**
	 * 检查版本是否已经发布
	 * @param  [type] $site [description]
	 * @param  [type] $vid  [description]
	 * @return [type]       [description]
	 */
	public function checkVersion($site, $vid) {
		$q = [
			'pub_status',
			'xxt_template_enroll',
			['siteid' => $site, 'id' => $vid],
		];
		if ($version = $this->query_obj_ss($q)) {
			if ($version->pub_status === "Y") {
				return array(true);
			} else {
				return array(false, '未发布');
			}
		} else {
			die('版本不存在');
		}
	}
	/**
	 * [createNewVersion 创建新版本]
	 * @param  [type] $site      [description]
	 * @param  [type] $tid       [description]
	 * @param  [type] &$matter   [description]
	 * @param  [type] $user      [description]
	 * @param  string $time      [description]
	 * @param  string $pubStatus [是否为发布状态]
	 * @return [type]            [description]
	 */
	public function createNewVersion($site, $tid, &$matter, $user, $time = '', $pubStatus = 'N') {
		$current = empty($time) ? time() : $time;
		//创建模板版本号
		$version = $this->model('matter\template')->getVersionNum($site, $tid, 'enroll');
		$options = [
			'version' => $version,
			'modifier' => $user->id,
			'modifier_name' => $user->name,
			'create_at' => $current,
			'siteid' => $site,
			'template_id' => $tid,
			'scenario_config' => empty($matter->scenario_config) ? '' : $this->escape($matter->scenario_config),
			'enrolled_entry_page' => $matter->enrolled_entry_page,
			'open_lastroll' => $matter->open_lastroll,
			'data_schemas' => $this->escape($matter->data_schemas),
			'pub_status' => $pubStatus,
		];
		//版本id
		$vid = $this->insert('xxt_template_enroll', $options, true);
		$options['id'] = $vid;

		/*复制页面*/
		if (count($matter->pages)) {
			$modelPage = $this->model('matter\enroll\page');
			$modelCode = $this->model('code\page');
			foreach ($matter->pages as $ep) {
				$newPage = $modelPage->add($user, $site, 'template:' . $vid);
				$rst = $this->update(
					'xxt_enroll_page',
					[
						'title' => $ep->title,
						'name' => $ep->name,
						'type' => $ep->type,
						'data_schemas' => $this->escape($ep->data_schemas),
						'act_schemas' => $this->escape($ep->act_schemas),
					],
					['aid' => 'template:' . $vid, 'id' => $newPage->id]
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

		return (object) $options;
	}
}