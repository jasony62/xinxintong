<?php
namespace sns\yx\call;
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
			'xxt_call_qrcode_yx',
			["id" => $id],
		];

		$call = $this->model()->query_obj_ss($q);

		if ($call && $cascaded === 'Y') {
			if ($call->matter_id) {
				$call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
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
			'xxt_call_qrcode_yx',
			"siteid='$siteId' and expire_at=0",
		];
		$q2['o'] = 'id desc';

		$calls = $this->model()->query_objs_ss($q, $q2);

		return $calls;
	}
	/**
	 *
	 */
	public function &byMatter($type, $id) {
		// 清除过期的二维码
		$current = time();
		$this->delete('xxt_call_qrcode_yx', "expire_at<>0 and expire_at<=$current");

		$result = [];
		$q = [
			'*',
			'xxt_call_qrcode_yx',
			["matter_type" => $type, "matter_id" => $id],
		];

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
}