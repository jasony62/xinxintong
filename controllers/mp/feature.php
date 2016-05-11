<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class feature extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function get_action($fields = '*') {
		$modelMpa = $this->model('mp\mpaccount');

		$features = $modelMpa->getFeature($this->mpid, $fields);

		return new \ResponseData($features);
	}
	/**
	 *
	 */
	public function update_action() {
		$nv = $this->getPostJson();

		if (isset($nv->admin_email_pwd)) {
			/**
			 * 邮箱口令要加密处理
			 */
			$pwd = $this->model()->encrypt($nv->admin_email_pwd, 'ENCODE', $this->mpid);
			$rst = $this->model()->update(
				'xxt_mpsetting',
				array('admin_email_pwd' => $pwd),
				"mpid='$this->mpid'"
			);
		} else {
			if (isset($nv->body_ele)) {
				$nv->body_ele = $this->model()->escape($nv->body_ele);
			} else if (isset($nv->body_css)) {
				$nv->body_css = $this->model()->escape($nv->body_css);
			} else if (isset($nv->follow_ele)) {
				$nv->follow_ele = $this->model()->escape($nv->follow_ele);
			} else if (isset($nv->follow_css)) {
				$nv->follow_css = $this->model()->escape($nv->follow_css);
			}

			$rst = $this->model()->update(
				'xxt_mpsetting',
				(array) $nv,
				"mpid='$this->mpid'"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function pageCreate_action($template = 'basic') {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);
		$uid = \TMS_CLIENT::get_client_uid();

		$data = $this->_makePage($mpa, $template);

		$code = \TMS_APP::model('code\page')->create($uid, $data);

		$rst = $this->model()->update(
			'xxt_mpsetting',
			array('follow_page_id' => $code->id),
			"mpid='$this->mpid'"
		);

		return new \ResponseData($code->id);
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($codeId, $template = 'basic') {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);

		$data = $this->_makePage($mpa, $template);

		$rst = \TMS_APP::model('code\page')->modify($codeId, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function &_makePage($mpa, $template) {
		$templateDir = dirname(__FILE__) . '/_template/follow';
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);
		/*填充页面*/
		$data['html'] = $this->_htmlByMpa($mpa, $data['html']);

		return $data;
	}
	/**
	 *
	 */
	private function &_htmlByMpa(&$mpa, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('mpa', $mpa);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
}