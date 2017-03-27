<?php
namespace matter\tmplmsg;
/**
 * 模版消息参数影射关系
 * 通过模板消息发送事件时，需要讲事件的信息和模板消息的参数进行映射，这样才能拼装出模板消息
 */
class config_model extends \TMS_MODEL {
	/**
	 * 返回模板消息参数映射关系
	 *
	 * @param string $id 模版消息映射关系ID
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
					$config->tmplmsg = $this->model('matter\tmplmsg')->byId($config->msgid, ['cascaded' => 'Y']);
				}
			}
		}

		return $config;
	}
}