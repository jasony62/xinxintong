<?php
namespace reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 文本回复
 */
class text_model extends Reply {
	/**
	 *
	 */
	private $content;
	/**
	 *
	 * $call
	 * $content 回复的内容
	 * $referred 指明$content是直接回复的内容，还是定义的文本素材
	 */
	public function __construct($call, $content, $referred = true) {
		parent::__construct($call);
		if ($referred) {
			if ($txt = \TMS_APP::model('matter\text')->byId($content, 'content')) {
				$content = $txt->content;
			} else if (!empty($content)) {
				$content = "文本回复【$content】不存在";
			} else {
				$content = "没有指定回复信息";
			}
		}
		$this->content = $content;
	}
	/**
	 *
	 */
	public function exec() {
		$r = $this->textResponse($this->content);
		die($r);
	}
}