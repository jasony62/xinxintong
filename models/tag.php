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

		$result['res'] = parent::query_objs($sql);
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
		$resTable = $this->resTable($res_type);
		if (is_array($res_id)) {
			$sql = 'select r.res_id,t.id,t.title';
			$sql .= " from xxt_tag t,$resTable r";
			$sql .= ' where t.id=r.tag_id';
			$sql .= " and r.res_id in (" . implode(',', $res_id) . ")";
			$sql .= " and r.sub_type=$subType";
			$sql .= ' order by r.res_id';
			if ($rels = parent::query_objs($sql)) {
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
			$result = parent::query_objs($sql);
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
						"mpid='$mpid' and title='$added->title'",
					);
					if (!($tag_id = $this->query_val_ss($q))) {
						/**
						 * 不存在，创建新标签
						 */
						$tag_id = $this->insert('xxt_tag',
							array(
								'mpid' => $mpid,
								'title' => $added->title,
							),
							true
						);
					}
					$added->id = $tag_id;
				}
				if ('1' === parent::query_value('1', 'xxt_article_tag'
					, "mpid='$mpid' and res_id=$res_id and tag_id=$added->id")
				) {
					// 关联已经存在
					continue;
				}
				/**
				 * 建立资源与标签之间的关联
				 */
				$tag2res = array(
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
						"mpid='$mpid' and title='$removed->title'",
					);
					if (!($removed->id = $this->query_val_ss($q))) {
						continue;
					}
				}
				/**
				 * 删除资源与标签之间的关联
				 */
				$this->delete('xxt_article_tag', "mpid='$mpid' and res_id=$res_id and tag_id=$removed->id");
			}
		}

		return true;
	}
}