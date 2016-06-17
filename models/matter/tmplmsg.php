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
	public function &bySite($site, $options = []) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			"t.*",
			'xxt_tmplmsg t',
			"t.siteid='$site' and t.state=1",
		);
		$q2['o'] = 't.create_at desc';
		$tmplmsgs = $this->query_objs_ss($q, $q2);

		if ($cascaded === 'Y' && !empty($tmplmsgs)) {
			$q = array(
				"id,pname,plabel",
				'xxt_tmplmsg_param',
			);
			foreach ($tmplmsgs as &$tmpl) {
				$q[2] = "tmplmsg_id=$tmpl->id";
				$tmpl->params = $this->query_objs_ss($q);
			}
		}

		return $tmplmsgs;
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