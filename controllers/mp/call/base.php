<?php
namespace mp\call;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class call_base extends \mp\mp_controller {
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        
        $mpa = $this->model('mp\mpaccount')->byId($this->mpid);
        
        $prights = $this->model('mp\permission')->hasMpRight(
            $this->mpid, 
            array('reply_text', 'reply_menu', 'reply_qrcode', 'reply_other'), 
            'read'
        );
        $entries = array();
        (true === $prights || $prights['reply_text']['read_p'] === 'Y') && $entries['text'] = array('title'=>'文本消息');
        (true === $prights || $prights['reply_menu']['read_p'] === 'Y') && $entries['menu'] = array('title'=>'菜单事件');
        $mpa->asparent==='N' && (true === $prights || $prights['reply_qrcode']['read_p'] === 'Y') && $entries['qrcode'] = array('title'=>'扫二维码');
        (true === $prights || $prights['reply_other']['read_p'] === 'Y') && $entries['other'] = array('title'=>'其他事件');
        $entries['timer'] = array('title'=>'定时推送');
        
        \TPL::assign('reply_view_entries', $entries);
    }
    /**
     * 设置访问白名单
     */
    public function setAcl_action($k)
    {
        if (empty($k)) die('parameters invalid.');

        $acl = $this->getPostJson();
        if (isset($acl->id)) {
            /**
             * 直接输入acl的情况下，才会产生更新操作，只允许更新identity这一个字段
             */
            $u['identity'] = $acl->identity;
            $rst = $this->model()->update('xxt_call_acl', $u, "id=$acl->id");
            return new \ResponseData($rst);
        } else {
            $i['mpid'] = $this->mpid;
            $i['call_type'] = $this->getCallType();
            $i['keyword'] = $k;
            $i['identity'] = $acl->identity;
            $i['idsrc'] = $acl->idsrc;
            $i['label'] = empty($acl->label) ? '' : $acl->label;
            $i['id'] = $this->model()->insert('xxt_call_acl', $i, true);

            return new \ResponseData($i);
        }
    }
    /**
     * 删除访问控制列表
     * $mpid
     * $id
     * $acl aclid
     */
    public function removeAcl_action($acl)
    {
        $ret = $this->model()->delete('xxt_call_acl', "mpid='$this->mpid' and id=$acl");

        return new \ResponseData($ret);
    }
}
