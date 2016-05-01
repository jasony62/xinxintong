<?php
require_once dirname(dirname(__FILE__)) . '/lib/Savant3.php';

class MySavant3 extends Savant3 {

	public function __construct($config = null) {
		parent::__construct($config);
	}
	/**
	 * 引入全局CSS
	 */
	public function global_css() {
		$names = func_get_args();
		foreach ($names as $name) {
			$link = '<link rel="stylesheet" type="text/css"';
			$link .= ' href="/static/css/' . $name . '.css"/>';
			echo $link . PHP_EOL;
		}
	}
	/**
	 * 引入CSS文件
	 */
	public function import_css($dir) {
		$current_uri = TMS_APP_URI . '/views/' . TMS_APP_VIEW_NAME . $dir;
		$names = func_get_args();
		$argnum = count($names);
		// 附加参数，是否刷新
		$fresh = is_bool($names[$argnum - 1]) ? $names[$argnum - 1] : false;
		$fresh && $argnum--;
		// 是否支持媒体查询参数
		$media = is_array($names[$argnum - 1]) ? $names[$argnum - 1][0] : false;
		$media && $argnum--;
		for ($i = 1; $i < $argnum; $i++) {
			$name = $names[$i];
			$link = '<link rel="stylesheet" type="text/css"';
			$media && $link .= " media='$media'";
			$link .= " href='$current_uri/$name.css";
			$fresh && $link .= '?_=' . time();
			$link .= "'/>";
			echo $link . PHP_EOL;
		}
	}
	/**
	 *
	 */
	public function global_js() {
		$args = func_get_args();
		if (is_array($args[0])) {
			foreach ($args as $arg) {
				$names = $arg;
				$argnum = count($names);
				// 是否需要强制刷新
				$fresh = is_bool($names[$argnum - 1]) ? $names[$argnum - 1] : false;
				$fresh && $argnum--;
				for ($i = 0; $i < $argnum; $i++) {
					$name = $names[$i];
					$script = '<script type="text/javascript"';
					if ($fresh) {
						$script .= ' src="/static/js/' . $name . '.js?_=' . time() . '"></script>';
					} else {
						$script .= ' src="/static/js/' . $name . '.js"></script>';
					}

					echo $script . PHP_EOL;
				}
			}
		} else {
			$names = $args;
			$argnum = count($names);
			// 是否需要强制刷新
			$fresh = is_bool($names[$argnum - 1]) ? $names[$argnum - 1] : false;
			$fresh && $argnum--;
			for ($i = 0; $i < $argnum; $i++) {
				$name = $names[$i];
				$script = '<script type="text/javascript"';
				if ($fresh) {
					$script .= ' src="/static/js/' . $name . '.js?_=' . time() . '"></script>';
				} else {
					$script .= ' src="/static/js/' . $name . '.js"></script>';
				}

				echo $script . PHP_EOL;
			}
		}
	}
	/**
	 * 引入本地JS文件
	 * 第一个参数是JS文件所在的目录
	 * 如果最后一个参数是boolean类型，代表是否要刷新（可选）
	 * 其他参数是JS的文件名
	 */
	public function import_js($dir) {
		$current_uri = TMS_APP_URI . '/views/' . TMS_APP_VIEW_NAME . $dir;
		$names = func_get_args();
		$argnum = count($names);
		// 是否需要强制刷新
		$fresh = is_bool($names[$argnum - 1]) ? $names[$argnum - 1] : false;
		$fresh && $argnum--;
		for ($i = 1; $i < $argnum; $i++) {
			$name = $names[$i];
			$script = '<script type="text/javascript"';
			if ($fresh) {
				$script .= ' src="' . $current_uri . '/' . $name . '.js?_=' . time() . '"></script>';
			} else {
				$script .= ' src="' . $current_uri . '/' . $name . '.js"></script>';
			}

			echo $script . PHP_EOL;
		}
	}
}

class TPL {
	public static $template_ext = '.tpl.htm';
	public static $view;
	public static $output_matchs;
	/**
	 * start position of finding template.
	 */
	public static $template_path;

	public static function init() {
		if (!is_object(self::$view)) {
			self::$template_path = realpath(TMS_APP_DIR . '/views/');
			self::$view = new MySavant3(
				array(
					'template_path' => array(self::$template_path),
					'exceptions' => true,
				)
			);
		}

		return self::$view;
	}

	public static function output($template_filename, $display = true) {
		self::init();

		if (!strstr($template_filename, self::$template_ext)) {
			$template_filename .= self::$template_ext;
		}

		$display_template_filename = TMS_APP_VIEW_NAME . '/' . $template_filename;

		self::assign('template_name', TMS_APP_VIEW_NAME);

		$output = self::$view->getOutput($display_template_filename);
		if ($display) {
			echo $output;
		} else {
			return $output;
		}
	}

	public static function set_meta($tag, $value) {
		self::init();
		self::assign('_meta_' . $tag, $value);
	}

	public static function assign($name, $value) {
		self::init();
		self::$view->$name = $value;
	}

	public static function val($name) {
		self::init();
		return isset(self::$view->$name) ? self::$view->$name : false;
	}

	public static function pt($name) {
		echo self::val($name);
	}

	public static function et($str, $escapemode = 'html') {
		echo $str;
	}

	public static function gt($str, $escapemode = 'html') {
		return $str;
	}

	public static function fetch($template_filename) {
		self::init();

		if (self::$in_app) {
			if (get_setting('ui_style') != TMS_APP_VIEW_NAME) {
				$custom_template_file = self::$template_path . '/' . get_setting('ui_style') . '/' . $template_filename . self::$template_ext;

				if (file_exists($custom_template_file)) {
					return file_get_contents($custom_template_file);
				}
			}
		}

		return file_get_contents(self::$template_path . '/' . TMS_APP_VIEW_NAME . '/' . $template_filename . self::$template_ext);
	}

	public static function is_output($output_filename, $template_filename) {
		if (!isset(self::$output_matchs[md5($template_filename)])) {
			preg_match_all("/TPL::output\(['|\"](.+)['|\"]\)/i", self::fetch($template_filename), $matchs);

			self::$output_matchs[md5($template_filename)] = $matchs[1];
		}

		if (is_array($output_filename)) {
			foreach ($output_filename as $key => $val) {
				if (!in_array($val, self::$output_matchs[md5($template_filename)])) {
					return false;
				}
			}

			return true;
		} else
		if (in_array($output_filename, self::$output_matchs[md5($template_filename)])) {
			return true;
		}

		return false;
	}
	/**
	 * 获得session中保存的数据
	 *
	 * $name
	 */
	public static function S($name) {
		return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
	}
	/**
	 * 获得model对象
	 *
	 * $path
	 */
	public static function M($path) {
		return TMS_APP::model($path);
	}
	/**
	 *
	 */
	private static function deepUrlencode($data) {
		$data2 = array();
		foreach ($data as $k => $v) {
			if (is_object($v) || is_array($v)) {
				$v2 = self::deepUrlencode($v);
				$data2[$k] = $v2;
			} else {
				$data2[$k] = urlencode($v);
			}

		}

		return $data2;
	}
	/**
	 *
	 */
	public static function json($data) {
		return json_encode(self::deepUrlencode($data));
	}
}