<?php
namespace matter\tmplmsg;
/**
 *
 */
class config_model extends \TMS_MODEL {
	/**
	 *
	 *
	 * @param string $id
	 * @param array $options
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,msgid,mapping';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';

		$q = [
			$fields,
			'xxt_tmplmsg_mapping',
			"id='$id'",
		];
		$config = $this->query_obj_ss($q);

		if ($config) {
			$config->mapping = empty($config->mapping) ? (new stdClass) : json_decode($config->mapping);
			if ($cascaded === 'Y') {
				if (!empty($config->msgid)) {
					$config->tmplmsg = \TMS_APP::M('matter\tmplmsg')->byId($config->msgid, ['cascaded' => 'Y']);
				}
			}
		}

		return $config;
	}
}