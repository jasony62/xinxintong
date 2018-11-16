<?php
namespace matter\article;
/**
 *
 */
class coin_model extends \TMS_MODEL {
	/**
	 * 返回文章对应的积分规则
	 *
	 * @param object $article
	 */
	public function &rulesByMatter($act, $article) {
		$q = ['*', 'xxt_coin_rule', "matter_type='article' and act='$act' and "];

		$w = "(";
		$w .= "matter_filter='*'";
		$w .= "or matter_filter='ID:{$article->id}'";
		$w .= "or matter_filter='ENTRY:{$article->entry}'";
		$w .= ")";

		$q[2] .= $w;

		$rules = $this->query_objs_ss($q);

		return $rules;
	}
	/**
	 * 返回用于记录积分的文章创建人
	 *
	 * @param object $article
	 */
	public function getCreator($article) {
		$creator = false;

		return $creator;
	}
}