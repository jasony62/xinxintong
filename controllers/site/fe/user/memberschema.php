<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点自定义用户信息
 */
class memberschema extends \site\fe\base {
	/**
	 * 进入选择认证接口页
	 *
	 * 如果被访问的页面支持多个认证接口，要求用户选择一种认证接口
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/schemaoptions');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $schema) {
		$modelSch = $this->model('site\user\memberschema');
		$aMemberSchemas = [];
		$aSchemaIds = explode(',', $schema);
		foreach ($aSchemaIds as $schemaId) {
			$schema = $modelSch->byId($schemaId, 'id,name,url');
			if ($schema) {
				$schema->url .= "?site={$site}&schema={$schemaId}";
				$aMemberSchemas[] = $schema;
			}
		}

		return new \ResponseData($aMemberSchemas);
	}
}