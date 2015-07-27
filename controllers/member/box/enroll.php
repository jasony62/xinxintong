<?php
namespace member\box;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 *
 */
class enroll extends \member_base {
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
     *
     * $mpid
     * $id
     * $code
     * $mocker
     */
    public function index_action($mpid, $id, $code=null, $mocker=null)
    {
        $openid = $this->doAuth($mpid, $code, $mocker);

        $this->view_action('/member/box/enroll/main');
    }
    /**
     *
     * $mpid
     * $id
     * $code
     * $mocker
     */
    public function get_action($mpid, $id, $code=null, $mocker=null)
    {
        $openid = $this->doAuth($mpid, $code, $mocker);

        $enroll = $this->model('app\enroll')->byId($id);

        return new \ResponseData($enroll);
    }
    /**
     * 创建一个空的登记活动
     */
    public function create_action($mpid) 
    {
        $openid = $this->getCookieOAuthUser($mpid);

        $fan = $this->model('user/fans')->byOPenid($mpid, $openid);
        /**
         * 获得的基本信息
         */
        $appid = uniqid();
        $newone['mpid'] = $mpid;
        $newone['id'] = $appid;
        $newone['title'] = '新登记活动';
        $newone['creater'] = $fan->fid;
        $newone['creater_src'] = 'F';
        $newone['creater_name'] = $fan->nickname;
        $newone['create_at'] = time();
        /**
         * 创建定制页
         */
        $page = $this->model('code/page')->create($fan->fid);
        $newone['form_code_id'] = $page->id;
        $page = array(
            'title' => '查看结果页',
            'type' => 'V',
        );
        $this->model('app\enroll')->addPage($this->mpid, $aid, $page);
        
        $this->model()->insert('xxt_enroll', $newone, false);

        $app = $this->model('app\enroll')->byId($appid);

        return new \ResponseData($app);
    }
    /**
     * 复制一个登记活动
     *
     * $mpid
     * $shopid
     */
    public function copy_action($mpid, $shopid)
    {
        if (empty($shopid))
            return new \ResponseError('没有指定要复制登记活动id');

        $openid = $this->getCookieOAuthUser($mpid);

        $fan = $this->model('user/fans')->byOpenid($mpid, $openid);

        $current = time();
        $enrollModel = $this->model('app\enroll');
        $codeModel = $this->model('code/page');

        $shopItem = $this->model('shop\shelf')->byId($shopid);
        $copied = $enrollModel->byId($shopItem->matter_id);
        $copied->title = $shopItem->title;
        $copied->summary = $shopItem->summary;
        $copied->pic = $shopItem->pic;
        /**
         * 获得的基本信息
         */
        $newaid = uniqid();
        $newact['mpid'] = $mpid;
        $newact['id'] = $newaid;
        $newact['creater'] = $fan->fid;
        $newact['creater_src'] = 'F';
        $newact['creater_name'] = $fan->nickname;
        $newact['create_at'] = $current;
        $newact['title'] = $copied->title.'（副本）';
        $newact['pic'] = $copied->pic;
        $newact['summary'] = $copied->summary;
        $newact['public_visible'] = 'N';
        $newact['open_lastroll'] = $copied->open_lastroll;
        $newact['can_signin'] = $copied->can_signin;
        $newact['can_lottery'] = 'N';
        $newact['enrolled_entry_page'] = $copied->enrolled_entry_page;
        $newact['receiver_page'] = $copied->receiver_page;
        /**
         * 复制固定页面 
         */
        $code = $codeModel->create($fan->fid);
        $copiedCode = $codeModel->byId($copied->form_code_id);
        $data = array(
            'html'=>$copiedCode->html,
            'css'=>$copiedCode->css,
            'js'=>$copiedCode->js
        );
        $codeModel->modify($code->id, $data);
        $newact['form_code_id'] = $code->id;
        
        $this->model()->insert('xxt_enroll', $newact, false);
        /**
         * 复制自定义页面
         */
        $extraPages = $enrollModel->getPages($shopItem->matter_id);
        foreach ($extraPages as $ep) {
            $newPage = $enrollModel->addPage($mpid, $newaid); 
            $rst = $this->model()->update(
                'xxt_enroll_page', 
                array('title'=>$ep->title,'name'=>$ep->name), 
                "aid='$newaid' and id=$newPage->id"
            );
            $data = array(
                'title'=>$ep->title,
                'html'=>$ep->html,
                'css'=>$ep->css,
                'js'=>$ep->js
            );
            $codeModel->modify($newPage->code_id, $data);
        }


        $app = $enrollModel->byId($newaid);

        return new \ResponseData($app);
    }
    /**
     * 更新活动的属性信息
     *
     * $id
     */
    public function update_action($mpid, $id) 
    {
        $nv = $this->getPostJson();
        
        if (isset($nv->pic)) {
            /**
             * 上传图片，转换为URL
             */
            if (!empty($nv->pic)) {
                $fsuser = \TMS_APP::model('fs/user', $mpid);
                $rst = $fsuser->storeImg((object)array('imgSrc'=>$nv->pic));
                if (false === $rst[0])
                    return $rst;
                $nv->pic = $rst[1];
            }
        }
        $rst = $this->model()->update('xxt_enroll', (array)$nv, "id='$id'");

        return new \ResponseData($rst);
    }
    /**
     * 添加活动页面
     *
     * $aid 获动的id
     */
    public function addPage_action($aid)
    {
        $newPage = $this->model('app\enroll')->addPage($this->mpid, $aid); 

        return new \ResponseData($newPage);
    }
    /**
     * 更新活动的页面的属性信息
     *
     * $id 活动的id
     * $cid 页面对应code page id
     */
    public function updPage_action($mpid, $cid) 
    {
        $nv = $this->getPostJson();

        $rst = 0;
        if (isset($nv->html)) {
            $data = array(
                'html'=>$nv->html
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else if (isset($nv->js)) {
            $data = array(
                'js'=>$nv->js
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        } else if (isset($nv->css)) {
            $data = array(
                'css'=>$nv->css
            );
            $rst = $this->model('code/page')->modify($cid, $data);
        }

        return new \ResponseData($rst);
    }
    /**
     * 删除活动的页面
     *
     * $id
     * $pid
     */
    public function delPage_action($id, $pid)
    {
        $page = $this->model('app\enroll')->getPage($id, $pid);

        $this->model('code/page')->remove($page->code_id);

        $rst = $this->model()->delete('xxt_enroll_page', "aid='$id' and id=$pid");

        return new \ResponseData($rst);
    }
    /**
     * 活动报名名单
     *
     * 1、如果活动仅限会员报名，那么要叠加会员信息
     * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     */
    public function records_action($mpid, $id, $page=1, $size=30, $kw=null, $by=null, $contain=null) 
    {
        $options = array(
            'page' => $page,
            'size' => $size,
            'kw' => $kw,
            'by' => $by,
            'contain' => $contain,
        );

        $result = $this->model('app\enroll')->getRecords($mpid, $id, $options);

        return new \ResponseData($result);
    }
    /**
     * 清空一条登记信息
     */
    public function removeRoll_action($id, $key)
    {
        $rst = $this->model('app\enroll')->removeRoll($id, $key);

        return new \ResponseData($rst);
    }
    /**
     * 清空登记信息
     */
    public function clean_action($aid)
    {
        $rst = $this->model('app\enroll')->cleanRoll($aid);

        return new \ResponseData($rst);
    }
    /**
     * 删除一个活动
     *
     * 如果没有报名数据，就将活动彻底删除
     * 否则只是打标记
     */
    public function remove_action($aid)
    {
        $q = array(
            'count(*)',
            'xxt_enroll_record',
            "mpid='$this->mpid' and aid='$aid'"
        );
        if ((int)$this->model()->query_val_ss($q) > 0)
            $rst = $this->model()->update(
                'xxt_enroll', 
                array('state'=>0),
                "mpid='$this->mpid' and id='$aid'"
            );
        else
            $rst = $this->model()->delete(
                'xxt_enroll', 
                "mpid='$this->mpid' and id='$aid'"
            );

        return new \ResponseData($rst);
    }
    /**
     * 统计登记信息
     *
     * 只统计radio/checkbox类型的数据项
     *
     * return
     * name => array(l=>label,c=>count)
     *
     */
    public function stat_action($aid)
    {
        $result = $this->model('app\enroll')->getStat($aid);

        return new \ResponseData($result);
    }
    /**
     * 更新报名信息
     *
     * $ek enroll_key
     */
    public function updateRoll_action($aid, $ek) 
    {
        $roll = $this->getPostJson();

        foreach ($roll as $k=>$v) {
            if (in_array($k, array('signin_at','tags','comment')))
                $this->model()->update(
                    'xxt_enroll_record', 
                    array($k=>$v), 
                    "enroll_key='$ek'"
                );
            else if ($k === 'data' and is_object($v)) {
                foreach ($v as $cn=>$cv) {
                    /**
                     * 检查数据项是否存在，如果不存在就先创建一条
                     */
                    $q = array(
                        'count(*)',
                        'xxt_enroll_record_data',
                        "enroll_key='$ek' and name='$cn'"
                    );
                    if (1 === (int)$this->model()->query_val_ss($q))
                        $this->model()->update(
                            'xxt_enroll_record_data', 
                            array('value'=>$cv), 
                            "enroll_key='$ek' and name='$cn'"
                        );
                    else {
                        $cd = array(
                            'aid'=>$aid,
                            'enroll_key'=>$ek,
                            'name'=>$cn,
                            'value'=>$cv
                        );
                        $this->model()->insert(
                            'xxt_enroll_record_data', 
                            $cd
                        );
                    }
                }
            }
        }

        return new \ResponseData('success');
    }
    /**
     * 手工添加报名信息
     */
    public function addRoll_action($aid) 
    {
        $d = (array)$this->getPostJson();
        /**
         * 报名记录
         */
        $current = time();
        $enroll_key = $this->model('app\enroll')->genEnrollKey($this->mpid, $aid);
        $r = array();
        $r['aid'] = $aid;
        $r['mpid'] = $this->mpid;
        $r['enroll_key'] = $enroll_key;
        $r['enroll_at'] = $current;
        $r['signin_at'] = $current;
        if (isset($d['tags'])) $r['tags'] = $d['tags'];

        $id = $this->model()->insert('xxt_enroll_record', $r, true);

        $r['id'] = $id;
        /**
         * 登记信息
         */
        foreach ($d as $n => $v) {
            if (in_array($n, array('signin_at','tags','comment')))
                continue;
            $cd = array(
                'aid'=>$aid,
                'enroll_key'=>$enroll_key,
                'name'=>$n,
                'value'=>$v
            );
            $this->model()->insert(
                'xxt_enroll_record_data', 
                $cd
            );
            $r[$n] = $v;
        }

        return new \ResponseData($r);
    }
}
