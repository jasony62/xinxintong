<?php
namespace mp\matter;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';

class matter_ctrl extends \mp\mp_controller {
	/**
	 * 当前用户拥有的操作权限
	 */
	protected $prights;
	/**
	 * 有权限的入口
	 */
	protected $entries;
	/**
	 * 检查权限
	 */
	public function __construct() {
		parent::__construct();

		$prights = $this->model('mp\permission')->hasMpRight(
			$this->mpid,
			array(
				'matter_article',
				'matter_text',
				'matter_news',
				'matter_channel',
				'matter_link',
				'matter_tmplmsg',
				'matter_media',
			),
			'read'
		);

		$entries = array();

		(true === $prights || (isset($prights['matter_article']) && $prights['matter_article']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/articles', 'title' => '单图文');
		(true === $prights || (isset($prights['matter_text']) && $prights['matter_text']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/texts', 'title' => '文本');
		(true === $prights || (isset($prights['matter_news']) && $prights['matter_news']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/newses', 'title' => '多图文');
		(true === $prights || (isset($prights['matter_channel']) && $prights['matter_channel']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/channels', 'title' => '频道');
		(true === $prights || (isset($prights['matter_link']) && $prights['matter_link']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/links', 'title' => '链接');
		(true === $prights || (isset($prights['matter_tmplmsg']) && $prights['matter_tmplmsg']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/tmplmsgs', 'title' => '模板消息');
		(true === $prights || (isset($prights['matter_media']) && $prights['matter_media']['read_p'] === 'Y')) && $entries[] = array('url' => '/mp/matter/media', 'title' => '图片');

		$this->prights = $prights;
		$this->entries = $entries;

		\TPL::assign('matter_view_entries', $entries);
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
	/**
	 * 素材的阅读日志
	 */
	public function readGet_action($id, $page = 1, $size = 30) {
		$model = $this->model('log');

		$type = $this->getMatterType();

		$reads = $model->getMatterRead($type, $id, $page, $size);

		return new \ResponseData($reads);
	}
}
