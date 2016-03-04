<?php
require_once dirname(dirname(__FILE__)) . '/includes/utilities.func.php';
require_once dirname(__FILE__) . '/utilities.cls.php';
require_once dirname(__FILE__) . '/tms_model.php';
require_once dirname(__FILE__) . '/tms_controller.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/template.php';
require_once dirname(__FILE__) . '/client.php';

class TMS_APP {
	//
	private static $models = array();
	/**
	 * 缓存全局变量
	 */
	private static $globals = array();
	//
	private static $app_dir = TMS_APP_DIR;
	private static $default_controller = 'main';
	private static $default_action = 'index';
	private static $default_view = 'main';
	private static $model_prefix = '_model';
	/**
	 * 实例化model
	 *
	 * model_path可以用'\'，'/'和'.'进行分割。用'\'代表namespace，用'/'代表目录，用'.'代表文件问题
	 * 例如：
	 * 1 - a/b/c，含义为：文件为a/b/c，类为c
	 * 2 - a/b/c.d，含义为：文件为a/b/c，类为c_d
	 */
	public static function &model($model_path = null) {
		if (!$model_path) {
			// 缺省的model实例
			$model_class = 'TMS_MODEL';
		} else {
			if (strpos($model_path, "\\")) {
				$model_class = $model_path;
				$model_file = preg_replace("/\\\\/", '/', $model_path);
			} else if (strpos($model_path, '/')) {
				$model_class = preg_replace('/^.*\//', '', $model_path);
				$model_file = $model_path;
			} else if (strpos($model_path, '.')) {
				$model_class = str_replace('.', '_', $model_path);
				$model_file = strstr($model_path, '.', true);
			} else {
				$model_class = $model_path;
				$model_file = $model_path;
			}
			if (strpos($model_file, self::$model_prefix)) {
				$model_file = strstr($model_path, self::$model_prefix, true);
			}

			if (false === strpos($model_class, self::$model_prefix)) {
				$model_class .= self::$model_prefix;
			}

		}
		//if (!isset(self::$models[$model_class])) {
		// no constructed class
		if (!class_exists($model_class)) {
			require_once dirname(dirname(__FILE__)) . '/models/' . $model_file . '.php';
		}
		$args = func_get_args();
		if (count($args) <= 1) {
			$model_obj = new $model_class();
		} else {
			$r = new ReflectionClass($model_class);
			$model_obj = $r->newInstanceArgs(array_slice($args, 1));
		}
		self::$models[$model_class] = $model_obj;
		//}

		return self::$models[$model_class];
	}
	/**
	 * 完成的任务
	 * 1、找到处理请求的controller
	 * 2、由controller处理请求
	 * 3、返回controller处理的结果
	 */
	public static function run($config) {
		global $__controller, $__action;
		$url = parse_url($_SERVER['REQUEST_URI']);
		$path = $url['path'];
		if (0 === strpos($path, '/site/')) {
			/*快速进入站点*/
			$short = substr($path, 6);
			$full = '/rest/site/fe?site=' . $short;
			header("Location: $full");
			exit;
		} else if (0 === strpos($path, TMS_APP_API_PREFIX . '/')) {
			$path = substr($path, strlen(TMS_APP_API_PREFIX));
			self::_request_api($path);
		} else if (0 === strpos($path, TMS_APP_VIEW_PREFIX . '/')) {
			$path = substr($path, strlen(TMS_APP_VIEW_PREFIX));
			self::_request_view($path);
		} else {
			/**
			 * 缺省情况下，跳转到管理端首页
			 */
			if (self::_authenticate()) {
				$path = TMS_APP_AUTHED;
				self::_request_api($path);
			}
		}
	}
	/**
	 * 除了API请求
	 *
	 * @param string $path
	 */
	private static function _request_api($path) {
		global $__controller, $__action;
		self::apiuri_to_controller($path);
		/**
		 * create controller.
		 */
		if (!$obj_controller = self::create_controller($__controller)) {
			throw new Exception("控制器($__controller)不存在！");
		}

		// check controller's action.
		$action_method = $__action . '_action';
		if (!method_exists($obj_controller, $action_method)) {
			throw new Exception("操作($__controller->$action_method)不存在！");
		}

		// access control
		if (method_exists($obj_controller, 'get_access_rule')) {
			$access_rule = $obj_controller->get_access_rule();
		}

		if (isset($access_rule)) {
			if (isset($access_rule['rule_type']) && ($access_rule['rule_type'] == 'white')) {
				if ((!$access_rule['actions']) || (!in_array($__action, $access_rule['actions']))) {
					self::_authenticate($obj_controller);
				}
			} else if (isset($access_rule['actions']) && in_array($__action, $access_rule['actions'])) {
				// 非白就是黑名单
				self::_authenticate($obj_controller);
			}
		} else {
			// 不指定就都检查
			self::_authenticate($obj_controller);
		}
		// parameters
		$trans = array_merge($_GET, $_POST);
		$rm = new ReflectionMethod($obj_controller, $action_method);
		$ps = $rm->getParameters();
		/**
		 * 通过controller处理当前请求
		 */
		if (empty($trans) || count($ps) == 0) {
			$response = $obj_controller->$action_method();
		} else {
			foreach ($ps as $p) {
				$pn = $p->getName();
				if (isset($trans[$pn])) {
					$args[] = $trans[$pn];
				} else {
					if ($p->isOptional()) {
						$args[] = $p->getDefaultValue();
					} else {
						$args[] = null;
					}

				}
			}
			$response = call_user_func_array(array($obj_controller, $action_method), $args);
		}
		/**
		 * 返回结果
		 */
		if ($response instanceof ResponseData) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache');
			// todo wx not support
			//if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && $_SERVER['HTTP_ACCEPT_ENCODING'] == 'gzip') {
			//    header("Content-Encoding: gzip");
			//    echo gzencode($response->toJsonString(), FORCE_GZIP);
			//} else {
			die($response->toJsonString());
			//}
		}
		die('unknown request');
	}
	/**
	 * $path controller's path
	 */
	private static function _request_view($path, $skipAuth = false) {
		global $__controller;
		self::viewuri_to_controller($path);
		/**
		 * create controller.
		 */
		if ($__controller) {
			if (!$obj_controller = self::create_controller($__controller, true)) {
				throw new Exception("控制器($__controller)不存在！");
			}

		} else {
			$obj_controller = new TMS_CONTROLLER;
		}

		// check controller's action.
		$action_method = 'view_action';
		if (!method_exists($obj_controller, $action_method)) {
			throw new Exception("操作($__controller->$action_method)不存在！");
		}

		/**
		 * access control
		 */
		if (!$skipAuth) {
			if (method_exists($obj_controller, 'get_access_rule')) {
				$ar = $obj_controller->get_access_rule();
			}
			if ($ar) {
				if ($ar['rule_type'] && $ar['rule_type'] == 'white') {
					if ((!$ar['actions']) || (!in_array('view', $ar['actions']))) {
						self::_authenticate();
					}
				} else if ($ar['actions'] && in_array('view', $ar['actions'])) {
					// 非白就是黑名单
					self::_authenticate($obj_controller);
				}
			} else {
				// 不指定就都检查
				self::_authenticate($obj_controller);
			}
		}
		/**
		 * 通过controller处理当前请求
		 */
		$obj_controller->$action_method($path);
	}
	/**
	 * controller:/moudle/name/action
	 */
	private static function apiuri_to_controller($path = '') {
		global $__controller, $__action;
		$cd = '/controllers/';
		if (empty($path) || is_dir(self::$app_dir . $cd . $path)) {
			// the path is moudle.
			// assign default controller and action.
			$path = rtrim($path, '/');
			$__controller = trim($path . '/' . self::$default_controller, '/');
			$__action = self::$default_action;
		} else {
			if (file_exists(self::$app_dir . $cd . trim($path, '/') . '.php')) {
				// the path is a controller.
				$__controller = trim($path, '/');
				$__action = self::$default_action;
			} else {
				// the path is action.
				$segments = $path ? explode('/', trim($path, '/')) : null;
				// action first.
				$__action = array_pop($segments);
				if (count($segments) == 0) {
					$__controller = self::$default_controller;
				} else {
					// assign controler.
					$__controller = trim(dirname($path), '/');
				}
			}
		}
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
	private static function viewuri_to_controller($path) {
		global $__controller;
		$cd = '/controllers';
		$postfix = 'php';
		$default_ctrl = 'main';
		//
		$segments = explode('/', substr($path, 1));
		// search dir
		$searched_dir = TMS_APP_DIR . $cd;
		foreach ($segments as $index => $segment) {
			if (!is_dir($searched_dir . '/' . $segment)) {
				break;
			}
			$searched_dir .= '/' . $segment;
		}
		$test_index = $index;
		$test_dir = $searched_dir;
		// search file
		do {
			if (isset($segments[$test_index])) {
				if (file_exists("$test_dir/{$segments[$test_index]}.$postfix")) {
					$ctrl_file = "$test_dir/{$segments[$test_index]}";
				} elseif (file_exists("$test_dir/$default_ctrl.$postfix")) {
					$ctrl_file = "$test_dir/$default_ctrl";
				}
			} else {
				if (file_exists("$test_dir/$default_ctrl.$postfix")) {
					$ctrl_file = "$test_dir/$default_ctrl";
				}
			}
			$test_index--;
			$test_index >= 0 && $test_dir = str_replace('/' . $segments[$test_index], '', $test_dir);
		} while (empty($ctrl_file) && $test_index >= 0);

		if (isset($ctrl_file)) {
			$__controller = $ctrl_file;
		}
	}
	/**
	 *
	 */
	private static function create_controller($controller, $fullpath = false) {
		if ($fullpath === false) {
			$controller_path = self::$app_dir . '/controllers/' . $controller;
		} else {
			$controller_path = $controller;
			$controller = str_replace(self::$app_dir . '/controllers', '', $controller);
		}
		if (is_dir($controller_path)) {
			// if ignore classname, then append it.
			$controller_path .= '/main';
			$controller .= '/main';
		}
		$class_file = $controller_path . '.php';
		if (is_file($class_file)) {
			//$class_name = basename($controller_path);
			$class_name = str_replace('/', '\\', $controller);
			if (!class_exists($class_name, false)) {
				require_once $class_file;
			}
			if (class_exists($class_name, false)) {
				return new $class_name();
			}
		}

		return false;
	}
	/**
	 * 检查用户是否已经通过认证
	 * 如果指定了controller对象，且controller对象提供认证接口，用controller的认证接口进行认证
	 *
	 * @param object $objController
	 */
	private static function _authenticate($objController = null) {
		if ($objController === null) {
			if (!TMS_CLIENT::is_authenticated()) {
				if (!self::_login()) {
					/**
					 * 如果当前用户没有登录过，跳转到指定的登录页面
					 */
					$_SERVER['HTTP_REFERER'] = $_SERVER['REQUEST_URI'];
					self::_request_api(str_replace(TMS_APP_API_PREFIX, '', TMS_APP_UNAUTH));
				}
			}
			return true;
		} else {
			// access control
			if (method_exists($objController, 'authenticated') && method_exists($objController, 'authenticateURL')) {
				if (true === $objController->authenticated()) {
					return true;
				}
				self::_request_api($objController->authenticateURL());
			} else if (!TMS_CLIENT::is_authenticated()) {
				self::_request_api(str_replace(TMS_APP_API_PREFIX, '', TMS_APP_UNAUTH));
			}
			return true;
		}
	}
	/**
	 * 是否已经登录？
	 *
	 * 允许通过http_auth登录
	 *
	 */
	private static function _login() {
		// directly visit, no login process.
		// analyze the PHP_AUTH_DIGEST variable
		// user exist?
		if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
			if ($data = self::http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) {
				$email = $data['username'];
				$account = self::model('account')->get_account_by_email($email);
				if (!$account) {
					return false;
				}
				session_destroy();
				TMS_CLIENT::account($account);
				return true;
			}
		}
		return false;
	}

	/**
	 * function to parse the http auth header
	 */
	private static function http_digest_parse($txt) {
		// protect against missing data
		$needed_parts = array(
			'nonce' => 1,
			'nc' => 1,
			'cnonce' => 1,
			'qop' => 1,
			'username' => 1,
			'uri' => 1,
			'response' => 1);
		$data = array();
		$keys = implode('|', array_keys($needed_parts));
		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches,
			PREG_SET_ORDER);
		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}
		return count($needed_parts) != 0 ? false : $data;
	}
	/**
	 * 获得session中保存的数据
	 *
	 * $name
	 */
	public static function S($name) {
		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}
	/**
	 * 获得model对象
	 *
	 * $path
	 */
	public static function &M($model_path) {
		if (!$model_path) {
			// 缺省的model实例
			$model_class = 'TMS_MODEL';
		} else {
			if (strpos($model_path, "\\")) {
				$model_class = $model_path;
				$model_file = preg_replace("/\\\\/", '/', $model_path);
			} else if (strpos($model_path, '/')) {
				$model_class = preg_replace('/^.*\//', '', $model_path);
				$model_file = $model_path;
			} else if (strpos($model_path, '.')) {
				$model_class = str_replace('.', '_', $model_path);
				$model_file = strstr($model_path, '.', true);
			} else {
				$model_class = $model_path;
				$model_file = $model_path;
			}
			if (strpos($model_file, self::$model_prefix)) {
				$model_file = strstr($model_path, self::$model_prefix, true);
			}

			if (false === strpos($model_class, self::$model_prefix)) {
				$model_class .= self::$model_prefix;
			}

		}
		//if (! isset(self::$models[$model_class])) {
		// no constructed class
		if (!class_exists($model_class)) {
			require_once dirname(dirname(__FILE__)) . '/models/' . $model_file . '.php';
		}
		$args = func_get_args();
		if (count($args) <= 1) {
			$model_obj = new $model_class();
		} else {
			$r = new ReflectionClass($model_class);
			$model_obj = $r->newInstanceArgs(array_slice($args, 1));
		}
		self::$models[$model_class] = $model_obj;
		//}

		return self::$models[$model_class];
	}
	/**
	 * 保存/获取全局变量
	 */
	public static function &G($path, &$val = false) {
		if ($val) {
			self::$globals[$path] = $val;
		}

		if (isset(self::$globals[$path])) {
			return self::$globals[$path];
		}

		return $val;
	}
}