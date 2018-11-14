<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 通信录管理页面
 */
class mschema extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action($mschema = null) {
		if (empty($mschema)) {
			\TPL::output('/pl/fe/site/mschema');
			exit;
		} else {
			$modelMs = $this->model('site\user\memberschema');
			$oMschema = $modelMs->byId($mschema, ['fields' => 'id,siteid,title,matter_id,matter_type', 'cascaded' => 'N']);
			if (false === $oMschema) {
				$this->outputError('指定通信录不存在');
			}
			if (!empty($oMschema->matter_id) && !empty($oMschema->matter_type)) {
				if ($oMschema->matter_type === 'mission') {
					$this->redirect('/rest/pl/fe/matter/mission/mschema?site=' . $oMschema->siteid . '&id=' . $oMschema->matter_id . '#' . $oMschema->id);
				} else {
					$this->redirect('/rest/pl/fe/site/mschema?site=' . $oMschema->siteid . '#' . $oMschema->id);
				}
			} else {
				$this->redirect('/rest/pl/fe?view=main&scope=user&sid=' . $oMschema->siteid . '#' . $oMschema->id);
			}
		}
	}
}