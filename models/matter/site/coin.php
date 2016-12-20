<?php
namespace matter\site;
/**
 *
 */
class coin_model extends \TMS_MODEL {
	/**
	 * 返回对应的积分规则
	 *
	 * @param object $siteConfig
	 */
	public function &rulesByMatter($act, $siteConfig) {
		$q = ['*', 'xxt_coin_rule', "matter_type='site' and act='$act' and "];

		$w = "(";
		$w .= "matter_filter='*'";
		$w .= "or matter_filter='ID:{$siteConfig->id}'";
		isset($siteConfig->entry) && $w .= "or matter_filter='ENTRY:{$siteConfig->entry}'";
		$w .= ")";

		$q[2] .= $w;

		$rules = $this->query_objs_ss($q);

		return $rules;
	}
	/**
	 * 返回用于记录积分的文章创建人
	 *
	 * @param object $siteConfig
	 */
	public function getCreator($siteConfig) {
		$creator = false;
		if (isset($siteConfig->creater_src) && $siteConfig->creater_src === 'M') {
			if ($member = $this->model('site\user\member')->byId($siteConfig->creater)) {
				$creator = $this->model('site\user\account')->byId($member->userid);
			}
		}

		return $creator;
	}
}