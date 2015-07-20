<?php
namespace app\merchant;

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
     * 进入发起订单页
     *
     * 要求当前用户必须是认证用户
     * 
     * $mpid mpid'id
     * $shop shop'id
     * $sku sku'id
     */
    public function index_action($mpid, $shop, $sku, $mocker=null, $code=null)
    {
        /**
         * 获得当前访问用户
         */
        //$openid = $this->doAuth($mpid, $code, $mocker);
        $openid = '';
        
        $this->afterOAuth($mpid, $shop, $sku, $openid);
    }
    /**
     *
     */
    public function afterOAuth($mpid, $shopId, $skuId, $openid)
    {
        $this->view_action('/app/merchant/order');
    }
    /**
     * 购买商品
     */
    public function buy_action($mpid, $sku)
    {
        $openid = $this->getCookieOAuthUser($mpid);
        if (empty($openid))
            return new \ResponseError('无法获得当前用户身份信息');
        
        $orderInfo = $this->getPostJson();
        
        $order = $this->model('app\merchant\order')->create($sku, $orderInfo);
        
        return new \ResponseData('ok');
    }
}
