<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class qrcode extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/wx/main');
		exit;
	}
	/**
	 * get all qrcode calls.
	 *
	 * 只返回永久二维码，不包含临时二维码
	 */
	public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$calls = $this->model('sns\wx\call\qrcode')->bySite($site);

		return new \ResponseData($calls);
	}
	/**
	 * get qrcode calls.
	 */
	public function get_action($site, $id, $cascaded = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$qrcode = $this->model('sns\wx\call\qrcode')->byId($id, ['cascaded' => $cascaded]);

		return new \ResponseData($qrcode);
	}

	/**
	 * 创建微信永久二维码
	 *
	 * 二维码最大值100000
	 *
	 * @param string $site
	 * @param int $expire
	 * @param string $matter_type
	 * @param string $matter_id
	 *
	 */
	public function create_action($site, $expire = 0, $matter_type = null, $matter_id = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelWx = $this->model('sns\wx');
		$snsSiteId = $site;
		if (false === ($wxConfig = $modelWx->bySite($snsSiteId)) || $wxConfig->joined !== 'Y') {
			$snsSiteId = 'platform';
			$wxConfig = $modelWx->bySite($snsSiteId);
		}

		if ($wxConfig === false) {
			return new \ResponseError('公众号还没有开通');
		}

		if ($wxConfig->can_qrcode === 'N') {
			return new \ResponseError('公众号还没有开通场景二维码接口');
		}
		/**
		 * 获取可用的场景ID
		 */
		$q = [
			'max(scene_id)',
			'xxt_call_qrcode_wx',
			"siteid='$snsSiteId' and scene_id<100000",
		];
		if ($scene_id = $modelWx->query_val_ss($q)) {
			$scene_id++;
		} else {
			$scene_id = 1;
		}
		/**
		 * 生成二维码
		 */
		$proxy = $this->model('sns\wx\proxy', $wxConfig);
		$rst = $proxy->qrcodeCreate($scene_id, false);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$qrcode = $rst[1];
		/**
		 * 保存数据并返回
		 */
		$current = time();
		$d = [];
		$d['siteid'] = $snsSiteId;
		$d['scene_id'] = $qrcode->scene_id;
		$d['create_at'] = $current;
		if (empty($expire)) {
			$d['expire_at'] = 0;
		} else {
			$d['expire_at'] = $current + $expire;
		}
		$d['pic'] = $qrcode->pic;
		if (!empty($matter_type) && !empty($matter_id)) {
			$d['matter_type'] = $matter_type;
			$d['matter_id'] = $matter_id;
			if (!empty($posted) && !empty($posted->params)) {
				$d['params'] = \TMS_MODEL::toJson($posted->params);
			}
		}
		// 模拟临时二维码
		if ((int) $expire === 0) {
			$d['name'] = '新场景二维码';
		} else {
			$d['name'] = '模拟场景二维码';
		}

		$d['id'] = $this->model()->insert('xxt_call_qrcode_wx', $d, true);

		return new \ResponseData((object) $d);
	}
	/**
	 * 创建一次性二维码
	 *
	 * 用临时二维码实现
	 * 创建二维码时直接指定回复的素材
	 * 只要做了扫描，二维码就失效（删除掉）
	 */
	public function createOneOff_action($site, $matter_type, $matter_id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('sns\wx\call\qrcode')->createOneOff($site, $matter_type, $matter_id);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($rst[1]);
	}
	/**
	 * 更新的基本信息
	 *
	 * @param string $site
	 * @param int $id
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_call_qrcode_wx',
			$nv,
			['siteid' => $site, 'id' => $id]
		);
		return new \ResponseData($rst);
	}
	/**
	 * 指定回复素材
	 *
	 */
	public function matter_action($site, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->model('matter\base')->getMatterInfoById($type, $id);

		return new \ResponseData($matter);
	}
}