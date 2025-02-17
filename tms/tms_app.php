<?php
require_once dirname(dirname(__FILE__)) . '/includes/utilities.func.php';
require_once dirname(__FILE__) . '/utilities.cls.php';
require_once dirname(__FILE__) . '/tms_model.php';
require_once dirname(__FILE__) . '/tms_controller.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/template.php';
require_once dirname(__FILE__) . '/client.php';

/**
 * 除理request
 */
class TMS_APP
{
  //
  private static $models = array();
  /**
   * 缓存全局变量
   */
  private static $globals = array();
  //
  private static $app_dir = TMS_APP_DIR;
  private static $default_controller = 'main';
  private static $index_action = 'index';
  private static $default_action = 'default';
  private static $model_prefix = '_model';
  /**
   * 调试用日志
   */
  public static function devLog($msg)
  {
    if (isset($_GET['TMSDEV']) && $_GET['TMSDEV'] === 'yes') {
      $devLogger = Logger::getLogger('dev');
      $devLogger->debug($msg);
    }
  }
  /**
   * 实例化model
   *
   * model_path可以用'\'，'/'和'.'进行分割。用'\'代表namespace，用'/'代表目录，用'.'代表文件问题
   * 例如：
   * 1 - a/b/c，含义为：文件为a/b/c，类为c
   * 2 - a/b/c.d，含义为：文件为a/b/c，类为c_d
   */
  public static function &model($model_path = null)
  {
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

    return self::$models[$model_class];
  }
  /**
   * 完成的任务
   * 1、找到处理请求的controller
   * 2、由controller处理请求
   * 3、返回controller处理的结果
   */
  public static function run()
  {
    /* 如果不是登录状态，尝试自动登录 */
    $uid = TMS_CLIENT::get_client_uid();
    if (empty($uid)) {
      self::_autoLogin();
    }

    /* 获得指定的controller或view */
    $url = parse_url($_SERVER['REQUEST_URI']);
    $path = $url['path'];
    if (0 === strpos($path, '/site/')) {
      /* 快速进入站点，如果urlrewrite没有配置，这一部分的代码不执行？？？ */
      $short = substr($path, 6);
      $full = '/rest/site/fe?site=' . $short;
      header("Location: $full");
      exit;
    } else if (0 === strpos($path, TMS_APP_API_PREFIX . '/')) {
      $path = substr($path, strlen(TMS_APP_API_PREFIX));
      self::devLog('调用API [path = ' . $path . ']');
      self::_request_api($path);
    } else if (0 === strpos($path, TMS_APP_VIEW_PREFIX . '/')) {
      $path = substr($path, strlen(TMS_APP_VIEW_PREFIX));
      self::_request_view($path);
    } else {
      if (defined('TMS_APP_HOME') && !empty(TMS_APP_HOME)) {
        /**
         * 跳转到指定的平台首页
         */
        header("Location: " . TMS_APP_HOME);
      } else {
        /**
         * 跳转到平台管理端首页
         */
        if (self::_authenticate()) {
          $path = TMS_APP_AUTHED;
          self::_request_api($path);
        }
      }
    }
  }
  /**
   * 准备调用控制器方法的参数
   */
  private static function _prepareControllerMethodArguments($oController, $sMethod, $aReqParams)
  {
    $rm = new ReflectionMethod($oController, $sMethod);
    $ps = $rm->getParameters();
    if (count($ps) === 0) {
      return [];
    }

    $model = self::model();
    $args = [];
    foreach ($ps as $p) {
      $pn = $p->getName();
      if (isset($aReqParams[$pn])) {
        $args[] = TMS_APP_REQUEST_DATA_ESCAPE === 1 ? $model->escape($aReqParams[$pn]) : $aReqParams[$pn];
      } else {
        if ($p->isOptional()) {
          $args[] = $p->getDefaultValue();
        } else {
          die('Program Error: Request parameters are incompleted.');
        }
      }
    }
    return $args;
  }
  /**
   * 检查请求是否合法
   */
  private static function _validateRequest()
  {
    /* 没有明确指定接受的类型 */
    if (empty($_SERVER['HTTP_ACCEPT']) || $_SERVER['HTTP_ACCEPT'] === '*/*')
      return false;

    if (empty($_SERVER['HTTP_USER_AGENT']) || strlen($_SERVER['HTTP_USER_AGENT']) < 65)
      return false;

    return true;
  }
  /**
   * 处理API请求
   *
   * @param string $path
   */
  private static function _request_api($path)
  {
    global $__controller, $__action;
    self::_apiuri_to_controller($path);
    /**
     * create controller.
     */
    if (!$obj_controller = self::create_controller($__controller)) {
      throw new UrlNotMatchException("控制器($__controller)不存在！");
    }
    /**
     * check controller's action.
     */
    if (false === $__action || !method_exists($obj_controller, $__action . '_action')) {
      $default_method = self::$default_action . '_action';
      if (!method_exists($obj_controller, $default_method)) {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
          /**
           * 如果访问的是页面，返回控制器的缺省页面
           */
          $default_method = self::$index_action . '_action';
          if (!method_exists($obj_controller, $default_method)) {
            throw new UrlNotMatchException("操作($__controller->$default_method)不存在！");
          }
        } else if (false === self::_validateRequest()) {
          die('tms unsupported request');
        } else {
          throw new UrlNotMatchException("操作($__controller->$default_method)不存在！");
        }
      }
      $action_method = $default_method;
    } else {
      $action_method = $__action . '_action';
    }

    self::devLog('获得控制器方法名称 [' . $action_method . ']');

    /**
     * access control
     */
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

    self::devLog('通过访问控制检查 [' . $action_method . ']');

    // 请求中包含的所有可用参数
    $aRequestParameters = array_merge($_GET, $_POST);
    /**
     * before each
     */
    if (method_exists($obj_controller, 'tmsBeforeEach')) {
      $args = self::_prepareControllerMethodArguments($obj_controller, 'tmsBeforeEach', $aRequestParameters);
      $aResultBeforeEach = call_user_func_array(array($obj_controller, 'tmsBeforeEach'), $args);
      if ($aResultBeforeEach[0] !== true) {
        if ($aResultBeforeEach[1] instanceof ResponseData) {
          header('Content-type: application/json');
          header('Cache-Control: no-cache');
          die($aResultBeforeEach[1]->toJsonString());
        }
      }
    }

    self::devLog('完成前置方法执行 [' . $action_method . ']');

    /**
     * 是否需要事物
     */
    $tmsTransId = false;
    if (method_exists($obj_controller, 'tmsRequireTransaction')) {
      $aTransActions = $obj_controller->tmsRequireTransaction();
      if (in_array(str_replace('_action', '', $action_method), $aTransActions)) {
        $modelTrans = self::model('tms\transaction');
        $oReq = new \stdClass;
        $oReq->begin_at = $_SERVER['REQUEST_TIME_FLOAT'];
        $oReq->request_uri = $_SERVER['REQUEST_URI'];
        $oReq->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $oReq->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $oReq->remote_addr = $obj_controller->client_ip();
        $oTrans = $modelTrans->begin($oReq);
        $obj_controller->tmsTransaction = $oTrans;
      }
    }

    self::devLog('准备执行方法 [' . $action_method . ']');

    /**
     * 通过controller处理当前请求
     */
    $args = self::_prepareControllerMethodArguments($obj_controller, $action_method, $aRequestParameters);

    self::devLog('准备执行方法需要的参数 [' . $action_method . '(' . count($args) . ')]');

    $response = call_user_func_array(array($obj_controller, $action_method), $args);
    // 结束事物
    if (isset($oTrans) && isset($modelTrans)) {
      $modelTrans->end($oTrans->id);
    }

    self::devLog('完成执行方法 [' . $action_method . ']');

    /**
     * 返回结果
     */
    if ($response instanceof ResponseData) {
      header('Content-type: application/json');
      header('Cache-Control: no-cache');
      die($response->toJsonString());
    }
    die('tms unknown request');
  }
  /**
   * $path controller's path
   */
  private static function _request_view($path, $skipAuth = false)
  {
    global $__controller;
    self::viewuri_to_controller($path);
    /**
     * create controller.
     */
    if ($__controller) {
      if (!$obj_controller = self::create_controller($__controller, true)) {
        throw new UrlNotMatchException("控制器($__controller)不存在！");
      }
    } else {
      $obj_controller = new TMS_CONTROLLER;
    }

    // check controller's action.
    $action_method = 'view_action';
    if (!method_exists($obj_controller, $action_method)) {
      throw new UrlNotMatchException("操作($__controller->$action_method)不存在！");
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
   * 根据url设置controller信息
   * controller:/moudle/name/action
   */
  private static function _apiuri_to_controller($path = '')
  {
    global $__controller, $__action;
    $cd = '/controllers/';
    if (empty($path) || is_dir(self::$app_dir . $cd . $path)) {
      // the path is moudle.
      // assign default controller and action.
      $path = rtrim($path, '/');
      $__controller = trim($path . '/' . self::$default_controller, '/');
      $__action = false;
    } else {
      if (file_exists(self::$app_dir . $cd . trim($path, '/') . '.php')) {
        // the path is a controller.
        $__controller = trim($path, '/');
        $__action = false;
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
    self::devLog('获得控制器名称和方法 [' . $__controller . '][' . $__action . ']');
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
  private static function viewuri_to_controller($path)
  {
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
  private static function create_controller($controller, $fullpath = false)
  {
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

    self::devLog('获得控制器文件名称 [' . $class_file . ']');

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
   * @param object $oController 要调用的controller
   *
   */
  private static function _authenticate($oController = null)
  {
    /**
     * 如果指定了controller，优先使用controller定义的认证方法
     */
    if (isset($oController)) {
      if (method_exists($oController, 'authenticated') && method_exists($oController, 'authenticateURL')) {
        if (true === $oController->authenticated()) {
          return true;
        }
        self::_request_api($oController->authenticateURL());
      }
    }
    /**
     * 使用平台定义的认证方法
     */
    $uid = TMS_CLIENT::get_client_uid();
    if (empty($uid)) {
      /**
       * 如果当前用户没有登录过，跳转到指定的登录页面，记录页面的跳转关系
       */
      $_SERVER['HTTP_REFERER'] = $_SERVER['REQUEST_URI'];
      self::_request_api(str_replace(TMS_APP_API_PREFIX, '', TMS_APP_UNAUTH));
    }

    return true;
  }
  /**
   * 尝试自动登录
   *
   * @see pl\fe\user\login.php
   */
  private static function _autoLogin()
  {
    if ('Y' === TMS_CONTROLLER::myGetCookie('_login_auto')) {
      $token = TMS_CONTROLLER::myGetCookie('_login_token');
      if (empty($token)) {
        return false;
      }

      $cookiekey = md5($_SERVER['HTTP_USER_AGENT']);
      $decodeToken = TMS_MODEL::encrypt($token, 'DECODE', $cookiekey);
      $oToken = json_decode($decodeToken);
      if (JSON_ERROR_NONE !== json_last_error()) {
        /* 清除错误数据 */
        TMS_CONTROLLER::mySetCookie('_login_token', '', 0);
        TMS_CONTROLLER::mySetCookie('_login_auto', '', 0);
        /* 记录日志 */
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        self::M('log')->log('error', 'tms_app::_autoLogin::json_error', $decodeToken, $agent, $referer);
        return false;
      }

      /* check */
      $modelAct = self::M('account');
      $oValidResult = $modelAct->validate($oToken->email, $oToken->password);
      if ($oValidResult->err_code != 0) {
        return false;
      }
      $oSiteAccount = $oValidResult->data;
      /* cookie中保留注册信息 */
      $modelWay = self::M('site\fe\way');
      $oRegUser = new \stdClass;
      $oRegUser->unionid = $oSiteAccount->uid;
      $oRegUser->uname = $oSiteAccount->email;
      $oRegUser->nickname = $oSiteAccount->nickname;

      $aResult = $modelWay->shiftRegUser($oRegUser);
      if (false === $aResult[0]) {
        return false;
      }

      return true;
    }

    return false;
  }
  /**
   * 获得session中保存的数据
   *
   * $name
   */
  public static function S($name)
  {
    return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
  }
  /**
   * 获得model对象
   *
   * $path
   */
  public static function &M($model_path)
  {
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
  public static function &G($path, &$val = false)
  {
    if ($val) {
      self::$globals[$path] = $val;
    }

    if (isset(self::$globals[$path])) {
      return self::$globals[$path];
    }

    return $val;
  }
  /**
   * 根据客户端环境，编码下载文件文件名
   */
  public static function setContentDisposition($filename)
  {
    $ua = $_SERVER["HTTP_USER_AGENT"];
    //if (preg_match("/MSIE|Edge/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
    if (preg_match("/Firefox/", $ua)) {
      header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
    } else if (preg_match("/Windows/", $ua)) {
      $encoded_filename = urlencode($filename);
      $encoded_filename = str_replace("+", "%20", $encoded_filename);
      $encoded_filename = iconv('UTF-8', 'GBK//IGNORE', $encoded_filename);
      header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
    } else {
      header('Content-Disposition: attachment; filename="' . $filename . '"');
    }

    return true;
  }
}
