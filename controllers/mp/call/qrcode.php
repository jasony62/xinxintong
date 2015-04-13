<?php
require_once dirname(dirname(__FILE__)).'/mp_controller.php';
/**
 *
 */
class qrcode extends mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * get all qrcode calls.
     *
     * 只返回永久二维码，不包含临时二维码
     */
    public function index_action($src) 
    {
        /**
         * 公众号自己的文本消息回复
         */
        $q = array(
            '*', 
            'xxt_qrcode_call_reply', 
            "mpid='$this->mpid' and src='$src' and expire_at=0"
        );
        $q2['o'] = 'id desc';

        $calls = $this->model()->query_objs_ss($q, $q2);

        return new ResponseData($calls); 
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
            'xxt_qrcode_call_reply',
            "id=$id"
        );
        $call = $this->model()->query_obj_ss($q);

        if (!empty($contain) && in_array('matter', $contain))
            if ($call->matter_id)
                $call->matter = $this->get_matter($call->mpid, $call->matter_type, $call->matter_id);

        return $call;
    }
    /**
     * 获得素材
     */
    private function get_matter($mpid, $type, $id)
    {
        $m = $this->model('matter/base')->get_by_id($type, $id);
        $m->type = $type;
        return $m;
    }
    /**
     * 创建一个二维码响应
     *
     * todo 企业号怎么办？
     *
     * 易信的永久二维码最大值1000
     * 微信的永久二维码最大值100000
     */
    public function create_action($src) 
    {
        /**
         * 获取可用的场景ID
         */
        $q = array(
            'max(scene_id)',
            'xxt_qrcode_call_reply',
            "mpid='$this->mpid' and src='$src' and expire_at=0"
        );
        if ($scene_id = $this->model()->query_val_ss($q))
            $scene_id++;
        else
            $scene_id = 1;
        /**
         * 获去二维码的ticket
         */
        if ($src === 'yx')
            $cmd = 'https://api.yixin.im/cgi-bin/qrcode/create';
        else if ($src === 'wx')
            $cmd = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';

        $posted = array(
            "action_name"=>"QR_LIMIT_SCENE", 
            "action_info"=>array(
                "scene"=>array("scene_id"=>$scene_id)
            )
        );
        $posted = json_encode($posted);
        $rst = $this->postToMp($this->mpid, $src, $cmd, $posted);
        if (false === $rst[0])
            return new ResponseError($rst[1]);
        $ticket = $rst[1]->ticket;
        if ($src === 'yx')
            $pic = "https://api.yixin.im/cgi-bin/qrcode/showqrcode?ticket=$ticket";
        else if ($src === 'wx')
            $pic = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
        /**
         * 保存数据并返回
         */
        $d['mpid'] = $this->mpid;
        $d['src'] = $src;
        $d['name'] = '新场景二维码';
        $d['scene_id'] = $scene_id;
        $d['pic'] = $pic;

        $d['id'] = $this->model()->insert('xxt_qrcode_call_reply', $d, true);
        
        return new ResponseData((object)$d);
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
            'xxt_qrcode_call_reply', 
            (array)$nv,
            "mpid='$this->mpid' and id=$id"
        );
        return new ResponseData($rst);
    }
    /**
     * 指定回复素材
     *
     * //todo 如果是父账号的资源怎么办？
     */
    public function matter_action($id, $type) 
    {
        $matter = $this->get_matter($this->mpid, $type, $id);

        return new ResponseData($matter);
    }
    /**
     * 创建一次性二维码
     *
     * 用临时二维码实现
     * 创建二维码时直接指定回复的素材
     * 只要做了扫描，二维码就失效（删除掉）
     */
    public function createOneOff_action($matter_type, $matter_id)
    {
        $mp = $this->model('mp\mpaccount')->getApis($this->mpid);

        if ($mp->mpsrc === 'qy')
            return new ResponseError('目前企业号不支持场景二维码');
        else if ($mp->mpsrc === 'yx' && $mp->yx_qrcode === 'N')
            return new  ResponseError('公众号还没有开通场景二维码接口');
        else if ($mp->mpsrc === 'wx' && $mp->wx_qrcode === 'N')
            return new  ResponseError('公众号还没有开通场景二维码接口');
        else if (empty($mp->mpsrc))
            return new  ResponseError("无法确定公众号的类型");

        $scene_id = mt_rand(100001, mt_getrandmax());
        while(true) {
            $q = array(
                'count(*)',
                'xxt_qrcode_call_reply',
                "mpid='$this->mpid' and src='$mp->mpsrc' and expire_at<>0 and scene_id=$scene_id"
            );
            if (1 === (int)$this->model()->query_val_ss($q))
                $scene_id = mt_rand(100001, mt_getrandmax());
            else
                break;
        }
        /**
         * 获去二维码的ticket
         */
        if ($mp->mpsrc === 'yx')
            $cmd = 'https://api.yixin.im/cgi-bin/qrcode/create';
        else if ($mp->mpsrc === 'wx')
            $cmd = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';

        $posted = array(
            "action_name"=>"QR_SCENE",
            "action_info"=>array(
                "expire_seconds"=>1800,
                "scene"=>array("scene_id"=>$scene_id)
            )
        );
        $posted = json_encode($posted);
        $rst = $this->postToMp($this->mpid, $mp->mpsrc, $cmd, $posted);
        if (false === $rst[0])
            return new ResponseError($rst[1]);
        $ticket = $rst[1]->ticket;
        if ($mp->mpsrc === 'yx')
            $pic = "https://api.yixin.im/cgi-bin/qrcode/showqrcode?ticket=$ticket";
        else if ($mp->mpsrc === 'wx')
            $pic = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
        /**
         * 保存数据并返回
         */
        $d['mpid'] = $this->mpid;
        $d['src'] = $mp->mpsrc;
        $d['name'] = '';
        $d['scene_id'] = $scene_id;
        $d['expire_at'] = time() + $rst[1]->expire_seconds - 30;
        $d['matter_type'] = $matter_type;
        $d['matter_id'] = $matter_id;
        $d['pic'] = $pic;

        $d['id'] = $this->model()->insert('xxt_qrcode_call_reply', $d, true);

        return new ResponseData((object)$d);
    }
}
