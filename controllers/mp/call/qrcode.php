<?php
namespace mp\call;

require_once dirname(__FILE__).'/base.php';
/**
 *
 */
class qrcode extends call_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        $rule_action['actions'][] = 'createOneOff';

        return $rule_action;
    }
    /**
     * get all text call.
     */
    public function index_action() 
    {
        $this->view_action('/mp/reply/qrcode');
    }
    /**
     * get all qrcode calls.
     *
     * 只返回永久二维码，不包含临时二维码
     */
    public function get_action() 
    {
        /**
         * 公众号自己的文本消息回复
         */
        $q = array(
            '*', 
            'xxt_call_qrcode', 
            "mpid='$this->mpid' and expire_at=0"
        );
        $q2['o'] = 'id desc';

        $calls = $this->model()->query_objs_ss($q, $q2);

        return new \ResponseData($calls); 
    }
    /**
     * get one text call.
     *
     * $id int text call id.
     * $contain array 
     */
    private function &get_by_id($id, $contain=array('matter')) 
    {
        $q = array(
            'id,mpid,name,pic,matter_type,matter_id',
            'xxt_call_qrcode',
            "id=$id"
        );
        $call = $this->model()->query_obj_ss($q);

        if (!empty($contain) && in_array('matter', $contain))
            if ($call->matter_id)
                $call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);

        return $call;
    }
    /**
     * 创建一个二维码响应
     *
     * todo 企业号怎么办？
     *
     * 易信的永久二维码最大值1000
     * 微信的永久二维码最大值100000
     */
    public function create_action() 
    {
        $mpa = $this->model('mp\mpaccount')->byId($this->mpid);
        /**
         * 获取可用的场景ID
         */
        $q = array(
            'max(scene_id)',
            'xxt_call_qrcode',
            "mpid='$this->mpid' and expire_at=0"
        );
        if ($scene_id = $this->model()->query_val_ss($q))
            $scene_id++;
        else
            $scene_id = 1;
        /**
         * 生成二维码
         */
        $mpproxy = $this->model('mpproxy/'.$mpa->mpsrc, $this->mpid);
        $rst = $mpproxy->qrcodeCreate($scene_id, false);
        if ($rst[0] === false)
            return new \ResponseError($rst[1]);
        $qrcode = $rst[1];
        /**
         * 保存数据并返回
         */
        $d['mpid'] = $this->mpid;
        $d['name'] = '新场景二维码';
        $d['scene_id'] = $qrcode->scene_id;
        $d['pic'] = $qrcode->pic;

        $d['id'] = $this->model()->insert('xxt_call_qrcode', $d, true);
        
        return new \ResponseData((object)$d);
    }
    /**
     * 更新的基本信息
     *
     * $mpid
     * $id
     */
    public function update_action($id) 
    {
        $nv = $this->getPostJson();
        $rst = $this->model()->update(
            'xxt_call_qrcode', 
            (array)$nv,
            "mpid='$this->mpid' and id=$id"
        );
        return new \ResponseData($rst);
    }
    /**
     * 指定回复素材
     *
     * //todo 如果是父账号的资源怎么办？
     */
    public function matter_action($id, $type) 
    {
        $matter = $this->model('matter\base')->getMatterInfoById($type, $id);

        return new \ResponseData($matter);
    }
    /**
     * 创建一次性二维码
     *
     * 用临时二维码实现
     * 创建二维码时直接指定回复的素材
     * 只要做了扫描，二维码就失效（删除掉）
     */
    public function createOneOff_action($matter_type, $matter_id, $mpid=null)
    {
        $mpid === null && $mpid = $this->mpid;

        $mp = $this->model('mp\mpaccount')->getApis($mpid);

        if ($mp->mpsrc === 'qy')
            return new \ResponseError('目前企业号不支持场景二维码');
        else if ($mp->mpsrc === 'yx' && $mp->yx_qrcode === 'N')
            return new  \ResponseError('公众号还没有开通场景二维码接口');
        else if ($mp->mpsrc === 'wx' && $mp->wx_qrcode === 'N')
            return new  \ResponseError('公众号还没有开通场景二维码接口');
        else if (empty($mp->mpsrc))
            return new  \ResponseError("无法确定公众号的类型");

        $scene_id = mt_rand(100001, mt_getrandmax());
        while(true) {
            $q = array(
                'count(*)',
                'xxt_call_qrcode',
                "mpid='$mpid' and expire_at<>0 and scene_id=$scene_id"
            );
            if (1 === (int)$this->model()->query_val_ss($q))
                $scene_id = mt_rand(100001, mt_getrandmax());
            else
                break;
        }
        /**
         * 获去二维码的ticket
         */
        $mpproxy = $this->model('mpproxy/'.$mp->mpsrc, $mpid);
        $rst = $mpproxy->qrcodeCreate($scene_id);
        if ($rst[0] === false)
            return new \ResponseError($rst[1]);
        $qrcode = $rst[1];
        /**
         * 保存数据并返回
         */
        $d['mpid'] = $mpid;
        $d['name'] = '';
        $d['scene_id'] = $qrcode->scene_id;
        $d['expire_at'] = time() + $qrcode->expire_seconds - 30;
        $d['matter_type'] = $matter_type;
        $d['matter_id'] = $matter_id;
        $d['pic'] = $qrcode->pic;

        $d['id'] = $this->model()->insert('xxt_call_qrcode', $d, true);

        return new \ResponseData((object)$d);
    }
}
