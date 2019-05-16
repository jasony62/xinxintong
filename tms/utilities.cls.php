<?php
/**
 * 校验密码强度
 * 可以实现密码强度等级划分
 */
class PwdStrengthCheck{
    /**
     * Current password's total score
     * @var integer
     */
    private $score = 0;
    /**
     * 密码
     */
    private $password;
    /**
     * 特殊字符
     */
    private $symbols = ['~', '!', '@', '#','$', '%', '^', '&', '*', '(', ')', '[', ']', '{', '}', '|', ':', '\'', '+', '=', '"', '<', '>', '?', ',', '.', '/', ';', '\\', '_', '-', '`'];
    /**
     * 键盘序
     */
    private $keyboardSeq = [
        "1234567890 0987654321", //数字倒序
        "qwertyuiop asdfghjkl zxcvbnm QWERTYUIOP ASDFGHJKL ZXCVBNM", //主键盘顺序
        "poiuytrewq lkjhgfdsa mnbvcxz POIUYTREWQ LKJHGFDSA MNBVCXZ", //主键盘逆序
        "qaz wsx edc rfv tgb yhn ujm QAZ WSX EDC RFV TGB YHN UJM",//主键盘正向斜
        "zaq xsw cde vfr bgt nhy mju ZAQ XSW CDE VFR BGT NHY MJU",//主键盘正向斜逆序
        "esz rdx tfc ygv uhb ijn okm OKM IJN UHB YGV TFC RDX ESZ",//主键盘反向斜
        "zse xdr cft vgy bhu nji mko MKO NJI BHU VGY CFT XDR ZSE",//主键盘反向斜逆序
        "147 369 258 852 963 741", //小键盘
        "abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ", //字母序
        "zyxwvutsrqponmlkjihgfedcba ZYXWVUTSRQPONMLKJIHGFEDCBA", //字母序
    ];
    /**
     * Constants defining the return values for 
     * strength ratings
     */
    const STRENGTH_VERY_WEAK = 0;
    const STRENGTH_WEAK = 1;
    const STRENGTH_FAIR = 2;
    const STRENGTH_STRONG = 3;
    const STRENGTH_VERY_STRONG = 4;

    /**
     * List of tests in the order to process
     * Does not include dictionary check
     *
     * @var array
     */
    private $filterFucns = [
        'length', 'upperCount', 'lowerCount', 'numberCount',
        'symbolCount', 'symbolOnly', 'symbolNumberMiddle', 'charactersOnly', 'numberOnly',
        'characterRepeat', 'consecUpper', 'consecLower', 'consecNumber',
        'seqNumber', 'seqCharacter', 'extra'
    ];
    /**
     *
     */
    public function __construct($password) {
        $password = trim($password);

        $this->password = $password;
    }
    /**
     * Get the current password strength
     *     Either numeric or as plain text
     * 
     * @param boolean $asText Flag to return either integer or text
     * @return mixed Either integer or string repreesnting strength
     */
    public function getStrength($asText = false){
        $score = $this->getScore();

        if ($score <= 60) {
            return ($asText) ? 'Very Weak' : self::STRENGTH_VERY_WEAK;
        } elseif ($score > 60 && $score <= 70) {
            return ($asText) ? 'Weak' : self::STRENGTH_WEAK;
        } elseif ($score > 70 && $score <= 80) {
            return ($asText) ? 'Fair' : self::STRENGTH_FAIR;
        } elseif ($score > 80 && $score <= 90) {
            return ($asText) ? 'Strong' : self::STRENGTH_STRONG;
        } else {
            return ($asText) ? 'Very Strong' : self::STRENGTH_VERY_STRONG;
        }
    }
    /**
     * 计算分数
     * 
     * @param string $password Password to evaluate
     * @return integer Resulting score
     */
    public function evaluate(){
        $passwordData = $this->parse();
        $score = 0;

        foreach ($this->filterFucns as $func) {
            $funcName = "_" . $func;
            $result = $this->{$funcName}($passwordData);
            $score += $result;
        }
        $this->setScore($score);
        return $score;
    }
    /**
     * Parse the password into relevant data
     * 
     * @param string $password Password to evaluate
     * @return array Parsed password data
     */
    public function parse($password = ''){
        empty($password) && $password = $this->password;
        $passwordData = array(
            'number' => array(
                'count' => 0,
                'list' => [],
                'raw' => ''
            ),
            'upper' => array(
                'count' => 0,
                'list' => [],
                'raw' => ''
            ),
            'lower' => array(
                'count' => 0,
                'list' => [],
                'raw' => ''
            ),
            'symbol' => array(
                'count' => 0,
                'list' => array(),
                'raw' => ''
            ),
            'list' => array(),
            'length' => 0,
            'raw' => trim($password)
        );
        $passwordData['length'] = strlen($password);

        for ($i=0; $i < strlen($password); $i++) {
            $character = $password[$i];
            $code = ord($character);

            if ($code >= 48 && $code <= 57) {
                $passwordData['number']['count']++;
                $passwordData['number']['list'][] = $character;
                $passwordData['number']['raw'] .= $character;
            } else if ($code >= 65 && $code <= 90) {
                $passwordData['upper']['count']++;
                $passwordData['upper']['list'][] = $character;
                $passwordData['upper']['raw'] .= $character;
            } else if ($code >= 97 && $code <= 122) {
                $passwordData['lower']['count']++;
                $passwordData['lower']['list'][] = $character;
                $passwordData['lower']['raw'] .= $character;
            } else {
                $passwordData['symbol']['count']++;
                $passwordData['symbol']['list'][] = $character;
                $passwordData['symbol']['raw'] .= $character;
            }
            (isset($passwordData['list'][$character]))
                ? $passwordData['list'][$character]++ : $passwordData['list'][$character] = 1;
        }

        return $passwordData;
    }

    /**
     * Get the current score
     * 
     * @return integer Score value
     */
    public function getScore(){
        return $this->score;
    }

    /**
     * Set the current score
     * 
     * @param integer $score
     */
    public function setScore($score){
        $this->score = $score;
    }
    /**
     * 验证账号相关性
     */
    public function verifyAccountRelation($parse, $account) {
        if (strpos($parse['raw'], $account) !== false) {
            return [false, '口令中不能包含账号信息'];
        }
        $parseAccount = $this->parse($account);
        $existSum = 0;
        foreach ($parseAccount['list'] as $ka => $va) {
            if (stristr($parse['raw'], (string) $ka) !== false) {
                $existSum++;
            }
        }
        // 如果改变账号字符顺序以及大小写，如果与账号的匹配字符大于账号中字符数量-2个返回false
        if ($existSum > count($parseAccount['list']) - 2) {
            return [false, '口令中不能包含账号信息（包括大小写变化或者位置变化）'];
        }

        return [true];
    }
    /**
     * 验证机械键盘序
     */
    public function verifyKeyboardSeq($parse) {
        $raw = $parse['raw'];
        for ($i = 0; $i < strlen($raw); $i++) {
            if ($i < strlen($raw) - 2) {
                $char = $raw[$i] . $raw[$i+1] . $raw[$i+2];
                foreach ($this->keyboardSeq as $vseq) {
                    if (strpos($vseq, $char) !== false) {
                        return [false, '口令不能包含3个连续以键盘键位为序的字符(如:正 反 斜方向 大小写，abc QWE 123 qaz 321)'];
                    }
                }
            }
        }

        return [true];
    }
    /**
     * 验证符号
     */
    public function verifySymbol($parse) {
        // 判断特殊字符是否符合规范
        foreach ($parse['symbol']['list'] as $vsb) {
            if (!in_array($vsb, $this->symbols)) {
                return [false, '只能使用英文符号'];
            }
        }

        return [true];
    }
    /**
     * 
     */
    private function _characterRepeat($passwordData){
        $score = 0;
        $result = 0;

        foreach ($passwordData['list'] as $character => $count) {
            if ($count > 1) {
                $score += $count - 1;
            }
        }
        if ($score > 0) {
            $result -= (int)($score / (strlen($passwordData['raw']) - $score)) + 1;
        }
        return $result;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _charactersOnly($passwordData){
        $passwordLength = strlen($passwordData['raw']);
        return (
            $passwordData['lower']['count'] + $passwordData['upper']['count'] == $passwordLength
        ) ? -$passwordLength : 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _symbolOnly($passwordData){
        $passwordLength = strlen($passwordData['raw']);
        return (
            $passwordData['symbol']['count'] == $passwordLength
        ) ? -$passwordLength : 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _consecLower($passwordData){
        preg_match_all('/[a-z]{2,}/', $passwordData['raw'], $matches);
        if (!empty($matches[0])) {
            $score = 0;
            foreach ($matches[0] as $match) {
                $score -= (strlen($match) - 1) * 2;
            }
            return $score;
        }
        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _consecNumber($passwordData){
        preg_match_all('/[0-9]{2,}/', $passwordData['raw'], $matches);
        if (!empty($matches[0])) {
            $score = 0;
            foreach ($matches[0] as $match) {
                $score -= (strlen($match) - 1) * 2;
            }
            return $score;
        }
        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _consecUpper($passwordData){
        preg_match_all('/[A-Z]{2,}/', $passwordData['raw'], $matches);
        if (!empty($matches[0])) {
            $score = 0;
            foreach ($matches[0] as $match) {
                $score -= (strlen($match) - 1) * 2;
            }
            return $score;
        }
        return 0;
    }
     /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _extra($passwordData){
        $score = 0;

        // at least 8 characters
        if (strlen($passwordData['raw']) >= 8) {
            $score += 2;
        }

        // contains at least three of lower, upper, numbers, special chars
        foreach (array('upper', 'lower', 'number', 'symbol') as $type) {
            if ($passwordData[$type]['count'] > 0) {
                $score += 2;
            }   
        }

        return $score;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _length($passwordData){
        return ($passwordData['length']*4);
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _lowerCount($passwordData){
        preg_match_all('/[a-z]{1}/', $passwordData['raw'], $matches);

        if (!empty($matches[0])) {
            // if there's only one, be sure it's not our password
            if (implode('', $matches[0]) == $passwordData['raw']) {
                return 0;
            }
            // otherwise, give credit for each one
            return (strlen($passwordData['raw']) - count($matches[0])) * 2;
        }
        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _numberCount($passwordData){
        preg_match_all('/[0-9]{1}/', $passwordData['raw'], $matches);

        if (!empty($matches[0])) {
            // if there's only one, be sure it's not our password
            if (implode('', $matches[0]) == $passwordData['raw']) {
                return 0;
            }
            // otherwise, give credit for each one
            return count($matches[0]) * 2;
        }
        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _numberOnly($passwordData){
        $passwordLength = strlen($passwordData['raw']);
        return (
            $passwordData['number']['count'] == $passwordLength
        ) ? -$passwordLength : 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _seqCharacter($passwordData){
        $found = 0;
        preg_match_all('/[a-zA-Z]{2,}/', $passwordData['raw'], $matches);

        if (isset($matches[0])) {
            foreach ($matches[0] as $match) {

                $parts = str_split($match);
                for ($i=0; $i<count($parts); $i++) {
                    // see if we have a "next"
                    if (!isset($parts[$i+1])) {
                        continue;
                    }
                    $current = ord($parts[$i]);
                    $next = ord($parts[$i+1]);

                    if ($next == ($current + 1) || $next == ($current - 1)) {
                        $found -= 1;
                    }
                }
            }
            return ($found + 1) * 2;
        }
        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _seqNumber($passwordData){
        $found = 0;
        preg_match_all('/[0-9]{2,}/', $passwordData['raw'], $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {

                $parts = str_split($match);
                for ($i=0; $i<count($parts); $i++) {
                    // see if we have a "next"
                    if (!isset($parts[$i+1])) {
                        continue;
                    }
                    $current = $parts[$i];
                    $next = $parts[$i+1];

                    if ($next == ($current + 1) || $next == ($current - 1)) {
                        $found -= 1;
                    }
                }
            }
            return ($found + 1) * 2;
        }

        return 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _symbolCount($passwordData){
        return ($passwordData['symbol']['count'] * 2);
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _symbolNumberMiddle($passwordData){
        // the Wolfram version only really accounts for numbers
        $password = substr($passwordData['raw'], 1, strlen($passwordData['raw']) - 2);
        preg_match_all('/[0-9]{1}/', $password, $matches);

        return (isset($matches[0])) ? count($matches[0]) * 2 : 0;
    }
    /**
     * @param array $passwordData Formatted password data
     * @return integer Resulting score
     */
    private function _upperCount($passwordData){
        preg_match_all('/[A-Z]{1}/', $passwordData['raw'], $matches);

        if (!empty($matches[0])) {
            // if there's only one, be sure it's not our password
            if (implode('', $matches[0]) == $passwordData['raw']) {
                return 0;
            }
            // otherwise, give credit for each one
            return (strlen($passwordData['raw']) - count($matches[0])) * 2;
        }
        return 0;
    }
}
/**
 * @author Jason
 * @copyright 2012
 */
class ResponseData {
	public $err_code;
	public $err_msg;
	public $data;

	public function __construct($data, $code = 0, $msg = 'success') {
		$this->err_code = $code;
		$this->err_msg = $msg;
		$this->data = $data;
	}

	public function toJsonString() {
		return json_encode($this);
	}
}
class ResponseError extends ResponseData {
	public function __construct($msg, $data = null) {
		parent::__construct($data, -1, $msg);
	}
}
class ResponseTimeout extends ResponseData {
	public function __construct($msg = '') {
		empty($msg) && $msg = '长时间未操作，请重新<a href="/rest/pl/fe/user/auth" target="_self">登录</a>！';
		parent::__construct(null, -2, $msg);
	}
}
class InvalidAccessToken extends ResponseData {
	public function __construct($msg = '没有获得有效访问令牌') {
		parent::__construct(null, -3, $msg);
	}
}
class ParameterError extends ResponseData {
	public function __construct($msg = '参数错误。') {
		parent::__construct(null, 100, $msg);
	}
}
class ObjectNotFoundError extends ResponseData {
	public function __construct($msg = '指定的对象不存在。') {
		parent::__construct(null, 100, $msg);
	}
}
class ResultEmptyError extends ResponseData {
	public function __construct($msg = '获得的结果为空。') {
		parent::__construct(null, 101, $msg);
	}
}
class ComplianceError extends ResponseData {
	public function __construct($msg = '业务逻辑错误。') {
		parent::__construct(null, 200, $msg);
	}
}
class DataExistedError extends ResponseData {
	public function __construct($msg = '数据已经存在。') {
		parent::__construct(null, 201, $msg);
	}
}
class DatabaseError extends ResponseData {
	public function __construct($msg = '数据库错误。') {
		parent::__construct(null, 900, $msg);
	}
}
/**
 * url找不到匹配的处理接口
 */
class UrlNotMatchException extends Exception {

}
/**
 * 包含用户信息的异常
 */
class SiteUserException extends Exception {
	/**
	 * 站点用户id
	 */
	protected $uid;

	public function __construct($msg, $uid) {
		parent::__construct($msg);
		$this->uid = $uid;
	}

	final public function getUserid() {
		return $this->uid;
	}
}
/**************************
 * 常用方法
 **************************/
/**
 * 数组中查找对象并返回
 */
function tms_array_search($array, $callback) {
	if (empty($array) || !is_array($array)) {
		return false;
	}

	foreach ($array as $item) {
		if ($callback($item)) {
			return $item;
		}
	}

	return false;
}
/**
 * 合并对象
 */
function tms_object_merge(&$oHost, $oNew, $fromProps = []) {
	if (empty($oHost) || empty($oNew) || !is_object($oHost)) {
		return $oHost;
	}
	if (empty($fromProps)) {
		foreach ($oNew as $prop => $val) {
			$oHost->{$prop} = $val;
		}
	} else {
		if (is_object($oNew)) {
			foreach ($fromProps as $prop) {
				if (isset($oNew->{$prop})) {
					$oHost->{$prop} = $oNew->{$prop};
				}
			}
		} else if (is_array($oNew)) {
			foreach ($fromProps as $prop) {
				if (isset($oNew[$prop])) {
					$oHost->{$prop} = $oNew[$prop];
				}
			}
		}
	}

	return $oHost;
}
/**
 * 时间变可读字符串
 */
function tms_time_to_str($timestamp) {
	$WeekdayZh = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
	$str = date('y年n月d日', $timestamp) . ' ' . $WeekdayZh[date('w', $timestamp)] . ' ' . date('H:i', $timestamp);

	return $str;
}
/**
 * 获取$_SERVER数据
 */
function tms_get_server($key, $escape = true){
	if (isset($_SERVER[$key])) {
		if ($escape === true) {
    		return TMS_MODEL::escape($_SERVER[$key]);
		} else {
			return $_SERVER[$key];
		}
	} else {
		return null;
	}
}
/**
 * 检查用户密码
 */
function tms_pwd_check($pwd, $options = [], $compel = false) {
    if ($compel === false) {
        switch (TMS_APP_PASSWORD_STRENGTH_CHECK) {
            case 0:
                return [true];
            case 9:
                return [false, '禁止注册'];
        }
    }

    // 过滤黑名单密码 $options['blackChars'] = []
    if (!empty($options['blackChars'])) {
        if (in_array($pwd, $options['blackChars'])) {
            return [false, '此口令存在风险请用其它口令'];
        }
    }

	$check = new PwdStrengthCheck($pwd);
    $parse = $check->parse();

	if ($parse['number']['count'] === 0 || $parse['upper']['count'] + $parse['lower']['count'] === 0 || $parse['symbol']['count'] === 0 || $parse['length'] < 8 || $parse['length'] > 16) {
		return [false, '必须包含数字、字母、特殊字符，且 8~16 位'];
    }
    // 判断特殊字符是否符合规范
    $rst = $check->verifySymbol($parse);
    if ($rst[0] === false) {
        return $rst;
    }
    // 过滤账号相关性 如果改变账号字符顺序以及大小写，如果与账号的匹配字符大于账号中字符数量-2个返回false
    if (!empty($options['account'])) {
        $rst = $check->verifyAccountRelation($parse, $options['account']);
        if ($rst[0] === false) {
            return $rst;
        }
    }
    // 机械键盘序 口令中不能包括连续的3个或3个以上键盘键位的字符,包括正、反、斜方向的顺序
    $rst = $check->verifyKeyboardSeq($parse);
    if ($rst[0] === false) {
        return $rst;
    }

	return [true];
}
/**
 * 生成随机密码 一个字符、一个大写字母、三个数字、三个小写字母，位置随机，字符随机
 */
function tms_pwd_create_random(int $upperNum = 1, int $lowerNum = 3, int $numberNum = 3, int $symbolNum = 1) {
    $numbers = '1234567890';
    $uppers = 'QWERTYUIOPLKJHGFDSAZXCVBNM';
    $lowers = strtolower($uppers);
    $symbols = ['~', '!', '@', '#','$', '%', '^', '&', '*', '(', ')', '[', ']', '{', '}', '|', ':', '+', '=', '<', '>', '?', ',', '.', ';', '_', '-', '`'];

    // 获取随机字符
    $symbol = [];
    for ($i = 0; $i < $symbolNum; $i++) {
        $symbol[] = $symbols[array_rand($symbols, 1)];
    }

    // 获取个大写字母
    $upper = [];
    for ($i = 0; $i < $upperNum; $i++) {
        $upper[] = $uppers[mt_rand(0, 25)];
    }
    // 获取小写字母
    $lower = [];
    for ($i = 0; $i < $lowerNum; $i++) {
        $lower[] = $lowers[mt_rand(0, 25)];
    }
    // 获取数字
    $number = [];
    for ($i = 0; $i < $numberNum; $i++) {
        $number[] = $numbers[mt_rand(0, 9)];
    }

    // 密码
    $pwd = array_merge($symbol, $upper, $lower, $number);
    // 打乱顺序
    shuffle($pwd);
    $pwd = implode('', $pwd);

    return $pwd;
}
/**
 * 检查登录条件
 */
function tms_login_check() {
    switch (TMS_APP_LOGIN_STRENGTH_CHECK) {
        case 0:
            return [true];
        case 1:
            if (tms_get_httpsOrHttp() === 'https') {
                return [true];
            } else {
                return [false, '登录失败，登录方式存在风险'];
            }
    }
}
/**
 * 检查当前请求是https还是http
 */
function tms_get_httpsOrHttp() {
    return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https' : 'http';
}