<?php
namespace matter\enroll;

/**
 * 题目的公式
 * 将用户输入的表达式转为逆波兰表达式计算
 */
class formula {
	/**
	 * 正则表达式，用于将表达式字符串，解析为单独的运算符和操作项
	 * (?:pattern)是非捕获型括号  匹配pattern，但不捕获匹配结果。让匹配结果仍然是完整的表达式。
	 */
	const PATTERN_EXP = '/(?:[a-zA-Z_]+|(?:\d+(?:\.\d+)?)|[\(\)\+\-\*\/]){1}/';
	const EXP_PRIORITIES = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, "(" => 0, ")" => 0];
	/**
	 *
	 * @param string $exp-普通表达式，例如 a+b*(c+d)
	 * @param $exp_values-表达式对应数据内容，例如 ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]
	 */
	public static function calculate($exp, $exp_values) {
		$exp_arr = self::parse_exp($exp); //将表达式字符串解析为列表
		if (!is_array($exp_arr)) {
			return null;
		}
		$output_queue = self::nifix2rpn($exp_arr);
		return self::calculate_value($output_queue, $exp_values);
	}

	//将字符串中每个操作项和运算符都解析出来
	public static function parse_exp($exp) {
		$match = [];
		preg_match_all(self::PATTERN_EXP, $exp, $match);

		return $match ? $match[0] : null;
	}

	//将中缀表达式转为后缀表达式
	protected static function nifix2rpn($input_queue) {
		$exp_stack = [];
		$output_queue = [];
		foreach ($input_queue as $input) {
			if (in_array($input, array_keys(self::EXP_PRIORITIES))) {
				if ($input == "(") {
					array_push($exp_stack, $input);
					continue;
				}
				if ($input == ")") {
					$tmp_exp = array_pop($exp_stack);
					while ($tmp_exp && $tmp_exp != "(") {
						array_push($output_queue, $tmp_exp);
						$tmp_exp = array_pop($exp_stack);
					}
					continue;
				}
				foreach (array_reverse($exp_stack) as $exp) {
					if (self::EXP_PRIORITIES[$input] <= self::EXP_PRIORITIES[$exp]) {
						array_pop($exp_stack);
						array_push($output_queue, $exp);
					} else {
						break;
					}
				}
				array_push($exp_stack, $input);
			} else {
				array_push($output_queue, $input);
			}
		}
		foreach (array_reverse($exp_stack) as $exp) {
			array_push($output_queue, $exp);
		}
		return $output_queue;
	}

	//传入后缀表达式队列、各项对应值的数组，计算出结果
	protected static function calculate_value($output_queue, $exp_values) {
		$res_stack = [];
		foreach ($output_queue as $out) {
			if (in_array($out, array_keys(self::EXP_PRIORITIES))) {
				$a = array_pop($res_stack);
				$b = array_pop($res_stack);
				switch ($out) {
				case '+':
					$res = $b + $a;
					break;
				case '-':
					$res = $b - $a;
					break;
				case '*':
					$res = $b * $a;
					break;
				case '/':
					$res = $b / $a;
					break;
				}
				array_push($res_stack, $res);
			} else {
				if (is_numeric($out)) {
					array_push($res_stack, floatval($out));
				} else {
					array_push($res_stack, isset($exp_values[$out]) ? $exp_values[$out] : 0);
				}
			}
		}

		return count($res_stack) == 1 ? $res_stack[0] : null;
	}
	/**
	 * 计算公式中内置函数的值并替换
	 */
	public static function calcAndReplaceInnerFunc($equation, $oSchema, $oContext) {
		/* 当前题目在活动的当前轮次中的填写的最小值 */
		if (strpos($equation, 'min()')) {
			if (isset($oContext->app->id)) {
				$valueOfMin = \TMS_MODEL::model('matter\enroll\data')->minBySchema($oContext->app, $oSchema);
			} else {
				$valueOfMin = 0;
			}
			$equation = str_replace('min()', $valueOfMin, $equation);
		}
		/* 当前题目在活动的当前轮次中的填写的最小值 */
		if (strpos($equation, 'max()')) {
			if (isset($oContext->app->id)) {
				$valueOfMax = \TMS_MODEL::model('matter\enroll\data')->maxBySchema($oContext->app, $oSchema);
			} else {
				$valueOfMax = 0;
			}
			$equation = str_replace('max()', $valueOfMax, $equation);
		}

		return $equation;
	}
}