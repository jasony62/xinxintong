<?php
namespace matter\signin;
/**
 *
 */
class page_model extends \TMS_MODEL {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_signin_page';
	}
	/**
	 *
	 */
	public function &byId($appId, $apid, $published = 'N') {
		$select = 'ap.*,cp.html,cp.css,cp.js';
		$from = 'xxt_signin_page ap,xxt_code_page cp';
		$where = "ap.aid='$appId' and ap.id=$apid and ap.code_id=cp.id";

		$q = [$select, $from, $where];

		$ep = $this->query_obj_ss($q);
		if ($ep) {
			if ($published === 'Y') {
				$code = \TMS_APP::model('code\page')->lastPublishedByName($ep->siteid, $ep->code_name);
			} else {
				$code = \TMS_APP::model('code\page')->lastByName($ep->siteid, $ep->code_name);
			}
			$ep->html = $code->html;
			$ep->css = $code->css;
			$ep->js = $code->js;
			$ep->ext_js = $code->ext_js;
			$ep->ext_css = $code->ext_css;
		}

		return $ep;
	}
	/**
	 *
	 */
	public function byName($appId, $name, $published = 'N') {
		$select = 'ep.*,cp.html,cp.css,cp.js';
		$from = 'xxt_signin_page ep,xxt_code_page cp';
		$where = "ep.aid='$appId' and ep.name='$name' and ep.code_id=cp.id";

		$q = [$select, $from, $where];

		if ($ep = $this->query_obj_ss($q)) {
			if ($published === 'Y') {
				$code = \TMS_APP::model('code\page')->lastPublishedByName($ep->siteid, $ep->code_name);
			} else {
				$code = \TMS_APP::model('code\page')->lastByName($ep->siteid, $ep->code_name);
			}
			$ep->html = $code->html;
			$ep->css = $code->css;
			$ep->js = $code->js;
			$ep->ext_js = $code->ext_js;
			$ep->ext_css = $code->ext_css;
			return $ep;
		} else {
			return false;
		}
	}
	/**
	 * 返回指定登记活动的页面
	 */
	public function &byApp($appId, $options = [], $published = 'N') {
		$cascade = isset($options['cascade']) ? $options['cascade'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_signin_page',
			"aid='$appId'",
		];
		$q2 = ['o' => 'seq,create_at'];
		$eps = $this->query_objs_ss($q, $q2);
		if ($cascade === 'Y' && !empty($eps)) {
			$modelCode = \TMS_APP::model('code\page');
			$pages = [];
			foreach ($eps as &$ep) {
				if ($published === 'Y') {
					$code = $modelCode->lastPublishedByName($ep->siteid, $ep->code_name);
				} else {
					$code = $modelCode->lastByName($ep->siteid, $ep->code_name);
				}
				$ep->html = $code->html;
				$ep->css = $code->css;
				$ep->js = $code->js;
				$ep->ext_js = $code->ext_js;
				$ep->ext_css = $code->ext_css;
				$pages[] = $ep;
			}
			return $pages;
		} else {
			return $eps;
		}
	}
	/**
	 * 创建活动页面
	 */
	public function add(&$user, $siteId, $appId, $data = null) {
		is_object($data) && $data = (array) $data;

		$code = \TMS_APP::model('code\page')->create($siteId, $user->id);

		if (empty($data['seq'])) {
			$q = [
				'max(seq)',
				'xxt_signin_page',
				"aid='$appId'",
			];
			$seq = $this->query_val_ss($q);
			$seq = empty($seq) ? 1 : $seq + 1;
		} else {
			$seq = $data['seq'];
		}
		$newPage = [
			'siteid' => $siteId,
			'aid' => $appId,
			'creater' => $user->id,
			'create_at' => time(),
			'type' => isset($data['type']) ? $data['type'] : 'V',
			'title' => isset($data['title']) ? $data['title'] : '新页面',
			'name' => isset($data['name']) ? $data['name'] : 'z' . time(),
			'code_id' => $code->id,
			'code_name' => $code->name,
			'seq' => $seq,
		];

		$apid = $this->insert('xxt_signin_page', $newPage, true);

		$newPage['id'] = $apid;
		$newPage['html'] = '';
		$newPage['css'] = '';
		$newPage['js'] = '';

		return (object) $newPage;
	}
	/**
	 *
	 */
	public function &htmlBySchema(&$schema, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('schema', $schema);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
}