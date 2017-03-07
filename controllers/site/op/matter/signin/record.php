<?php
namespace site\op\matter\signin;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class record extends \site\op\base {
	/**
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {

		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'signinStartAt' => $signinStartAt,
			'signinEndAt' => $signinEndAt,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		!empty($rid) && $rid !== 'ALL' && $options['rid'] = $rid;

		$mdoelRec = $this->model('matter\signin\record');
		$result = $mdoelRec->find($app, $options, $criteria);

		return new \ResponseData($result);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;

		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app, ['cascaded' => 'N']);

		foreach ($eks as $ek) {
			$rst = $modelApp->update(
				'xxt_signin_record',
				['verified' => 'Y'],
				"enroll_key='$ek'"
			);
		}

		// 记录操作日志
		//$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

		return new \ResponseData('ok');
	}
	/**
	 * 更新登记记录
	 *
	 * 1、是否带报名信息
	 * 2、指定签到的轮次和对应的签到时间
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$record = $this->getPostJson();
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');

		$signinApp = $modelApp->byId($app, ['cascaded' => 'N']);

		$current = time();
		$updatedRecord = new \stdClass;
		$updatedRecord->enroll_at = $current;
		isset($record->comment) && $updatedRecord->comment = $record->comment;

		// 是否通过验证
		if (isset($record->verified)) {
			$updatedRecord->verified = $record->verified;
			if ($record->verified === 'N') {
				// 如果不通过验证，解除关联的报名应用信息
				$updatedRecord->verified_enroll_key = '';
			}
		}

		// 标签
		if (isset($record->tags)) {
			// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
			$updatedRecord->tags = $record->tags;
			$modelApp->updateTags($signinApp->id, $record->tags);
		}

		// 签到日志
		if (isset($record->signin_log)) {
			$signinNum = 0;
			$signinAtLast = 0;
			$modelSinLog = $this->model('matter\signin\log');
			foreach ($record->signin_log as $roundId => $signinAt) {
				if ($signinAt) {
					$signinAt > $signinAtLast && $signinAtLast = $signinAt;
					$signinNum++;
					// 保存签到日志
					if ($sinLog = $modelSinLog->byRecord($ek, $roundId)) {
						$modelSinLog->update(
							'xxt_signin_log',
							['signin_at' => $signinAt],
							['enroll_key' => $ek, 'rid' => $roundId]
						);
					} else {
						$modelRec->insert(
							'xxt_signin_log',
							[
								'siteid' => $site,
								'aid' => $signinApp->id,
								'rid' => $roundId,
								'enroll_key' => $ek,
								'userid' => '',
								'nickname' => '',
								'signin_at' => $signinAt,
							],
							false
						);
					}
				} else {
					// 清除掉无效的数据
					unset($record->signin_log->{$roundId});
					$modelSinLog->delete(
						'xxt_signin_log',
						['enroll_key' => $ek, 'rid' => $roundId]
					);
				}
			}
			$updatedRecord->signin_num = $record->signin_num = $signinNum;
			$updatedRecord->signin_at = $record->signin_at = $signinAtLast;
			$updatedRecord->signin_log = \TMS_MODEL::toJson($record->signin_log);
		}
		// 更新登记记录数据
		$modelRec->update(
			'xxt_signin_record',
			$updatedRecord,
			"enroll_key='$ek'"
		);

		// 更新登记数据
		$modelRec->setData($site, $signinApp, $ek, $record->data);

		// 记录操作日志
		// $signinApp->type = 'signin';
		// $this->model('matter\log')->matterOp($site, $user, $signinApp, 'update', $record);

		// 返回完整的记录
		$record = $modelRec->byId($ek);

		return new \ResponseData($record);
	}
	/**
	 * 给记录批量添加标签
	 */
	public function batchTag_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;
		$tags = $posted->tags;

		/**
		 * 给记录打标签
		 */
		$modelRec = $this->model('matter\signin\record');
		if (!empty($eks) && !empty($tags)) {
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek);
				$existent = $record->tags;
				if (empty($existent)) {
					$aNew = $tags;
				} else {
					$aExistent = explode(',', $existent);
					$aNew = array_unique(array_merge($aExistent, $tags));
				}
				$newTags = implode(',', $aNew);
				$modelRec->update('xxt_signin_record', ['tags' => $newTags], "enroll_key='$ek'");
			}
		}
		/**
		 * 给应用打标签
		 */
		$this->model('matter\signin')->updateTags($app, $posted->appTags);

		return new \ResponseData('ok');
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $ek, $keepData = 'Y') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$rst = $this->model('matter\signin\record')->remove($app, $ek, $keepData !== 'Y');

		// 记录操作日志
		// $app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		// $app->type = 'signin';
		// $this->model('matter\log')->matterOp($site, $user, $app, 'remove', $ek);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app, $keepData = 'Y') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$rst = $this->model('matter\signin\record')->clean($app, $keepData !== 'Y');

		// 记录操作日志
		// $app = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		// $app->type = 'signin';
		// $this->model('matter\log')->matterOp($site, $user, $app, 'empty');

		return new \ResponseData($rst);
	}
}