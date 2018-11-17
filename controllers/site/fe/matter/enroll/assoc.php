<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动记录间的关联
 */
class assoc extends base {
	/**
	 * 建立关联
	 */
	public function link_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields', 'id,state,siteid,aid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('指定的对象不存在（1）');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->entityA->id) || empty($oPosted->entityA->type)) {
			return new \ParameterError('没有指定实体对象A');
		}
		if (empty($oPosted->entityB->id) || empty($oPosted->entityB->type)) {
			return new \ParameterError('没有指定实体对象B');
		}
		$oEntityA = $modelRec->findEntity($oPosted->entityA->id, $oPosted->entityA->type, ['fields' => 'id']);
		if (false === $oEntityA) {
			return new \ObjectNotFoundError('指定的对象不存在（2）');
		}
		$oEntityB = $modelRec->findEntity($oPosted->entityB->id, $oPosted->entityB->type, ['fields' => 'id']);
		if (false === $oEntityB) {
			return new \ObjectNotFoundError('指定的对象不存在（3）');
		}
		$oEntityA->record = $oRecord;

		if (empty($oPosted->assoc->text)) {
			return new \ParameterError('关联内容说明不允许为空');
		}

		$oUser = $this->who;
		$modelAss = $this->model('matter\enroll\assoc');
		$aOptions = [];
		if (isset($oPosted->assoc->text)) {
			$aOptions['assocText'] = $modelAss->escape($oPosted->assoc->text);
		}
		if (isset($oPosted->assoc->reason)) {
			$aOptions['assocReason'] = $modelAss->escape($oPosted->assoc->reason);
		}
		if (isset($oPosted->assoc->mode) && $modelAss::Assoc_Mode[$oPosted->assoc->mode]) {
			$aOptions['assocMode'] = $oPosted->assoc->mode;
		}
		if (isset($oPosted->assoc->public) && in_array($oPosted->assoc->public, ['Y', 'N'])) {
			$aOptions['public'] = $oPosted->assoc->public;
		}
		$oAssoc = $modelAss->link($oEntityA, $oEntityB, $oUser, $aOptions);
		if (false === $oAssoc[0]) {
			return new \ResponseError($oAssoc[1]);
		}

		$oAssoc = $modelAss->adapt($oAssoc[1], $oUser);

		return new \ResponseData($oAssoc);
	}
	/**
	 * 拆除关联
	 */
	public function unlink_action($assoc) {
		$oUser = $this->who;

		$modelAss = $this->model('matter\enroll\assoc');
		$oResult = $modelAss->unlink($assoc, $oUser);
		if (false === $oResult[0]) {
			return new \ResponseError($oRecord[1]);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 更新关联
	 */
	public function update_action($assoc) {
		$oUser = $this->who;

		$modelAss = $this->model('matter\enroll\assoc');
		$oAssoc = $modelAss->byId($assoc, ['fields' => 'id,siteid,aid,record_id']);
		if (false === $oAssoc) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$oLinkLog = $modelAss->getLinkLog($oAssoc->id, $oUser);
		if (false === $oLinkLog) {
			/* 如果用户没有建立过关联，自动创建一个 */
			$oLinkLog = new \stdClass;
			$oLinkLog->siteid = $oAssoc->siteid;
			$oLinkLog->aid = $oAssoc->aid;
			$oLinkLog->record_id = $oAssoc->record_id;
			$oLinkLog->assoc_id = $oAssoc->id;
			$oLinkLog->userid = $oUser->uid;
			$oLinkLog->link_at = $current;
			$oLinkLog->id = $modelAss->insert('xxt_enroll_assoc_log', $oLinkLog, true);
		}

		$oPosted = $this->getPostJson();
		$aUpdated = [];
		$aUpdatedLog = [];
		$bUpdatePublic = false;
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'text':
				$aUpdatedLog['assoc_text'] = $modelAss->escape($oPosted->text);
				break;
			case 'reason':
				$aUpdatedLog['assoc_reason'] = $modelAss->escape($oPosted->reason);
				break;
			case 'mode':
				if (isset($modelAss::Assoc_Mode[$val])) {
					$aUpdated['assoc_mode'] = $modelAss::Assoc_Mode[$val];
				}
				break;
			case 'public':
				if (in_array($val, ['Y', 'N'])) {
					$aUpdated['public'] = $val;
				}
				break;
			case 'updatePublic':
				$bUpdatePublic = (bool) $val;
				break;
			}
		}
		if (empty($aUpdatedLog) && empty($aUpdated)) {
			return new \ParameterError('没有有效的更新数据');
		}
		if (!empty($aUpdatedLog)) {
			$modelAss->update(
				'xxt_enroll_assoc_log',
				$aUpdatedLog,
				['id' => $oLinkLog->id]
			);
			if ($bUpdatePublic) {
				$aUpdated = array_merge($aUpdated, $aUpdatedLog);
			}
		}
		if (!empty($aUpdated)) {
			$modelAss->update('xxt_enroll_assoc', $aUpdated, ['id' => $oAssoc->id]);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 获得一条记录的所有关联
	 */
	public function byRecord_action($ek) {
		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'id,state']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('指定的对象不存在（1）');
		}

		$oUser = $this->who;
		$modelAss = $this->model('matter\enroll\assoc');
		$aOptions = [
			'fields' => 'id,assoc_mode,assoc_num,first_assoc_at,last_assoc_at,entity_a_id,entity_a_type,entity_b_id,entity_b_type,public,assoc_text,assoc_reason',
		];
		$oAssocs = $modelAss->byRecord($oRecord, $oUser, $aOptions);
		if (count($oAssocs)) {
			$oUser = $this->who;
			foreach ($oAssocs as $oAssoc) {
				$modelAss->adapt($oAssoc, $oUser);
			}
		}

		return new \ResponseData($oAssocs);
	}
}