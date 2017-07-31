<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材标签
 */
class tag extends \pl\fe\base {
	/**
	 * 根据资源类型获得已有的标签
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function list_action($site, $resType, $subType = 0) {
		$tags = $this->model('tag')->get_tags($site, $resType, $subType);

		return new \ResponseData($tags);
	}
	/**
	 * 获得已有的标签
	 */
	public function listTags_action($site, $page = null, $size = null) {
		$options = [];
		if(!empty($page) && !empty($size)){
			$options['at'] = [];
			$options['at']['page'] = $page;
			$options['at']['size'] = $size;
		}
		$tags = $this->model('tag')->bySite($site, $options);

		return new \ResponseData($tags);
	}
	/**
	 *  创建标签
	 *  @param string $resType 素材类型
	 *  @param string $resId 素材id
	 *  @param string $subType 标签类型
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$tags = $this->getPostJson();

		$model = $this->model('tag');
		$model->setOnlyWriteDbConn(true);
		$newTags = $model->create($site, $user, $tags);

		return new \ResponseData($newTags);
	}
	/**
	 *  添加标签
	 *  @param string $resType 素材类型
	 *  @param string $resId 素材id
	 *  @param string $subType 标签类型 M(管理),C(内容)
	 */
	public function add_action($site, $resId, $resType, $subType = 'M') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('tag');
		$model->setOnlyWriteDbConn(true);
		switch($resType){
			case 'article':
				$fields = "id,siteid,title,summary,pic,matter_cont_tag,matter_mg_tag,'$resType' type";
				break;
			default:
				$fields = "id,siteid,title,summary,pic,matter_mg_tag,'$resType' type";
				break;
		}
		$q = [
			$fields,
			'xxt_' . $resType,
			['id' => $resId, 'state' => 1]
		];
		if (false === ($matter = $model->query_obj_ss($q))) {
			return new \ResponseError('指定的活动不存在或已删除！');
		}

		$tags = $this->getPostJson();

		$addTags = $model->save2($site, $user, $matter, $subType, $tags);

		/* 记录操作日志 */
		if(!empty($addTags)){
			$data = $addTags;
		}else{
			$data = new \stdClass;
		}
		$this->model('matter\log')->matterOp($matter->siteid, $user, $matter, 'bindMatterTags:' . $subType, $data);

		!empty($addTags) && $addTags = json_decode($addTags);
		return new \ResponseData($addTags);
	}
	/**
	 * 删除某一个标签
	 */
	public function remove_action($tagId){
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = [
			'sum',
			'xxt_tag',
			['id' => $tagId]
		];
		if (($tag = $model->query_obj_ss($q)) === false) {
			return new \ResponseError('指定的标签不存在！请检查参数');
		}

		if($tag->sum > 0 ){
			return new \ResponseError('标签正在被使用无法删除');
		}

		/*删除标签*/
		$rst = $model->delete('xxt_tag', ['id' => $tagId]);

		return new \ResponseData($rst);
	}
}