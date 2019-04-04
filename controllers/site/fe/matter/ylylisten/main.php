<?php
namespace site\fe\matter\ylylisten;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请
 */
class main extends \site\fe\matter\base {
	//天翼云的API服务器
	private $endpoint = OOS_ENDPOINT;
	//Access Key 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessKey = OOS_ACCESS_KEY;
	//Access Secret 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessSecret = OOS_ACCESS_SECRET;
	//
	private $modelOOS;
	/**
	 *
	 */
	public function __construct() {
		$this->modelOOS = $this->model('fs\oos', $this->endpoint, $this->accessKey, $this->accessSecret);
	}
	/**
	 * 容器列表
	 */
	public function bucketList_action() {
		$rst = $this->modelOOS->bucketList();
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$buckets = $rst[1];

		return new \ResponseData($buckets);
	}
	/**
	 * 容器列表
	 */
	public function objectList_action() {
		$post = $this->getPostJson();
		if (empty($post->bucket)) {
			return new \ParameterError();
		}
		
		$rst = $this->modelOOS->objectList($post);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$data = $rst[1];

		if (!empty($data['Contents'])) {
			$Contents = &$data['Contents'];
			foreach ($Contents as $key => &$val) {
				if (strlen($val['Key']) > strlen($prefix) + 27) {
					$fileName = substr($val['Key'], strlen($prefix), 23);
					$fileType = substr($val['Key'], strrpos($val['Key'], '.'));
					$val['fileName'] = $fileName . $fileType;
				} else {
					$val['fileName'] = $val['Key'];
				}
			}
		}

		return new \ResponseData($data);
	}
	/**
	*
	*/
	public function getObject_action() {
		$post = $this->getPostJson();
		if (empty($post->bucket) || empty($post->fileName)) {
			return new \ParameterError();
		}

		$rst = $this->modelOOS->getObject($post);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$data = $rst[1];

		return new \ResponseData($data);
	}
	/**
	 * 获取文件下载地址
	 */
	public function getObjectUrl_action() {
		$post = $this->getPostJson();
		if (empty($post->bucket) || empty($post->fileName)) {
			return new \ParameterError();
		}

		$rst = $this->modelOOS->getObjectUrl($post);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$url = $rst[1];

		$data = new \stdClass;
		$data->url = $url;
		return new \ResponseData($data);
	}
}