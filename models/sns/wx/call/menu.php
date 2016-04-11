<?php
namespace sns\wx\call;
/**
 * 微信公众号菜单
 */
class menu_model extends \TMS_MODEL {
	/**
	 * 创建菜单
	 */
	public function &createButton($data) {
		$uid = \TMS_CLIENT::get_client_uid();
		$data['creater'] = $uid;
		$data['create_at'] = time();

		if ($rst = $this->insert('xxt_call_menu_wx', $data, false)) {
			$rst = $this->getButtonById($data['siteid'], $data['menu_key']);
		}

		return $rst;
	}
	/**
	 * 获得菜单按钮定义
	 *
	 * 每个菜单按钮有两个状态，编辑状态和发布状态，缺省返回编辑状态
	 */
	public function &getButtonById($siteId, $key, $published = 'N', $fields = '*', $cascade = array()) {
		/**
		 * 获得最新版本号
		 */
		if ($version = $this->getVersion($siteId, $published)) {
			$q = array(
				$fields,
				'xxt_call_menu_wx',
				"siteid='$siteId' and menu_key='$key' and version=$version->v",
			);
			if ($button = $this->query_obj_ss($q)) {
				/**
				 * 回复素材
				 */
				if (in_array('matter', $cascade) && $button->matter_id) {
					$button->matter = \TMS_APP::model('matter\base')->getMatterInfoById($button->matter_type, $button->matter_id);
				}
			}
		} else {
			$button = array();
		}
		return $button;

	}
	/**
	 * 获得菜单的所有按钮定义
	 *
	 * 如果菜单继承于父账号，那么需要合并父菜单的定义，合并规则为：
	 *
	 *
	 * 返回最新版本的菜单定义
	 */
	public function &getMenu($siteId, $published = 'N', $fields = '*') {
		$myMenu = $this->getMyMenu($siteId, $published, $fields);
		$myButtons = $myMenu[0];

		return $myButtons;
	}
	/**
	 * 获得公众号内的菜单定义
	 *
	 * return array
	 */
	private function getMyMenu($siteId, $published, $fields) {
		/**
		 * 获得最新版本号
		 */
		if ($version = $this->getVersion($siteId, $published)) {
			/**
			 * 获得指定版本的数据
			 */
			$q = array(
				$fields,
				'xxt_call_menu_wx',
				"siteid='$siteId' and version=$version->v",
			);
			$q2['o'] = 'l1_pos,l2_pos';
			$buttons = $this->query_objs_ss($q, $q2);
		} else {
			$buttons = array();
		}
		return array($buttons, $version);
	}
	/**
	 * 检查菜单定义中是否存在指定的项
	 */
	private function _exists($buttons, $menu_key) {
		foreach ($buttons as $btn) {
			if ($btn->menu_key === $menu_key) {
				return $btn;
			}
		}
		return false;
	}
	/**
	 * 调整菜单项的位置
	 *
	 * 被调整的菜单项只进行位置的互换
	 * 互换后，被调整的菜单项中存在于基准菜单的内容和基准菜单项的排序一致
	 */
	private function _interchange($changed, $base) {
		/**
		 * 按照一级菜单进行分组
		 */
		$groups = array();
		$groupInBaseOrder = array();
		$groupInChangedIndex = array();
		foreach ($changed as $btn) {
			if ($btn->l2_pos == 0) {
				$groups[] = array();
				$groups[count($groups) - 1][] = $btn;
				/**
				 * 是否在基准定义中
				 */
				if ($newbtn = $this->_exists($base, $btn->menu_key)) {
					$groupInBaseOrder[] = array((int) $newbtn->l1_pos, count($groups) - 1);
					$groupInChangedIndex[] = array(count($groups) - 1, (int) $btn->l1_pos);
				}
			} else {
				$groups[count($groups) - 1][] = $btn;
			}
		}
		/**
		 * 计算一级菜单的位置调整情况
		 */
		usort($groupInBaseOrder, function ($a1, $a2) {return $a1[0] - $a2[0];});
		foreach ($groupInBaseOrder as $i => &$go) {
			$go[] = $groupInChangedIndex[$i][0];
			$go[] = $groupInChangedIndex[$i][1];
		}
		/**
		 * 调整菜单项的排序
		 */
		$groupsSorted = array();
		for ($i = 0; $i < count($groups); $i++) {
			$sortedIndex = false;
			foreach ($groupInBaseOrder as $gibo) {
				if ($i === $gibo[2]) {
					$sortedIndex = $gibo[1];
					$g = &$groups[$sortedIndex];
					foreach ($g as &$btn) {
						$btn->l1_pos = $gibo[3];
					}

					break;
				}
			}
			if (false !== $sortedIndex) {
				/**
				 * 一级菜单的位置进行了调整
				 */
				$groupsSorted[$i] = $groups[$sortedIndex];
			} else {
				$groupsSorted[$i] = $groups[$i];
			}

		}
		/**
		 * 恢复成一维数组
		 */
		$result = array();
		foreach ($groupsSorted as $gs) {
			$result = array_merge($result, $gs);
		}

		return $result;
	}
	/**
	 * 合并菜单定义
	 *
	 * 如果新版本菜单项目的位置在旧版本中也是个父账号的菜单项，就插入新的菜单项
	 * 如果新版本菜单项目的位置在旧版本中没有定义，就插入新的菜单项
	 * 否则：
	 *
	 * return array
	 */
	private function &_mergeButtons($pButtons, $myButtons) {
		/**
		 * 根据父账号新版本的定义，调整子账号菜单项的排序
		 */
		$myButtons = $this->_interchange($myButtons, $pButtons);

		$buttons = array();
		$pc = count($pButtons);
		$pi = $i = $pl1_offset = $myl1_offset = 0;
		for (; $pi < $pc; $pi++) {
			$pBtn = $pButtons[$pi];
			/**
			 * 没有参与比较的菜单项
			 */
			if (!isset($myButtons[$i])) {
				$buttons[] = $pBtn;
				continue;
			}
			$myBtn = $myButtons[$i];
			/**
			 * 比较一级菜单
			 */
			if ($pBtn->l2_pos == 0 && $myBtn->l2_pos == 0) {
				/**
				 * 开始处理
				 */
				$buttons[] = $myBtn;
				$pl1_offset++;
				$i++;
				$pi--;
				continue;
			}
			/**
			 * 比较二级菜单
			 */
			if (($pBtn->l1_pos + $pl1_offset) == ($myBtn->l1_pos + $myl1_offset)) {
				/**
				 * 同一个一级菜单内
				 */
				$buttons[] = $myBtn;
				$i++;
				$pi--;
			} else if (($pBtn->l1_pos + $pl1_offset) > ($myBtn->l1_pos + $myl1_offset)) {
				/**
				 * A1无法被处理，等着A2追上来
				 * 旧版本的父账号菜单项直接去掉
				 */
				$buttons[] = $myBtn;
				$i++;
				$pi--;
			} else if (($pBtn->l1_pos + $pl1_offset) < ($myBtn->l1_pos + $myl1_offset)) {
				/**
				 * 新版本的菜单项位置在现有菜单定义是空的（新增了父菜单项目）
				 * A2要等着A1追上来
				 */
				$buttons[] = $pBtn;
			}
		}
		if ($i < count($myButtons)) {
			/**
			 * 处理剩余的子账号菜单项
			 */
			$c = count($myButtons);
			for (; $i < $c; $i++) {
				if ($myButtons[$i]->pversion == -1) {
					$buttons[] = $myButtons[$i];
				}
			}
		}
		//header('Content-Type:application/json;charset=utf-8');
		//die(json_encode($buttons));

		return $buttons;
	}
	/**
	 * 获得最新版本号
	 *
	 * 如果没有找到版本返回false
	 */
	public function getVersion($siteId, $published = 'N') {
		if ($published === 'N') {
			/**
			 * 如果还没有编辑版本，就返回最新的发布版本
			 * todo published
			 */
			$q = array(
				'max(version) v',
				'xxt_call_menu_wx',
				"siteid='$siteId'",
			);
			$version = $this->query_obj_ss($q);
			if ($version->v !== null) {
				$q = array(
					'published',
					'xxt_call_menu_wx',
					"siteid='$siteId' and version={$version->v}",
				);
				$q2 = array('r' => array('o' => 0, 'l' => 1));
				if ($p = $this->query_objs_ss($q, $q2)) {
					$version->p = $p[0]->published;
				}

			}
		} else {
			$q = array(
				"max(version) v,'Y' p",
				'xxt_call_menu_wx',
				"siteid='$siteId' and published='Y'",
			);
			$version = $this->query_obj_ss($q);
		}

		if ($version->v === null) {
			return false;
		}

		return $version;
	}
	/**
	 * 根账单当前的版本情况，生成新版本
	 *
	 * 返回版本信息
	 */
	public function &newVersion($siteId) {
		// 账号当前菜单的版本
		$version = $this->getVersion($siteId);

		if ($version === false) {
			/**
			 * 菜单为空，还没有版本
			 */
			$version = new stdClass;
			$version->v = 0;
			$version->p = 'N';
			$version->pv = -1;
			return $version;
		}

		if ($version->p === 'N') {
			/**
			 * 如果当前菜单为编辑版本，就不需要生成新版本
			 */
			return $version;
		}

		/**
		 * 当前版本为发布版本
		 * 复制当前版本，创建新版本
		 */
		$newVersion = (int) $version->v + 1;
		$fields = 'siteid,menu_key,creater,create_at,menu_name,l1_pos,l2_pos,url,matter_type,matter_id,asview';
		$sql = "insert into xxt_call_menu_wx($fields,version)";
		$sql .= " select $fields,$newVersion";
		$sql .= ' from xxt_call_menu_wx';
		$sql .= " where siteid='$siteId' and version=$version->v";

		$this->insert($sql, null, false);

		$version->v++;
		$version->p = 'N';

		return $version;
	}
}