<?php
namespace matter\enroll;
/**
 *
 */
class tag_model extends \TMS_MODEL {
	/**
	 * 获得登记活动的填写项标签
	 *
	 * @param object $oApp
	 */
	public function byApp($oApp, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_record_tag',
			['aid' => $oApp->id]
		];
		$q2['o'] = 'create_at desc';
		if (isset($options['at'])) {
			$page = $options['at']['page'];
			$size = $options['at']['size'];
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
	/**
	 * 添加登记活动的填写项标签
	 *
	 * @param object $oApp
	 */
	public function add(&$oApp, $user, $data) {
		$current = time();
		$newTags = [];
		$this->setOnlyWriteDbConn(true);
		foreach ($data as $tagLabel) {
			$tagLabel = $this->escape(trim($tagLabel));
			$q = [
				'label',
				'xxt_enroll_record_tag',
				"aid = '{$oApp->id}' and label = '$tagLabel'"
			];
			if ($labels = $this->query_obj_ss($q)) {
				continue;
			}
			/*获取排序*/
			$q[0] = 'max(seq)';
			$q[2] = "aid = '{$oApp->id}'";
			$seq = (int)$this->query_val_ss($q);
			
			$oNewTag = new \stdClass;
			$oNewTag->siteid = $oApp->siteid;
			$oNewTag->aid = $oApp->id;
			$oNewTag->create_at = $current;
			$oNewTag->creater = $user->uid;
			$oNewTag->label = $tagLabel;
			$oNewTag->scope = 'U';
			$oNewTag->seq = $seq + 1;
			$oNewTag->id = $this->insert('xxt_enroll_record_tag', $oNewTag, true);

			$newTags[] = $oNewTag;
		}

		return $newTags;
	}
}