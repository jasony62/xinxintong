<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'wall';
	}
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
		$oWall = $modelWall->byId($id, '*');
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
	public function create_action($site = null, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$customConfig = $this->getPostJson();
		$newone = new \stdClass;
		$current = time();
		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
			if (false === $site) {
				return new \ObjectNotFoundError();
			}
			$newone->siteid = $site->id;
			$newone->pic = $site->heading_pic; //使用站点的缺省头图
			$newone->summary = '';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$newone->siteid = $mission->siteid;
			$newone->summary = $mission->summary;
			$newone->pic = $mission->pic;
			$newone->mission_id = $mission->id;
		}

		$model = $this->model();
		$wid = uniqid();
		$newone->id = $wid;
		/* 前端指定的信息 */
		$newone->title = empty($customConfig->proto->title) ? '新信息墙' : $model->escape($customConfig->proto->title);
		$newone->creater = $user->id;
		$newone->creater_name = $model->escape($user->name);
		$newone->create_at = $current;
		$newone->quit_cmd = 'q';
		$newone->join_reply = '欢迎加入';
		$newone->quit_reply = '已经退出';

		$model->insert('xxt_wall', $newone, false);

		/* 记录操作日志 */
		$newone->type = 'wall';
		$this->model('matter\log')->matterOp($newone->siteid, $user, $newone, 'C');

		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $newone->siteid, $mission->id, $newone);
		}

		return new \ResponseData($wid);
	}
	/**
	 * submit basic.
	 */
	public function update_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\wall');
		$modelApp->setOnlyWriteDbConn(true);

		$nv = $this->getPostJson();
		if (isset($nv->title)) {
			$nv->title = $modelApp->escape($nv->title);
		} else if (isset($nv->join_reply)) {
			$nv->join_reply = $modelApp->escape($nv->join_reply);
		} else if (isset($nv->quit_reply)) {
			$nv->quit_reply = $modelApp->escape($nv->quit_reply);
		} else if (isset($nv->entry_ele)) {
			$nv->entry_ele = $modelApp->escape($nv->entry_ele);
		} else if (isset($nv->entry_css)) {
			$nv->entry_css = $modelApp->escape($nv->entry_css);
		} else if (isset($nv->body_css)) {
			$nv->body_css = $modelApp->escape($nv->body_css);
		} else if (isset($nv->active) && $nv->active === 'N') {
			//如果停用信息墙，退出所有用户
			$modelApp->update('xxt_wall_enroll', ['close_at' => time()], ['wid' => $app]);
		}

		$rst = $modelApp->update('xxt_wall', $nv, ['id' => $app]);
		/*记录操作日志*/
		if ($rst) {
			$matter = $modelApp->byId($app, 'id,title,summary,pic');
			$this->model('matter\log')->matterOp($site, $user, $matter, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 导出Excel
	 */
	public function export_action($site, $wid) {
		if (false === ($user = $this->accountUser())) {
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		if (($oApp = $modelWall->byId($app)) === false) {
			return new \ResponseError('指定的信息墙不存在');
		}
		/*pages*/
		$modelPage = $this->model('matter\wall\page');
		$modelCode = $this->model('code\page');
		$oPages = [];
		$oPage = $modelPage->byType('op', $oApp->id);
		if (is_array($oPage)) {
			$oPages = array_merge($oPages, $oPage);
		} else {
			$oPages[] = $oPage;
		}

		/*copy*/
		$newWall = new \stdClass;
		$wid = uniqid();
		$newWall->id = $wid;
		$newWall->siteid = $modelWall->escape($site);
		$newWall->creater = $user->id;
		$newWall->creater_name = $user->name;
		$newWall->create_at = time();
		$newWall->title = $modelWall->escape($oApp->title) . '(副本)';
		$newWall->pic = $oApp->pic;
		$newWall->summary = $modelWall->escape($oApp->summary);
		$newWall->join_reply = $modelWall->escape($oApp->join_reply);
		$newWall->quit_reply = $modelWall->escape($oApp->quit_reply);
		$newWall->quit_cmd = $modelWall->escape($oApp->quit_cmd);
		$newWall->entry_css = $modelWall->escape($oApp->entry_css);
		$newWall->body_css = $modelWall->escape($oApp->body_css);
		$newWall->skip_approve = $oApp->skip_approve;
		$newWall->push_others = $oApp->push_others;
		$newWall->entry_ele = $modelWall->escape($oApp->entry_ele);
		/* 记录和任务的关系 */
		if (!empty($mission)) {
			$modelMis = $this->model('matter\mission');
			if ($mission = $modelMis->byId($mission)) {
				$newWall->mission_id = $mission->id;
			} else {
				return new \ResponseError('指定的项目不存在');
			}
		}

		$modelWall->insert('xxt_wall', $newWall, false);

		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$newWall->type = 'wall';
			$modelMis->addMatter($user, $newWall->siteid, $mission->id, $newWall);
		}

		/*复制页面*/
		if (empty($oPages)) {
			$wp = [
				'name' => '信息墙大屏幕',
				'title' => '信息墙大屏幕',
				'type' => 'op',
				'seq' => 1,
				'templateDir' => TMS_APP_TEMPLATE . '/site/op/matter/wall/',
			];
			$newPage = $modelPage->add($site, $wp, $newWall->id);
			$templateDir = $wp['templateDir'];
			$data = array(
				'html' => file_get_contents($templateDir . 'basic.html'),
				'css' => file_get_contents($templateDir . 'basic.css'),
				'js' => file_get_contents($templateDir . 'basic.js'),
			);
			$modelCode->modify($newPage->code_id, $data);
		} else {
			foreach ($oPages as $oPage) {
				$wp = [
					'name' => $modelPage->escape($oPage->name),
					'title' => $modelPage->escape($oPage->title),
					'type' => $oPage->type,
					'seq' => $oPage->seq,
				];
				$newPage = $modelPage->add($site, $wp, $newWall->id);
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
		$newWall->type = 'wall';
		$this->model('matter\log')->matterOp($newWall->siteid, $user, $newWall, 'C');

		return new \ResponseData($newWall);
	}
	/**
	 * 删除信息墙
	 */
	public function remove_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		if (($oApp = $modelWall->byId($app)) === false) {
			return new \ResponseError('指定的信息墙不存在');
		}

		if ($oApp->creater !== $user->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		/* 删除和任务的关联 */
		if ($oApp->mission_id) {
			$this->model('matter\mission')->removeMatter($oApp->id, 'wall');
		}

		$q = [
			'count(*)',
			'xxt_wall_enroll',
			['wid' => $oApp->id],
		];
		$userNum = (int) $modelWall->query_val_ss($q);
		/*打标记*/
		if ($userNum > 0) {
			$rst = $modelWall->update('xxt_wall_enroll', ['close_at' => time()], ['wid' => $oApp->id]);
			$rst = $modelWall->update('xxt_wall', ['state' => 0], ['id' => $oApp->id]);
			/* 记录操作日志 */
			$this->model('matter\log')->matterOp($site, $user, $oApp, 'Recycle');
		} else {
			/*删除信息墙*/
			$rst = $modelWall->delete('xxt_wall_log', "wid='$oApp->id'");
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

				$rst = $modelWall->delete('xxt_code_page', "id in ($pages2)");
				$rst = $modelWall->delete('xxt_code_external', "code_id in ($pages2)");
				$rst = $modelWall->delete('xxt_wall_page', "Wid='$oApp->id'");
			}

			$rst = $modelWall->delete('xxt_wall', "id='$oApp->id'");

			/* 记录操作日志 */
			$this->model('matter\log')->matterOp($site, $user, $oApp, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的信息墙
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelWall = $this->model('matter\wall');
		if (false === ($app = $modelWall->byId($id, 'id,title,summary,pic,mission_id'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}
		if ($app->mission_id) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $app->mission_id, $app);
		}

		/* 恢复数据 */
		$rst = $modelWall->update(
			'xxt_wall',
			['state' => 1],
			["id" => $app->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $app, 'Restore');

		return new \ResponseData($rst);
	}
}