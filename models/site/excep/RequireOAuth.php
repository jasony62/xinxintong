<?php
namespace site\fe\excep;
/**
 *
 */
class RequireOAuth extends \Exception {
	public function __construct($msg) {
		parent::__construct($msg);
	}
}