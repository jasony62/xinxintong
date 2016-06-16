<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class other extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/site/sns/wx/main');
		exit;
	}
	/**
	 * 其他事件
	 *
	 * 如果没有创建过相应的事件，系统自动创建
	 *
	 * subscribe,universal,templatemsg
	 */
	public function list_action($site) {
		/**
		 * 支持的消息类型
		 */
		$events = array(
			'subscribe' => '关注',
			'universal' => '缺省',
			'location' => '地理位置',
			'templatemsg' => '模板消息结果',
			'cardevent' => '卡卷事件',
		);

		$q = array(
			'id,name,title,matter_type,matter_id',
			'xxt_call_other_wx',
			"siteid='$site'",
		);
		if ($calls = $this->model()->query_objs_ss($q)) {
			foreach ($calls as $call) {
				if ($events[$call->name]) {
					unset($events[$call->name]);
				}
				/**
				 * 回复素材
				 */
				if ($call->matter_id) {
					$call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
				}
			}
		}
		/**
		 * 添加新支持的事件
		 */
		foreach ($events as $n => $t) {
			$call = array(
				'siteid' => $site,
				'name' => $n,
				'title' => $t,
				'matter_type' => '',
				'matter_id' => '',
			);
			$call['id'] = $this->model()->insert(
				'xxt_call_other_wx',
				$call,
				true
			);
			unset($call['siteid']);
			$calls[] = (object) $call;
		}

		return new \ResponseData($calls);
	}
	/**
	 * 设置回复素材
	 */
	public function setreply_action($site, $id) {
		$matter = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_call_other_wx',
			$matter,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
}