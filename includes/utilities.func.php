<?php

class Convert2Pinyin {
	private $_dat = 'py.dat';
	private $_fd = false;

	public function __construct($pdat = '') {
		if ('' != $pdat) {
			$this->_dat = $pdat;
		}

	}

	function load($pdat = '') {
		if ('' == $pdat) {
			$pdat = $this->_dat;
		}

		$this->unload();
		$dir = dirname(__FILE__);
		$this->_fd = @fopen($dir . '/' . $pdat, 'rb');
		if (!$this->_fd) {
			die("unable to load PinYin data file `$pdat`");
		}
		return true;
	}

	function unload() {
		if ($this->_fd) {
			@fclose($this->_fd);
			$this->_fd = false;
		}
	}

	function get($zh) {
		if (strlen($zh) != 2) {
			trigger_error("`$zh` is not a valid GBK hanzi-1", E_USER_WARNING);
			return false;
		}

		if (!$this->_fd && !$this->load()) {
			die("load py.dat failed.");
		}

		$high = ord($zh[0]) - 0x81;
		$low = ord($zh[1]) - 0x40;

		// 计算偏移位置
		$off = ($high << 8) + $low - ($high * 0x40);
		// 判断 off 值
		if ($off < 0) {
			trigger_error("`$zh` is not a valid GBK hanzi-2", E_USER_WARNING);
			return false;
		}

		fseek($this->_fd, $off * 8, SEEK_SET);
		$ret = fread($this->_fd, 8);
		$ret = unpack('a8py', $ret);

		return $ret['py'];
	}

	function _Convert2Pinyin() {
		$this->_unload();
	}
}

function pinyin($zhongwen, $encode = 'GBK') {
	if ($encode == 'UTF-8') {
		$zhongwen_gbk = mb_convert_encoding($zhongwen, 'GBK', 'UTF-8');
	} else {
		$zhongwen_gbk = $zhongwen;
	}
	$oCpy = new Convert2Pinyin;
	$words = '';
	$len = strlen($zhongwen_gbk); //length of bytes(x2).
	for ($i = 0; $i < $len; $i++) {
		$code = ord($zhongwen_gbk[$i]);
		if ($code > 0x80) {
			// >128
			$hanzi = substr($zhongwen_gbk, $i, 2);
			if ($xx = $oCpy->get($hanzi)) {
				$words[] = $xx;
				//} else {
				///    die("error - i:$i,code:$code,len:$len,xx:$xx,hanzi:$hanzi,zhongwen:$zhongwen");
			}
			$i++;
		} else {
			$words[] = $zhongwen_gbk[$i];
		}
	}
	$oCpy->unload();

	return implode(',', $words);
}
/**
 *
 */
function AppLog($message) {
	error_log("\n" . date('[Y-m-d H:i:s] - ') . $message, 3, 'logs/app.log');
}
