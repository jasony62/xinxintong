<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 分组活动主控制器
 */
class main_base extends \pl\fe\matter\main_base {
    /**
     * 返回视图
     */
    public function index_action() {
        \TPL::output('/pl/fe/matter/group/frame');
        exit;
    }
    /**
     * 在调用每个控制器的方法前调用
     */
    public function tmsBeforeEach($app = null) {
        // 要求登录用户操作
        if (false === ($oUser = $this->accountUser())) {
            return [false, new \ResponseTimeout()];
        }
        $this->user = $oUser;
        // 分组活动基本信息
        if (!empty($app)) {
            $oApp = $this->model('matter\group')->byId($app);
            if (false === $oApp || $oApp->state !== '1') {
                return [false, new \ObjectNotFoundError()];
            }
            $this->app = $oApp;
        }

        return [true];
    }
}