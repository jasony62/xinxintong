<?php
namespace op\merchant;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * 订单
 */
class order extends \member_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 查看订单
     */
    public function get_action($mpid, $order)
    {
        //$openid = $this->getCookieOAuthUser($mpid);
        //if (empty($openid))
        //    return new \ResponseError('无法获得当前用户身份信息');
        
        $order = $this->model('app\merchant\order')->byId($order);
        
        return new \ResponseData(array('order' => $order));
    }
    /**
     * 查询订单
     */
    public function list_action($mpid, $shop)
    {
        //$openid = $this->getCookieOAuthUser($mpid);
        //if (empty($openid))
        //    return new \ResponseError('无法获得当前用户身份信息');
        
        $orders = $this->model('app\merchant\order')->byShopid($shop);
        
        return new \ResponseData($orders);
    }
}
