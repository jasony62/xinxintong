<?php
namespace pl\fe\site\setting;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点配置的通知消息
 */
class notice extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/setting');
		exit;
	}
	/**
	 * 根据名称获得指定的通知消息设置
	 */
	public function get_action($site, $name, $cascaded = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNot = $this->model('site\notice');
		$modelNot->setOnlyWriteDbConn(true);
		if (false === ($notice = $modelNot->byName($site, $name))) {
			/**
			 * 如果不存在就创建
			 */
			$data = [
				'creater' => $user->id,
				'creater_name' => $user->name,
				'create_at' => time(),
			];
			$notice = $modelNot->add($site, $name, $data);
		} else {
			if ($cascaded === 'Y' && $notice->tmplmsg_config_id) {
				$modelMap = $this->model('matter\tmplmsg\config');
				$notice->tmplmsgConfig = $modelMap->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
			}
		}

		return new \ResponseData($notice);
	}
	/**
	 * 站点管理员
	 */
	public function list_action($site, $cascaded = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			'*',
			'xxt_site_notice',
			"siteid='$site'",
		);
		$notifies = $this->model()->query_objs_ss($q);

		return new \ResponseData($admins);
	}
	/**
	 * 建立映射关系
	 */
	public function setup_action($site, $name, $mapping = 0) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson(false);
		$model = $this->model();

		foreach ($posted->mapping as &$prop) {
			if (isset($prop->src) && $prop->src === 'text') {
				$prop->id = $model->escape($prop->id);
			}
		}

		if ($mapping == 0) {
			/*建立影射关系*/
			$mapping = $model->insert(
				'xxt_tmplmsg_mapping',
				array(
					'siteid' => $site,
					'msgid' => $model->escape($posted->msgid),
					'mapping' => $model->escape($model->toJson($posted->mapping)),
				),
				true
			);
			/*记录影射关系*/
			$model->update(
				'xxt_site_notice',
				array(
					'tmplmsg_config_id' => $mapping,
				),
				"siteid='$site' and event_name='$name'"
			);
		} else {
			/*建立影射关系*/
			$model->update(
				'xxt_tmplmsg_mapping',
				array(
					'msgid' => $model->escape($posted->msgid),
					'mapping' => $model->escape($model->toJson($posted->mapping)),
				),
				"id=$mapping"
			);
		}

		$config = $this->model('matter\tmplmsg\config')->byId($mapping, ['cascaded' => 'Y']);

		return new \ResponseData($config);
	}
	/**
	 * 清除映射关系
	 */
	public function clean_action($site, $name, $mapping = 0) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		if (!empty($mapping)) {
			$model->delete('xxt_tmplmsg_mapping', "siteid='$site' and id=$mapping");

			$rst = $model->update(
				'xxt_site_notice',
				array('tmplmsg_config_id' => 0),
				"siteid='$site' and event_name='$name'"
			);
		} else {
			$rst = 0;
		}

		return new \ResponseData($rst);
	}
}