<?php
namespace template;
/**
 * 应用模板商店
 */
class acl_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter_acl',
			["matter_id" => $matterId, "matter_type" => $matterType],
		];

		$acls = $this->query_objs_ss($q);

		return $acls;
	}
	/**
	 *
	 */
	public function &byReceiver($receiverId, $matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_shop_matter_acl',
			["receiver" => $receiverId, "matter_id" => $matterId, "matter_type" => $matterType],
		];

		$acl = $this->query_obj_ss($q);

		return $acl;
	}
	/**
	 *
	 */
	public function add($creater, $shopMatter, $acl) {
		$data = [
			'shop_matter_id' => $shopMatter->id,
			'matter_type' => $shopMatter->matter_type,
			'matter_id' => $shopMatter->matter_id,
			'receiver' => $acl->receiver,
			'receiver_label' => $acl->receiver_label,
			'creater' => $creater->id,
			'create_at' => time(),
		];

		$data['id'] = $this->insert('xxt_shop_matter_acl', $data, true);

		return (object) $data;
	}
}