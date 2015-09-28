<?php
namespace mi;

require_once dirname(dirname(__FILE__)) . '/member_base.php';

class matter_page_base extends \member_base {
	/**
	 *
	 */
	protected function __construct(&$matter, $openid) {
		$this->matter = $matter;
		$this->openid = $openid;
	}
	/**
	 * 返回素材对象
	 *
	 * 至少包含：
	 * mpid
	 *
	 */
	public function &getMatter() {
		return $this->matter;
	}
}