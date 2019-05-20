<?php
class tag_model extends TMS_MODEL {
	/**
	 * 创建标签
	 */
	public function create($site, $user, $tags, $subType = 'M') {
		$current = time();
		$newTags = [];
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				/**
				 * 标签是否已经存在？
				 */
				$q = array(
					'id',
					'xxt_tag',
					"siteid='$site' and title='$tag' and sub_type = '$subType'",
				);
				if ($tag_id = $this->query_val_ss($q)) {
					continue;
				}
				$seq = $this->getSeqMax($site, $subType);
				/**
				 * 不存在，创建新标签
				 */
				$inData = new \stdClass;
				$inData->siteid = $site;
				$inData->creater = $user->id;
				$inData->creater_name = $user->name;
				$inData->create_at = $current;
				$inData->title = $tag;
				$inData->seq = $seq + 1;
				$inData->sub_type = $subType;

				$inData->id = $this->insert('xxt_tag', $inData, true);
				$newTags[] = $inData;
			}
		}

		return $newTags;
	}
	/**
	 * 获取当前团队中最大排序
	 */
	private function getSeqMax($site, $subType) {
		$q = [
			'max(seq)',
			'xxt_tag',
			"siteid = '$site' and sub_type = '$subType'",
		];

		$seq = (int) $this->query_val_ss($q);

		return $seq;
	}
	/**
	 * 素材添加标签
	 */
	public function save2($site, $user, &$matter, $subType, $tags) {
		$current = time();
		$rst = false;
		$addTags = [];
		/*记录标签使用数*/
		if ($subType === 'C') {
			$tagOld = $matter->matter_cont_tag;
		} elseif ($subType === 'M') {
			$tagOld = $matter->matter_mg_tag;
		}
		if (!empty($tagOld)) {
			$tagOld = json_decode($tagOld);
		} else {
			$tagOld = [];
		}

		$tagNew = [];
		foreach ($tags as $tag) {
			if (false !== ($key = array_search($tag->id, $tagOld))) {
				unset($tagOld[$key]);
			} else {
				$tagNew[] = $tag->id;
			}
			$addTags[] = (string) $tag->id;
		}
		//删除的标签
		if (!empty($tagOld)) {
			foreach ($tagOld as $tag) {
				$this->update("update xxt_tag set sum = sum - 1 where id = " . $tag);
			}
		}
		//增加的标签
		if (!empty($tagNew)) {
			foreach ($tagNew as $tag) {
				$this->update("update xxt_tag set sum = sum + 1 where id = " . $tag);
			}
		}

		$addTags = json_encode($addTags);
		$upData = [];
		$upData['modifier'] = $user->id;
		$upData['modifier_name'] = $user->name;
		$upData['modify_at'] = $current;
		if ($subType === 'C') {
			$upData['matter_cont_tag'] = $addTags;
		} elseif ($subType === 'M') {
			$upData['matter_mg_tag'] = $addTags;
		}

		$rst = $this->update('xxt_' . $matter->type, $upData, ['id' => $matter->id]);

		return $addTags;
	}
	/**
	 * 获得团队内所有的标签
	 */
	public function bySite($site, $subType = 'M', $options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];

		$site = $this->escape($site);
		$subType = $this->escape($subType);
		$q = [
			$fields,
			'xxt_tag',
			"siteid = '$site' and sub_type = '$subType'",
		];
		$q2 = ['o' => 'seq desc,create_at desc'];
		if (isset($options['at'])) {
			if (!empty($options['at']['page'] && !empty($options['at']['size']))) {
				$page = $options['at']['page'];
				$size = $options['at']['size'];
				$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
			}
		}

		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
}