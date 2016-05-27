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
		\TPL::output('/pl/fe/site/console');
		exit;
	}
	/**
	 * 创建站点
	 */
	public function create_action($pid = '', $asparent = 'N') {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$site['name'] = '新站点';
		$site['creater'] = $user->id;
		$site['creater_name'] = $user->name;
		$site['create_at'] = time();
		$siteid = $this->model('site')->create($site);

		return new \ResponseData(array('id' => $siteid));
	}
	/**
	 * 删除站点
	 * 只允许站点的创建者删除站点
	 * 不实际删除站点，只是打标记
	 */
	public function remove_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		/**
		 * 做标记
		 */
		$rst = $this->model()->update(
			'xxt_site',
			array('state' => 0),
			"id='$site' and creater='{$user->id}'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function get_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		return new \ResponseData($site);
	}
	/**
	 *
	 */
	public function list_action() {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$q = array(
			'id,creater_name,create_at,name',
			'xxt_site s',
			"(creater='{$user->id}' or exists(select 1 from xxt_site_admin sa where sa.siteid=s.id and uid='{$user->id}')) and state=1",
		);
		$q2 = array('o' => 'create_at desc');

		$sites = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($sites);
	}
	/**
	 * 获得站点绑定的第三方公众号
	 */
	public function snsList_action($site) {
		$sns = array();
		if ($wx = $this->model('sns\wx')->bySite($site)) {
			if ($wx->joined === 'Y') {
				$sns['wx'] = $wx;
			}
		}
		if ($yx = $this->model('sns\yx')->bySite($site)) {
			if ($yx->joined === 'Y') {
				$sns['yx'] = $yx;
			}
		}
		if ($qy = $this->model('sns\qy')->bySite($site)) {
			if ($qy->joined === 'Y') {
				$sns['qy'] = $qy;
			}
		}

		if (empty($sns)) {
			return new \ResponseData(false);
		} else {
			return new \ResponseData($sns);
		}
	}
	/**
	 *
	 */
	public function update_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_site',
			$nv,
			"id='$site'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 创建站点首页页面
	 */
	public function pageCreate_action($site, $page, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $page, $template);

		$code = $this->model('code\page')->create($site->id, $user->id, $data);

		$rst = $this->model()->update(
			'xxt_site',
			array(
				$page . '_page_id' => $code->id,
				$page . '_page_name' => $code->name,
			),
			"id='{$site->id}'"
		);

		return new \ResponseData($code);
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($site, $page, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $page, $template);

		$rst = $this->model('code\page')->modify($site->{$page . '_page_id'}, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function &_makePage($site, $page, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/page/' . $page;
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);
		/*填充页面*/
		//$data['html'] = $this->_htmlBySite($site, $data['html']);

		return $data;
	}
	/**
	 *
	 */
	private function &_htmlBySite(&$site, $template) {
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