<?php
namespace site\fe\matter\diaoting;
include_once dirname(dirname(__FILE__)) . '/base.php';
include_once TMS_APP_DIR . '/vendor/autoload.php';
use Aws\S3\S3Client;
/**
 * 用户邀请
 */
class main extends \site\fe\matter\base {
	//天翼云的API服务器
	private $endpoint = 'https://oos-js.ctyunapi.cn/';
	//Access Key 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessKey = "5b79c659ce70ace89ce2";
	//Access Secret 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessSecret = "4493e6cc8c992cb194cfdd8760949becc6029223";
	/**
	 * 链接天翼云接口
	 */
	private function S3OOS() {
		//创建S3 client 对象
		$client = \Aws\S3\S3Client::factory([
			'endpoint' => $this->endpoint,  //声明使用指定的endpoint
			'key'      => $this->accessKey,
			'secret'   => $this->accessSecret
		]);

		return $client;
	}
	/**
	 * 处理天翼云返回的数据
	 */
	private function disposeData($data) {
		$data = (array) $data;
		$data2 = new \stdClass;
		foreach ($data as $key => $value) {
			if (strpos($key, '*') === 1) {
				$key2 = substr($key, 3);
				$data2->{$key2} = $value;
			} else {
				$data2->{$key} = $value;
			}
		}

		return $data2;
	}
	/**
	 * 容器列表
	 */
	public function bucketList_action() {
		$buckets = $this->S3OOS()->listBuckets();
		$data = $this->disposeData($buckets);
		
		return new \ResponseData($data);
	}
	/**
	 * 容器列表
	 */
	public function objectList_action() {
		$post = $this->getPostJson();
		if (empty($post->bucket)) {
			return new \ParameterError();
		}
		
		$options = [
				'Bucket' => $post->bucket,
				'MaxKeys' => 5,
				'Delimiter' => '/',
		];
		//指定以什么字符串开头
		$prefix = '';
		if (!empty($post->prefix)) {
			$prefix = $post->prefix;
		}
		$options['Prefix'] = $prefix;
		//page 分页 是上一页的最后对象的name
		if (!empty($post->marker)) {
			$options['Marker'] = $post->marker;
		}
		//获取的个数,每页显示数量
		if (!empty($post->size)) {
			$options['MaxKeys'] = $post->size;
		}
		$objects = $this->S3OOS()->listObjects($options);
		$data = $this->disposeData($objects);

		if (!empty($data->data['Contents'])) {
			$Contents = &$data->data['Contents'];
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

		$file = $this->S3OOS()->getObject([
				'Bucket' => $post->bucket,
				'Key' => $post->fileName
		]);
		$data = $this->disposeData($file);

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

		$url = $this->S3OOS()->getObjectUrl($post->bucket, $post->fileName, '+10 minutes'); // 下载对象

		$data = new \stdClass;
		$data->url = urlencode($url);
		return new \ResponseData($data);
	}
}