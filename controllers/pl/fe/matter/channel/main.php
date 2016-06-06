<?php
namespace pl\fe\matter\channel;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/channel/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/channel/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelChn = $this->model('matter\channel');
		$channel = $modelChn->byId($id);
		$channel->matters = $modelChn->getMatters($id, $channel, $site);
		$channel->acl = $this->model('acl')->byMatter($site, 'channel', $id);

		return new \ResponseData($channel);
	}
	/**
	 *
	 * $src 是否从父账号获取资源
	 * $acceptType
	 * $cascade 是否获得频道内的素材和访问控制列表
	 */
	public function list_action($site, $acceptType = null, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$options = $this->getPostJson();
		/**
		 * 素材的来源
		 */
		$q = array(
			"c.*",
			'xxt_channel c',
			"c.siteid='$site' and c.state=1",
		);
		!empty($acceptType) && $q[2] .= " and (matter_type='' or matter_type='$acceptType')";
		$q2['o'] = 'create_at desc';
		$channels = $this->model()->query_objs_ss($q, $q2);
		/* 获得子资源 */
		if ($channels && $cascade == 'Y') {
			$modelChn = $this->model('matter\channel');
			$modelAcl = $this->model('acl');
			foreach ($channels as $c) {
				$c->matters = $modelChn->getMatters($c->id, $c, $site);
				$c->acl = $modelAcl->byMatter($site, 'channel', $c->id);
			}
		}

		return new \ResponseData($channels);
	}
	/**
	 * 创建频道素材
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		$current = time();

		$channel = array();
		$channel['siteid'] = $site;
		$channel['creater'] = $user->id;
		$channel['create_at'] = $current;
		$channel['creater_src'] = 'A';
		$channel['creater_name'] = $user->name;
		$channel['modifier'] = $user->id;
		$channel['modifier_src'] = 'A';
		$channel['modifier_name'] = $user->name;
		$channel['modify_at'] = $current;
		$channel['title'] = isset($posted->title) ? $posted->title : '新频道';

		$id = $this->model()->insert('xxt_channel', $channel, true);

		/* 记录操作日志 */
		$matter = (object) $channel;
		$matter->id = $id;
		$matter->type = 'channel';
		$this->model('log')->matterOp($site, $user, $matter, 'C');

		$channel = $this->model('matter\channel')->byId($id);

		return new \ResponseData($channel);
	}
	/**
	 * 更新频道的属性信息
	 *
	 * $id channel's id
	 * $nv pair of name and value
	 */
	public function update_action($site, $id) {
		$user = $this->accountUser();
		$nv = $this->getPostJson();
		$current = time();

		$nv->modifier = $user->id;
		$nv->modifier_src = 'A';
		$nv->modifier_name = $user->name;
		$nv->modify_at = $current;

		$rst = $this->model()->update('xxt_channel',
			$nv,
			"siteid='$site' and id=$id"
		);
		/* 记录操作日志 */
		if ($rst) {
			$channel = $this->model('matter\\' . 'channel')->byId($id, 'id,title');
			$channel->type = 'channel';
			$this->model('log')->matterOp($site, $user, $channel, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $id channel's id.
	 * $pos top|bottom
	 *
	 * post
	 * $t matter's type.
	 * $id matter's id.
	 *
	 */
	public function setfixed_action($site, $id, $pos) {
		$matter = $this->getPostJson();

		if ($pos === 'top') {
			$this->model()->update('xxt_channel',
				array(
					'top_type' => $matter->t,
					'top_id' => $matter->id,
				),
				"siteid='$site' and id=$id"
			);
		} else if ($pos === 'bottom') {
			$this->model()->update('xxt_channel',
				array(
					'bottom_type' => $matter->t,
					'bottom_id' => $matter->id,
				),
				"siteid='$site' and id=$id"
			);
		}

		$matters = $this->model('matter\channel')->getMatters($id);

		return new \ResponseData($matters);
	}
	/**
	 *
	 */
	public function addMatter_action($site) {
		$user = $this->accountUser();

		$relations = $this->getPostJson();

		$channels = $relations->channels;
		$matter = $relations->matter;

		$model = $this->model('matter\channel');
		foreach ($channels as $channel) {
			$model->addMatter($channel->id, $matter, $user->id, $user->name);
		}

		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	public function removeMatter_action($site, $id, $reload = 'N') {
		$matter = $this->getPostJson();

		$model = $this->model('matter\channel');

		$rst = $model->removeMatter($id, $matter);

		if ($reload === 'Y') {
			$matters = $model->getMatters($id);
			return new \ResponseData($matters);
		} else {
			return new \ResponseData($rst);
		}
	}
	/**
	 * 删除频道
	 */
	public function delete_action($id) {
		$rst = $this->model()->update('xxt_channel', array('state' => 0), "mpid='$this->mpid' and id=$id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'channel';
	}
	/**
	 * 创建频道定制页面
	 */
	public function pageCreate_action($site, $id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$code = $this->model('code\page')->create($site, $user->id);

		$rst = $this->model()->update(
			'xxt_channel',
			array(
				$page . '_page_id' => $code->id,
				$page . '_page_name' => $code->name,
			),
			"siteid='{$site}' and id='$id'"
		);

		return new \ResponseData($code);
	}
	/**
	 * 重置定制页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($site, $id, $page) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelChn = $this->model('matter\channel');
		$channel = $modelChn->byId($id);
		$data = array(
			'html' => '',
			'css' => '',
			'js' => '',
		);
		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($site, $channel->{$page . '_page_name'});
		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
}