<?php
namespace member\box;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 *
 */
class article extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 * $mpid
	 * $id
	 * $code
	 * $mocker
	 */
	public function index_action($mpid, $id, $code = null, $mocker = null) {
		$openid = $this->doAuth($mpid, $code, $mocker);

		$params = array();
		\TPL::assign('params', $params);

		$this->view_action('/member/box/article/article');
	}
	/**
	 *
	 * $mpid
	 * $id
	 * $code
	 * $mocker
	 */
	public function get_action($mpid, $id, $code = null, $mocker = null) {
		$openid = $this->doAuth($mpid, $code, $mocker);

		$article = $this->model('matter\article')->byId($id);

		return new \ResponseData($article);
	}
	/**
	 * 创建一个空的文章
	 */
	public function create_action($mpid) {
		$fan = $this->getCookieOAuthUser($mpid);
		/**
		 * 获得的基本信息
		 */
		$articleId = uniqid();
		$newone['mpid'] = $mpid;
		$newone['id'] = $articleId;
		$newone['title'] = '新话题';
		$newone['creater'] = $fan->fid;
		$newone['creater_src'] = 'F';
		$newone['creater_name'] = $fan->nickname;
		$newone['create_at'] = time();
		$newone['can_share'] = 'Y';
		$newone['remark_notice'] = 'Y';

		$this->model()->insert('xxt_article', $newone, false);

		$article = $this->model('matter\article')->byId($articleId);

		return new \ResponseData($article);
	}
	/**
	 * 更新文章的属性信息
	 *
	 * $id
	 */
	public function update_action($mpid, $id) {
		$nv = $this->getPostJson();

		if (isset($nv->pic)) {
			/**
			 * 上传图片，转换为URL
			 */
			if (!empty($nv->pic)) {
				$fsuser = \TMS_APP::model('fs/user', $mpid);
				$rst = $fsuser->storeImg((object) array('imgSrc' => $nv->pic));
				if (false === $rst[0]) {
					return $rst;
				}

				$nv->pic = $rst[1];
			}
		} else if (isset($nv->body)) {
			/**
			 * 将正文的前120个字作为摘要
			 */
			$summary = mb_substr($nv->body, 0, 120, 'utf-8');
			$nv->summary = $summary;
		}
		$rst = $this->model()->update('xxt_article', (array) $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}
