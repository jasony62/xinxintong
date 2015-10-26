<?php
namespace matter;
/**
 *
 */
class tmplmsg_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';
		/**/
		$q = array(
			$fields,
			'xxt_tmplmsg',
			"id=$id",
		);
		$tmpl = $this->query_obj_ss($q);
		/*参数*/
		if ($tmpl && $cascaded === 'Y') {
			$q = array(
				"id,pname,plabel",
				'xxt_tmplmsg_param',
				"tmplmsg_id=$id",
			);
			$tmpl->params = $this->query_objs_ss($q);
		}

		return $tmpl;
	}
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