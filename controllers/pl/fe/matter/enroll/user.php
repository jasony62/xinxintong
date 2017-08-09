<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动用户
 */
class user extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 提交过登记记录的用户
	 */
	public function enrollee_action($app, $rid = '', $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if(!empty($oApp->group_app_id)){
			foreach ($oApp->dataSchemas as $schema) {
				if($schema->id=='_round_id'){
					$ops=$schema->ops;
				}
			}
		}
		$modelUsr = $this->model('matter\enroll\user');
		$options = [];
		!empty($rid) && $options['rid'] = $rid;
		$result = $modelUsr->enrolleeByApp($oApp, $page, $size, $options);
		/*查询有openid的用户发送消息的情况*/
		if(count($result->users)){
			foreach ($result->users as &$user) {
				$q = [
					'd.tmplmsg_id,d.status,b.create_at',
					'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
					"d.userid = '{$user->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $user->aid . "'"
				];
				$q2 = [
					'r' => ['o' => 0, 'l' => 1],
					'o' => 'b.create_at desc'
				];
				if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
					$user->tmplmsg = $tmplmsg[0];
				}else{
					$user->tmplmsg = new \stdClass;
				}
				$user->task=$oApp->userTask;
				if(isset($ops) && $user->group_id){
					foreach ($ops as $v) {
						if($v->v==$user->group_id){
							$user->group=$v;
						}
					}
				}
			}
		}
		
		return new \ResponseData($result);
	}
	/**
	 * 根据通讯录返回用户完成情况
	 */
	public function byMschema_action($app, $mschema, $rid = '', $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('site\user\memberschema');
		$oMschema = $modelEnl->byId($mschema, ['cascaded' => 'N']);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$options = [];
		!empty($rid) && $options['rid'] = $rid;
		$result = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page, $size, $options);
		/*查询有openid的用户发送消息的情况*/
		if(count($result->members)){
			foreach ($result->members as $member) {
				$q = [
					'd.tmplmsg_id,d.status,b.create_at',
					'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
					"d.userid = '{$member->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $oApp->id . "'"
				];
				$q2 = [
					'r' => ['o' => 0, 'l' => 1],
					'o' => 'b.create_at desc'
				];
				if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
					$member->tmplmsg = $tmplmsg[0];
				}else{
					$member->tmplmsg = new \stdClass;
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 发表过评论的用户
	 */
	public function remarker_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->remarkerByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 数据导出
	 */
	public function export_action($site, $app, $mschema = '', $rid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		if (false === ($oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,entry_rule', 'cascaded' => 'N']))) {
			return new \ParameterError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		if(!empty($mschema)){
			$modelEnl = $this->model('site\user\memberschema');
			$mschema = $modelEnl->escape($mschema);
			$oMschema = $modelEnl->byId($mschema);
			if (false === $oMschema) {
				return new \ObjectNotFoundError();
			}
			$options = [];
			!empty($rid) && $options['rid'] = $rid;
			$result = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page='', $size='', $options);
			$data = $result->members;
		}else{
			$options = [];
			!empty($rid) && $options['rid'] = $rid;
			$result = $modelUsr->enrolleeByApp($oApp, $page='', $size='', $options);
			$data = $result->users;
		}
		
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');

		// 转换标题
		if(!empty($mschema)){
			if($oMschema->attr_name[0] === '0'){
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '姓名');
			}
			if($oMschema->attr_mobile[0] === '0'){
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '手机号');
			}
			if($oMschema->attr_email[0] === '0'){
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '电子邮箱');
			}
			if(!empty($oMschema->extattr)){
				foreach ($oMschema->extattr as $extattr) {
					$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, $extattr->label);
				}
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '评论');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '积分');

			// 转换数据
			for ($j = 0; $j < count($data); $j++) {
				$record = $data[$j];
				$rowIndex = $j + 2;
				$columnNum2 = 0; //列号

				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j+1);
				if($oMschema->attr_name[0] === '0'){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->name);
				}
				if($oMschema->attr_mobile[0] === '0'){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->mobile);
				}
				if($oMschema->attr_email[0] === '0'){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->email);
				}
				if(!empty($oMschema->extattr)){
					foreach ($oMschema->extattr as $extattr) {
						if(isset($record->extattr->{$extattr->id})){
							$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->extattr->{$extattr->id});
						}else{
							$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
						}
					}
				}

				if(isset($record->user->enroll_num)){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->enroll_num);
				}else{
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if(isset($record->user->remark_other_num)){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->remark_other_num);
				}else{
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
				if(isset($record->user->user_total_coin)){
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user->user_total_coin);
				}else{
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, '');
				}
			}
		}else{
			// 转换标题
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '昵称');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '评论');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '积分');
			// 转换数据
			for ($j = 0; $j < count($data); $j++) {
				$record = $data[$j];
				$rowIndex = $j + 2;
				$columnNum2 = 0; //列号

				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j+1);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->nickname);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->enroll_num);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->remark_other_num);
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $record->user_total_coin);
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}