<?php

// include_once dirname(__FILE__) . '/uploader.php';
// include_once dirname(__FILE__) . '/browser.php';
// include_once dirname(dirname(__FILE__)) . '/lib/class_input.php';
// include_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

/** 
 * 天翼云存储
 */
class browser_tyoos extends browser {

	public function __construct() {
		parent::__construct();
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
				die('获取容器错误' . $rst[1]);
			}
			$Buckets = $rst[1]['Buckets'];
			$dirs = [];
			foreach ($Buckets as $val) {
				$d = [];
				$d['name'] = $val['Name'];
				$d['readable'] = true;
				$d['writable'] = false;
				$d['removable'] = false;
				
				$d['hasDirs'] = $this->getDirsFromOss($OOS, $val['Name']);
				$dirs[] = (object) $d;
			}
		} else {
			$filter = new \stdClass;
			$filter->bucket = OOS_BUCKET;
			// $filter->prefix = $Prefix;
			$filter->size = 100;
			$rst = $OOS->objectList($filter);
			if ($rst[0] === false) {
				die('错误2' . $rst[1]);
			}
			$objects = $rst[1];
			$CommonPrefixes = $objects['CommonPrefixes'];
			//dirs
			$dirs = [];
			foreach ($CommonPrefixes as $val) {
				$d = [];
				$d['name'] = substr($val['Prefix'], 0, -1);
				$d['readable'] = true;
				$d['writable'] = false;
				$d['removable'] = false;
				
				$d['hasDirs'] = $this->getDirsFromOss($OOS, OOS_BUCKET, $val['Prefix']);
				$dirs[] = (object) $d;
			}
			// files
			$Contents = $objects['Contents'];
			// var_dump($Contents);die;
			$files = [];
			foreach ($Contents as $o) {
				$f['name'] = $o['Key'];
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
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$dirWritable = true;
		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->prefix = empty($this->post['dir']) ? '' : $this->post['dir'] . '/';
		$filter->size = 1000;
		$rst = $OOS->objectList($filter);
		if ($rst[0] === false) {
			die('错误3' . $rst[1]);
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
			$d['name'] = substr(str_replace($filter->prefix, '', $val['Prefix']), 0, -1);
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
	// 获取文件
	protected function act_chDir() {
		// 加载oos api
		include_once dirname(__FILE__) . '/oos.php';
		$OOS = new oos(OOS_ENDPOINT, OOS_ACCESS_KEY, OOS_ACCESS_SECRET);
		$dirWritable = true;

		$filter = new \stdClass;
		$filter->bucket = OOS_BUCKET;
		$filter->prefix = empty($this->post['dir']) ? '' : $this->post['dir'] . '/';
		$filter->size = 1000;
		$rst = $OOS->objectList($filter);
		if ($rst[0] === false) {
			die('错误3' . $rst[1]);
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
			$f['name'] = str_replace($filter->prefix, '', $o['Key']);
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
			die('错误4' . $file[1]);
		}
		$file = $file[1];

		// url
		$fileUrl = $OOS->getObjectUrl($filter);
		if ($fileUrl[0] === false) {
			die('错误5' . $fileUrl[1]);
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
