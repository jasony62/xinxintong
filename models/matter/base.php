<?php
namespace matter;
/**
 *
 */
class base_model extends \TMS_MODEL {
	/**
	 * 根据类型和ID获得素材
	 */
	public static function getCardInfoById($type, $id) {
		switch ($type) {
		case 'enrollsignin':
			$q = array('id,title,summary,pic', 'xxt_enroll', "id='$id'");
			break;
		case 'joinwall':
			$q = array('id,title,summary,pic', 'xxt_wall', "id='$id'");
			break;
		default:
			$table = 'xxt_' . $type;
			$q = array('id,title,summary,pic', $table, "id='$id'");
		}
		if ($matter = self::query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 * 根据类型和ID获得素材基本信息，mpid,id和title
	 */
	public static function getMatterInfoById($type, $id) {
		switch ($type) {
		case 'text':
			$q = array('id,content title,content', 'xxt_text', "id='$id'");
			break;
		case 'relay':
			$q = array('id,title', 'xxt_mprelay', "id='$id'");
			break;
		case 'enrollsignin':
			$q = array('id,title', 'xxt_enroll', "id='$id'");
			break;
		case 'joinwall':
			$q = array('id,title', 'xxt_wall', "id='$id'");
			break;
		default:
			$table = 'xxt_' . $type;
			$q = array('id,title', $table, "id='$id'");
		}

		if ($matter = self::query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 *
	 */
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			$this->table(),
			"id='$id'",
		);
		$matter = $this->query_obj_ss($q);

		return $matter;
	}
}