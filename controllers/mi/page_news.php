<?php
namespace mi;
/**
 * 页面形式的多图文
 *
 * 支持按照访问控制权限进行过滤
 */
class page_news extends matter_page_base {
    /**
     *
     */
    public function __construct($id, $openid)
    {
        $this->news = \TMS_APP::M('matter\news')->byId($id, "*,'news' type");
        parent::__construct($this->news, $openid);
    }
    /**
     *
     * $runningMpid 当前运行的公众号
     */
    public function output($runningMpid)
    {
        $members = \TMS_APP::M('user/member')->byOpenid($runningMpid, $this->openid);
        $matters = \TMS_APP::M('matter\news')->getMatters($this->news->id);
        $modelAcl = \TMS_APP::M('acl');

        $matters2 = array();
        foreach ($matters as $m) {
            if ($m->access_control === 'Y' && $this->news->filter_by_matter_acl === 'Y') {
                $inacl = false;
                foreach ($members as $member) {
                    if ($modelAcl->canAccessMatter($runningMpid, $m->type, $m->id, $member, $m->authapis)){
                        $inacl = true;
                        break;
                    }
                }
                if (!$inacl) continue;
            }
            $m->url = \TMS_APP::M('matter\\'.$m->type)->getEntryUrl($runningMpid, $m->id, $this->openid);
            $matters2[] = $m;
        }

        if (count($matters2) === 1) {
            header("Location: ".$matters2[0]->url);
            exit;
        } else if (count($matters2) === 0) {
            $url = \TMS_APP::M('matter\\'.$this->news->empty_reply_type)->getEntryUrl($runningMpid, $this->news->empty_reply_id, $this->openid);
            header("Location: ".$url);
            exit;
        } else {
            $body_ele = \TMS_APP::model()->query_value('body_ele', 'xxt_mpsetting', "mpid='$runningMpid'");
            \TPL::assign('list_title', $this->news->title);
            \TPL::assign('body_ele', $body_ele);
            \TPL::assign('matters', $matters2);
            \TPL::output('article-list');
            exit;
        }
    }
}
