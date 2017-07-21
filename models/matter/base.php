<?php
namespace matter;
/**
 *
 */
class base_model extends \TMS_MODEL {
	/**
	 * 根据类型和ID获得素材
	 */
	public function getCardInfoById($type, $id) {
		switch ($type) {
		case 'joinwall':
			$q = ['id,title,summary,pic', 'xxt_wall', ["id" => $id]];
			break;
		default:
			$table = 'xxt_' . $type;
			$q = ['id,title,summary,pic', $table, ["id" => $id]];
		}
		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 * 根据类型和ID获得素材基本信息，mpid,id和title
	 */
	public function getMatterInfoById($type, $id) {
		switch ($type) {
		case 'text':
			$q = ['id,title', 'xxt_text', ["id" => $id]];
			break;
		case 'relay':
			$q = ['id,title', 'xxt_mprelay', ["id" => $id]];
			break;
		case 'joinwall':
			$q = ['id,title', 'xxt_wall', ["id" => $id]];
			break;
		case 'mschema':
			$q = ['id,title', 'xxt_site_member_schema', ["id" => $id]];
			break;
		default:
			$table = 'xxt_' . $type;
			$q = ['id,title', $table, ["id" => $id]];
		}

		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			$this->table(),
			["id" => $id],
		];
		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $this->getTypeName();
		}

		return $matter;
	}
}