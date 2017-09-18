<?php
namespace matter;
/**
 * 发布在平台主页的素材
 */
class home_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_home_matter',
			["id" => $id],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 */
	public function &findApp($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h,xxt_site s',
			"h.matter_type<>'article' and matter_type<>'channel' and h.siteid = s.id and s.state=1",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.weight desc,h.put_at desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 *
	 */
	public function &findArticle($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h,xxt_site s',
			"h.matter_type='article' and h.siteid = s.id and s.state=1",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.weight desc,h.put_at desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 *
	 */
	public function &findChannel($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h,xxt_site s',
			"h.matter_type='channel' and h.siteid = s.id and s.state=1",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.weight desc,h.put_at desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 *
	 */
	public function &byMatter($matterId, $matterType, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_home_matter',
			["matter_id" => $matterId, "matter_type" => $matterType],
		];

		$item = $this->query_obj_ss($q);

		return $item;
	}
	/**
	 *
	 * @param string $siteId 来源于哪个站点
	 * @param object $matter 共享的素材
	 */
	public function putMatter($siteId, &$account, &$matter, $options = array()) {
		$site = $this->model('site')->byId($siteId);
		if ($this->byMatter($matter->id, $matter->type)) {
			// 更新素材信息
			$current = time();

			$item = [
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'site_name' => $site->name,
			];
			
			$this->update(
				'xxt_home_matter',
				$item,
				["siteid" => $siteId, "matter_type" => $matter->type, "matter_id" => $matter->id]
			);
		} else {
			// 新申请素材信息
			$current = time();

			$item = [
				'creater' => $account->id,
				'creater_name' => $account->name,
				'put_at' => $current,
				'siteid' => $siteId,
				'matter_type' => $matter->type,
				'matter_id' => $matter->id,
				'scenario' => empty($matter->scenario) ? '' : $matter->scenario,
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
				'site_name' => $site->name,
			];
			
			$id = $this->insert('xxt_home_matter', $item, true);
			$item = $this->byId($id);
		}

		return $item;
	}
	/**
	 * 推送到主页
	 */
	public function pushHome($applicationId, $homeGroup = '') {
		$data = [
			'xxt_home_matter',
			['approved' => 'Y'],
			["id" => $applicationId]
		];
		!empty($homeGroup) && $data[1]['home_group'] = $this->escape($homeGroup);
		
		$rst = $this->update($data);

		return $rst;
	}
	/**
	 * 取消推送到主页
	 */
	public function pullHome($applicationId) {
		$rst = $this->update(
			'xxt_home_matter',
			['approved' => 'N'],
			["id" => $applicationId]
		);

		return $rst;
	}
	/**
	 * 已经批准在主页上的素材
	 */
	public function &atHome($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h, xxt_site s',
			"approved='Y' and matter_type<>'article' and matter_type<>'channel' and h.siteid = s.id and s.state=1 ",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.score desc,h.put_at desc,h.weight desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 * 已经批准在主页上的素材
	 */
	public function &atHomeArticle($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.' . $fields . ',s.heading_pic';
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h, xxt_site s',
			"h.approved='Y' and h.matter_type='article' and h.siteid = s.id and s.state=1 ",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.score desc,h.put_at desc,h.weight desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 * 已经批准在主页上的频道
	 */
	public function &atHomeChannel($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter h, xxt_site s',
			"h.approved='Y' and h.matter_type='channel' and h.siteid = s.id and s.state=1 ",
		];
		if (!empty($options['byHGroup'])) {
			$homeGroup = $this->escape($options['byHGroup']);
			$q[2] .= " and h.home_group = '{$homeGroup}'";
		}

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.score desc,h.put_at desc,h.weight desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 * 已经批准在主页上的素材
	 */
	public function &atHomeTop($options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		if(strpos($fields, 'h.') === false && strpos($fields, 's.') === false){
			$fields = str_replace(',',',h.',$fields);
			$fields = 'h.'.$fields;
		}
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 3];
		$type = isset($options['type']) ? $this->escape($options['type']) : 'article';

		$q = [
			$fields,
			'xxt_home_matter h, xxt_site s',
			"h.approved='Y' and h.weight>0 and h.siteid = s.id and s.state=1 ",
		];
		if(!empty($type) && $type !== 'ALL') {
			$q[2] .= " and h.matter_type = '" . $type . "'";
		}

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'h.weight desc,h.score desc,h.put_at desc',
		];

		$result = new \stdClass;
		$result->matters = $this->query_objs_ss($q, $q2);
		if (count($result->matters)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		} else {
			$result->total = 0;
		}

		return $result;
	}
	/**
	 * 素材置顶
	 */
	public function pushHomeTop($applicationId) {
		$p = [
			'weight',
			'xxt_home_matter',
			["id" => $applicationId]
		];
		$result = $this->query_obj_ss($p);
		$weightNow = (int)$result->weight;
		if($weightNow > 0){
			return array(false, '素材已置顶');
		}

		$q = [
			'max(weight)',
			'xxt_home_matter',
		];
		$weightMax = (int)$this->query_val_ss($q);

		$rst = $this->update(
			'xxt_home_matter',
			['weight' => $weightMax+1],
			["id" => $applicationId]
		);

		return $rst;
	}
	/**
	 * 撤销素材置顶
	 */
	public function pullHomeTop($applicationId) {
		$q = [
			'weight',
			"xxt_home_matter",
			["id" => $applicationId]
		];
		$weight = $this->query_obj_ss($q);
		$weightNum = (int)$weight->weight;
		$rst = $this->update(
			'xxt_home_matter',
			['weight' => 0],
			["id" => $applicationId]
		);
		if($rst){
			$this->update("update xxt_home_matter set weight = weight-1 where weight > ".$weightNum);
		}

		return $rst;
	}
}