<?php
require_once dirname(__FILE__).'/member_base.php';
/**
 *
 */
class q extends member_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'index';

        return $rule_action;
    }
    /**
     *
     */
    public function index_action($code=null) 
    {
        if (empty($code)) {
            TPL::output('quick-entry');
            exit;
        } else {
            $task = $this->model('task')->getTask($code);
            if (false === $task) {
                $this->outputError('任务不存在');
            }
            $fan = $this->model('user/fans')->byId($task->fid);
            $this->setCookieOAuthUser($task->mpid, $fan->openid);
            $this->redirect($task->url);
        }
    }
}
