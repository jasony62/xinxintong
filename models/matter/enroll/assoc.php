<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';
/**
 * 登记记录间的关联
 */
class assoc_model extends entity_model {
	/**
	 * 管理方式
	 */
	const Assoc_Mode = ['Free' => 0, 'ParentAndChild' => 1, 'Sibling' => 1];
	/**
	 * 改写成前端需要的形式
	 */
	public function &adapt(&$oAssoc, $oUser = null) {
		/* 实体类型定义转换 */
		$oAssoc->entity_a_type = self::Type_IntToStr[(int) $oAssoc->entity_a_type];
		$oAssoc->entity_b_type = self::Type_IntToStr[(int) $oAssoc->entity_b_type];
		/* 关联的实体对象 */
		$aOptions = [];
		switch ($oAssoc->entity_b_type) {
		case 'record':
			$aOptions['fields'] = 'id,enroll_key';
			break;
		}
		if ($oEntityB = $this->findEntity($oAssoc->entity_b_id, $oAssoc->entity_b_type, $aOptions)) {
			unset($oAssoc->entity_b_id);
			unset($oAssoc->entity_b_type);
			$oAssoc->entityB = $oEntityB;
		}
		if ($oUser) {
			/* 当前用户是否建立了关联 */
			$oLinkLog = $this->getLinkLog($oAssoc->id, $oUser);
			if ($oLinkLog) {
				$oAssoc->log = $oLinkLog;
			}
		}
		return $oAssoc;
	}
	/**
	 * 当前用户已经做过关联
	 */
	public function getLinkLog($oAssocId, $oUser, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? 'id,link_at,assoc_text,assoc_reason' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_enroll_assoc_log',
			['assoc_id' => $oAssocId, 'userid' => $oUser->uid, 'state' => 1],
		];
		$oLog = $this->query_obj_ss($q);

		return $oLog;
	}
	/**
	 *
	 */
	public function byId($id, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_enroll_assoc',
			['id' => $id],
		];
		$oAssoc = $this->query_obj_ss($q);

		return $oAssoc;
	}
	/**
	 * 检查关联关系是否已经存在
	 */
	private function _byEntityAnB($oEntityA, $oEntityB, $assocMode, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];

		$q = [
			$fields,
			'xxt_enroll_assoc',
			['entity_a_id' => $oEntityA->id, 'entity_a_type' => $oEntityA->intType, 'entity_b_id' => $oEntityB->id, 'entity_b_type' => $oEntityB->intType, 'assoc_mode' => $assocMode],
		];
		$oAssoc = $this->query_obj_ss($q);

		return $oAssoc;
	}
	/**
	 * 建立两条记录的关联
	 */
	public function link($oEntityA, $oEntityB, $oUser, $aOptions = []) {
		$assocReason = isset($aOptions['assocReason']) ? $aOptions['assocReason'] : '';
		$assocText = isset($aOptions['assocText']) ? $aOptions['assocText'] : '';
		$assocMode = isset($aOptions['assocMode']) ? assoc_model::Assoc_Mode[$aOptions['assocMode']] : assoc_model::Assoc_Mode['Free'];
		$isPublic = isset($aOptions['public']) ? $aOptions['public'] : 'N';

		/* 更新关联关系 */
		$current = time();
		$oAssoc = $this->_byEntityAnB($oEntityA, $oEntityB, $assocMode, ['fields' => 'id,siteid,aid,record_id,assoc_num,assoc_text,assoc_reason,first_assoc_at,last_assoc_at,entity_a_id,entity_a_type,entity_b_id,entity_b_type']);
		if ($oAssoc) {
			/* 当前用户是否已经做过关联 */
			if ($oUserLog = $this->getLinkLog($oAssoc->id, $oUser)) {
				return [false, '不允许重复建立关联'];
			}
			$oUpdated = [
				'last_assoc_at' => $current,
				'assoc_num' => (object) ['op' => '+=', 'pat' => 1],
			];
			if ($oAssoc->first_assoc_at === '0') {
				$oUpdated['first_assoc_at'] = $current;
			}
			$rst = $this->update(
				'xxt_enroll_assoc',
				$oUpdated,
				['id' => $oAssoc->id]
			);
			if ($rst) {
				$oAssoc->last_assoc_at = $current;
				$oAssoc->assoc_num++;
			}
		} else {
			$oAssoc = new \stdClass;
			if (empty($oEntityA->record->siteid) || empty($oEntityA->record->aid) || empty($oEntityA->record->id)) {
				$oRecord = $this->recordByEntity($oEntityA);
			} else {
				$oRecord = $oEntityA->record;
			}
			$oAssoc->siteid = $oRecord->siteid;
			$oAssoc->aid = $oRecord->aid;
			$oAssoc->record_id = $oRecord->id;
			$oAssoc->entity_a_id = $oEntityA->id;
			$oAssoc->entity_a_type = $oEntityA->intType;
			$oAssoc->entity_b_id = $oEntityB->id;
			$oAssoc->entity_b_type = $oEntityB->intType;
			$oAssoc->first_assoc_at = $oAssoc->last_assoc_at = $current;
			$oAssoc->public = $isPublic;
			$oAssoc->assoc_text = $assocText;
			$oAssoc->assoc_reason = $assocReason;
			$oAssoc->assoc_mode = $assocMode;
			$oAssoc->assoc_num = 1;

			$oAssoc->id = $this->insert('xxt_enroll_assoc', $oAssoc, true);
		}

		/* 记录用户日志 */
		$oLog = new \stdClass;
		$oLog->siteid = $oAssoc->siteid;
		$oLog->aid = $oAssoc->aid;
		$oLog->record_id = $oAssoc->record_id;
		$oLog->assoc_id = $oAssoc->id;
		$oLog->assoc_text = $assocText;
		$oLog->assoc_reason = $assocReason;
		$oLog->userid = $oUser->uid;
		$oLog->link_at = $current;
		$this->insert('xxt_enroll_assoc_log', $oLog, false);

		return [true, $oAssoc];
	}
	/**
	 * 拆除记录间的关联
	 */
	public function unlink($assocId, $oUser) {
		/* 更新关联关系 */
		$oAssoc = $this->byId($assocId, ['fields' => 'id,siteid,aid,record_id,first_assoc_at,last_assoc_at']);
		if (false === $oAssoc) {
			return [false, '关联不存在'];
		}
		/* 当前用户是否已经做过关联 */
		$oLinkLog = $this->getLinkLog($oAssoc->id, $oUser);
		if (false === $oLinkLog) {
			return [false, '当前用户没有建立过关联，无法拆除关联'];
		}
		/* 更新关联数据 */
		$oUpdated = ['assoc_num' => (object) ['op' => '-=', 'pat' => 1]];
		if ($oAssoc->first_assoc_at === $oLinkLog->link_at) {
			$firstAssocAt = $this->query_val_ss(
				[
					'min(link_at)',
					'xxt_enroll_assoc_log',
					['assoc_id' => $oAssoc->id, 'state' => 1, 'id' => (object) ['op' => '<>', 'pat' => $oLinkLog->id]],
				]
			);
			$oUpdated['first_assoc_at'] = empty($firstAssocAt)? 0 : $firstAssocAt;
		}
		if ($oAssoc->last_assoc_at === $oLinkLog->link_at) {
			$lastAssocAt = $this->query_val_ss(
				[
					'max(link_at)',
					'xxt_enroll_assoc_log',
					['assoc_id' => $oAssoc->id, 'state' => 1, 'id' => (object) ['op' => '<>', 'pat' => $oLinkLog->id]],
				]
			);
			$oUpdated['last_assoc_at'] = empty($lastAssocAt)? 0 : $lastAssocAt;
		}
		$this->update(
			'xxt_enroll_assoc',
			$oUpdated,
			['id' => $oAssoc->id]
		);
		/* 记录用户日志 */
		$oLog = new \stdClass;
		$oLog->siteid = $oAssoc->siteid;
		$oLog->aid = $oAssoc->aid;
		$oLog->record_id = $oAssoc->record_id;
		$oLog->assoc_id = $oAssoc->id;
		$oLog->userid = $oUser->uid;
		$oLog->unlink_at = time();
		$oLog->state = 0;
		$oLog->id = $this->insert('xxt_enroll_assoc_log', $oLog, true);

		$this->update(
			'xxt_enroll_assoc_log',
			['undo_log_id' => $oLog->id, 'state' => 0],
			['id' => $oLinkLog->id]
		);

		return [true];
	}
	/**
	 * 记录下包含的所有关联
	 */
	public function byRecord($oRecord, $oUser, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];

		$w = "record_id={$oRecord->id}";
		$w .= " and assoc_num>0";
		$w .= " and(public='Y'";
		$w .= " or exists(select 1 from xxt_enroll_assoc_log l where l.state=1 and l.assoc_id=a.id and l.userid='{$oUser->uid}')";
		$w .= ")";
		if (isset($aOptions['entityA'])) {
			$entityA = $aOptions['entityA'];
			if ($this->getDeepValue($entityA, 'id') && $this->getDeepValue($entityA, 'type')) {
				$w .= " and entity_a_id = " . $this->getDeepValue($entityA, 'id') . " and entity_a_type = " . self::Type_StrToInt[$this->getDeepValue($entityA, 'type')];
			}
		}
		$q = [
			$fields,
			'xxt_enroll_assoc a',
			$w,
		];
		$oAssocs = $this->query_objs_ss($q);

		return $oAssocs;
	}
	/**
	 *
	 */
	public function byEntityB($oEntity, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];

		$q = [
			$fields,
			'xxt_enroll_assoc a',
			['entity_b_id' => $oEntity->id, 'entity_b_type' => self::Type_StrToInt[$oEntity->type], 'public' => 'Y', 'assoc_num' => (object) ['op' => '>', 'pat' => 0]],
		];

		$assocs = $this->query_objs_ss($q);
		foreach ($assocs as $oAssoc) {
			$oAssoc->entity_a_type = self::Type_IntToStr[(int) $oAssoc->entity_a_type];
		}

		return $assocs;
	}
}