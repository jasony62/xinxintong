<?php
/**
 * 校验密码强度
 * 可以实现密码强度等级划分
 */
class pwdStrengthCheck{
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
    public function parse(){
        $password = $this->password;
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
function tms_pwd_check($pwd) {
	if (TMS_APP_PASSWORD_STRENGTH_CHECK === 0) {
		return [true];
	}

	$pwd = trim($pwd);
	if (empty($pwd)) {
		return [false, '密码不能为空'];
	}
	$check = new pwdStrengthCheck($pwd);
	$parse = $check->parse();
	if ($parse['number']['count'] === 0) {
		return [false, '密码中必须包含数字'];
	}
	if ($parse['upper']['count'] + $parse['lower']['count'] === 0) {
		return [false, '密码中必须包含字母'];
	}
	if ($parse['symbol']['count'] === 0) {
		return [false, '密码中必须包含特殊符号'];
	}
	if ($parse['length'] < 8) {
		return [false, '密码长度需要大于8位'];
	}

	return [true];
}