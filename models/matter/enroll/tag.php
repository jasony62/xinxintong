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
			['aid' => $oApp]
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
		foreach ($data as $tagLabel) {
			$oNewTag = new \stdClass;
			$oNewTag->siteid = $oApp->siteid;
			$oNewTag->aid = $oApp->id;
			$oNewTag->create_at = $current;
			$oNewTag->creater = $user->uid;
			$oNewTag->label = $this->escape($tagLabel);
			$oNewTag->scope = 'U';
			/*获取排序*/
			$q = [
				'max(seq)',
				'xxt_enroll_record_tag',
				"aid = '{$oApp->id}'"
			];
			$seq = (int)$this->query_val_ss($q);
			$oNewTag->seq = $seq + 1;
			$oNewTag->id = $this->insert('xxt_enroll_record_tag', $oNewTag, true);

			$newTags[] = $oNewTag;
		}

		return $newTags;
	}
	/**
	 * 使用标签
	 *
	 * @param object $oApp
	 */
	public function useTags($tags) {
		$newTags = [];
		foreach ($tags as $tag) {	
			$this->update("update xxt_enroll_record_tag set use_num = use_num +1 where id= $tag");
		}

		return 'ok';
	}
}