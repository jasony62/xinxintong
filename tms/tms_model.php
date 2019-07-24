<?php
/**
 *
 */
class TMS_MODEL {
    /**
     *
     */
    private static $models = [];
    /**
     *
     */
    private static $model_prefix = '_model';
    /**
     * 仅使用数据库读链接访问
     * 在数据库主从分离的部署环境下，有可能写操作同步有延迟，导致后续的读操作取不到数据，因为有些读操作必须强制使用写连接
     */
    private $onlyWriteDbConn = false;
    /**
     * @param boolean $only
     */
    public function setOnlyWriteDbConn($only) {
        $this->onlyWriteDbConn = $only;

        return $this;
    }
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

        return self::$models[$model_class];
    }
    /**
     *
     */
    public static function insert($table, $data = null, $autoid = DEFAULT_DB_AUTOID) {
        return TMS_DB::db()->insert($table, $data, $autoid);
    }

    public static function update($table, $data = null, $where = null) {
        return TMS_DB::db()->update($table, $data, $where);
    }

    public static function delete($table, $where) {
        return TMS_DB::db()->delete($table, $where);
    }
    /**
     *
     */
    public function query_value($select, $from = null, $where = null) {
        return TMS_DB::db()->query_value($select, $from, $where, $this->onlyWriteDbConn);
    }
    /**
     *
     */
    public function query_val_ss($p) {
        $select = $p[0];
        $from = $p[1];
        $where = isset($p[2]) ? $p[2] : null;

        return $this->query_value($select, $from, $where);
    }
    /**
     *
     */
    public function query_values($select, $from = null, $where = null) {
        return TMS_DB::db()->query_values($select, $from, $where, $this->onlyWriteDbConn);
    }
    /**
     * $params [select,from,where]
     */
    public function query_vals_ss($p) {
        $select = $p[0];
        $from = $p[1];
        $where = isset($p[2]) ? $p[2] : null;

        return $this->query_values($select, $from, $where);
    }
    /**
     * 返回单个对象
     */
    public function query_obj($select, $from = null, $where = null) {
        return TMS_DB::db()->query_obj($select, $from, $where, $this->onlyWriteDbConn);
    }
    /**
     * 获得要执行的SQL语句
     */
    public function query_obj_ss_toSql($q) {
        $sql = call_user_func_array([TMS_DB::db(), "assemble_query"], $q);

        return $sql;
    }
    /**
     *  返回单个对象
     */
    public function query_obj_ss($q) {
        $select = $q[0];
        $from = $q[1];
        $where = isset($q[2]) ? $q[2] : null;

        return $this->query_obj($select, $from, $where);
    }
    /**
     *
     */
    public function query_objs($select, $from = null, $where = null, $group = null, $order = null, $limit = null, $offset = null) {
        return TMS_DB::db()->query_objs($select, $from, $where, $group, $order, $limit, $offset, $this->onlyWriteDbConn);
    }
    /**
     * 处理传入的参数
     */
    private static function _query_objs_ss_params($q, $q2 = null) {
        // select,from,where
        list($select, $from, $where) = $q;

        // group,order by,limit
        $group = $order = $offset = $limit = null;
        if ($q2) {
            $group = !empty($q2['g']) ? $q2['g'] : null;
            $order = !empty($q2['o']) ? $q2['o'] : null;
            if (!empty($q2['r'])) {
                $offset = $q2['r']['o'];
                $limit = $q2['r']['l'];
            }
        }

        return [$select, $from, $where, $group, $order, $offset, $limit];
    }
    /**
     *
     */
    public function query_objs_ss($q, $q2 = null) {
        // select,from,where
        $select = $q[0];
        $from = $q[1] ? $q['1'] : null;
        $where = isset($q[2]) ? $q[2] : null;
        // group,order by,limit
        $group = $order = $offset = $limit = null;
        if ($q2) {
            $group = !empty($q2['g']) ? $q2['g'] : null;
            $order = !empty($q2['o']) ? $q2['o'] : null;
            if (!empty($q2['r'])) {
                $offset = $q2['r']['o'];
                $limit = $q2['r']['l'];
            }
        }

        return $this->query_objs($select, $from, $where, $group, $order, $offset, $limit);
    }
    /**
     * 获得要执行的SQL语句
     */
    public static function query_objs_ss_toSql($q, $q2 = null) {
        $params = self::_query_objs_ss_params($q, $q2);

        $sql = call_user_func_array([TMS_DB::db(), "assemble_query"], $params);

        return $sql;
    }
    /**
     *
     */
    public static function found_rows() {
        return TMS_DB::db()->query_value('SELECT FOUND_ROWS()');
    }
    /**
     *
     */
    public static function escape($data) {
        if (is_string($data)) {
            return TMS_DB::db()->escape($data);
        } else if (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->{$k} = TMS_MODEL::escape($v);
            }
            return $data;
        } else if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = TMS_MODEL::escape($v);
            }
            return $data;
        } else {
            return $data;
        }
    }
    /**
     *
     */
    public static function unescape($data) {
        if (is_string($data)) {
            return TMS_DB::db()->unescape($data);
        } else if (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->{$k} = TMS_MODEL::unescape($v);
            }
            return $data;
        } else if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = TMS_MODEL::unescape($v);
            }
            return $data;
        } else {
            return $data;
        }
    }

    /**
     *
     * return 32bit
     */
    protected static function uuid($prefix) {
        !$prefix && $prefix = TMS_CLIENT::get_client_uid();
        return md5(uniqid($prefix) . mt_rand());
    }
    /**
     * 用户信息加密解密函数
     * 待加密内容用\t分割
     *
     * @return String 加密或解密字符串
     * @param String $string 待加密或解密字符串
     * @param String $operation 操作类型定义 DECODE=解密 ENCODE=加密
     * @param String $key 加密算子
     */
    public static function encrypt($string, $operation, $key) {
        /**
         * 获取密码算子,如未指定，采取系统默认算子
         * 默认算子是论坛授权码和用户浏览器信息的md5散列值
         */
        $str_length = strlen($string);
        $key_length = strlen($key);
        /**
         * 如果解密，先对密文解码
         * 如果加密，将密码算子和待加密字符串进行md5运算后取前8位
         * 并将这8位字符串和待加密字符串连接成新的待加密字符串
         */
        $string = $operation == 'DECODE' ? base64_decode(str_replace(array('-', '_'), array('+', '/'), $string)) : substr(md5($string . $key), 0, 8) . $string;

        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        /**
         * 初始化加密变量，$rndkey和$box
         */
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        /**
         * $box数组打散供加密用
         */
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        /**
         * $box继续打散,并用异或运算实现加密或解密
         */
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($result));
        }
    }
    /**
     * generate a 32bits salt.
     */
    public static function gen_salt($length = 32) {
        $alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alpha_digits_len = strlen($alpha_digits) - 1;

        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
        }

        return $salt;
    }
    /**
     * 对用户的密码进行加密
     */
    public static function compile_password($identity, $password, $salt) {
        $sign = $identity . $salt . $password;

        $sha = hash('sha256', $sign);

        return $sha;
    }
    /**
     *
     */
    public static function toLocalEncoding($str) {
        if (defined('TMS_LOCAL_ENCODING') && TMS_LOCAL_ENCODING !== 'UTF-8') {
            $str = iconv('UTF-8', TMS_LOCAL_ENCODING, $str);
        }

        return $str;
    }
    /**
     *
     */
    public static function toUTF8($str) {
        if (defined('TMS_LOCAL_ENCODING') && TMS_LOCAL_ENCODING !== 'UTF-8') {
            $str = iconv(TMS_LOCAL_ENCODING, 'UTF-8', $str);
        }

        return $str;
    }
    /**
     *
     */
    public function &M($model_path) {
        return TMS_APP::M($model_path);
    }
    /**
     * 给JSON的字符转义
     */
    private static function _escape4Json($str) {
        $str = str_replace(array("\\", '"', "\n", "\r", "\t"), array("\\\\", '\"', "\\n", "\\r", "\\t"), $str);
        return $str;
    }
    /**
     * 为了解决json_encode处理中文的问题，对对象的值进行urlencode处理
     */
    private static function _urlencodeObj4Json($obj) {
        // 替换为空的
        $pattern1 = "/[\r\n\t]/";

        if (is_object($obj)) {
            $newObj = new \stdClass;
            foreach ($obj as $k => $v) {
                $k = self::_escape4Json($k);
                $newObj->{urlencode($k)} = self::_urlencodeObj4Json($v);
            }
        } else if (is_array($obj)) {
            $newObj = array();
            foreach ($obj as $k => $v) {
                $k = self::_escape4Json($k);
                $newObj[urlencode($k)] = self::_urlencodeObj4Json($v);
            }
        } else {
            if (is_bool($obj) || is_numeric($obj)) {
                $newObj = $obj;
            } else {
                $obj = self::_escape4Json($obj);
                $newObj = urlencode($obj);
            }
        }

        return $newObj;
    }
    /**
     * 将对象转换为JSON格式的字符串
     *
     * 利用urlencode解决中文问题。json_encode会讲中文转换为unicode的形式（\uxxxx），这样会影响后续处理。
     */
    public static function toJson($obj) {
        $obj = self::_urlencodeObj4Json($obj);
        $json = urldecode(json_encode($obj));

        return $json;
    }
    /**
     * 过滤掉字符串中的emoji字符
     */
    public function cleanEmoji($str, $bKeepEmoji = false) {
        $str = json_encode($str);
        $str = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($matches) use ($bKeepEmoji) {
            if (true === $bKeepEmoji) {
                return str_replace('%5C', '\\\\', urlencode($matches[0]));
            } else {
                return '';
            }
        }, $str);

        $str = json_decode($str);

        return $str;
    }
    /**
     * 获得对象的指定属性的值
     * 属性可以是‘.’连接，例如a.b，对表对象的属性a是一个对象，取这个对象的属性b
     */
    public static function getDeepValue($deepObj, $deepProp, $notSetVal = null) {
        $props = explode('.', $deepProp);
        $val = $deepObj;
        foreach ($props as $prop) {
            if (!isset($val->{$prop})) {
                return $notSetVal;
            } else if (empty($val->{$prop})) {
                return $val->{$prop};
            } else {
                $val = $val->{$prop};
            }
        }
        return $val;
    }
    /**
     * 设置对象的指定属性的值
     * 属性可以是‘.’连接，例如a.b，对表对象的属性a是一个对象，取这个对象的属性b
     */
    public static function setDeepValue($deepObj, $deepProp, $setVal) {
        $props = explode('.', $deepProp);
        $last = count($props) - 1; // 最后一个属性的位置

        $propObj = $deepObj;
        for ($i = 0; $i < $last; $i++) {
            $prop = $props[$i];
            if (empty($propObj->{$prop})) {
                $propObj->{$prop} = new \stdClass;
            }
            $propObj = $propObj->{$prop};
        }

        $propObj->{$props[$last]} = $setVal;

        return $deepObj;
    }
    /**
     * 替换字符串中的html标签
     * $brValue <br>标签要替换成的值
     */
    public static function replaceHTMLTags($text, $brValue = '') {
        if (!is_string($text)) {
            return false;
        }

        $text = str_replace(['<br>', '</br>'], [$brValue, ""], $text);
        $text = strip_tags($text);
        $text = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $text);

        return $text;
    }
    /**
     * 获取当前毫秒时间戳
     */
    public static function getMsectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return substr($msectime, 0, 13);
    }
}