<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/main');
		exit;
	}
	/**
	 * 创建站点
	 */
	public function create_action($pid = '', $asparent = 'N') {
		$site['name'] = '新站点';
		$site['asparent'] = $asparent;
		$siteid = $this->model('site')->create($site);

		/* @TODO 兼容改造前的模型，改造后应该去掉 */
		$mpa = array();
		$mpa['mpid'] = $siteid;
		$mpa['name'] = '新站点';
		$mpa['asparent'] = $asparent;
		$mpa['parent_mpid'] = '';
		$this->model('mp\mpaccount')->create($mpa);

		return new \ResponseData(array('id' => $siteid));
	}
	/**
	 * 删除站点
	 * 只允许站点的创建者删除站点
	 * 不实际删除站点，只是打标记
	 */
	public function remove_action($id) {
		$acnt = \TMS_CLIENT::account();
		/**
		 * 做标记
		 */
		$rst = $this->model()->update(
			'xxt_site',
			array('state' => 0),
			"id='$id' and creater='$acnt->uid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function get_action($id) {
		$site = $this->model('site')->byId($id);

		return new \ResponseData($site);
	}
	/**
	 *
	 */
	public function list_action() {
		/**
		 * 当前用户是站点的创建人或者被授权人
		 */
		$uid = \TMS_CLIENT::get_client_uid();

		$q = array(
			'id,creater_name,create_at,name',
			'xxt_site',
			"creater='$uid' and state=1",
		);
		$q2 = array('o' => 'create_at desc');

		$sites = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($sites);
	}
	/**
	 *
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_site',
			$nv,
			"id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function pageCreate_action($id, $page, $template = 'basic') {
		$site = $this->model('site')->byId($id);
		$uid = \TMS_CLIENT::get_client_uid();

		$data = $this->_makePage($site, $page, $template);

		$code = $this->model('code/page')->create($uid, $data);

		$rst = $this->model()->update(
			'xxt_site',
			array($page . '_page_id' => $code->id),
			"id='$id'"
		);

		return new \ResponseData(array('id' => $code->id));
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($id, $page, $template = 'basic') {
		$site = $this->model('site')->byId($id);

		$data = $this->_makePage($site, $page, $template);

		$rst = $this->model('code/page')->modify($site->{$page . '_page_id'}, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function &_makePage($site, $page, $template) {
		$templateDir = TMS_APP_DIR . '/controllers/pl/_template/fe/site/' . $page;
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);
		/*填充页面*/
		$data['html'] = $this->_htmlByMpa($site, $data['html']);

		return $data;
	}
	/**
	 *
	 */
	private function &_htmlByMpa(&$site, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('site', $site);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
}