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
	 * 获得自定义用户的定义
	 *
	 * @param string $schema
	 * @param string $matter 要通过通信录访问的素材
	 *
	 */
	public function get_action($schema, $matter = null) {
		$modelMs = $this->model('site\user\memberschema');
		$schema = $modelMs->escape($schema);
		$oMschema = $modelMs->byId($schema, ['fields' => 'id,type,title,attr_name,attr_mobile,attr_email,ext_attrs,matter_id,matter_type,require_invite']);
		if ($oMschema === false) {
			return new \ResponseError('指定的自定义用户定义不存在');
		}

		$params = [];
		$params['schema'] = $oMschema;

		/* 已填写的用户信息 */

		/* 要访问的素材 */
		if (!empty($matter)) {
			$matter = $modelMs->escape($matter);
			$matter = explode(',', $matter);
			if (count($matter) === 2) {
				list($type, $id) = $matter;
				$modelMat = $this->model('matter\\' . $type);
				$oMatter = $modelMat->byId($id, ['fields' => 'id,state,title,summary,pic']);
				$params['matter'] = $oMatter;
			}
		}

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	public function list_action($site, $schema) {
		if (empty($schema)) {
			return new \ParameterError();
		}
		$modelSch = $this->model('site\user\memberschema');
		$aMemberSchemas = [];
		$aSchemaIds = explode(',', $schema);
		foreach ($aSchemaIds as $schemaId) {
			$schema = $modelSch->byId($schemaId, ['fields' => 'id,name,url']);
			if ($schema) {
				$schema->url .= "?site={$site}&schema={$schemaId}";
				$aMemberSchemas[] = $schema;
			}
		}

		return new \ResponseData($aMemberSchemas);
	}
	/**
	 * 获得用户主页可用的自定义联系人定义
	 */
	public function atHome_action($site) {
		$modelSchema = $this->model('site\user\memberschema');

		$schemas = $modelSchema->bySite($site, 'Y', ['atUserHome' => 'Y', 'fields' => 'id,require_invite,title,type,url']);

		return new \ResponseData($schemas);
	}
}