<?php
require_once dirname(dirname(__FILE__)).'/member_base.php';

class activity extends member_base {
    /**
     *
     */
    public function __construct() 
    {
    }
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
     * 获得图文 
     */
    public function index_action($aid)
    {
        $a = $this->model('activity/enroll')->byId($aid);
        /**
         * 是否支持按文章编号检索
         */
        if ($k = $this->model('reply')->canActivityCodesearch($a->mpid)) {
            $a->codesearchKeyword = $k;
        }

        return new ResponseData($a);
    }
    /**
     * 创建新活动
     *
     * 返回数据或编辑页面
     */
    public function create_action($mpid,$src,$openid)
    {
        // 获得的编码
        $acode = $this->model('activity/enroll')->code($mpid);

        $aid = uniqid();
        $newone['mpid'] = $mpid;
        $newone['aid'] = $aid;
        $newone['title'] = '新活动';
        $newone['promoter'] = $openid;
        $newone['src'] = $src;
        $newone['code'] = $acode;
        $newone['create_at'] = time();
        //todo default template
        //$newone['enroll_ele'] = '<div wrap="text" class="form-group">请填写如下信息：</div>'; 
        //$newone['state_ele'] = '<div wrap="text" class="form-group">活动报名成功。</div>'; 

        $this->model()->insert('xxt_activity', $newone, false);

        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            return new ResponseData($newone);
        } else {
            header("Location:/page/wbox/activity?aid=$aid");
            die();
        }
    }
    /**
     * 更新活动信息
     *
     * $id activity's id
     * $nv pair of name and value
     */
    public function update_action($aid) 
    {
        $nv = $this->getPostJson();

        $rst = $this->model()->update(
            'xxt_activity', 
            (array)$nv,
            "aid='$aid'"
        );

        return new ResponseData($rst);
    }
    /**
     * 删除活动
     *
     * 如果活动已经被使用，则标记为不可见
     */
    public function remove_action($aid)
    {
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
    public function roll_action($aid, $page=1, $size=30, $kw=null, $by=null, $contain=null) 
    {
        $mpid = '???';

        $options = array(
            'page' => $page,
            'size' => $size,
            'rid' => $rid,
            'kw' => $kw,
            'by' => $by,
            'contain' => $contain,
        );

        $result = $this->model('activity/enroll')->getRecords($mpid, $aid, $options);

        return new ResponseData($result);
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
        $result = $this->model('activity/enroll')->getStat($aid);

        return new ResponseData($result);
    }
    /**
     * 将活动参与者页面推送给活动的发起者
     */
    public function sendme_action($aid)
    {
        $a = $this->model('activity/enroll')->byId($aid);

        $url = 'http://'.$_SERVER['HTTP_HOST'].'/rest/activity?aid='.$aid;

        $msg = array(
            'msgtype'=>'news',
            'news'=>array(
                'articles'=>array(
                    array(
                        'title'=>urlencode($a->title),
                        'description'=>'',
                        'url'=>$url,
                        'picurl'=>'',
                    )
                )
            )
        );

        $this->send_to_user($a->mpid, $a->src, $a->promoter, $msg);

        return new ResponseData($msg);
    }
}
