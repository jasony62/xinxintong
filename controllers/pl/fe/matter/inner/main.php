<?php
namespace pl\fe\matter\inner;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model('matter\inner');

		$matters = $model->bySite($site);

		return new \ResponseData($matters);
	}
}