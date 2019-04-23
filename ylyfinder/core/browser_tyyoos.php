<?php

/** 
 * 天翼云存储
 */
class browser_tyyoos extends uploader {
	protected $action;

	public function __construct() {
		parent::__construct();
		
		if (isset($this->post['dir'])) {
			$dir = $this->checkInputDir($this->post['dir'], true, false);
			if ($dir === false) {
				unset($this->post['dir']);
			}

			$this->post['dir'] = $dir;
		}

		if (isset($this->get['dir'])) {
			$dir = $this->checkInputDir($this->get['dir'], true, false);
			if ($dir === false) {
				unset($this->get['dir']);
			}

			$this->get['dir'] = $dir;
		}
	}
	
	public function action() {
		if (empty($this->get['act'])) {
			$this->errorMsg('参数错误');
		}
		$act = $this->get['act'];
		if (!method_exists($this, "act_$act")) {
			$this->errorMsg('没有找到方法');
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

		if ($act == "tyyoos") {
			// open browser
			header("X-UA-Compatible: chrome=1");
			header("Content-Type: text/html; charset={$this->charset}");
		}

		$return = $this->$method();
		echo ($return === true)
		? '{}'
		: $return;
	}

	protected function act_tyyoos() {
		return $this->output();
	}

	protected function act_init() {
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';

		$tree = $this->getDirInfo($this->typeDir);
		$data = $this->getTree();
		$tree['dirs'] = $data->dirs;
		if (!is_array($tree['dirs']) || !count($tree['dirs'])) {
			unset($tree['dirs']);
		}

		$files = [];
		if (isset($data->files)) {
			$files = $data->files;
		}
		$dirWritable = true;
		$data = array(
			'tree' => &$tree,
			'files' => &$files,
			'dirWritable' => $dirWritable,
		);
		// var_dump($data);die;
		return json_encode($data);
	}
	// 获取目录结构
	protected function getTree($dir = '', $index = 0) {
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		if (!defined('OOS_BUCKET') || empty(OOS_BUCKET)) {
			$rst = $OOS->bucketList();
			if ($rst[0] === false) {
				$this->errorMsg('获取容器错误' . $rst[1]);
			}
			$Buckets = $rst[1]['Buckets'];
			$dirs = [];
			foreach ($Buckets as $val) {
				$d = [];
				$d['name'] = $val['Name'];
				$d['title'] = $val['Name'];
				$d['readable'] = true;
				$d['writable'] = false;
				$d['removable'] = false;
				
				$d['hasDirs'] = $this->getDirsFromOss($OOS, $val['Name']);
				$dirs[] = (object) $d;
			}
		} else {
			if (empty($this->session['mpid'])) {
				$this->errorMsg('未获取到用户ID');
			}
			// 查询条件
			$Prefix = $this->session['mpid'] . '/';
			$filter = new \stdClass;
			$filter->bucket = OOS_BUCKET;
			$filter->prefix = $Prefix;
			$filter->size = 100;
			$rst = $OOS->objectList($filter);
			if ($rst[0] === false) {
				$this->errorMsg('错误2' . $rst[1]);
			}
			$objects = $rst[1];
			//dirs
			$dirs = [];
			$CommonPrefixes = isset($objects['CommonPrefixes']) ? $objects['CommonPrefixes'] : [];
			foreach ($CommonPrefixes as $val) {
				$d = [];
				$name = substr(str_replace($filter->prefix, '', $val['Prefix']), 0, -1);
				$d['name'] = $name;
				$d['title'] = $name;
				$d['readable'] = true;
				$d['writable'] = false;
				$d['removable'] = false;
				
				$d['hasDirs'] = $this->getDirsFromOss($OOS, OOS_BUCKET, $val['Prefix']);
				$dirs[] = (object) $d;
			}
			// files
			$files = [];
			$Contents = isset($objects['Contents']) ? $objects['Contents'] : [];
			foreach ($Contents as $o) {
				$f['name'] = $o['Key'];
				$f['title'] = $o['Key'];
				$f['size'] = (int) $o['Size'];
				$lm = strtotime((string) $o['LastModified']);
				$f['mtime'] = $lm;
				$f['readable'] = false;
				$f['writable'] = false;
				$f['bigIcon'] = true;
				$f['smallIcon'] = true;
				$f['thumb'] = false;
				$f['smallthumb'] = false;
				$files[] = $f;
			}
		}

		// $mpid = $this->session['mpid'];
		$data = new \stdClass;
		$data->dirs = $dirs;
		isset($files) && $data->files = $files;
		return $data;
	}

	protected function getDirsFromOss($OOS, $bucket, $Prefix = '') {
		$filter = new \stdClass;
		$filter->bucket = $bucket;
		$filter->prefix = $Prefix;
		$filter->size = 100;
		$dirs = $OOS->objectList($filter);
		if ($dirs[0] === false) {
			return false;
		}
		$dirs = $dirs[1];
		// var_dump($dirs);
		if (!empty($dirs['CommonPrefixes'])) {
			return true; 
		} else {
			return false;
		}
	}
	// 获取下级目录
	protected function act_expand() {
		if (empty($this->session['mpid'])) {
			$this->errorMsg('未获取到用户ID');
		}
		if (empty($this->post['dir'])) {
			$this->errorMsg('未获取到目录参数');
		}

		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$dirWritable = true;

		//
		$Prefix = $this->session['mpid'] . '/' . $this->post['dir'] . '/';
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->prefix = $Prefix;
		$filter->size = 1000;
		$rst = $OOS->objectList($filter);
		if ($rst[0] === false) {
			$this->errorMsg('错误3' . $rst[1]);
		}
		$objects = $rst[1];
		if (empty($objects['CommonPrefixes'])) {
				return json_encode(array(
				'dirs' => [],
			));
		}
		$CommonPrefixes = $objects['CommonPrefixes'];
		$dirs = [];
		foreach ($CommonPrefixes as $val) {
			$d = [];
			$name = substr(str_replace($filter->prefix, '', $val['Prefix']), 0, -1);
			$d['name'] = $name;
			$d['title'] = $name;
			$d['readable'] = false;
			$d['writable'] = false;
			$d['removable'] = false;
			
			$d['hasDirs'] = $this->getDirsFromOss($OOS, OOS_BUCKET, $val['Prefix']);
			$dirs[] = (object) $d;
		}

		return json_encode(array(
			'dirs' => $dirs,
		));
	}
	// 获取文件列表
	protected function act_chDir() {
		if (empty($this->session['mpid'])) {
			$this->errorMsg('未获取到用户ID');
		}
		if (empty($this->post['dir'])) {
			return json_encode(array(
				'files' => [],
				'dirWritable' => true,
			));
		}
		
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$dirWritable = true;

		//
		$Prefix = $this->session['mpid'] . '/' . $this->post['dir'] . '/';
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->prefix = $Prefix;
		$filter->size = 1000;
		$rst = $OOS->objectList($filter);
		if ($rst[0] === false) {
			$this->errorMsg('错误3' . $rst[1]);
		}
		$objects = $rst[1];
		if (empty($objects['Contents'])) {
				return json_encode(array(
					'files' => [],
					'dirWritable' => $dirWritable,
				));
		}
		// files
		$Contents = $objects['Contents'];
		$files = [];
		foreach ($Contents as $o) {
			$name = str_replace($filter->prefix, '', $o['Key']);
			$f['name'] = $name;
			$f['title'] = $name;
			$f['size'] = (int) $o['Size'];
			$lm = strtotime((string) $o['LastModified']);
			$f['mtime'] = $lm;
			$f['date'] = date('Y-m-d H:i:s', $lm);
			$f['readable'] = false;
			$f['writable'] = false;
			$f['bigIcon'] = true;
			$f['smallIcon'] = true;
			$f['thumb'] = false;
			$f['xiazai'] = true;
			$f['smallthumb'] = false;
			$files[] = $f;
		}

		return json_encode(array(
			'files' => $files,
			'dirWritable' => $dirWritable,
		));
	}
	// 以指定名称开头的文件列表
	protected function act_searchDir() {
		if (empty($this->session['mpid'])) {
			$this->errorMsg('未获取到用户ID');
		}
		if (empty($this->post['begin'])) {
			$this->errorMsg('搜索项不能为空');
		}
		if (empty($this->post['dir'])) {
			return json_encode(array(
				'files' => [],
				'dirWritable' => true,
			));
		}
		
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$dirWritable = true;
		//
		$Prefix = $this->session['mpid'] . '/' . $this->post['dir'] . '/';
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->prefix = $Prefix . $this->post['begin'];
		$filter->size = 1000;
		$rst = $OOS->objectList($filter);
		if ($rst[0] === false) {
			$this->errorMsg('错误3' . $rst[1]);
		}
		$objects = $rst[1];
		if (empty($objects['Contents'])) {
				return json_encode(array(
					'files' => [],
					'dirWritable' => $dirWritable,
				));
		}
		// files
		$Contents = $objects['Contents'];
		$files = [];
		foreach ($Contents as $o) {
			$name = str_replace($Prefix, '', $o['Key']);
			$f['name'] = $name;
			$f['title'] = $name;
			$f['size'] = (int) $o['Size'];
			$lm = strtotime((string) $o['LastModified']);
			$f['mtime'] = $lm;
			$f['date'] = date('Y-m-d H:i:s', $lm);
			$f['readable'] = false;
			$f['writable'] = false;
			$f['bigIcon'] = true;
			$f['smallIcon'] = true;
			$f['thumb'] = false;
			$f['xiazai'] = true;
			$f['smallthumb'] = false;
			$files[] = $f;
		}

		return json_encode(array(
			'files' => $files,
			'dirWritable' => $dirWritable,
		));
	}
	// get object
	protected function act_getFile() {
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->fileName = $this->get['dir'] . '/' . $this->get['file'];
		$file = $OOS->getObject($filter);
		if ($file[0] === false) {
			$this->errorMsg('错误4' . $file[1]);
		}
		$file = $file[1];

		// url
		$fileUrl = $OOS->getObjectUrl($filter);
		if ($fileUrl[0] === false) {
			$this->errorMsg('错误5' . $fileUrl[1]);
		}
		$fileUrl = $fileUrl[1];

		$data = [];
		$data['file'] = $file;
		$data['url'] = urlencode($fileUrl);

		return json_encode($data);
	}
	/**
	 *
	 */
	protected function getDirInfo($dir, $removable = false, $skipEncoding = false) {
		$info = array(
			'name' => stripslashes($this->my_basename($dir)),
			'title' => '文件夹',
			'readable' => false,
			'writable' => false,
			'removable' => false,
			'notrefresh' => true,
			'hasDirs' => true,
		);

		if ($dir == "{$this->config['uploadDir']}/{$this->session['dir']}") {
			$info['current'] = true;
		}

		return $info;
	}
	//
	protected function act_thumb() {
        echo '';
	}
	// xiazai
	protected function act_download() {
		if (empty($this->session['mpid'])) {
			$this->errorMsg('未获取到用户ID');
		}

		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);

		//
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->fileName = $this->session['mpid'] . '/' . $this->post['dir'] . '/' . $this->post['file'];
		$file = $OOS->getObject($filter);
		if ($file[0] === false) {
			$this->errorMsg('错误4' . $file[1]);
		}
		$file = $file[1];

		// url
		$fileUrl = $OOS->getObjectUrl($filter);
		if ($fileUrl[0] === false) {
			$this->errorMsg('错误5' . $fileUrl[1]);
		}
		$fileUrl = $fileUrl[1];

		header("Content-type: " . $file['ContentType']);
		header("Accept-Ranges:bytes");
		header("Accept-Length: " . $file['ContentLength']);
		header("Content-Disposition:attachment;filename=" . $this->post['file']);
		readfile($fileUrl);
		exit();
	}
	// 预览
	protected function act_view() {
		if (empty($this->session['mpid'])) {
			$this->errorMsg('未获取到用户ID');
		}
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);

		//
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->fileName = $this->session['mpid'] . '/' . $this->post['dir'] . '/' . $this->post['file'];
		// url
		$fileUrl = $OOS->getObjectUrl($filter);
		if ($fileUrl[0] === false) {
			$this->errorMsg('错误5' . $fileUrl[1]);
		}
		$fileUrl = $fileUrl[1];

		return $fileUrl;
	}
	// 输出页面
	protected function output($data = null, $template = null) {
		if (!is_array($data)) {
			$data = array();
		}

		if ($template === null) {
			$template = $this->action;
		}

		if (file_exists("tpl/tpl_$template.php")) {
			ob_start();
			$eval = "unset(\$data);unset(\$template);unset(\$eval);";
			$_ = $data;
			foreach (array_keys($data) as $key) {
				if (preg_match('/^[a-z\d_]+$/i', $key)) {
					$eval .= "\$$key=\$_['$key'];";
				}
			}

			$eval .= "unset(\$_);require \"tpl/tpl_$template.php\";";
			eval($eval);
			return ob_get_clean();
		}

		return "";
	}
	// 输出错误信息
	protected function errorMsg($message, array $data = null) {
		if (in_array($this->action, array("thumb", "upload", "download", "downloadDir"))) {
			die($this->label($message, $data));
		}

		if (($this->action === null) || ($this->action == "browser")) {
			$this->backMsg($message, $data);
		} else {
			$message = $this->label($message, $data);
			die(json_encode(array('error' => $message)));
		}
	}
}
