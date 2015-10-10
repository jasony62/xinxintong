<?php
namespace matter;
/**
 *
 */
class tmplmsg_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &mappingById($id) {
		$q = array(
			'msgid,mapping',
			'xxt_tmplmsg_mapping',
			"id=$id",
		);

		if ($mapping = $this->query_obj_ss($q)) {
			if (!empty($mapping->mapping)) {
				$mapping->mapping = json_decode($mapping->mapping);
			}
		}

		return $mapping;
	}
}