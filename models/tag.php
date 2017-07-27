<?php
class tag_model extends TMS_MODEL {
	/**
	 *
	 */
	private function resTable($resType) {
		switch ($resType) {
		case 'article':
			$resTable = 'xxt_article_tag';
			break;
		default:
			return false;
		}
		return $resTable;
	}
	/**
	 * 获得符合条件的标签
	 * 只有被使用的标签才会被列出来
	 *
	 * $res_type 标签关联的资源类型
	 * $offset
	 * $limit
	 */
	public function get_tags($mpid, $resType = 'article', $subType = 0) {
		$resTable = $this->resTable($resType);
		$sql = 'select t.id,t.title';
		$sql .= ',count(t.id) weight';
		$sql .= " from xxt_tag t,$resTable r";
		$sql .= " where t.mpid='$mpid' and t.mpid=r.mpid and t.id=r.tag_id and r.sub_type=$subType";
		$sql .= ' group by t.id';
		$sql .= ' order by weight desc';

		$tags = $this->query_objs($sql);

		return $tags;
	}
	/**
	 * 获得符合条件的资源对象
	 *
	 * $res_type
	 * $tag_ids the res has at least one of the tag.
	 * $offset
	 * $limit
	 *
	 */
	public function get_res($res_type, $tag_ids, $offset = 0, $limit = 25) {
		$begin = round(microtime(true) * 1000);

		$user_id = TMS_CLIENT::get_client_uid();
		is_array($tag_ids) && $tag_ids = explode(',', $tag_ids);

		$sql = 'select SQL_CALC_FOUND_ROWS res_id';
		$sql .= ',count(distinct tag_id) matched';
		$sql .= ',count(distinct creater) weight';
		$sql .= ' from mail_tag2res';
		$sql .= " where tag_id in ($tag_ids)";
		$sql .= " and res_type=$res_type";
		$sql .= ' group by res_id';
		$sql .= ' order by matched desc, weight desc, max(create_time) desc';
		$sql .= " limit $offset,$limit";

		$result['res'] = $this->query_objs($sql);
		$result['foundRows'] = parent::found_rows();

		$end = round(microtime(true) * 1000);
		AppLog('mail_tag::get_res - time:' . ($end - $begin) . 'ms.');
		AppLog('mail_tag::get_res - sql:' . $sql);

		return $result;
	}
	/**
	 * 获得资源的标签
	 *
	 * $res_id single id or an array of id
	 * $res_type
	 *
	 * return Array multi - res_id=>tags; single - tag
	 *
	 */
	public function &tagsByRes($res_id, $res_type, $subType = 0) {
		$res_id = $this->escape($res_id);
		$res_type = $this->escape($res_type);
		$resTable = $this->resTable($res_type);
		if (is_array($res_id)) {
			$sql = 'select r.res_id,t.id,t.title';
			$sql .= " from xxt_tag t,$resTable r";
			$sql .= ' where t.id=r.tag_id';
			$sql .= " and r.res_id in (" . implode(',', $res_id) . ")";
			$sql .= " and r.sub_type=$subType";
			$sql .= ' order by r.res_id';
			if ($rels = $this->query_objs($sql)) {
				foreach ($rels as $rel) {
					$result[$rel->res_id][] = array(
						'id' => $rel->id,
						'title' => $rel->title,
					);
				}
			}
		} else {
			$sql = 'select t.id,t.title';
			$sql .= " from xxt_tag t,$resTable r";
			$sql .= ' where t.id=r.tag_id';
			$sql .= " and r.res_id='$res_id'";
			$sql .= " and r.sub_type=$subType";
			$result = $this->query_objs($sql);
		}

		!isset($result) && $result = array();

		return $result;
	}
	/**
	 * 建立资源和标签之间的关联
	 *
	 * $res_id
	 * $res_type
	 * $aAdded 标签对象的集合
	 * $aRemoved 标签对象的集合
	 */
	public function save($mpid, $res_id, $res_type, $subType, $aAdded = null, $aRemoved = null) {
		$resTable = $this->resTable($res_type);
		/**
		 * 建立关联
		 */
		if (!empty($aAdded)) {
			foreach ($aAdded as $added) {
				if (!isset($added->id)) {
					/**
					 * 标签是否已经存在？
					 */
					$q = array(
						'id',
						'xxt_tag',
						"(mpid='$mpid' or siteid='$mpid') and title='$added->title'",
					);
					if (!($tag_id = $this->query_val_ss($q))) {
						/**
						 * 不存在，创建新标签
						 */
						$tag_id = $this->insert('xxt_tag',
							array(
								'siteid' => $mpid,
								'mpid' => $mpid,
								'title' => $added->title,
							),
							true
						);
					}
					$added->id = $tag_id;
				}
				if ('1' === $this->query_value('1', 'xxt_article_tag'
					, "(mpid='$mpid' or siteid='$mpid') and res_id=$res_id and tag_id=$added->id")
				) {
					// 关联已经存在
					continue;
				}
				/**
				 * 建立资源与标签之间的关联
				 */
				$tag2res = array(
					'siteid' => $mpid,
					'mpid' => $mpid,
					'res_id' => $res_id,
					'tag_id' => $added->id,
					'sub_type' => $subType,
				);
				$this->insert('xxt_article_tag', $tag2res);
			}
		}
		/**
		 * 删除关联
		 */
		if (!empty($aRemoved)) {
			foreach ($aRemoved as $removed) {
				if (!isset($removed->id)) {
					/**
					 * 没有指定标签的id
					 * 查找标签是否存在，若不存在跳过
					 */
					$q = array(
						'id',
						'xxt_tag',
						"(mpid='$mpid' or siteid='$mpid') and title='$removed->title'",
					);
					if (!($removed->id = $this->query_val_ss($q))) {
						continue;
					}
				}
				/**
				 * 删除资源与标签之间的关联
				 */
				$this->delete('xxt_article_tag', "(mpid='$mpid' or siteid='$mpid') and res_id=$res_id and tag_id=$removed->id");
			}
		}

		return true;
	}
	/**
	 * 创建标签
	 */
	public function create($site, $user, $tags){
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
					"(mpid='$site' or siteid='$site') and title='$tag'",
				);
				if ($tag_id = $this->query_val_ss($q)) {
					continue;
				}
				$seq = $this->getSeqMax($site);
				/**
				 * 不存在，创建新标签
				 */
				$inData = new \stdClass;
				$inData->siteid = $site;
				$inData->mpid = $site;
				$inData->creater = $user->id;
				$inData->creater_name = $user->name;
				$inData->create_at = $current;
				$inData->title = $tag;
				$inData->seq = $seq + 1;

				$inData->id = $this->insert('xxt_tag', $inData, true);
				$newTags[] = $inData;
			}
		}

		return $newTags;
	}
	/**
	 * 获取当前团队中最大排序
	 */
	private function getSeqMax($site){
		$q = [
			'max(seq)',
			'xxt_tag',
			"siteid = '$site' or mpid = '$site'"
		];

		$seq = (int)$this->query_val_ss($q);

		return $seq;
	}
	/**
	 * 素材添加标签
	 */
	public function save2($site, $user, &$matter, $subType, $tags){
		$current = time();
		$rst = false;
		$addTags = [];
		if (!empty($tags)) {
			/*记录标签使用数*/
			if($subType === 'C'){
				$tagOld = $matter->matter_cont_tag;
			}elseif($subType === 'M'){
				$tagOld = $matter->matter_mg_tag;
			}
			if(!empty($tagOld)){
				$tagOld = json_decode($tagOld);
			}else{
				$tagOld = [];
			}

			$tagNew = [];
			foreach ($tags as $tag) {
				if(false !== ($key = array_search($tag->id, $tagOld))){
					unset($tagOld[$key]);
				}else{
					$tagNew[] = $tag->id;
				}
				$addTags[] = $tag->id;
			}
			//删除的标签
			if(!empty($tagOld)){
				foreach ($tagOld as $tag) {
					$this->update("update xxt_tag set sum = sum - 1 where id = " . $tag);
				}
			}
			//增加的标签
			if(!empty($tagNew)){
				foreach ($tagNew as $tag) {
					$this->update("update xxt_tag set sum = sum + 1 where id = " . $tag);
				}
			}

			$addTags = json_encode($addTags);
			//记录活动标签
			switch ($matter->type) {
				case 'wall':
					$upData = [];
					break;
				default:
					$upData = [];
					$upData['modifier'] = $user->id;
					$upData['modifier_name'] = $user->name;
					$upData['modifier_src'] = $user->src;
					$upData['modify_at'] = $current;
					break;
			}
			if($subType === 'C'){
				$upData['matter_cont_tag'] = $addTags;
			}elseif($subType === 'M'){
				$upData['matter_mg_tag'] = $addTags;
			}

			$rst = $this->update('xxt_' . $matter->type, $upData, ['id' => $matter->id]);
		}

		return $addTags;
	}
	/**
	 * 获得团队内所有的标签
	 */
	public function bySite($site, $options = []){
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$q = [
			$fields,
			'xxt_tag',
			"siteid = '$site' or mpid = '$site'"
		];
		$q2 = ['o' => 'seq desc,create_at desc'];
		if(isset($options['at'])){
			if(!empty($options['at']['page'] && !empty($options['at']['size']))){
				$page = $options['at']['page'];
				$size = $options['at']['size'];
				$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
			}
		}

		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
}