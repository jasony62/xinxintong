<?php
class TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'debug';

		return $rule_action;
	}
	/**
	 *
	 */
	public function hello_action() {
		die('hello.');
	}
	/**
	 *
	 */
	protected function getRequestUrl() {
		$url[] = strtolower(strtok($_SERVER['SERVER_PROTOCOL'], '/'));
		$url[] = '://';
		$url[] = $_SERVER['HTTP_HOST'];
		$url[] = $_SERVER['REQUEST_URI'];

		return implode('', $url);
	}
	/**
	 * 前端跳转到指定页面
	 */
	protected function redirect($path) {
		if (false !== strpos($path, 'http')) {
			$url = $path;
		} else {
			$url = 'http://' . $_SERVER['HTTP_HOST'];
			$url .= $path;
		}
		header("Location: $url");
		exit;
	}
	/**
	 *
	 */
	protected function getPost($name, $default = null) {
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}
	/**
	 *
	 */
	protected function getGet($name, $default = null) {
		return isset($_GET[$name]) ? $_GET[$name] : $default;
	}
	/**
	 *
	 */
	protected function &getPostJson() {
		$json = file_get_contents("php://input");
		$obj = json_decode($json);
		return $obj;
	}
	/**
	 * 设置 COOKIE
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param string $secure
	 */
	protected function mySetCookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false) {
		if (!$domain and G_COOKIE_DOMAIN) {
			$domain = G_COOKIE_DOMAIN;
		}
		return setcookie(G_COOKIE_PREFIX . $name, $value, $expire, $path, $domain, $secure);
	}
	/**
	 * 获取cookie的值
	 */
	protected function myGetCookie($name) {
		$cookiename = G_COOKIE_PREFIX . $name;
		if (isset($_COOKIE[$cookiename])) {
			return $_COOKIE[$cookiename];
		}

		return false;
	}
	/**
	 *
	 */
	public function model() {
		$args = func_get_args();
		return call_user_func_array(array('TMS_APP', "model"), $args);
	}
	/**
	 * 获得URL对应的view
	 *
	 * 查找规则：
	 * 从视图模板的根目录开始查找
	 * URL的第一个segment是否有对应的模板文件？
	 * 有：结束查找，第一个段之后的部分转换为参数
	 * 无：这个段是否有对应的目录？
	 * 有：将这个目录设置为当前目录，查找是否有和第二个端匹配的view
	 * 无：是否有缺省的模板文件？
	 * 有：缺省文件为当前的view，剩余的URL都为参数
	 * 无：找不到匹配的view
	 *
	 */
	public function view_action($path) {
		// view
		$view = $this->load_view($path);
		// template's parameters
		if ($params = isset($view['params']) ? $view['params'] : array()) {
			foreach ($params as $k => $v) {
				TPL::assign($k, $v);
			}
		}
		TPL::output($view['template']);
		exit;
	}
	/**
	 * 获得URL对应的view
	 *
	 * view查找规则：
	 * 从视图模板的根目录开始查找
	 * URL的第一个segment是否有对应的模板文件？
	 * 有：结束查找，第一个段之后的部分转换为参数
	 * 无：这个段是否有对应的目录？
	 * 有：将这个目录设置为当前目录，查找是否有和第二个端匹配的view
	 * 无：是否有缺省的模板文件？
	 * 有：缺省文件为当前的view，剩余的URL都为参数
	 * 无：找不到匹配的view
	 *
	 * 参数组合规则：
	 *
	 */
	protected function load_view($path) {
		//
		$vd = '/views/default';
		$postfix = 'vw.php';
		$default_view = 'main';
		$segments = explode('/', substr($path, 1));
		// search dir
		$searched_dir = TMS_APP_DIR . $vd;
		foreach ($segments as $index => $segment) {
			if (!is_dir($searched_dir . '/' . $segment)) {
				break;
			}
			$searched_dir .= '/' . $segment;
		}
		$viewfile_index = $index; // assumed view's index in segments
		$viewdir = $searched_dir; // assumed view's dir
		// search file
		do {
			if (isset($segments[$viewfile_index])) {
				// assigned view's name
				if (file_exists("$viewdir/{$segments[$viewfile_index]}.$postfix")) {
					$viewfile = "$viewdir/{$segments[$viewfile_index]}.$postfix";
				} elseif (file_exists("$viewdir/$default_view.$postfix")) {
					$viewfile = "$viewdir/$default_view.$postfix";
				}
			} else {
				// not assigned view's name, test default view file.
				// if not found view file, then current search dir is not a real view's dir
				if (file_exists("$viewdir/$default_view.$postfix")) {
					$viewfile = "$viewdir/$default_view.$postfix";
				}
			}
			// found file
			if (isset($viewfile)) {
				break;
			}

			/**
			 * test parent segment
			 */
			$viewfile_index--;
			$viewfile_index >= 0 && $viewdir = str_replace('/' . $segments[$viewfile_index], '', $viewdir);
		} while (empty($viewfile) && $viewfile_index >= 0);
		// parameters
		if ($params = array_slice($segments, $viewfile_index + 1)) {
			for ($i = 0, $l = count($params); ($i + 1) < $l; $i += 2) {
				$_GET[$params[$i]] = $params[++$i];
			}
		}
		//
		if (isset($viewfile)) {
			include $viewfile;
		} else {
			throw new Exception("view '$path' not exist.");
		}

		return $view;
	}
	/**
	 *
	 */
	private function view_dir($path, $start) {
		$test = $start;
		foreach ($segments as $index => $segment) {
			if (!is_dir($test . '/' . $segment)) {
				break;
			}
			$test .= '/' . $segment;
		}
		return array(--$index, $test);
	}
	/**
	 *
	 */
	protected function client_ip() {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
			$this->valid_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip_address = $_SERVER['REMOTE_ADDR'];
		} else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		if ($ip_address === false) {
			$ip_address = '0.0.0.0';
			return $ip_address;
		}
		if (strstr($ip_address, ',')) {
			$x = explode(',', $ip_address);
			$ip_address = end($x);
		}
		return $ip_address;
	}
	/**
	 *
	 */
	private function valid_ip($ip) {
		$ip_segments = explode('.', $ip);
		// Always 4 segments needed
		if (count($ip_segments) != 4) {
			return false;
		}
		// IP can not start with 0
		if (substr($ip_segments[0], 0, 1) == '0') {
			return false;
		}
		// Check each segment
		foreach ($ip_segments as $segment) {
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if (preg_match("/[^0-9]/", $segment) ||
				$segment > 255 || strlen($segment) > 3) {
				return false;
			}
		}
		return true;
	}
}