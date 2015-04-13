<?php
/**
 * 页面形式的多图文
 *
 * 支持按照访问控制权限进行过滤
 */
class page_news extends matter_page_base {
    /**
     *
     */
    public function __construct($id, $openid, $src)
    {
        $q = array(
            'id,mpid,title,access_control,authapis,"N" type,filter_by_matter_acl,empty_reply_type,empty_reply_id', 
            'xxt_news', 
            "id=$id"
        );
        $this->news = TMS_APP::model()->query_obj_ss($q);
        parent::__construct($this->news, $openid, $src);
    }
    /**
     * 
     *$runningMpid 当前运行的公众号
     */
    public function output($runningMpid)
    {
        $members = TMS_APP::model('user/member')->byOpenid($runningMpid, $this->openid);
        $matters2 = array();
        $matters = TMS_APP::model('matter/news')->getMatters($this->news->id);
        foreach ($matters as $m) {
            $m->url = TMS_APP::model('reply')->getMatterUrl($runningMpid, $m, $this->openid, $this->src);
            if ($m->access_control === 'Y' && $this->news->filter_by_matter_acl === 'Y') {
                $model = TMS_APP::model('acl');
                switch (lcfirst($m->type)) {
                case 'activity':
                    $actType = 'A';
                    break;
                case 'lottery':
                    $actType = 'L';
                    break;
                case 'discuss':
                    $actType = 'W';
                    break;
                case 'article':
                    $matterType = 'A';
                    break;
                case 'link':
                    $matterType = 'L';
                    break;
                }
                if (isset($actType)) {
                    $inacl = false;
                    foreach ($members as $member) {
                        if ($model->canAccessAct($runningMpid, $m->id, $actType, $member, $m->authapis)){
                            $inacl = true;
                            break;
                        }
                    }
                    if (!$inacl) continue;
                } else if (isset($matterType)) {
                    $inacl = false;
                    foreach ($members as $member) {
                        if ($model->canAccessMatter($runningMpid, $matterType, $m->id, $member, $m->authapis)){
                            $inacl = true;
                            break;
                        }
                    }
                    if (!$inacl) continue;
                }
            }
            $matters2[] = $m;
        }

        if (count($matters2) === 1) {
            header("Location: ".$matters2[0]->url);
            exit;
        } else if (count($matters2) === 0) {
            $m = TMS_APP::model('matter/base')->get_by_id($this->news->empty_reply_type, $this->news->empty_reply_id);
            $url = TMS_APP::model('reply')->getMatterUrl($runningMpid, $m, $this->openid, $this->src);
            header("Location: ".$url);
            exit;
        } else {
            $body_ele = TMS_APP::model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$mpid'");
            TPL::assign('list_title', $this->news->title);
            TPL::assign('body_ele', $body_ele);
            TPL::assign('matters', $matters2);
            TPL::output('article-list');
            exit;
        }
    }
}
