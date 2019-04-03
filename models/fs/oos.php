<?php
namespace fs;

// include_once dirname(dirname(__FILE__)) . '/base.php';
// include_once dirname(__FILE__) . '/config.php';
include_once TMS_APP_DIR . '/vendor/autoload.php';

/** 
 * 天翼云存储
 */
class oos_model {
	//天翼云的API服务器
	private $endpoint;
	//Access Key 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessKey;
	//Access Secret 在天翼云门户网站-帐户管理-API密钥管理中获取
	private $accessSecret;
	/**
	 *
	 */
	public function __construct($endpoint, $accessKey, $accessSecret) {
		$this->endpoint = $endpoint;
		$this->accessKey = $accessKey;
		$this->accessSecret = $accessSecret;
	}
	/**
	 * 链接天翼云接口
	 */
	private function S2AMZ() {
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

		return $data2->data;
	}
	/**
	 * 容器列表
	 */
	public function bucketList() {
		$buckets = $this->S2AMZ()->listBuckets();
		$data = $this->disposeData($buckets);

		return [true, $data];
	}
	/**
	 * 容器列表
	 */
	public function objectList($post) {
		if (empty($post->bucket)) {
			return [false, '未找到bucket'];
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
		$objects = $this->S2AMZ()->listObjects($options);
		$data = $this->disposeData($objects);

		return [true, $data];
	}
	/**
	*
	*/
	public function getObject($post) {
		if (empty($post->bucket) || empty($post->fileName)) {
			return [false, "未找到bucket或文件名"];
		}

		$file = $this->S2AMZ()->getObject([
				'Bucket' => $post->bucket,
				'Key' => $post->fileName
		]);
		$data = $this->disposeData($file);

		return [true, $data];
	}
	/**
	 * 获取文件下载地址
	 */
	public function getObjectUrl($post) {
		if (empty($post->bucket) || empty($post->fileName)) {
			return [false, "未找到bucket或文件名"];
		}

		$url = $this->S2AMZ()->getObjectUrl($post->bucket, $post->fileName, '+10 minutes'); // 下载对象

		return [true, urlencode($url)];
	}
}
