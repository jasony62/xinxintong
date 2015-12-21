<?php
namespace mp\app;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';
/**
 *
 * 继承了matter_ctrl中设置ACL的相关功能
 */
class app_base extends \mp\mp_controller {
	/**
	 * 当前用户拥有的操作权限
	 */
	protected $prights;
	/**
	 * 有权限的入口
	 */
	protected $entries;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		$prights = $this->model('mp\permission')->hasMpRight(
			$this->mpid,
			array(
				'app_enroll',
				'app_lottery',
				'app_wall',
				'app_addressbook',
				'app_contribute',
				'app_merchant',
			),
			'read'
		);

		$entries = array();
		(true === $prights || (isset($prights['app_enroll']) && $prights['app_enroll']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/enroll', 'title' => '登记活动');
		(true === $prights || (isset($prights['app_lottery']) && $prights['app_lottery']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/lottery', 'title' => '抽奖活动');
		(true === $prights || (isset($prights['app_wall']) && $prights['app_wall']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/wall', 'title' => '信息墙');
		(true === $prights || (isset($prights['app_addressbook']) && $prights['app_addressbook']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/addressbook', 'title' => '通讯录');
		(true === $prights || (isset($prights['app_contribute']) && $prights['app_contribute']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/contribute', 'title' => '投稿');
		(true === $prights || (isset($prights['app_merchant']) && $prights['app_merchant']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/app/merchant', 'title' => '订购');

		$this->prights = $prights;
		$this->entries = $entries;

		\TPL::assign('app_view_entries', $entries);
	}
	/**
	 * 设置访问白名单
	 */
	public function setAcl_action($id) {
		if (empty($id)) {
			die('parameters invalid.');
		}

		$acl = $this->getPostJson();
		if (isset($acl->id)) {
			$u['identity'] = $acl->identity;
			empty($acl->idsrc) && $u['label'] = $acl->identity;
			$rst = $this->model()->update('xxt_matter_acl', $u, "id=$acl->id");
			return new \ResponseData($rst);
		} else {
			$i['mpid'] = $this->mpid;
			$i['matter_type'] = $this->getMatterType();
			$i['matter_id'] = $id;
			$i['identity'] = $acl->identity;
			$i['idsrc'] = $acl->idsrc;
			$i['label'] = isset($acl->label) ? $acl->label : '';
			$i['id'] = $this->model()->insert('xxt_matter_acl', $i, true);

			return new \ResponseData($i);
		}
	}
	/**
	 * 删除访问控制列表
	 * $acl aclid
	 */
	public function removeAcl_action($acl) {
		$rst = $this->model()->delete(
			'xxt_matter_acl',
			"mpid='$this->mpid' and id=$acl"
		);
		return new \ResponseData($rst);
	}
}