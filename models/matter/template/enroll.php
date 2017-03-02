<?php
namespace matter\template;
/**
 *
 */
class enroll_model extends \TMS_MODEL {
	/**
	 * 创建版本
	 */
	public function create(&$site, $user, &$data){
		$current = time();
		/* 新建模板 */
		$template = [
			'creater' => $user->id,
			'creater_name' => $user->name,
			'put_at' => $current,
			'siteid' => $site->id,
			'site_name' => $site->name,
			'matter_type' => $data->matter_type,
			'scenario' => empty($data->scenario) ? '' : $data->scenario,
			'title' => $data->title,
			'pic' => $data->pic,
			'summary' => $data->summary,
			'coin' => $data->coin,
			'visible_scope' => $data->visible_scope,
			'push_home' => isset($data->push_home) ? $data->push_home : 'N',
		];
		$template['id'] = $this->insert('xxt_template', $template, true);

		//创建版本
		$versionNum = $this->model('matter\template')->getVersion($site->id, $template['id']);
		$options = [
			'version' => $versionNum,
			'create_at' => $current,
			'siteid' => $site->id,
			'template_id' => $template['id'],
			'scenario_config' => isset($data->scenario_config)? $data->scenario_config : '',
			'enrolled_entry_page' => isset($data->enrolled_entry_page)? $data->enrolled_entry_page : '',
			'open_lastroll' => isset($data->open_lastroll)? $data->open_lastroll : 'Y',
			'data_schemas' => $data->data_schemas,
		];
		$options['id'] = $this->insert('xxt_template_enroll', $options, true);

		$this->update(
				'xxt_template',
				['last_version'=>$versionNum],
				['id' => $template['id']]
			);

		$app = (object)$template;
		$app->last_version = $versionNum;
		$app->version = (object)$options;
		return $app;
	} 
}