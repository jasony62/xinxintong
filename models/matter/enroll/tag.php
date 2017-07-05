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
		$q2['o'] = 'seq asc';
		if (isset($options['at'])) {
			$page = $options['at']['page'];
			$size = $options['at']['size'];
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
	/**
	 * 
	 */
	public function byId($tagId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_record_tag',
			['id' => $tagId]
		];

		$tag = $this->query_obj_ss($q);

		return $tag;
	}
	/**
	 * 添加登记活动的填写项标签
	 *
	 * @param object $oApp
	 */
	public function add(&$oApp, $user, $data, $scope = 'U') {
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
			$oNewTag->creater_src = $user->creater_src;
			$oNewTag->label = $tagLabel;
			$oNewTag->scope = $scope;
			$oNewTag->seq = $seq + 1;
			$oNewTag->id = $this->insert('xxt_enroll_record_tag', $oNewTag, true);

			$newTags[] = $oNewTag;
		}

		return $newTags;
	}
	/**
	 * 修改活动的填写项标签
	 *
	 */
	public function updateTag(&$tag, $user, $data) {
		$current = time();
		$this->setOnlyWriteDbConn(true);

		$newTags = new \stdClass;
		!empty(trim($data->label)) && $newTags->label = $this->escape($data->label);

		if (isset($data->seq) && $data->seq === 'U') {
			$q = [
				'id,min(seq) seq',
				'xxt_enroll_record_tag',
				"aid = '$tag->aid' and seq > $tag->seq"
			];
			if ($min = $this->query_obj_ss($q)) {
				$this->update('xxt_enroll_record_tag', ['seq' => $min->seq], ['id' => $tag->id]);
				$this->update('xxt_enroll_record_tag', ['seq' => $tag->seq], ['id' => $min->id]);
			}
		}
		if (isset($data->seq) && $data->seq === 'D') {
			$q = [
				'id,max(seq) seq',
				'xxt_enroll_record_tag',
				"aid = '$tag->aid' and seq < $tag->seq"
			];
			if ($max = $this->query_obj_ss($q)) {
				$this->update('xxt_enroll_record_tag', ['seq' => $max->seq], ['id' => $tag->id]);
				$this->update('xxt_enroll_record_tag', ['seq' => $tag->seq], ['id' => $max->id]);
			}
		}

		if(!empty($newTags)){
			$rst = $this->update('xxt_enroll_record_tag', $newTags, ['id' => $tag->id]);
		}

		return 'ok';
	}
}