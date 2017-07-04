<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动积分管理控制器
 */
class tag extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function create_action($app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 登记活动定义 */
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}

		$posted = $this->getPostJson();

		$newTags = $this->model('matter\enroll\tag')->add($oApp, $user, $posted, 'I');

		return new \ResponseData($newTags);
	}
	/**
	 *修改标签
	 */
	public function update_action($tag) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTag = $this->model('matter\enroll\tag');
		if (($tag = $modelTag->byId($tag)) === false) {
			return new \ResponseError('指定的标签不存在，请检查参数是否正确');
		}

		$posted = $this->getPostJson();
		if(empty($posted)) {
			return new \ResponseData('ok');
		}

		$rst = $this->model('matter\enroll\tag')->updateTag($tag, $user, $posted);

		return new \ResponseData($rst);
	}
	/**
	 * 删除标签
	 */
	public function remove_action($tag){
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTag = $this->model('matter\enroll\tag');
		if (($tag = $modelTag->byId($tag)) === false) {
			return new \ResponseError('指定的标签不存在，请检查参数是否正确');
		}

		if((int)$tag->use_num > 0){
			return new \ResponseError('标签已被使用');
		}

		$rst = $modelTag->delete('xxt_enroll_record_tag', ['id' => $tag->id]);

		return new \ResponseData($rst);
	}
}