<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 * 信息墙
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		$oWall = $modelWall->byId($id);
		/**
		 * 获得信息墙的url
		 */
		$oWall->user_url = $modelWall->getEntryUrl($site, $id);
		/*所属项目*/
		if ($oWall->mission_id) {
			$oWall->mission = $this->model('matter\mission')->byId($oWall->mission_id, ['cascaded' => 'phase']);
		}
		/**
		 * acl
		 */
		$oWall->acl = $this->model('acl')->byMatter($site, 'wall', $id);
		if (!empty($oWall->source_app)) {
			$sourceApp = json_decode($oWall->source_app);
			$options = array('cascaded' => 'N', 'fields' => 'id,title');
			$oWall->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
		}

		return new \ResponseData($oWall);
	}
	/**
	 *
	 */
	public function list_action($site = null, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($site) && empty($mission)) {
			return new \ParameterError();
		}

		$oPosted = $this->getPostJson();
		$modelWall = $this->model('matter\wall');
		$q = [
			'*',
			'xxt_wall w',
		];
		if (!empty($mission)) {
			$q[2] = "state = 1 and mission_id = " . $modelWall->escape($mission);
		} else {
			$q[2] = "state = 1 and siteid = '" . $modelWall->escape($site) . "'";
		}
		if (!empty($oPosted->byTitle)) {
			$q[2] .= " and title like '%" . $modelWall->escape($oPosted->byTitle) . "%'";
		}
		if (!empty($oPosted->byTags)) {
			foreach ($oPosted->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $modelWall->escape($tag->id) . "%'";
			}
		}
		if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='wall' and t.matter_id=w.id and userid='{$oUser->id}')";
		}
		$q2['o'] = 'create_at desc';

		$walls = $modelWall->query_objs_ss($q, $q2);
		/**
		 * 获得每个信息墙的url
		 */
		if ($walls) {
			foreach ($walls as &$wall) {
				$wall->type = 'wall';
				$wall->user_url = $modelWall->getEntryUrl($site, $wall->id);
			}
		}

		return new \ResponseData(['apps' => $walls, 'total' => count($walls)]);
	}
	/**
	 * 创建一个信息墙
	 */
	public function create_action($site = null, $mission = null, $scenario = 'discuss', $template = 'simple') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oCustomConfig = $this->getPostJson();
		$oNewApp = new \stdClass;
		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
			if (false === $site) {
				return new \ObjectNotFoundError();
			}
			$oNewApp->siteid = $oSite->id;
			$oNewApp->pic = $oSite->heading_pic; //使用站点的缺省头图
			$oNewApp->summary = '';
			$title = '信息墙-' . (($scenario === 'interact')? '互动' : '讨论');
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
			$oNewApp->siteid = $oMission->siteid;
			$oNewApp->summary = $oMission->summary;
			$oNewApp->pic = $oMission->pic;
			$oNewApp->mission_id = $oMission->id;
			$title = $oMission->title . '-' . (($scenario === 'interact')? '互动' : '讨论');
		}

		$modelWall = $this->model('matter\wall')->setOnlyWriteDbConn(true);
		/* 前端指定的信息 */
		$oNewApp->title = empty($oCustomConfig->proto->title) ? $title : urldecode($modelWall->escape(urlencode($oCustomConfig->proto->title)));
		!empty($oCustomConfig->proto->summary) && $oNewApp->summary = $modelWall->escape($oCustomConfig->proto->summary);
		!empty($oCustomConfig->proto->start_at) && $oNewApp->start_at = $modelWall->escape($oCustomConfig->proto->start_at);!empty($oCustomConfig->proto->end_at) && $oNewApp->end_at = $modelWall->escape($oCustomConfig->proto->end_at);
		$oNewApp->scenario = $modelWall->escape($scenario);
		!empty($oCustomConfig->proto->scenario_config) && $oNewApp->scenario_config = $modelWall->toJson($oCustomConfig->proto->scenario_config);
		$oNewApp->quit_cmd = 'q';
		$oNewApp->join_reply = '欢迎加入';
		$oNewApp->quit_reply = '已经退出';

		$oNewApp = $modelWall->create($oUser, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oNewApp->siteid, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * submit basic.
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\wall');
		$oMatter = $modelApp->byId($app, 'id,title,summary,pic,scenario,start_at,end_at,mission_id');
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$oUpdated = $this->getPostJson();
		if (isset($oUpdated->title)) {
			$oUpdated->title = $modelApp->escape($oUpdated->title);
		} else if (isset($oUpdated->join_reply)) {
			$oUpdated->join_reply = $modelApp->escape($oUpdated->join_reply);
		} else if (isset($oUpdated->quit_reply)) {
			$oUpdated->quit_reply = $modelApp->escape($oUpdated->quit_reply);
		} else if (isset($oUpdated->entry_ele)) {
			$oUpdated->entry_ele = $modelApp->escape($oUpdated->entry_ele);
		} else if (isset($oUpdated->entry_css)) {
			$oUpdated->entry_css = $modelApp->escape($oUpdated->entry_css);
		} else if (isset($oUpdated->body_css)) {
			$oUpdated->body_css = $modelApp->escape($oUpdated->body_css);
		} else if (isset($oUpdated->active) && $oUpdated->active === 'N') {
			//如果停用信息墙，退出所有用户
			$modelApp->update('xxt_wall_enroll', ['close_at' => time()], ['wid' => $app]);
		} else if (isset($oUpdated->scenario_config)) {
			$oUpdated->scenario_config = $modelApp->escape(json_encode($oUpdated->scenario_config));
		} else if (isset($oUpdated->matters_img)) {
			$oUpdated->matters_img = $modelApp->toJson($oUpdated->matters_img);
		} else if (isset($oUpdated->result_img)) {
			$oUpdated->result_img = $modelApp->toJson($oUpdated->result_img);
		}

		if ($oMatter = $modelApp->modify($oUser, $oMatter, $oUpdated)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($oMatter);
	}
	/**
	 * 导出Excel
	 */
	public function export_action($site, $wid) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\wall');
		$oApp = $model->query_obj_ss(['*', 'xxt_wall', ['siteid' => $site, 'id' => $wid]]);

		if (empty($oApp)) {
			return new \ResponseError('信息墙未创建！');
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		$PHPExcel = new \PHPExcel();
		$PHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $PHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		//第一行
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户信息');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言内容');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '状态');

		$data = $model->msgList($site, $wid);
		$row = 2;

		foreach ($data as $k => $v) {
			$columnNum2 = 0; //列号

			if ($v->approved == 0) {
				$status = '未审核';
			} else if ($v->approved == 1) {
				$status = '审核通过';
			} else {
				$status = '审核未通过';
			}

			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, !empty($v->nickname) ? $v->nickname : ('用户' . $v->userid));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, date('Y-m-d H:i:s', $v->approve_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, date('Y-m-d H:i:s', $v->publish_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $v->data);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row++, $status);
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $oApp->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 复制信息墙
	 */
	public function copy_action($site, $app, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		if (($oCopiedApp = $modelWall->byId($app)) === false) {
			return new \ObjectNotFoundError();
		}
		/*pages*/
		$modelPage = $this->model('matter\wall\page');
		$modelCode = $this->model('code\page');
		$oPages = [];
		$oPage = $modelPage->byType('op', $oCopiedApp->id);
		if (is_array($oPage)) {
			$oPages = array_merge($oPages, $oPage);
		} else {
			$oPages[] = $oPage;
		}

		/*copy*/
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $modelWall->escape($site);
		$oNewApp->title = $modelWall->escape($oCopiedApp->title) . '(副本)';
		$oNewApp->pic = $oCopiedApp->pic;
		$oNewApp->summary = $modelWall->escape($oCopiedApp->summary);
		$oNewApp->join_reply = $modelWall->escape($oCopiedApp->join_reply);
		$oNewApp->quit_reply = $modelWall->escape($oCopiedApp->quit_reply);
		$oNewApp->quit_cmd = $modelWall->escape($oCopiedApp->quit_cmd);
		$oNewApp->entry_css = $modelWall->escape($oCopiedApp->entry_css);
		$oNewApp->body_css = $modelWall->escape($oCopiedApp->body_css);
		$oNewApp->skip_approve = $oCopiedApp->skip_approve;
		$oNewApp->push_others = $oCopiedApp->push_others;
		$oNewApp->entry_ele = $modelWall->escape($oCopiedApp->entry_ele);
		/* 记录和任务的关系 */
		if (!empty($mission)) {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
			$oNewApp->mission_id = $oMission->id;
		}

		$oNewApp = $modelWall->create($oUser, $oNewApp);

		/*复制页面*/
		if (empty($oPages)) {
			$wp = [
				'name' => '信息墙大屏幕',
				'title' => '信息墙大屏幕',
				'type' => 'op',
				'seq' => 1,
				'templateDir' => TMS_APP_TEMPLATE . '/site/op/matter/wall/',
			];
			$newPage = $modelPage->add($site, $wp, $oNewApp->id);
			$templateDir = $wp['templateDir'];
			$data = [
				'html' => file_get_contents($templateDir . 'basic.html'),
				'css' => file_get_contents($templateDir . 'basic.css'),
				'js' => file_get_contents($templateDir . 'basic.js'),
			];
			$modelCode->modify($newPage->code_id, $data);
		} else {
			foreach ($oPages as $oPage) {
				$wp = [
					'name' => $modelPage->escape($oPage->name),
					'title' => $modelPage->escape($oPage->title),
					'type' => $oPage->type,
					'seq' => $oPage->seq,
				];
				$newPage = $modelPage->add($site, $wp, $oNewApp->id);
				$data = array(
					'html' => $oPage->html,
					'css' => $oPage->css,
					'js' => $oPage->js,
				);
				$modelCode->modify($newPage->code_id, $data);
				if (!empty($oPage->ext_js)) {
					foreach ($oPage->ext_js as $js) {
						$this->insert('xxt_code_external', array('code_id' => $newPage->code_id, 'type' => 'J', 'url' => $js->url), false);
					}
				}
				if (!empty($oPage->ext_css)) {
					foreach ($oPage->ext_css as $css) {
						$this->insert('xxt_code_external', array('code_id' => $newPage->code_id, 'type' => 'C', 'url' => $css->url), false);
					}
				}
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oNewApp->siteid, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 删除信息墙
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		if (($oApp = $modelWall->byId($app)) === false) {
			return new \ObjectNotFoundError();
		}

		if ($oApp->creater !== $oUser->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		$q = [
			'count(*)',
			'xxt_wall_enroll',
			['wid' => $oApp->id],
		];
		$oUserNum = (int) $modelWall->query_val_ss($q);
		if ($oUserNum > 0) {
			$modelWall->update('xxt_wall_enroll', ['close_at' => time()], ['wid' => $oApp->id]);
			$rst = $modelWall->remove($oUser, $oApp, 'Recycle');
		} else {
			/*删除信息墙*/
			$modelWall->delete('xxt_wall_log', ['wid' => $oApp->id]);
			$d = [
				'code_id',
				'xxt_wall_page',
				['wid' => $oApp->id],
			];
			if ($pages = $modelWall->query_objs_ss($d)) {
				$pages2 = [];
				foreach ($pages as $page) {
					$pages2[] = $page->code_id;
				}
				$pages2 = implode(',', $pages2);

				$modelWall->delete('xxt_code_page', "id in ($pages2)");
				$modelWall->delete('xxt_code_external', "code_id in ($pages2)");
				$modelWall->delete('xxt_wall_page', ['Wid' => $oApp->id]);
			}

			$rst = $modelWall->remove($oUser, $oApp, 'D');
		}

		return new \ResponseData($rst);
	}
	/*
	*互动场景添加素材
	*/
	public function addInteractMatter_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall')->setOnlyWriteDbConn(true);
		if (($oApp = $modelWall->byId($app, ['fields' => 'siteid,interact_matter'])) === false) {
			return new \ObjectNotFoundError();
		}
		$oInteractMatters = $oApp->interact_matter;
		if(empty($oInteractMatters)){
			$oInteractMatters = [];
		}

		$post = $this->getPostJson();
		if(empty($post->matters)){
			return new \ResponseError('没有选择素材');
		}

		foreach ($post->matters as $matter) {
			$matter2 = new \stdClass;
			$matter2->id = $matter->id;
			$matter2->type = $matter->type;
			$matter2->title = $matter->title;
			if (!in_array($matter2, $oInteractMatters)) {
				array_unshift($oInteractMatters, $matter2);
			}
		}
		$interactMatters = $modelWall->tojson($oInteractMatters);

		$modelWall->update(
			'xxt_wall',
			['interact_matter' => $interactMatters],
			['id' => $app]
		);

		$oApp = $modelWall->byId($app, ['fields' => 'siteid,interact_matter']);

		return new \ResponseData($oApp);
	}
	/*
	*
	*/
	public function removeInteractMatter_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall')->setOnlyWriteDbConn(true);
		if (($oApp = $modelWall->byId($app, ['fields' => 'siteid,interact_matter'])) === false) {
			return new \ObjectNotFoundError();
		}
		$oInteractMatters = $oApp->interact_matter;
		if(empty($oInteractMatters)){
			return new \ResponseError('未指定互动素材');
		}

		$post = $this->getPostJson();
		if(empty($post)){
			return new \ResponseError('没有选择素材');
		}

		$removeMatter = new \stdClass;
		$removeMatter->id = $post->id;
		$removeMatter->type = $post->type;
		$removeMatter->title = $post->title;

		$key = array_search($removeMatter, $oInteractMatters);
		array_splice($oInteractMatters,$key,1);

		$interactMatters = $modelWall->tojson($oInteractMatters);

		$modelWall->update(
			'xxt_wall',
			['interact_matter' => $interactMatters],
			['id' => $app]
		);
		$oApp = $modelWall->byId($app, ['fields' => 'siteid,interact_matter']);

		return new \ResponseData($oApp);
	}
}