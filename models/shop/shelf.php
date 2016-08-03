<?php
namespace shop;

class shelf_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["id" => $id],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 */
	public function &byMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter',
			["matter_id" => $matterId, "matter_type" => $matterType],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 * @param string $siteId 来源于哪个公众号
	 * @param object $matter 共享的素材
	 */
	public function putMatter($siteId, $account, $matter, $options = array()) {
		if ($item = $this->byMatter($matter->id, $matter->type)) {
			/*更新模板*/
			$scope = isset($options['scope']) ? $options['scope'] : 'U';
			$current = time();

			$item = array(
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'visible_scope' => $scope,
			);
			$this->update(
				'xxt_shop_matter',
				$item,
				["siteid" => $siteId, "matter_type" => $matter->type, "matter_id" => $matter->id]
			);
		} else {
			/*新建模板*/
			$scope = isset($options['scope']) ? $options['scope'] : 'U';
			$current = time();

			$item = array(
				'creater' => $account->id,
				'creater_name' => $account->name,
				'put_at' => $current,
				'siteid' => $siteId,
				'matter_type' => $matter->type,
				'matter_id' => $matter->id,
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'visible_scope' => $scope,
			);
			$id = $this->insert('xxt_shop_matter', $item, true);
			$item = $this->byId($id);
		}

		return $item;
	}
}