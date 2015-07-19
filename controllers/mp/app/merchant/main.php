<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)).'/base.php';
/**
 * 商店管理
 */
class main extends \mp\app\app_base {
    /**
     *
     */
    protected function getMatterType() 
    {
        return 'shop';
    }
    /**
     * 商店列表
     */
    public function index_action($shopId=null) 
    {
        if ($shopId === null)
            $this->view_action('/mp/app/merchant');
        else
            $this->view_action('/mp/app/merchant/shop');
    }
    /**
     * 商店列表
     */
    public function get_action() 
    {
        $shops = $this->model('app\merchant\shop')->byMpid($this->mpid);
        
        return new \ResponseData($shops);
    }
    /**
	 * 创建新商店
	 */
	public function shopCreate_action()
	{
        $creater = \TMS_CLIENT::get_client_uid();
        $creater_name = \TMS_CLIENT::account()->nickname;
        
		$shop = array(
			'mpid' => $this->mpid,
			'create_at' => time(),
			'creater' => $creater,
			'creater_name' => $creater_name,
			'title' => '新商店',
			'pic' => '',
            'summary' => '新商店'
		);
        
        $shopId = $this->model()->insert('xxt_merchant_shop', $shop, true);
        
        return new \ResponseData($shopId);
	}
}
