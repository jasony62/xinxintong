<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录的评论
 */
class remark extends \pl\fe\matter\base {
	/**
	 * 返回一条登记记录的所有评论
	 *
	 * @param string $ek
	 * @param string $schema schema's id，如果不指定，返回的是对整条记录的评论
	 *
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$result = $this->model('matter\enroll\record')->listRemark($ek, $schema, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function summary_action($ek) {
		$q = [
			'schema_id,remark_num,last_remark_at',
			'xxt_enroll_record_data',
			['enroll_key' => $ek],
		];
		$values = $this->model()->query_objs_ss($q);

		return new \ResponseData($values);
	}
	/**
	 * 给指定的登记记录的添加评论
	 */
	public function add_action($ek, $schema = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}
		$current = time();

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->siteid, ['cascaded' => 'N']);
		/**
		 * 发表评论的用户
		 */
		$remark = new \stdClass;
		$remark->userid = $user->id;
		$remark->user_src = 'P';
		$remark->nickname = $user->name;
		$remark->enroll_key = $ek;
		$remark->schema_id = $schema;
		$remark->create_at = $current;
		$remark->content = $modelRec->escape($data->content);

		$remark->id = $modelRec->insert('xxt_enroll_record_remark', $remark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (isset($schema)) {
			$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='$schema'");
		}

		//$this->_notifyHasRemark();

		return new \ResponseData($remark);
	}
}