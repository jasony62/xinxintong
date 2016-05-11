<?php

/** This file is part of KCFinder project
 *
 *      @desc Browser actions class
 *   @package KCFinder
 *   @version 2.51
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010, 2011 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @link http://kcfinder.sunhater.com
 */

class browser_alioss extends browser {
	protected $action;
	protected $thumbsDir;
	protected $thumbsTypeDir;

	public function __construct() {
		parent::__construct();
	}

	protected function initThumbsDir($thumbsDir) {
	}

	protected function act_init() {
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
		$this->session['dir'] = $this->type . "/" . $this->post['dir'];
		$dirWritable = true;
		return json_encode(array(
			'files' => $this->getFiles($this->session['dir']),
			'dirWritable' => $dirWritable,
		));
	}

	protected function act_newDir() {
		$bucket = 'xinxintong';
		$mpid = $this->session['mpid'];
		$root_dir = $mpid . '/' . $this->type; // hidden dir.
		$parent_dir = $this->post['dir'] ? $this->post['dir'] . '/' : ''; // user select dir.

		$newDir = $this->normalizeDirname(trim($this->post['newDir']));

		$dir = $root_dir . '/' . $parent_dir . $newDir;

		$alioss = $this->get_alioss();
		$rsp = $alioss->create_object_dir($bucket, $dir);
		if ($rsp->status != 200) {
			die(json_encode(simplexml_load_string($rsp->body)));
		}

		return true;
	}

	protected function act_deleteDir() {
		if (!$this->config['access']['dirs']['delete'] ||
			!isset($this->post['dir']) ||
			!strlen(trim($this->post['dir']))) {
			$this->errorMsg("Unknown error.");
		}

		$bucket = 'xinxintong';
		$mpid = $this->session['mpid'];

		$object = "$mpid/$this->type/{$this->post['dir']}/";

		$alioss = $this->get_alioss();
		$rsp = $alioss->delete_object($bucket, $object);

		if ($rsp->status != 200) {
			die(json_encode($rsp->body));
		}

		return true;
	}

	protected function act_upload() {
		if (!$this->config['access']['files']['upload'] ||
			!isset($this->post['dir'])
		) {
			$this->errorMsg("Unknown error.");
		}

		$dir = $this->postDir();

		if (is_array($this->file['name'])) {
			$return = array();
			foreach ($this->file['name'] as $i => $name) {
				$return[] = $this->moveUploadFile(array(
					'name' => $name,
					'tmp_name' => $this->file['tmp_name'][$i],
					'error' => $this->file['error'][$i],
				), $dir);
			}
			return implode("\n", $return);
		} else {
			return $this->moveUploadFile($this->file, $dir);
		}

	}

	protected function act_delete() {
		if (!$this->config['access']['files']['delete'] ||
			!isset($this->post['dir']) ||
			!isset($this->post['file'])) {
			$this->errorMsg("Unknown error.");
		}

		$bucket = 'xinxintong';
		$dir = $this->postDir();

		$file = "$dir/{$this->post['file']}";
		$mpid = strtok($file, '/');
		$thumb = str_replace($mpid, "$mpid/{$this->config['thumbsDir']}", $file);
		$alioss = $this->get_alioss();
		$rsp = $alioss->delete_object($bucket, $file);
		$rsp = $alioss->delete_object($bucket, $thumb);

		if ($rsp->status != 200) {
			die(json_encode($rsp));
		}

		return true;
	}
	/**
	 * 批量删除文件
	 */
	protected function act_rm_cbd() {
		if (!$this->config['access']['files']['delete'] ||
			!isset($this->post['files']) ||
			!is_array($this->post['files']) ||
			!count($this->post['files'])
		) {
			$this->errorMsg("Unknown error.");
		}

		$dir = $this->postDir();
		$mpid = strtok($dir, '/');
		$bucket = 'xinxintong';
		$alioss = $this->get_alioss();

		$error = array();
		foreach ($this->post['files'] as $file) {
			$file = "$mpid/$file";
			$thumb = str_replace($mpid, "$mpid/{$this->config['thumbsDir']}", $file);
			$rsp = $alioss->delete_object($bucket, $file);
			$rsp = $alioss->delete_object($bucket, $thumb);
			if ($rsp->status != 200) {
				$error[] = $rsp->body;
			}

		}
		if (count($error)) {
			die(json_encode($error));
		}

		return true;
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
		$thumb = "$mpid/{$this->config['thumbsDir']}/{$this->type}/{$this->get['dir']}/$file";
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

	protected function moveUploadFile($file, $dir) {
		$message = $this->checkUploadedFile($file);

		if ($message !== true) {
			if (isset($file['tmp_name'])) {
				@unlink($file['tmp_name']);
			}

			return "{$file['name']}: $message";
		}

		$bucket = 'xinxintong';
		$filename = $this->normalizeFilename($file['name']);
		$target = "$dir/$filename";
		$fileUpload = $file['tmp_name'];

		$alioss = $this->get_alioss();
		$rsp = $alioss->upload_file_by_file($bucket, $target, $fileUpload);

		$this->makeThumb2($target, $fileUpload);

		return "/" . $this->my_basename($target);
	}

	protected function makeThumb2($target, $fileUpload = null, $overwrite = true) {
		$gd = new gd($fileUpload);

		// Drop files which are not GD handled images
		if ($gd->init_error) {
			return true;
		}

		$mpid = strtok($target, '/');
		$thumb = str_replace($mpid, "$mpid/{$this->config['thumbsDir']}", $target);
		$thumb = path::normalize($thumb);

		//if (!$overwrite && is_file($thumb))
		//    return true;
		// Resize image
		if (!$gd->resize_fit($this->config['thumbWidth'], $this->config['thumbHeight'])) {
			return false;
		}
		// Save thumbnail
		if (defined('SAE_TMP_PATH')) {
			$temp = SAE_TMP_PATH . uniqid();
		} else {
			$temp = tempnam(sys_get_temp_dir(), uniqid());
		}
		if (!$gd->imagejpeg($temp, $this->config['jpegQuality'])) {
			@unlink($temp);
			return false;
		}

		$bucket = 'xinxintong';
		$alioss = $this->get_alioss();
		$rsp = $alioss->upload_file_by_file($bucket, $thumb, $temp);
		@unlink($temp);

		return true;
	}

	private function &get_alioss() {
		require_once dirname(dirname(dirname(__FILE__))) . '/lib/ali-oss/sdk.class.php';
		$oss_sdk_service = new ALIOSS();

		//设置是否打开curl调试模式
		$oss_sdk_service->set_debug_mode(FALSE);

		return $oss_sdk_service;
	}

	/**
	 * $dir relatvie path from 'type', $this->session['dir']
	 */
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

	/**
	 * working dir's local full path.
	 */
	protected function postDir($existent = true) {
		$mpid = $this->session['mpid'];
		$type = $this->type;
		$dir = "$mpid/$type/{$this->post['dir']}";
		return $dir;
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

	protected function getDirsFromOss($prefix, $checkSubDir = true) {
		$bucket = 'xinxintong';
		$delimiter = '/';

		$alioss = $this->get_alioss();
		$options = array(
			'delimiter' => $delimiter,
			'prefix' => $prefix,
			'max-keys' => 100,
		);
		$rsp = $alioss->list_object($bucket, $options);
		$rsp->body = str_replace('&', '', $rsp->body);
		$xmlBody = simplexml_load_string($rsp->body);
		$objects = $xmlBody->CommonPrefixes;
		$dirs = array();
		foreach ($objects as $o) {
			if ($o->Prefix == $prefix) {
				continue;
			}
			$name = str_replace($prefix, '', $o->Prefix);
			$d['name'] = substr($name, 0, -1);
			$d['readable'] = true;
			$d['writable'] = true;
			$d['removable'] = true;
			/**
			 * get sub dirs
			 */
			if ($checkSubDir) {
				$subdirs = $this->getDirsFromOss($o->Prefix, false);
				$d['hasDirs'] = !empty($subdirs);
			} else {
				$d['hasDirs'] = true;
			}
			$dirs[] = $d;
		}

		return $dirs;
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