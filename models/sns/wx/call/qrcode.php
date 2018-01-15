<?php
namespace sns\wx\call;
/**
 * 微信公众号二维码
 */
class qrcode_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = [
			'id,siteid,name,pic,matter_type,matter_id',
			'xxt_call_qrcode_wx',
			["id" => $id],
		];

		$call = $this->query_obj_ss($q);

		if ($call && $cascaded === 'Y') {
			if ($call->matter_id) {
				$call->matter = \TMS_APP::M('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
			}
		}

		return $call;
	}
	/**
	 *
	 */
	public function &bySite($siteId) {
		$q = [
			'*',
			'xxt_call_qrcode_wx',
			['siteid' => $siteId, 'expire_at' => 0],
		];
		$q2['o'] = 'id desc';

		$calls = $this->query_objs_ss($q, $q2);

		return $calls;
	}
	/**
	 *
	 */
	public function &bySceneId($siteId, $sceneId) {
		$q = [
			'*',
			'xxt_call_qrcode_wx',
			['siteid' => $siteId, 'scene_id' => $sceneId],
		];
		$call = $this->query_obj_ss($q);
		if (false === $call && $sceneId !== 'platform') {
			$q = [
				'*',
				'xxt_call_qrcode_wx',
				['siteid' => 'platform', 'scene_id' => $sceneId],
			];
			$call = $this->query_obj_ss($q);
		}

		return $call;
	}
	/**
	 * 获得素材对应的场景二维码
	 */
	public function &byMatter($type, $id, $params = null) {
		// 清除过期的二维码
		$current = time();

		$result = [];
		$q = [
			'*',
			'xxt_call_qrcode_wx',
			["matter_type" => $type, "matter_id" => $id],
		];
		if (!empty($params)) {
			$q[2]['params'] = $params;
		}
		$calls = $this->query_objs_ss($q);
		if (count($calls)) {
			$current = time();
			foreach ($calls as $call) {
				if ($call->expire_at === '0' || $call->expire_at > $current) {
					$result[] = $call;
				}
			}
		}

		return $result;
	}
	/**
	 * 创建微信永久二维码
	 *
	 * 二维码最大值100000
	 *
	 * @param string $site
	 * @param string $matter
	 * @param int $expire
	 *
	 */
	public function create($siteId, $oMatter = null, $expire = 0) {
		$modelWx = $this->model('sns\wx');
		/**
		 * 公众号配置
		 */
		$snsSiteId = $siteId;
		if (false === ($oWxConfig = $modelWx->bySite($snsSiteId)) || $oWxConfig->joined !== 'Y') {
			$snsSiteId = 'platform';
			$oWxConfig = $modelWx->bySite($snsSiteId);
		}
		if ($oWxConfig === false) {
			return [false, '公众号还没有开通'];
		}
		if ($oWxConfig->can_qrcode === 'N') {
			return [false, '公众号还没有开通场景二维码接口'];
		}
		/**
		 * 获取可用的场景ID
		 */
		$q = [
			'max(scene_id)',
			'xxt_call_qrcode_wx',
			"siteid='$snsSiteId' and scene_id<100000",
		];
		if ($sceneId = (int) $modelWx->query_val_ss($q)) {
			$sceneId++;
		} else {
			$sceneId = 1;
		}
		/**
		 * 生成二维码
		 */
		$oProxy = $this->model('sns\wx\proxy', $oWxConfig);
		$rst = $oProxy->qrcodeCreate($sceneId, false);
		if ($rst[0] === false) {
			return $rst;
		}
		$oWxQrcode = $rst[1];
		/**
		 * 微信二维码信息
		 */
		$current = time();
		$oNewQrcode = new \stdClass;
		$oNewQrcode->siteid = $snsSiteId;
		$oNewQrcode->scene_id = $oWxQrcode->scene_id;
		$oNewQrcode->create_at = $current;
		if (empty($expire)) {
			$oNewQrcode->expire_at = 0;
		} else {
			$oNewQrcode->expire_at = $current + $expire;
		}
		$oNewQrcode->pic = $oWxQrcode->pic;
		/**
		 * 关联的素材
		 */
		if (!empty($oMatter->type) && !empty($oMatter->id)) {
			$oNewQrcode->matter_type = $oMatter->type;
			$oNewQrcode->matter_id = $oMatter->id;
			if (!empty($oMatter->params)) {
				$oNewQrcode->params = $modelWx->toJson($oMatter->params);
			}
		}
		/**
		 * 模拟临时二维码
		 */
		if ((int) $expire === 0) {
			$oNewQrcode->name = '新场景二维码';
		} else {
			$oNewQrcode->name = '模拟场景二维码';
		}

		$oNewQrcode->id = $modelWx->insert('xxt_call_qrcode_wx', $oNewQrcode, true);

		return [true, $oNewQrcode];
	}
	/**
	 * 创建一次性二维码
	 *
	 * 用临时二维码实现
	 * 创建二维码时直接指定回复的素材
	 * 只要做了扫描，二维码就失效（删除掉）
	 */
	public function createOneOff($snsSiteId, $oMatter, $expire = null) {
		$modelWx = $this->model('sns\wx');
		if (false === ($oWxConfig = $modelWx->bySite($snsSiteId)) || $oWxConfig->joined !== 'Y') {
			$snsSiteId = 'platform';
			$oWxConfig = $modelWx->bySite($snsSiteId);
		}
		if ($oWxConfig === false) {
			return [false, '公众号还没有开通'];
		}
		if ($oWxConfig->can_qrcode === 'N') {
			return [false, '公众号还没有开通场景二维码接口'];
		}
		/**
		 * 清除过期的临时二维码
		 */
		$current = time();
		$modelWx->delete('xxt_call_qrcode_wx', "expire_at<>0 and expire_at<=$current and scene_id>100000");
		/**
		 * 生成场景ID
		 */
		$sceneId = mt_rand(100001, mt_getrandmax());
		while (true) {
			$q = [
				'count(*)',
				'xxt_call_qrcode_wx',
				"siteid='{$oWxConfig->siteid}' and expire_at<>0 and scene_id=$sceneId",
			];
			if (1 === (int) $modelWx->query_val_ss($q)) {
				$sceneId = mt_rand(100001, mt_getrandmax());
			} else {
				break;
			}
		}
		/**
		 * 获取二维码
		 */
		$proxy = $this->model('sns\wx\proxy', $oWxConfig);
		if (isset($expire)) {
			$rst = $proxy->qrcodeCreate($sceneId, true, $expire);
		} else {
			$rst = $proxy->qrcodeCreate($sceneId);
		}
		if ($rst[0] === false) {
			return $rst;
		}
		$oWxQrcode = $rst[1];

		$current = time();
		$oNewQrcode = new \stdClass;
		$oNewQrcode->siteid = $snsSiteId;
		$oNewQrcode->name = '';
		$oNewQrcode->scene_id = $oWxQrcode->scene_id;
		$oNewQrcode->create_at = $current;
		$oNewQrcode->expire_at = $current + $oWxQrcode->expire_seconds - 30;
		$oNewQrcode->matter_type = $oMatter->type;
		$oNewQrcode->matter_id = $oMatter->id;
		$oNewQrcode->pic = $oWxQrcode->pic;
		isset($oMatter->params) && $oNewQrcode->params = $this->escape($this->toJson($oMatter->params));

		$oNewQrcode->id = $modelWx->insert('xxt_call_qrcode_wx', $oNewQrcode, true);

		return [true, $oNewQrcode];
	}
}