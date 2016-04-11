<?php
namespace pl\fe\site\sns\yx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';

class MenuInvalidException extends \Exception {}

/**
 * 易信公众号
 */
class menu extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/yx/main');
		exit;
	}
	/**
	 * 返回菜单的完整定义，或者一个菜单项的定义
	 */
	public function get_action($site, $k = null) {
		$modelMenu = $this->model('sns\yx\call\menu');
		if (empty($k)) {
			$menu = $modelMenu->getMenu($site);
			return new \ResponseData($menu);
		} else {
			$button = $modelMenu->getButtonById($site, $k, 'N', '*', array('matter', 'acl'));
			return new \ResponseData($button);
		}
	}
	/**
	 * 获得当前账号菜单的可编辑版本号
	 */
	private function &_getVersion($mpid) {
		$modelMenu = $this->model('sns\yx\call\menu');
		$version = $modelMenu->getVersion($mpid);
		if ($version === false) {
			$version = new \stdClass;
			$version->v = 0;
			$version->p = 'N';
		} else if ($version->p === 'Y') {
			/**
			 * 版本已经发布，需要生成新版本
			 */
			$version = $modelMenu->newVersion($mpid);
		}
		return $version;
	}
	/**
	 * 创建一级菜单按钮
	 */
	public function createButton_action($site) {
		$version = $this->_getVersion($site);

		$button = $this->getPostJson();

		$button->siteid = $site;
		$button->menu_key = uniqid();
		$button->version = $version->v;
		$button->published = 'N';

		if (empty($button->menu_name)) {
			$button->menu_name = '新菜单';
		}

		if (isset($button->l1_pos)) {
			/**
			 * 检查是否存在位置冲突
			 */
		} else {
			/**
			 * 获得处于编辑状态的最后一个一级菜单的位置
			 */
			$q = array(
				'max(l1_pos)',
				'xxt_call_menu_yx',
				"siteid='$site' and l2_pos=0 and published='N'",
			);
			$l1_pos = $this->model()->query_val_ss($q);
			if (false === $l1_pos || $l1_pos === null) {
				$button->l1_pos = 1;
			} else {
				$button->l1_pos = (int) $l1_pos + 1;
			}
		}
		$modelMenu = $this->model('sns\yx\call\menu');
		$button = $modelMenu->createButton((array) $button);

		return new \ResponseData($button);
	}
	/**
	 * 创建二级菜单按钮
	 */
	public function createSubButton_action($site) {
		$version = $this->_getVersion($site);

		$button = $this->getPostJson();

		$button->siteid = $site;
		$button->menu_key = uniqid();
		$button->version = $version->v;
		$button->published = 'N';

		if (empty($button->menu_name)) {
			$button->menu_name = '新子菜单';
		}

		if (isset($button->l2_pos)) {
			/**
			 *
			 */
			$q = array(
				'max(l2_pos)',
				'xxt_call_menu_yx',
				"siteid='$site' and l1_pos=$button->l1_pos and published='N'",
			);
			$max_l2_pos = (int) $this->model()->query_val_ss($q);
			if ($button->l2_pos > ($max_l2_pos + 1)) {
				$button->l2_pos = $max_l2_pos + 1;
			} else {
				/**
				 * 更新现有的数据
				 */
				$this->model()->update(
					"update xxt_call_menu_yx set l2_pos=l2_pos+1 where siteid='$site' and published='N' and l2_pos>=$button->l2_pos");
			}
		} else {
			/**
			 * 获得处于编辑状态的最后一个一级菜单的位置
			 */
			$q = array(
				'max(l2_pos)',
				'xxt_call_menu_yx',
				"siteid='$site' and l1_pos=$button->l1_pos and published='N'",
			);
			$l2_pos = (int) $this->model()->query_val_ss($q);
			$button->l2_pos = (int) $l2_pos + 1;
		}
		$modelMenu = $this->model('sns\yx\call\menu');
		$button = $modelMenu->createButton((array) $button);

		return new \ResponseData($button);
	}
	/**
	 *
	 */
	public function removeButton_action($site, $k) {
		$model = $this->model();
		$version = $this->_getVersion($site);
		$modelMenu = $this->model('sns\yx\call\menu');
		$button = $modelMenu->getButtonById($site, $k);
		if (0 === (int) $button->l2_pos) {
			/**
			 * 删除一级菜单及子菜单
			 */
			$model->delete(
				'xxt_call_menu_yx',
				"siteid='$site' and published='N' and l1_pos=$button->l1_pos"
			);
			/**
			 * 更新菜单项位置
			 */
			$sql = 'update xxt_call_menu_yx';
			$sql .= ' set l1_pos=l1_pos-1';
			$sql .= " where siteid='$site' and published='N'";
			$sql .= " and l1_pos>$button->l1_pos";
			$model->update($sql);
		} else {
			/**
			 * 删除二级菜单
			 */
			$model->delete(
				'xxt_call_menu_yx',
				"siteid='$site' and published='N' and menu_key='$k'"
			);
			/**
			 * 更新菜单项位置
			 */
			$sql = 'update xxt_call_menu_yx';
			$sql .= ' set l2_pos=l2_pos-1';
			$sql .= " where siteid='$site' and published='N' and l1_pos=$button->l1_pos";
			$sql .= " and l2_pos>$button->l2_pos";
			$model->update($sql);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 更新基本属性
	 *
	 * 需要考虑是否要生成新版本，如果修改了name，url，asview
	 *
	 * 如果指定了URL，就会把设置过的回复素材清空
	 * 如果URL为空，asview设置为false
	 *
	 * 如果属性的修改影响了其他属性，会返回新对象，否者只返回true
	 *
	 * $k menu_key
	 */
	public function update_action($site, $k) {
		$nv = $this->getPostJson();
		$modelMenu = $this->model('sns\yx\call\menu');
		$version = $modelMenu->getVersion($site);
		if ($version === false) {
			$version = new \stdClass;
			$version->v = 0;
			$version->p = 'N';
		} else if ($version->p === 'Y') {
			/**
			 * 版本已经发布
			 * 如果修改的属性影响发布版本，就生成一个编辑版本
			 */
			if (isset($nv->menu_name) || isset($nv->url) || isset($nv->asview)) {
				$version = $modelMenu->newVersion($site);
			}
		}
		/**
		 * 是否需要更新关联数据
		 */
		$updateOther = false;
		$button = $modelMenu->getButtonById($site, $k);
		if (isset($nv->url)) {
			/**
			 * 如果是设置URL，将matter清空
			 */
			if (!empty($button->matter_type)) {
				$nv->matter_type = '';
				$nv->matter_id = '';
				$updateOther = true;
			}
			if (!empty($nv->url) && $button->asview === 'N') {
				$nv->asview = 'Y';
				$updateOther = true;
			} else if (empty($nv->url) && $button->asview === 'Y') {
				$nv->asview = 'N';
				$updateOther = true;
			}
		}
		/**
		 * 在最新版本上更新
		 */
		$rst = $this->model()->update(
			'xxt_call_menu_yx',
			$nv,
			"siteid='$site' and menu_key='$k' and version=$version->v"
		);

		if (isset($nv->menu_key)) {
			/**
			 * 修改的是菜单的key
			 */
			$k = $nv->menu_key;
		}
		if ($updateOther) {
			$button = $modelMenu->getButtonById($site, $k);
			return new \ResponseData($button);
		} else {
			return new \ResponseData($rst);
		}
	}
	/**
	 * 设置菜单按钮的位置
	 *
	 * 只允许在编辑状态的版本上改变菜单项的位置
	 */
	public function setpos_action($site, $k) {
		$version = $this->_getVersion($site);

		$newpos = $this->getPostJson();

		$q = array(
			'l1_pos,l2_pos',
			'xxt_call_menu_yx',
			"siteid='$site' and menu_key='$k' and published='N'",
		);
		$oldpos = $this->model()->query_obj_ss($q);
		if (empty($oldpos->l2_pos) && empty($newpos->l2_pos)) {
			/**
			 * 一级菜单
			 */
			$this->model()->update(
				'xxt_call_menu_yx',
				array('l1_pos' => -1),
				"siteid='$site' and published='N' and l1_pos=$oldpos->l1_pos"
			);
			if ($newpos->l1_pos > $oldpos->l1_pos) {
				$sql = 'update xxt_call_menu_yx';
				$sql .= ' set l1_pos=l1_pos-1';
				$sql .= " where siteid='$site' and published='N'";
				$sql .= " and l1_pos>$oldpos->l1_pos and l1_pos<=$newpos->l1_pos";
				$this->model()->update($sql);
			} else {
				$sql = 'update xxt_call_menu_yx';
				$sql .= ' set l1_pos=l1_pos+1';
				$sql .= " where siteid='$site' and published='N'";
				$sql .= " and l1_pos>=$newpos->l1_pos and l1_pos<$oldpos->l1_pos";
				$this->model()->update($sql);
			}
			$this->model()->update(
				'xxt_call_menu_yx',
				array('l1_pos' => $newpos->l1_pos),
				"siteid='$site' and published='N' and l1_pos=-1"
			);
		} else if ($oldpos->l1_pos == $newpos->l1_pos) {
			/**
			 * 在同一个一级菜单内调整二级菜单的顺序
			 */
			if ($newpos->l2_pos > $oldpos->l2_pos) {
				$sql = 'update xxt_call_menu_yx';
				$sql .= ' set l2_pos=l2_pos-1';
				$sql .= " where siteid='$site' and published='N' and l1_pos=$oldpos->l1_pos";
				$sql .= " and l2_pos>$oldpos->l2_pos and l2_pos<=$newpos->l2_pos";
				$this->model()->update($sql);
			} else {
				$sql = 'update xxt_call_menu_yx';
				$sql .= ' set l2_pos=l2_pos+1';
				$sql .= " where siteid='$site' and published='N' and l1_pos=$oldpos->l1_pos";
				$sql .= " and l2_pos>=$newpos->l2_pos and l2_pos<$oldpos->l2_pos";
				$this->model()->update($sql);
			}
			$this->model()->update(
				'xxt_call_menu_yx',
				array('l2_pos' => $newpos->l2_pos),
				"siteid='$site' and published='N' and menu_key='$k'"
			);
		} else {
			/**
			 * 在不同一级菜单间调整二级菜单的顺序
			 */
			$sql = 'update xxt_call_menu_yx';
			$sql .= ' set l2_pos=l2_pos-1';
			$sql .= " where siteid='$site' and published='N' and l1_pos=$oldpos->l1_pos";
			$sql .= " and l2_pos>$oldpos->l2_pos";
			$this->model()->update($sql);

			$sql = 'update xxt_call_menu_yx';
			$sql .= ' set l2_pos=l2_pos+1';
			$sql .= " where siteid='$site' and published='N' and l1_pos=$newpos->l1_pos";
			$sql .= " and l2_pos>=$newpos->l2_pos";
			$this->model()->update($sql);

			$this->model()->update(
				'xxt_call_menu_yx',
				array('l1_pos' => $newpos->l1_pos, 'l2_pos' => $newpos->l2_pos),
				"siteid='$site' and published='N' and menu_key='$k'"
			);
		}
		return new \ResponseData(true);
	}
	/**
	 * 指定菜单项的回复素材
	 */
	public function setreply_action($site, $k) {
		if (empty($k)) {
			return new ParameterError('参数错误，没有指定菜单项的唯一标识！');
		}
		$modelMenu = $this->model('sns\yx\call\menu');
		$updateOther = false; // 是否更新了关联属性，或者菜单的版本
		$button = $modelMenu->getButtonById($site, $k);
		/**
		 * 判断是否需要新版本
		 */
		$version = $modelMenu->getVersion($site);
		if ($version === false) {
			$version = new \stdClass;
			$version->v = 0;
			$version->p = 'N';
		} else if ($version->p === 'Y') {
			/**
			 * 版本已经发布
			 * 如果之前是asview的状态，需要生成新版本
			 */
			$button = $modelMenu->getButtonById($site, $k);
			if ($button->asview === 'Y') {
				$version = $modelMenu->newVersion($site);
				$updateOther = true;
			}
		}
		/**
		 * 如果设置了回复的素材，需要将URL清空，并缺省设置为不作为view
		 */
		$matter = $this->getPostJson();
		if (!empty($button->url)) {
			$matter->url = '';
			$updateOther = true;
		}
		if (!empty($button->asview) && $button->asview === 'Y') {
			$matter->asview = 'N';
			$updateOther = true;
		}
		/**
		 * update mapping.
		 */
		$rst = $this->model()->update(
			'xxt_call_menu_yx',
			(array) $matter,
			"siteid='$site' and version=$version->v and menu_key='$k'"
		);

		if ($updateOther) {
			$button = $modelMenu->getButtonById($site, $k);
			return new \ResponseData($button);
		} else {
			return new \ResponseData(true);
		}
	}
	/**
	 * 将处于编辑状态的菜单发布到公众平台
	 *
	 * 1、检查是否为编辑状态
	 * 2、将菜单项转变为菜单定义消息
	 * 3、向公众平台发送
	 * 4、若发送成功将菜单设置为发布状态
	 */
	public function publish_action($site) {
		/**
		 * 检查是否为编辑状态，不用检查版本号，因为最多只有1个编辑版本
		 */
		$q = array(
			'count(*)',
			'xxt_call_menu_yx',
			"siteid='$site' and published='N'",
		);
		if (0 === (int) $this->model()->query_val_ss($q)) {
			return new \ResponseError('空菜单无法发布');
		}
		/**
		 * 获得菜单的消息格式
		 */
		try {
			$literalMenu = $this->_convertMenu($site);
		} catch (MenuInvalidException $e) {
			return new \ResponseError($e->getMessage());
		}
		/**
		 * 向公众号平台发布消息
		 */
		$yxConfig = $this->model('sns\yx')->bySite($site);
		if ($yxConfig->joined === 'N') {
			return new \ResponseError('公众账号未连接成功，请检查。');
		}
		if ($yxConfig->can_menu === 'N') {
			return new \ResponseError("未开通发布菜单高级接口");
		}
		$proxy = $this->model("sns\yx\proxy", $yxConfig);
		$rst = $proxy->menuCreate($literalMenu);
		if ($rst[0] === false) {
			return new \ResponseError("菜单发布失败：" . $rst[1]);
		}
		/**
		 * 将菜单设置为发布状态
		 */
		$rst = $this->model()->update(
			'xxt_call_menu_yx',
			array('published' => 'Y'),
			"siteid='$site' and published='N'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function removeMenu_action($site) {
		$yxConfig = $this->model('sns\yx')->bySite($site);
		$proxy = $this->model("sns\yx\proxy", $yxConfig);
		$rst = $proxy->menuDelete();
		if ($rst[0] === false) {
			return new \ResponseError("菜单删除失败：" . $rst[1]);
		}

		$this->model()->delete('xxt_call_menu_yx', "siteid='$site'");

		return new \ResponseData($rst[1]);
	}
	/**
	 * 将编辑状态的菜单定义转化为消息格式
	 * todo 检查菜单数量，不允许只有一个子菜单的父菜单
	 * todo 检查菜单数量，一级菜单微信1-3个，易信1-4个
	 * todo 检查菜单数量，二级菜单2-5个
	 */
	private function _convertMenu($site) {
		$modelMenu = $this->model('sns\yx\call\menu');

		$buttons = array();
		$menu = $modelMenu->getMenu($site);
		foreach ($menu as $button) {
			if (0 === (int) $button->l2_pos) {
				/**
				 * 一级菜单
				 */
				$buttons[] = $this->_convertButton($site, $button);
			} else {
				/**
				 * 二级菜单
				 */
				$pButtons = &$buttons[count($buttons) - 1];
				if (!isset($pButtons['sub_button'])) {
					unset($pButtons['type']);
					unset($pButtons['key']);
				}
				$pButtons['sub_button'][] = $this->_convertButton($site, $button);
			}
		}

		$msg = new \stdClass;
		$msg->button = &$buttons;
		$literal = urldecode(json_encode($msg));

		return $literal;
	}
	/**
	 * 将传递的菜单定义，转换为标准定义和映射关系
	 *
	 * $param array $button
	 *
	 * return array(button, mapping)
	 */
	private function &_convertButton($site, $button) {
		$key = $button->menu_key;
		$type = $button->asview === 'N' ? 'click' : 'view';
		$formatted = array(
			'name' => urlencode($button->menu_name),
		);
		if (!empty($button->matter_id) || !empty($button->url)) {
			$formatted['type'] = $type;
			$formatted['key'] = $key;
		} else {
			if (0 !== (int) $button->l2_pos) {
				throw new MenuInvalidException("菜单【{$button->menu_name}】没有指定响应内容。");
			}
		}
		if ($type === 'view') {
			if (!empty($button->url)) {
				$formatted['url'] = $button->url;
				/**
				 * 如果指定URL，URL必须以http开头
				 */
				if (0 !== strpos($button->url, 'http://')) {
					throw new MenuInvalidException("菜单【{$button->menu_name}】的链接必须以【http://】开头。");
				}

			} else if (!empty($button->matter_id)) {
				/**
				 * matter as link.
				 */
				$url = $this->model('matter\\' . $button->matter_type)->getEntryUrl($site, $button->matter_id);
				$formatted['url'] = $url;
			} else {
				throw new MenuInvalidException("菜单【{$button->menu_name}】的链接不允许为空。");
			}
		}

		return $formatted;
	}
}