<?php
namespace pl\fe\matter\channel;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 *
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('channel', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

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
		if ($oChannel = $modelChn->byId($id)) {
			$oChannel->entryUrl = $modelChn->getEntryUrl($site, $id);
			/* 所属项目 */
			if ($oChannel->mission_id) {
				$oChannel->mission = $this->model('matter\mission')->byId($oChannel->mission_id);
			}
			!empty($oChannel->matter_mg_tag) && $oChannel->matter_mg_tag = json_decode($oChannel->matter_mg_tag);

			$oChannel->matters = $modelChn->getMatters($id, $oChannel, $site);
		}

		return new \ResponseData($oChannel);
	}
	/**
	 *
	 * @param string $site site's id
	 * @param string $acceptType 频道素材类型
	 * @param string $cascade 是否获得频道内的素材和访问控制列表
	 */
	public function list_action($site, $acceptType = null, $cascade = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelChn = $this->model('matter\channel');
		$oOptions = $this->getPostJson();
		/**
		 * 素材的来源
		 */
		$q = [
			'*',
			'xxt_channel c',
			"siteid = '" . $modelChn->escape($site) . "' and state = 1",
		];
		if (!empty($acceptType)) {
			$acceptType = ['', $acceptType];
			$acceptType = "('";
			$acceptType .= implode("','", $v);
			$acceptType .= "')";
			$q[2] .= " and matter_type in $acceptType";
		}
		if (!empty($oOptions->byTitle)) {
			$q[2] .= " and title like '%" . $modelChn->escape($oOptions->byTitle) . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $modelChn->escape($tag->id) . "%'";
			}
		}
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='channel' and t.matter_id=c.id and userid='{$oUser->id}')";
		}

		$q2['o'] = 'create_at desc';
		$channels = $modelChn->query_objs_ss($q, $q2);
		/* 获得子资源 */
		if ($channels) {
			foreach ($channels as $c) {
				$c->type = 'channel';
			}
			if ($cascade == 'Y') {
				foreach ($channels as $c) {
					$c->url = $modelChn->getEntryUrl($site, $c->id);
					$c->matters = $modelChn->getMatters($c->id, $c, $site);
				}
			}
		}

		return new \ResponseData(['docs' => $channels, 'total' => count($channels)]);
	}
	/**
	 * 在指定团队下创建频道素材
	 */
	public function create_action($site = null, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($site) && empty($mission)) {
			return new \ParameterError();
		}

		$oPosted = $this->getPostJson();
		$oChannel = new \stdClass;
		if (!empty($mission)) {
			$oMission = $this->model('matter\mission')->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
			$oChannel->siteid = $oMission->siteid;
			$oChannel->mission_id = $oMission->id;
		} else if (!empty($site)) {
			$oSite = $this->model('site')->byId($site);
			if (false === $oSite) {
				return new \ObjectNotFoundError();
			}
			$oChannel->siteid = $oSite->id;
		}

		$modelCh = $this->model('matter\channel')->setOnlyWriteDbConn(true);

		$q = ['count(*)', 'xxt_channel', ['siteid' => $site, 'state' => 1]];
		$countOfChn = (int) $modelCh->query_val_ss($q);

		$oChannel->title = isset($oPosted->title) ? $modelCh->escape($oPosted->title) : ('新频道-' . ++$countOfChn);
		$oChannel->matter_type = '';

		$oChannel = $modelCh->create($oUser, $oChannel);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $oChannel, 'C');

		return new \ResponseData($oChannel);
	}
	/**
	 * 更新频道的属性信息
	 *
	 * @param string $site site's id
	 * @param int $id channel's id
	 *
	 */
	public function update_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCh = $this->model('matter\channel');
		$oChannel = $modelCh->byId($id, 'id,title');
		if (false === $oChannel) {
			return new \ObjectNotFoundError();
		}

		$aUpdatedHomeCh = []; // 更新站点频道
		$oUpdated = new \stdClass;
		$oPosted = $this->getPostJson();
		foreach ($oPosted as $k => $v) {
			if (in_array($k, ['title', 'summary', 'fixed_title'])) {
				$aUpdatedHomeCh[$k] = $oUpdated->{$k} = $modelCh->escape($v);
			}if ($k === 'pic') {
				$aUpdatedHomeCh[$k] = $oUpdated->{$k} = $v;
			} else {
				$oUpdated->{$k} = $v;
			}
		}

		if ($oChannel = $modelCh->modify($oUser, $oChannel, $oUpdated)) {
			/* 更新站点频道中的信息 */
			if (count($aUpdatedHomeCh)) {
				$modelCh->update('xxt_site_home_channel', $aUpdatedHomeCh, ['channel_id' => $id]);
			}
			$this->model('matter\log')->matterOp($site, $oUser, $oChannel, 'U');
		}

		return new \ResponseData($oChannel);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$matter = $this->getPostJson();
		$modelChn = $this->model('matter\channel')->setOnlyWriteDbConn(true);

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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$relations = $this->getPostJson();
		$modelCh = $this->model('matter\channel')->setOnlyWriteDbConn(true);

		$matters = is_array($relations->matter) ? $relations->matter : [$relations->matter];
		if (empty($channel)) {
			$channels = $relations->channels;
			foreach ($channels as $channel) {
				foreach ($matters as $matter) {
					$modelCh->addMatter($channel->id, $matter, $oUser->id, $oUser->name);
				}
			}

			return new \ResponseData('ok');
		} else {
			foreach ($matters as $matter) {
				$modelCh->addMatter($channel, $matter, $oUser->id, $oUser->name);
			}
			$matters = $modelCh->getMatters($channel);

			return new \ResponseData($matters);
		}
	}
	/**
	 *
	 */
	public function removeMatter_action($site, $id, $reload = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$matter = $this->getPostJson();

		$modelCh = $this->model('matter\channel');
		$modelCh->setOnlyWriteDbConn(true);

		$rst = $modelCh->removeMatter($id, $matter);

		if ($reload === 'Y') {
			$matters = $modelCh->getMatters($id);
			return new \ResponseData($matters);
		} else {
			return new \ResponseData($rst);
		}
	}
	/**
	 * 删除频道
	 */
	public function remove_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCh = $this->model('matter\channel');
		$oChannel = $modelCh->byId($id, 'id,title');
		if (false === $oChannel) {
			return new \ObjectNotFoundError();
		}
		$rst = $modelCh->remove($oUser, $oChannel);

		return new \ResponseData($rst);
	}
	/**
	 * 创建频道定制页面
	 */
	public function pageCreate_action($site, $id, $page) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$code = $this->model('code\page')->create($site, $oUser->id);

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
		if (false === ($oUser = $this->accountUser())) {
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