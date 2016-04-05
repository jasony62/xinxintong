<?php
/**
 *
 */
class acl_model extends TMS_MODEL {
	/**
	 * 获得文本事件的ACL
	 *
	 * 需要根据指定的授权对象的不同获取不同的数据
	 */
	public function textCall($mpid, $keyword) {
		return $this->call_acls($mpid, 'Text', $keyword);
	}
	/**
	 * 获得菜单事件的ACL
	 *
	 * 需要根据指定的授权对象的不同获取不同的数据
	 */
	public function menuCall($mpid, $key) {
		return $this->call_acls($mpid, 'Menu', $key);
	}
	/**
	 * 通用活动登记通知消息的接收人
	 */
	public function enrollReceiver($mpid, $aid) {
		return $this->enrollReceiver_acls($mpid, $aid);
	}
	/**
	 * 获得讨论组的所有用户
	 * 将ACL翻译为具体的用户
	 */
	public function wallUsers($mpid, $wid) {
		$users = array();
		/**
		 * 直接指定
		 */
		$q = array(
			'a.identity',
			'xxt_matter_acl a',
			"a.mpid='$mpid' and a.matter_type= 'wall' and a.matter_id='$wid' and idsrc=''",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as $acl) {
				$users[] = $acl->identity;
			}

		}
		/**
		 * 部门
		 */
		/*$q = array(
			            'a.id,a.identity,a.idsrc,d.name dept',
			            'xxt_matter_acl a,xxt_member_department d',
			            "a.mpid='$mpid' and a.matter_type='wall' and a.matter_id='$wid' and idsrc='D' and a.mpid=d.mpid and a.identity=d.id"
			        );
			        if ($acls = $this->query_objs_ss($q))
		*/
		/**
		 * 标签
		 */
		$q = array(
			'a.identity',
			'xxt_matter_acl a',
			"a.mpid='$mpid' and a.matter_type='wall' and a.matter_id='$wid' and idsrc='T'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as $acl) {
				$tagIds = explode(',', $acl->identity);
				foreach ($tagIds as $tagId) {
					$q = array(
						'openid',
						'xxt_member',
						"mpid='$mpid' and concat(',',tags,',') like '%,$tagId,%'",
					);
					if ($members = $this->query_objs_ss($q)) {
						foreach ($members as $m) {
							if (!in_array($m->openid, $users)) {
								$users[] = $m->openid;
							}

						}
					}
				}
			}
		}
		/**
		 * 部门+标签
		 */
		/*$q = array(
			            'a.id,a.identity,a.idsrc',
			            'xxt_matter_acl a',
			            "a.mpid='$mpid' and a.matter_type='wall' and a.matter_id='$wid' and idsrc='DT'"
			        );
			        if ($acls = $this->query_objs_ss($q)) {
			            foreach ($acls as &$acl) {
			                $ids = explode(',',$acl->identity);
			                $deptname = $this->query_value(
			                    'name',
			                    'xxt_member_department',
			                    "mpid='$mpid' and id={$ids[0]}"
			                );
			                unset($ids[0]);
			                $tagnames = $this->query_values(
			                    'name',
			                    'xxt_member_tag',
			                    "mpid='$mpid' and id in (".implode(',',$ids).")"
			                );
			                $acl->dept = $deptname.','.implode(',', $tagnames);
			            }
			            $all = array_merge($all, $acls);
		*/

		return $users;
	}
	/**
	 *
	 * todo 需要指定src？需要指定authid？
	 */
	public function enrollReceivers($mpid, $aid) {
		$users = array();
		/**
		 * 直接指定
		 */
		/*$q = array(
			            'a.identity',
			            'xxt_enroll_receiver a',
			            "a.mpid='$mpid' and a.aid='$aid' and idsrc=''"
			        );
			        if ($acls = $this->query_objs_ss($q)) {
			            foreach ($acls as $acl)
			                $users[] = $acl->identity;
		*/
		/**
		 * 部门
		 */
		/*$q = array(
			            'a.id,a.identity,a.idsrc,d.name dept',
			            'xxt_enroll_receiver a,xxt_member_department d',
			            "a.mpid='$mpid' and a.aid='$aid' and idsrc='D' and a.mpid=d.mpid and a.identity=d.id"
			        );
			        if ($acls = $this->query_objs_ss($q))
		*/
		/**
		 * 标签
		 */
		$q = array(
			'a.identity',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='T'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as $acl) {
				$tagIds = explode(',', $acl->identity);
				foreach ($tagIds as $tagId) {
					$q = array(
						'openid',
						'xxt_member',
						"mpid='$mpid' and concat(',',tags,',') like '%,$tagId,%'",
					);
					if ($members = $this->query_objs_ss($q)) {
						foreach ($members as $m) {
							if (!in_array($m->openid, $users)) {
								$users[] = $m->openid;
							}

						}
					}
				}
			}
		}
		/**
		 * 部门+标签
		 */
		/*$q = array(
			            'a.id,a.identity,a.idsrc',
			            'xxt_enroll_receiver a',
			            "a.mpid='$mpid' and a.aid='$aid' and idsrc='DT'"
			        );
			        if ($acls = $this->query_objs_ss($q)) {
			            foreach ($acls as &$acl) {
			                $ids = explode(',',$acl->identity);
			                $deptname = $this->query_value(
			                    'name',
			                    'xxt_member_department',
			                    "mpid='$mpid' and id={$ids[0]}"
			                );
			                unset($ids[0]);
			                $tagnames = $this->query_values(
			                    'name',
			                    'xxt_member_tag',
			                    "mpid='$mpid' and id in (".implode(',',$ids).")"
			                );
			                $acl->dept = $deptname.','.implode(',', $tagnames);
			            }
			            $all = array_merge($all, $acls);
		*/
		/**
		 * 认证用户
		 */
		$q = array(
			'a.identity',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='M'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as $acl) {
				$q = array(
					'openid',
					'xxt_member m',
					"m.mpid='$mpid' and m.mid='$acl->identity'",
				);
				if ($openid = $this->query_val_ss($q)) {
					if (!in_array($openid, $users)) {
						$users[] = $openid;
					}

				}
			}
		}

		return $users;
	}
	/**
	 * 获得获得事件的ACL
	 *
	 * 需要根据指定的授权对象的不同获取不同的数据
	 */
	private function call_acls($mpid, $type, $keyword) {
		$q = array(
			'a.id,a.identity,a.idsrc,a.label',
			'xxt_call_acl a',
			"a.mpid='$mpid' and a.call_type='$type' and a.keyword='$keyword'",
		);
		$acls = $this->query_objs_ss($q);

		return $acls;
	}
	/**
	 * 获得素材的ACL
	 *
	 * 需要根据指定的授权对象的不同获取不同的数据
	 */
	public function byMatter($mpid, $type, $id) {
		$q = array(
			'a.id,a.identity,a.idsrc,a.label',
			'xxt_matter_acl a',
			"a.mpid='$mpid' and a.matter_type='$type' and a.matter_id='$id'",
		);
		$acls = $this->query_objs_ss($q);

		return $acls;
	}
	/**
	 * 获得活动登记通知接收人的ACL
	 */
	private function enrollReceiver_acls($mpid, $aid) {
		$all = array();
		/**
		 * 直接指定
		 */
		$q = array(
			'a.id,a.identity,a.idsrc',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc=''",
		);
		if ($acls = $this->query_objs_ss($q)) {
			$all = array_merge($all, $acls);
		}

		/**
		 * 部门
		 */
		$q = array(
			'a.id,a.identity,a.idsrc,d.name dept',
			'xxt_enroll_receiver a,xxt_member_department d',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='D' and a.mpid=d.mpid and a.identity=d.id",
		);
		if ($acls = $this->query_objs_ss($q)) {
			$all = array_merge($all, $acls);
		}

		/**
		 * 标签
		 */
		$q = array(
			'a.id,a.identity,a.idsrc',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='T'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as &$acl) {
				$names = $this->query_values(
					'name',
					'xxt_member_tag',
					"mpid='$mpid' and id in ($acl->identity)"
				);
				$acl->tag = implode(',', $names);
			}
			$all = array_merge($all, $acls);
		}
		/**
		 * 部门+标签
		 */
		$q = array(
			'a.id,a.identity,a.idsrc',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='DT'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as &$acl) {
				$ids = explode(',', $acl->identity);
				$deptname = $this->query_value(
					'name',
					'xxt_member_department',
					"mpid='$mpid' and id={$ids[0]}"
				);
				unset($ids[0]);
				$tagnames = $this->query_values(
					'name',
					'xxt_member_tag',
					"mpid='$mpid' and id in (" . implode(',', $ids) . ")"
				);
				$acl->dept = $deptname . ',' . implode(',', $tagnames);
			}
			$all = array_merge($all, $acls);
		}
		/**
		 * 认证用户
		 */
		$q = array(
			'a.id,a.identity,a.idsrc',
			'xxt_enroll_receiver a',
			"a.mpid='$mpid' and a.aid='$aid' and idsrc='M'",
		);
		if ($acls = $this->query_objs_ss($q)) {
			foreach ($acls as &$acl) {
				$q = array(
					'm.mobile,m.name,m.email,m.authed_identity',
					'xxt_member m',
					"m.mpid='$mpid' and m.mid='$acl->identity'",
				);
				if ($member = $this->query_obj_ss($q)) {
					if (!empty($member->name)) {
						$acl->label = $member->name;
					} else if (!empty($member->mobile)) {
						$acl->label = $member->mobile;
					} else if (!empty($member->email)) {
						$acl->label = $member->email;
					} else {
						$acl->label = $member->authed_identity;
					}
				} else {
					$acl->label = '用户不存在';
				}
			}
			$all = array_merge($all, $acls);
		}

		return $all;
	}
	/**
	 * 事件访问控制检查
	 * 1、检查是否已经设置了白名单，若没有设置则所有认证用户可访问
	 * 2、若设置了白名单，则检查当前用户是否在白名单中
	 */
	public function canAccessCall($mpid, $call_type, $keyword, $member, $authapis) {
		$whichAcl = "mpid='$mpid' and call_type='$call_type' and keyword='$keyword'";

		return $this->canAccess($mpid, 'xxt_call_acl', $whichAcl, $member->authed_identity, $authapis);
	}
	/**
	 * 素材访问控制检查
	 * 1、检查是否已经设置了白名单，若没有设置则所有注册用户可访问
	 * 2、若设置了白名单，则检查当前用户是否在白名单中
	 */
	public function canAccessMatter($mpid, $matter_type, $matter_id, $member, $authapis) {
		$whichAcl = "matter_type='$matter_type' and matter_id='$matter_id'";

		return $this->canAccess($mpid, 'xxt_matter_acl', $whichAcl, $member->authed_identity, $authapis);
	}
	/**
	 * 通用的白名单检查机制
	 *
	 * $mpid
	 * $table 访问控制列表
	 * $whichAcl 需要检查的列表项
	 * $identity 用户身份标识
	 * $authapis
	 */
	public function canAccess($mpid, $table, $whichAcl, $identity, $authapis, $mustInclude = false) {
		/**
		 * 是否设置了白名单
		 */
		if (!$mustInclude) {
			$q = array(
				'count(*)',
				$table,
				$whichAcl,
			);
			if (0 === (int) $this->query_val_ss($q)) {
				return true;
			}

		}
		/**
		 * 检查当前用户是否在白名单中
		 * 如果有多个认证身份信息，有一个在白名单中就行
		 * todo 用户身份必须和指定认证接口匹配才可以
		 */
		$q = array(
			'count(*)',
			$table,
			"$whichAcl and idsrc='' and identity='$identity'",
		);
		if (0 < (int) $this->query_val_ss($q)) {
			return true;
		}

		/**
		 * 后缀匹配，例如：域名匹配
		 */
		$q = array(
			'count(*)',
			$table,
			"$whichAcl and idsrc='' and '$identity' like concat('%',identity)",
		);
		if (1 === ((int) $this->query_val_ss($q))) {
			return true;
		}

		/**
		 * 由认证接口进行检查
		 */
		$q = array(
			'identity,idsrc',
			$table,
			"$whichAcl",
		);
		$acls = $this->query_objs_ss($q);
		if (true === $this->checkAclByAuthapi($mpid, $authapis, $acls, $identity)) {
			return true;
		}

		return false;
	}
	/**
	 * 通用的白名单检查机制
	 *
	 * $mpid
	 * $table 访问控制列表
	 * $whichAcl 需要检查的列表项
	 * $identity 用户身份标识
	 * $authapis
	 */
	public function canAccess2($siteId, $table, $whichAcl, $identity, $aSchemaIds, $mustInclude = false) {
		/**
		 * 是否设置了白名单
		 */
		if (!$mustInclude) {
			$q = array(
				'count(*)',
				$table,
				$whichAcl,
			);
			if (0 === (int) $this->query_val_ss($q)) {
				return true;
			}

		}
		/**
		 * 检查当前用户是否在白名单中
		 * 如果有多个认证身份信息，有一个在白名单中就行
		 * todo 用户身份必须和指定认证接口匹配才可以
		 */
		$q = array(
			'count(*)',
			$table,
			"$whichAcl and idsrc='' and identity='$identity'",
		);
		if (0 < (int) $this->query_val_ss($q)) {
			return true;
		}

		/**
		 * 后缀匹配，例如：域名匹配
		 */
		$q = array(
			'count(*)',
			$table,
			"$whichAcl and idsrc='' and '$identity' like concat('%',identity)",
		);
		if (1 === ((int) $this->query_val_ss($q))) {
			return true;
		}

		/**
		 * 由认证接口进行检查
		 */
		$q = array(
			'identity,idsrc',
			$table,
			"$whichAcl",
		);
		$acls = $this->query_objs_ss($q);
		if (true === $this->_checkBySchema($siteId, $aSchemaIds, $acls, $identity)) {
			return true;
		}

		return false;
	}
	/**
	 *
	 */
	private function checkAclByAuthapi($mpid, $authapis, $acls, $identity) {
		$authids = explode(',', $authapis);
		$posted = json_encode($acls);
		foreach ($authids as $authid) {
			$q = array(
				'url',
				'xxt_member_authapi',
				"authid=$authid and valid='Y'",
			);
			if ($url = $this->query_val_ss($q)) {
				false === strpos($url, 'http') && $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
				$url .= "/checkAcl?authid=$authid&uid=$identity";
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
				if (false === ($response = curl_exec($ch))) {
					$err = curl_error($ch);
					curl_close($ch);
					return array(false, $err);
				}
				curl_close($ch);
				$rst = json_decode($response);
				if ($rst->err_code == 0 && $rst->data === 'passed') {
					return true;
				}
			}
		}
		return false;
	}
	/**
	 *
	 */
	private function _checkBySchema($siteId, &$aSchemaIds, $acls, $identity) {
		$posted = json_encode($acls);
		foreach ($aSchemaIds as $schemaId) {
			$q = array(
				'url',
				'xxt_site_member_schema',
				"id=$schemaId and valid='Y'",
			);
			if ($url = $this->query_val_ss($q)) {
				false === strpos($url, 'http') && $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
				$url .= "/checkAcl?site=$siteId&schema=$schemaId&uid=$identity";
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
				if (false === ($response = curl_exec($ch))) {
					$err = curl_error($ch);
					curl_close($ch);
					return array(false, $err);
				}
				curl_close($ch);
				$rst = json_decode($response);
				if ($rst->err_code == 0 && $rst->data === 'passed') {
					return true;
				}
			}
		}
		return false;
	}
}