<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)).'/base.php';
/**
 * 商品分类
 */
class catelog extends \mp\app\app_base {
    /**
     * 打开订购商品管理页面
     */
    public function index_action() 
    {
        $this->view_action('/mp/app/merchant/shop');
    }
    /**
     * $shopId
     */
    public function get_action($shopId) 
    {
        $catelogs = $this->model('app\merchant\catelog')->byShopId($shopId);
        
        return new \ResponseData($catelogs);
    }
    /**
     * 关联的数据
     *
     * $id
     */
    public function cascaded_action($id) 
    {
        $cascaded = $this->model('app\merchant\catelog')->cascaded($id);
        
        return new \ResponseData($cascaded);
    }
    /**
     * $shopId
     */
    public function create_action($shopId) 
    {
        $creater = \TMS_CLIENT::get_client_uid();
        
		$cate = array(
			'mpid' => $this->mpid,
			'sid' => $shopId,
			'create_at' => time(),
			'creater' => $creater,
            'name' => '新分类'
		);
        
        $cate['id'] = $this->model()->insert('xxt_merchant_catelog', $cate, true);
        
        return new \ResponseData($cate);
    }
    /**
     * 更新分类的基础信息
     */
    public function update_action($id) 
    {
        $reviser = \TMS_CLIENT::get_client_uid();
        
        $nv = $this->getPostJson();
        
        $nv->reviser = $reviser;
        $nv->modify_at = time();
        
        $rst = $this->model()->update('xxt_merchant_catelog', (array)$nv, "id='$id'");
        
        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function remove_action() 
    {
        return new \ResponseData('ok');
    }
    /**
     * 
     */
    public function propertyGet_action() 
    {
        return new \ResponseData('ok');
    }
    /**
     * 添加属性
     * 
     * $id catelog's id
     */
    public function propCreate_action($id) 
    {
        $cate = $this->model('app\merchant\catelog')->byId($id);
        if (false === $cate)
            return new \ResponseError('指定的分类不存在，无法添加属性');
            
        $creater = \TMS_CLIENT::get_client_uid();
        
		$prop = array(
			'mpid' => $this->mpid,
			'sid' => $cate->sid,
			'cate_id' => $id,
			'create_at' => time(),
			'creater' => $creater,
            'name' => '新属性'
		);
        
        $prop['id'] = $this->model()->insert('xxt_merchant_catelog_property', $prop, true);
        
        return new \ResponseData($prop);
    }
    /**
     *
     */
    public function propUpdate_action() 
    {
        $updated = $this->getPostJson();
        
        $data = array();
        $data['name'] = $updated->name;
        
        $rst = $this->model()->update('xxt_merchant_catelog_property', $data, "id=$updated->id");
        
        return new \ResponseData($data);
    }
    /**
     *
     */
    public function propRemove_action($id) 
    {
        $rst = $this->model()->delete('xxt_merchant_catelog_property', "id=$id");
        
        return new \ResponseData($rst);
    }
    /**
     * 
     */
    public function skuGet_action() 
    {
        return new \ResponseData('ok');
    }
    /**
     *
     */
    public function skuCreate_action() 
    {
        return new \ResponseData('ok');
    }
    /**
     *
     */
    public function skuUpdate_action() 
    {
        return new \ResponseData('ok');
    }
    /**
     *
     */
    public function skuRemove_action() 
    {
        return new \ResponseData('ok');
    }
}
