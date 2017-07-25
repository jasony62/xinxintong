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
	public function index_action($app) {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($app, $page = '', $size = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		if (false === ($oApp = $modelEnl->byId($app, ['fields' => 'id', 'cascaded' => 'N']))) {
			return new \ResponseError('指定的数据不存在');
		}

		$options = [];
		if (!empty($page) && !empty($size)) {
			$options['at']['page'] = $page;
			$options['at']['size'] = $size;
		}

		$modelTag = $this->model('matter\enroll\tag');
		$tags = $modelTag->byApp($oApp, $options);
		$options2 = ['fields' => 'count(*) as total'];
		$total = $modelTag->byApp($oApp, $options2);

		$data = new \stdClass;
		$data->tags = $tags;
		$data->total = $total[0]->total;
		return new \ResponseData($data);
	}
	/**
	 *
	 */
	public function create_action($app, $scope = 'U') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 登记活动定义 */
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}

		$posted = $this->getPostJson();
		$oUser = new \stdClass;
		$oUser->uid = $user->id;
		$oUser->creater_src = 'P';
		$newTags = $this->model('matter\enroll\tag')->add($oApp, $oUser, $posted, $scope);

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
		if (empty($posted)) {
			return new \ResponseData('ok');
		}

		$rst = $this->model('matter\enroll\tag')->updateTag($tag, $user, $posted);

		return new \ResponseData($rst);
	}
	/**
	 * 删除标签
	 */
	public function remove_action($tag) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTag = $this->model('matter\enroll\tag');
		if (($tag = $modelTag->byId($tag)) === false) {
			return new \ResponseError('指定的标签不存在，请检查参数是否正确');
		}

		if ((int) $tag->use_num > 0) {
			return new \ResponseError('标签已被使用');
		}

		$rst = $modelTag->delete('xxt_enroll_record_tag', ['id' => $tag->id]);

		return new \ResponseData($rst);
	}
}