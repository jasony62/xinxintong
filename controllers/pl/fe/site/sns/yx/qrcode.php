<?php
namespace pl\fe\site\sns\yx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 易信公众号
 */
class qrcode extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/yx/main');
		exit;
	}
	/**
	 * get all qrcode calls.
	 *
	 * 只返回永久二维码，不包含临时二维码
	 */
	public function list_action($site) {
		/**
		 * 公众号自己的文本消息回复
		 */
		$q = array(
			'*',
			'xxt_call_qrcode_yx',
			"siteid='$site' and expire_at=0",
		);
		$q2['o'] = 'id desc';

		$calls = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($calls);
	}
	/**
	 * get onr qrcode calls.
	 */
	public function get_action($site, $id) {
		/**
		 * 公众号自己的文本消息回复
		 */
		$q = array(
			'*',
			'xxt_call_qrcode_yx',
			"siteid='$site' and id=$id",
		);

		$qrcode = $this->model()->query_obj_ss($q);

		return new \ResponseData($qrcode);
	}
	/**
	 * get one qrcode call.
	 *
	 * $id int qrcode call id.
	 * $contain array
	 */
	private function &_byId($id, $contain = array('matter')) {
		$q = array(
			'id,siteid,name,pic,matter_type,matter_id',
			'xxt_call_qrcode_yx',
			"id=$id",
		);
		$call = $this->model()->query_obj_ss($q);

		if (!empty($contain) && in_array('matter', $contain)) {
			if ($call->matter_id) {
				$call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
			}
		}

		return $call;
	}
	/**
	 * 创建一个二维码响应
	 *
	 * todo 企业号怎么办？
	 *
	 * 易信的永久二维码最大值1000
	 * 微信的永久二维码最大值100000
	 */
	public function create_action($site) {
		/**
		 * 获取可用的场景ID
		 */
		$q = array(
			'max(scene_id)',
			'xxt_call_qrcode_yx',
			"siteid='$site' and expire_at=0",
		);
		if ($scene_id = $this->model()->query_val_ss($q)) {
			$scene_id++;
		} else {
			$scene_id = 1;
		}
		/**
		 * 生成二维码
		 */
		$yx = $this->model('sns\yx')->bySite($site);
		$proxy = $this->model('sns\yx\proxy', $yx);
		$rst = $proxy->qrcodeCreate($scene_id, false);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$qrcode = $rst[1];
		/**
		 * 保存数据并返回
		 */
		$d['siteid'] = $site;
		$d['name'] = '新场景二维码';
		$d['scene_id'] = $qrcode->scene_id;
		$d['create_at'] = time();
		$d['pic'] = $qrcode->pic;

		$d['id'] = $this->model()->insert('xxt_call_qrcode_yx', $d, true);

		return new \ResponseData((object) $d);
	}
	/**
	 * 更新的基本信息
	 *
	 * $mpid
	 * $id
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();
		$rst = $this->model()->update(
			'xxt_call_qrcode_yx',
			$nv,
			['siteid' => $site, 'id' => $id]
		);
		return new \ResponseData($rst);
	}
	/**
	 * 指定回复素材
	 *
	 * //todo 如果是父账号的资源怎么办？
	 */
	public function matter_action($site, $id, $type) {
		$matter = $this->model('matter\base')->getMatterInfoById($type, $id);

		return new \ResponseData($matter);
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

		$rst = $this->model('sns\yx\call\qrcode')->createOneOff($site, $matter_type, $matter_id);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($rst[1]);
	}
}