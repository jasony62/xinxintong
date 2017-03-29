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
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelChn = $this->model('matter\channel');
		if ($channel = $modelChn->byId($id)) {
			$channel->matters = $modelChn->getMatters($id, $channel, $site);
			$channel->acl = $this->model('acl')->byMatter($site, 'channel', $id);
		}

		return new \ResponseData($channel);
	}
	/**
	 *
	 * @param string $site site's id
	 * @param string $acceptType 频道素材类型
	 * @param string $cascade 是否获得频道内的素材和访问控制列表
	 */
	public function list_action($site, $acceptType = null, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$options = $this->getPostJson();
		/**
		 * 素材的来源
		 */
		$q = [
			'*',
			'xxt_channel',
			['siteid' => $site, 'state' => 1],
		];
		!empty($acceptType) && $q[2]['matter_type'] = ['', $acceptType];
		$q2['o'] = 'create_at desc';
		$modelChn = $this->model('matter\channel');
		$channels = $modelChn->query_objs_ss($q, $q2);
		/* 获得子资源 */
		if ($channels && $cascade == 'Y') {
			$modelAcl = $this->model('acl');
			foreach ($channels as $c) {
				$c->url = $modelChn->getEntryUrl($site, $c->id);
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

		$modelCh = $this->model('matter\channel');
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
		$channel['matter_type'] = '';

		$id = $modelCh->insert('xxt_channel', $channel, true);

		/* 记录操作日志 */
		$matter = (object) $channel;
		$matter->id = $id;
		$matter->type = 'channel';
		$this->model('matter\log')->matterOp($site, $user, $matter, 'C');

		$channel = $modelCh->byId($id);

		return new \ResponseData($channel);
	}
	/**
	 * 更新频道的属性信息
	 *
	 * @param string $site site's id
	 * @param int $id channel's id
	 *
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCh = $this->model('matter\channel');
		$channel = $modelCh->byId($id, 'id,title');

		$updatedHomeCh = []; // 更新站点频道
		$updated = new \stdClass;
		$posted = $this->getPostJson();
		foreach ($posted as $k => $v) {
			if (in_array($k, ['title', 'summary'])) {
				$updatedHomeCh[$k] = $updated->{$k} = $modelCh->escape($v);
			}if ($k === 'pic') {
				$updatedHomeCh[$k] = $updated->{$k} = $v;
			} else {
				$updated->{$k} = $v;
			}
		}

		$current = time();
		$updated->modifier = $user->id;
		$updated->modifier_src = 'A';
		$updated->modifier_name = $user->name;
		$updated->modify_at = $current;

		$rst = $modelCh->update('xxt_channel',
			$updated,
			["siteid" => $site, "id" => $id]
		);
		if ($rst) {
			/* 更新站点频道中的信息 */
			if (count($updatedHomeCh)) {
				$modelCh->update('xxt_site_home_channel', $updatedHomeCh, ['channel_id' => $id]);
			}
			/* 记录操作日志 */
			$this->model('matter\log')->matterOp($site, $user, $channel, 'U');
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$matter = $this->getPostJson();
		$modelChn = $this->model('matter\channel');
		if ($pos === 'top') {
			$modelChn->update('xxt_channel',
				[
					'top_type' => $matter->t,
					'top_id' => $matter->id,
				],
				['siteid' => $site, 'id' => $id]
			);
		} else if ($pos === 'bottom') {
			$modelChn->update('xxt_channel',
				[
					'bottom_type' => $matter->t,
					'bottom_id' => $matter->id,
				],
				['siteid' => $site, 'id' => $id]
			);
		}

		$matters = $modelChn->getMatters($id);

		return new \ResponseData($matters);
	}
	/**
	 * 建立频道和素材的关联
	 *
	 * @param string $site site's id
	 * @param int $channel channel's id
	 *
	 */
	public function addMatter_action($site, $channel = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$relations = $this->getPostJson();
		$modelCh = $this->model('matter\channel');

		$matters = is_array($relations->matter) ? $relations->matter : [$relations->matter];
		if (empty($channel)) {
			$channels = $relations->channels;
			foreach ($channels as $channel) {
				foreach ($matters as $matter) {
					$modelCh->addMatter($channel->id, $matter, $user->id, $user->name);
				}
			}

			return new \ResponseData('ok');
		} else {
			foreach ($matters as $matter) {
				$modelCh->addMatter($channel, $matter, $user->id, $user->name);
			}
			$matters = $modelCh->getMatters($channel);

			return new \ResponseData($matters);
		}
	}
	/**
	 *
	 */
	public function removeMatter_action($site, $id, $reload = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

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
			[
				$page . '_page_id' => $code->id,
				$page . '_page_name' => $code->name,
			],
			['siteid' => $site, 'id' => $id]
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
		$data = [
			'html' => '',
			'css' => '',
			'js' => '',
		];
		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($site, $channel->{$page . '_page_name'});
		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
}