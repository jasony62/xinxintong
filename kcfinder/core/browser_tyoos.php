<?php

// include_once dirname(__FILE__) . '/uploader.php';
// include_once dirname(__FILE__) . '/browser.php';
// include_once dirname(dirname(__FILE__)) . '/lib/class_input.php';
// include_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

/** 
 * 天翼云存储
 */
class browser_tyoos extends browser {
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
		parent::__construct();

		$this->endpoint = $endpoint;
		$this->accessKey = $accessKey;
		$this->accessSecret = $accessSecret;
	}
	/**
	 * 链接天翼云接口
	 */
	private function _S2AMZ() {
		//创建S3 client 对象
include_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';
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
	private function _disposeData($data) {
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
	private function _bucketList() {
		$buckets = $this->S2AMZ()->listBuckets();
		$data = $this->disposeData($buckets);

		return [true, $data];
	}
	/**
	 * 容器列表
	 */
	private function _objectList($post) {
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
	private function _getObject($post) {
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
	private function _getObjectUrl($post) {
		if (empty($post->bucket) || empty($post->fileName)) {
			return [false, "未找到bucket或文件名"];
		}

		$url = $this->S2AMZ()->getObjectUrl($post->bucket, $post->fileName, '+10 minutes'); // 下载对象

		return [true, urlencode($url)];
	}

	public function action() {
		$act = isset($this->get['act']) ? $this->get['act'] : "ylylisten";
		if (!method_exists($this, "act_$act")) {
			$act = "ylylisten";
		}

		$this->action = $act;
		$method = "act_$act";

		if ($this->config['disabled']) {
			$message = $this->label("You don't have permissions to browse server.");
			if (in_array($act, array("browser", "upload")) ||
				(substr($act, 0, 8) == "download")
			) {
				$this->backMsg($message);
			} else {
				header("Content-Type: text/plain; charset={$this->charset}");
				die(json_encode(array('error' => $message)));
			}
		}

		if (!isset($this->session['dir'])) {
			$this->session['dir'] = $this->type;
		} else {
			$type = $this->getTypeFromPath($this->session['dir']);
			$dir = $this->config['uploadDir'] . "/" . $this->session['dir'];
			if (($type != $this->type) || !is_dir($this->toLocalEncoding($dir)) || !is_readable($this->toLocalEncoding($dir))) {
				$this->session['dir'] = $this->type;
			}

		}
		$this->session['dir'] = path::normalize($this->session['dir']);

		if ($act == "ylylisten") {
			// open browser
			header("X-UA-Compatible: chrome=1");
			header("Content-Type: text/html; charset={$this->charset}");
		} elseif (
			(substr($act, 0, 8) != "download") &&
			!in_array($act, array("thumb", "upload"))
		) {
			header("Content-Type: text/plain; charset={$this->charset}");
		}

		$return = $this->$method();
		echo ($return === true)
		? '{}'
		: $return;
	}

	protected function act_ylylisten() {
		if (isset($this->get['dir']) &&
			is_dir("{$this->typeDir}/{$this->get['dir']}") &&
			is_readable("{$this->typeDir}/{$this->get['dir']}")
		) {
			$this->session['dir'] = path::normalize("{$this->type}/{$this->get['dir']}");
		}

		return $this->output();
	}

	protected function act_init() {
		var_dump($this->typeDir);die;
		$tree = $this->getDirInfo($this->typeDir);
		$tree['dirs'] = $this->getTree($this->session['dir']);
		if (!is_array($tree['dirs']) || !count($tree['dirs'])) {
			unset($tree['dirs']);
		}

		$files = $this->getFiles($this->session['dir']);
		//$dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}");
		$dirWritable = true;
		$data = array(
			'tree' => &$tree,
			'files' => &$files,
			'dirWritable' => $dirWritable,
		);
		return json_encode($data);
	}

	protected function act_chDir() {
		$this->postDir(); // Just for existing check
		if (empty($this->post['dir'])) {
			$this->session['dir'] = $this->type;
		} else {
			$this->session['dir'] = $this->type . "/" . $this->post['dir'];
		}

		$dirWritable = true;
		return json_encode(array(
			'files' => $this->getFiles($this->session['dir']),
			'dirWritable' => $dirWritable,
		));
	}

	protected function act_thumb() {
		//$this->getDir($this->get['dir'], true);
		if (!isset($this->get['file']) || !isset($this->get['dir'])) {
			$this->sendDefaultThumb();
		}

		$file = $this->get['file'];
		if ($this->my_basename($file) != $file) {
			$this->sendDefaultThumb();
		}

		$mpid = $this->session['mpid'];
		$thumb = "$mpid/{$this->config['thumbsDir']}/{$this->type}";
		!empty($this->get['dir']) && $thumb .= '/' . $this->get['dir'];
		$thumb .= '/' . $file;

		$bucket = 'xinxintong';
		$alioss = $this->get_alioss();
		$rsp = $alioss->get_object($bucket, $thumb);

		header("Content-Type: image/jpeg");
		die($rsp->body);
	}

	protected function act_download() {
		$dir = $this->postDir();
		if (!isset($this->post['dir']) ||
			!isset($this->post['file']) ||
			(false === ($file = "$dir/{$this->post['file']}"))) {
			$this->errorMsg("Unknown error.");
		}

		$bucket = 'xinxintong';
		$alioss = $this->get_alioss();
		$rsp = $alioss->get_object($bucket, $file);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/octet-stream");
		header('Content-Disposition: attachment; filename="' . $this->post['file'] . '"');
		header("Content-Transfer-Encoding:­ binary");
		header("Content-Length: " . (string) $rsp->header['content-length']);
		die($rsp->body);
	}
	
	protected function getFiles($dir) {
		$files = array();
		$bucket = 'xinxintong';
		$mpid = $this->session['mpid'];
		$prefix = "$mpid/$dir/";
		$options = array(
			'prefix' => $prefix,
			'delimiter' => '/',
			'max-keys' => 100);

		$alioss = $this->get_alioss();
		$rsp = $alioss->list_object($bucket, $options);
		$xmlBody = simplexml_load_string($rsp->body);
		$objects = $xmlBody->Contents;
		foreach ($objects as $o) {
			if ($o->Key == $prefix) {
				continue;
			}
			$f['name'] = str_replace($prefix, '', $o->Key);
			$f['size'] = (int) $o->Size;
			$lm = strtotime((string) $o->LastModified);
			$f['mtime'] = $lm;
			$f['date'] = @strftime($this->dateTimeSmall, $lm);
			$f['readable'] = true;
			$f['writable'] = true;
			$f['bigIcon'] = true;
			$f['smallIcon'] = true;
			$f['thumb'] = true;
			$f['smallthumb'] = false;
			$files[] = $f;
		}
		return $files;
	}

	protected function getTree($dir, $index = 0) {
		$mpid = $this->session['mpid'];
		$prefix = $mpid . '/' . $dir . '/';

		return $this->getDirsFromOss($prefix);
	}

	protected function getDirs($dir) {
		$mpid = $this->session['mpid'];
		$root_dir = $mpid . '/' . $this->type; // hidden dir.
		$working_dir = $this->post['dir'];
		if (empty($working_dir)) {
			$prefix = "$root_dir/";
		} else {
			$prefix = "$root_dir/$working_dir/";
		}

		return $this->getDirsFromOss($prefix);
	}
	/**
	 *
	 */
	protected function getDirInfo($dir, $removable = false, $skipEncoding = false) {
		$info = array(
			'name' => stripslashes($this->my_basename($dir)),
			'readable' => true,
			'writable' => true,
			'removable' => true,
			'hasDirs' => true,
		);

		if ($dir == "{$this->config['uploadDir']}/{$this->session['dir']}") {
			$info['current'] = true;
		}

		return $info;
	}
}
