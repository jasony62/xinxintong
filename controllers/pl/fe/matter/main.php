<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class main extends \pl\fe\base {
	/**
	 * 团队下的所有素材
	 *
	 * @param string $resType
	 * @param string $category doc/app
	 *
	 */
	public function bySite_action($site, $category = '', $page = 1, $size = 12) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$oPosted = $this->getPostJson();

		$q = [
			'siteid,matter_id id,matter_type type,matter_title title,matter_category,scenario,creater_name,create_at',
			'xxt_site_matter m',
			['siteid' => $site],
		];
		if (!empty($category)) {
			$q[2]['matter_category'] = $category;
		}
		if (!empty($oPosted->byTitle)) {
			$q[2]['matter_title'] = (object) ['op' => 'like', 'pat' => '%' . $oPosted->byTitle . '%'];
		}
		if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
			$q[2]['byStar'] = (object) ['op' => 'exists', 'pat' => "select 1 from xxt_account_topmatter t where t.matter_type=m.matter_type and t.matter_id=m.matter_id and userid='{$oUser->id}'"];
		}
		if (!empty($oPosted->byCreator)) {
			$q[2]['creater_name'] = (object) ['op' => 'like', 'pat' => '%' . $oPosted->byCreator . '%'];
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			'o' => 'create_at desc',
		];

		$matters = $modelSite->query_objs_ss($q, $q2);
		foreach ($matters as $oMatter) {
			$qStar = [
				'id',
				'xxt_account_topmatter',
				['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'userid' => $oUser->id],
			];
			if ($oStar = $modelSite->query_obj_ss($qStar)) {
				$oMatter->star = $oStar->id;
			}
		}

		if (count($matters) < $size) {
			$total = ($page - 1) * $size + count($matters);
		} else {
			$q[0] = 'count(*)';
			$total = (int) $modelSite->query_val_ss($q);
		}

		$result = (object) ['matters' => $matters, 'total' => $total];

		return new \ResponseData($result);
	}
	/**
	 * 更新指定素材的进入规则
	 */
	public function updateEntryRule_action($matter) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = explode(',', $matter);
		if (count($matter) !== 2) {
			return new \ParameterError();
		}
		$modelMat = $this->model('matter\\' . $matter[1]);
		$oMatter = $modelMat->byId($matter[0]);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$oEntryRule = $this->getPostJson(false);

		$aScanResult = $modelMat->scanEntryRule($oEntryRule);
		if (false === $aScanResult[0]) {
			return new \ResponseError($aScanResult[1]);
		}

		$oScaned = $aScanResult[1];

		$modelMat->modify($oUser, $oMatter, (object) ['entry_rule' => $modelMat->escape($modelMat->toJson($oScaned))], ['id' => $oMatter->id]);

		return new \ResponseData($oScaned);
	}
}