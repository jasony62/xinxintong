<?php
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
    public function __construct($msg) {
        parent::__construct(null, -1, $msg);
    }
}
class ParameterError extends ResponseData {
    public function __construct($msg = '参数错误。') {
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
