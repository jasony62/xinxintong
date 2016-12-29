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
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter',
			"matter_type<>'article'",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'put_at desc',
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
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter',
			"matter_type='article'",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'put_at desc',
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
		if ($this->byMatter($matter->id, $matter->type)) {
			// 更新素材信息
			$current = time();

			$item = [
				'title' => $matter->title,
				'pic' => $matter->pic,
				'summary' => $matter->summary,
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
			];

			$id = $this->insert('xxt_home_matter', $item, true);
			$item = $this->byId($id);
		}

		return $item;
	}
	/**
	 * 推送到主页
	 */
	public function pushHome($applicationId) {
		$rst = $this->update(
			'xxt_home_matter',
			['approved' => 'Y'],
			["id" => $applicationId]
		);

		return $rst;
	}
	/**
	 * 推送到主页
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
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter',
			"approved='Y' and matter_type<>'article'",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'score desc,weight desc,put_at desc',
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
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

		$q = [
			$fields,
			'xxt_home_matter',
			"approved='Y' and matter_type='article'",
		];

		$q2 = [
			'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
			'o' => 'score desc,weight desc,put_at desc',
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
}