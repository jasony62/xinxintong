<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';

class link_model extends base_model {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_link';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'link';
	}
	/**
	 * 返回链接和链接的参数
	 */
	public function byIdWithParams($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_link',
			"id=$id",
		);
		if ($link = $this->query_obj_ss($q)) {
			$q = array(
				'pname,pvalue,authapi_id',
				'xxt_link_param',
				"link_id=$id",
			);
			if ($params = $this->query_objs_ss($q)) {
				$link->params = $params;
			}

		}

		return $link;
	}
	/**
	 * 返回进行推送的消息格式
	 *
	 * $runningMpid
	 * $id
	 */
	public function &forCustomPush($runningMpid, $id) {
		$link = $this->byId($id);
		$link->type = 'link';

		$msg = array(
			'msgtype' => 'news',
			'news' => array(
				'articles' => array(
					array(
						'title' => $link->title,
						'description' => $link->summary,
						'url' => $this->getEntryUrl($runningMpid, $id),
						'picurl' => $link->pic,
					),
				),
			),
		);

		return $msg;
	}
	/**
	 *
	 */
	public function getEntryUrl($runningMpid, $id, $openid = null, $call = null) {
		if (isset($matter->urlsrc)) {
			/**
			 * link
			 */
			switch ($matter->urlsrc) {
			case 0: // external link
				if ($matter->open_directly === 'Y') {
					$url = $matter->url;
					$q = array(
						'pname,pvalue,authapi_id',
						'xxt_link_param',
						"link_id=$matter->id",
					);
					if ($params = $this->query_objs_ss($q)) {
						$url .= (strpos($url, '?') === false) ? '?' : '&';
						$url .= $this->spliceParams($runningMpid, $params, null, $openid);
					}
					if (preg_match('/^(http:|https:)/', $url) === 0) {
						$url = 'http://' . $url;
					}

					return $url;
				} else {
					$url = "?mpid=$runningMpid&id=$matter->id&type=link";
				}
				break;
			case 1: // news
				$url = "?mpid=$runningMpid&type=news&id=" . $matter->url;
				break;
			case 2: // channel
				$url = "?mpid=$runningMpid&type=channel&id=" . $matter->url;
				break;
			case 3: // inner
				$reply = TMS_APP::model('reply\inner', $call, $matter->url);
				$url = $reply->exec(false);
				$q = array(
					'pname,pvalue,authapi_id',
					'xxt_link_param',
					"link_id=$matter->id",
				);
				if ($params = $this->query_objs_ss($q)) {
					$url .= (strpos($url, '?') === false) ? '?' : '&';
					$url .= $this->spliceParams($runningMpid, $params, null, $openid);
				}
				if (preg_match('/^(http:|https:)/', $url) === 0) {
					$url = 'http://' . $url;
				}

				return $url;
			default:
				die('unknown link urlsrc.');
			}
		} else {
			$url = "http://" . $_SERVER['HTTP_HOST'] . "/rest/mi/matter";
			$url .= "?mpid=$runningMpid&id=$id&type=" . $this->getTypeName();
			if (!empty($openid)) {
				$url .= "&openid=$openid";
			}

			return $url;
		}
	}
}