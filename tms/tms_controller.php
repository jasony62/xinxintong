<?php
class TMS_CONTROLLER
{
  /**
   * 根日志，默认info级别
   */
  protected $logger;
  /**
   * 运维日志，默认trace级别
   */
  protected $devLogger;

  public function __construct()
  {
    $this->logger = \Logger::getLogger(get_class($this));
    $this->devLogger = \Logger::getLogger('dev');
  }
  /**
   *
   */
  public function get_access_rule()
  {
    $rule_action['rule_type'] = 'white';
    $rule_action['actions'] = array();
    $rule_action['actions'][] = 'hello';
    $rule_action['actions'][] = 'debug';

    return $rule_action;
  }
  /**
   *
   */
  public function hello_action()
  {
    die('hello.');
  }
  /**
   *
   */
  protected function getRequestUrl()
  {
    $url[] = strtolower(strtok($_SERVER['SERVER_PROTOCOL'], '/'));
    $url[] = '://';
    $url[] = APP_HTTP_HOST;
    $url[] = $_SERVER['REQUEST_URI'];

    return implode('', $url);
  }
  /**
   * 前端跳转到指定页面
   */
  protected function redirect($path)
  {
    if (false !== strpos($path, 'http')) {
      $url = $path;
    } else {
      $url = APP_PROTOCOL . APP_HTTP_HOST;
      $url .= $path;
    }
    header("Location: $url");
    exit;
  }
  /**
   * 请求发起时间
   */
  protected function getRequestTime()
  {
    return $_SERVER['REQUEST_TIME'];
  }
  /**
   *
   */
  protected function getPost($name, $default = null)
  {
    return isset($_POST[$name]) ? $_POST[$name] : $default;
  }
  /**
   *
   */
  protected function getGet($name, $default = null)
  {
    return isset($_GET[$name]) ? $_GET[$name] : $default;
  }
  /**
   * 将post数据转换为对象
   */
  protected function &getPostJson($escape = true)
  {
    if ('POST' === $_SERVER['REQUEST_METHOD']) {
      $json = file_get_contents("php://input");
      // 过滤掉数据的emoji字符
      $json = $this->model()->cleanEmoji($json, true);
      $obj = json_decode($json);
      if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \Exception('请求参数解析错误：' . json_last_error_msg());
      }
      if ($escape === true && TMS_APP_REQUEST_DATA_ESCAPE === 1) {
        $obj = $this->escape($obj);
      }
    } else {
      $obj = null;
    }

    return $obj;
  }
  /**
   * 设置 COOKIE
   *
   * @param string $name
   * @param string $value
   * @param int $expire
   * @param string $path
   * @param string $domain
   * @param string $secure
   */
  public static function mySetCookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false)
  {
    if (!$domain and G_COOKIE_DOMAIN) {
      $domain = G_COOKIE_DOMAIN;
    }

    $_COOKIE[G_COOKIE_PREFIX . $name] = $value;

    return setcookie(G_COOKIE_PREFIX . $name, $value, $expire, $path, $domain, $secure, false);
  }
  /**
   * 获取cookie的值
   */
  public static function myGetCookie($name)
  {
    $cookiename = G_COOKIE_PREFIX . $name;
    if (isset($_COOKIE[$cookiename])) {
      return $_COOKIE[$cookiename];
    }

    return false;
  }
  /**
   * 返回事物ID，如果存在
   */
  public function tmsTransactionId()
  {
    return isset($this->tmsTransaction->id) ? $this->tmsTransaction->id : 0;
  }
  /**
   *
   */
  public function model()
  {
    $args = func_get_args();
    $model = call_user_func_array(array('TMS_MODEL', "model"), $args);
    if (isset($this->tmsTransaction)) {
      $model->tmsTransaction = $this->tmsTransaction;
    }
    return $model;
  }
  /**
   *
   */
  public function escape($data)
  {
    return TMS_MODEL::escape($data);
  }
  /**
   *
   */
  public static function getDeepValue($deepObj, $deepProp, $notSetVal = null)
  {
    return TMS_MODEL::getDeepValue($deepObj, $deepProp, $notSetVal);
  }
  /**
   *
   */
  public static function setDeepValue($deepObj, $deepProp, $setVal)
  {
    return TMS_MODEL::setDeepValue($deepObj, $deepProp, $setVal);
  }
  /**
   *
   */
  public static function replaceHTMLTags($text, $brValue = '')
  {
    return TMS_MODEL::replaceHTMLTags($text, $brValue);
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
  public function view_action($path)
  {
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
  protected function load_view($path)
  {
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
   * 获得访问用户的ip地址
   */
  public function client_ip()
  {
    if (
      isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
      $this->_valid_ipv4($_SERVER['HTTP_X_FORWARDED_FOR'])
    ) {
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
  private function _valid_ipv4($ip)
  {
    $ip_segments = explode('.', $ip);
    // Always 4 segments needed
    if (count($ip_segments) !== 4) {
      return false;
    }
    // IP can not start with 0
    if (substr($ip_segments[0], 0, 1) === '0') {
      return false;
    }
    // Check each segment
    foreach ($ip_segments as $segment) {
      // IP segments must be digits and can not be
      // longer than 3 digits or greater then 255
      if (
        preg_match("/[^0-9]/", $segment) ||
        $segment > 255 || strlen($segment) > 3
      ) {
        return false;
      }
    }
    return true;
  }
  /**
   * 获取当前毫秒时间戳
   */
  public function getMsectime()
  {
    return TMS_MODEL::getMsectime();
  }
}
