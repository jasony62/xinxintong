<?php
/**
 * 平台首页
 */
class home extends TMS_CONTROLLER {
    /**
     *
     */
    public function get_access_rule() {
        $ruleAction = [
            'rule_type' => 'black',
        ];

        return $ruleAction;
    }
    /**
     *
     */
    public function index_action($template = 'basic') {
        $current = time();
        $modelPl = $this->model('platform');
        $platform = $modelPl->get();
        $modelCode = $this->model('code\page');
        $template = $modelPl->escape($template);
        //自动更新主页页面
        if ($platform->autoup_homepage === 'Y' && !empty($template)) {
            $oHomePage = $modelCode->lastPublishedByName('platform', $platform->home_page_name, ['fields' => 'id,create_at']);
            $templatePageMTimes = $this->_getPageMTime('home', $template);
            if (false === $oHomePage || $templatePageMTimes['upAtHtml'] > $oHomePage->create_at || $templatePageMTimes['upAtCss'] > $oHomePage->create_at || $templatePageMTimes['upAtJs'] > $oHomePage->create_at) {
                //更新主页页面
                $data = $this->_makePage('home', $template);
                $data['create_at'] = $current;
                $data['modify_at'] = $current;
                $modelCode->modify($platform->{'home_page_id'}, $data);
            }
        }
        if ($platform->autoup_sitepage === 'Y' && !empty($template)) {
            $oSitePage = $modelCode->lastPublishedByName('platform', $platform->site_page_name, ['fields' => 'id,create_at']);
            $templatePageMTimes = $this->_getPageMTime('site', $template);
            if (false === $oSitePage || $templatePageMTimes['upAtHtml'] > $oSitePage->create_at || $templatePageMTimes['upAtCss'] > $oSitePage->create_at || $templatePageMTimes['upAtJs'] > $oSitePage->create_at) {
                //更新主页页面
                $data = $this->_makePage('site', $template);
                $data['create_at'] = $current;
                $data['modify_at'] = $current;
                $modelCode->modify($platform->{'site_page_id'}, $data);
            }
        }

        TPL::output('/home');
        exit;
    }
    /**
     * 通过系统内置模板生成页面
     */
    private function &_makePage($name, $template) {
        if (file_exists(TMS_APP_TEMPLATE . '/pl/be/' . $name)) {
            $templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
        } else {
            $templateDir = TMS_APP_TEMPLATE_DEFAULT . '/pl/be/' . $name;
        }
        $data = array(
            'html' => file_get_contents($templateDir . '/' . $template . '.html'),
            'css' => file_get_contents($templateDir . '/' . $template . '.css'),
            'js' => file_get_contents($templateDir . '/' . $template . '.js'),
        );

        return $data;
    }
    /**
     * 获得系统内置模板的修改时间
     */
    private function &_getPageMTime($name, $template) {
        if (file_exists(TMS_APP_TEMPLATE . '/pl/be/' . $name)) {
            $templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
        } else {
            $templateDir = TMS_APP_TEMPLATE_DEFAULT . '/pl/be/' . $name;
        }
        $templatePageMTimes = array(
            'upAtHtml' => filemtime($templateDir . '/' . $template . '.html'),
            'upAtCss' => filemtime($templateDir . '/' . $template . '.css'),
            'upAtJs' => filemtime($templateDir . '/' . $template . '.js'),
        );

        return $templatePageMTimes;
    }
    /**
     *
     */
    public function get_action() {
        $platform = $this->model('platform')->get(['cascaded' => 'home_page,template_page,site_page']);
        if (!empty($platform->home_carousel)) {
            $platform->home_carousel = json_decode($platform->home_carousel);
        }
        if (!empty($platform->home_nav)) {
            $platform->home_nav = json_decode($platform->home_nav);
        }
        /* 群二维码 */
        if (!empty($platform->home_qrcode_group)) {
            $platform->home_qrcode_group = json_decode($platform->home_qrcode_group);
        }

        $param = [
            'platform' => $platform,
        ];

        return new \ResponseData($param);
    }
    /**
     * 首页推荐团队列表
     *
     * @param string $userType 站点用户还是团队管理员用户
     *
     */
    public function listSite_action($page = 1, $size = 8) {
        $modelHome = $this->model('site\home');

        $options = [];
        $options['page']['at'] = $page;
        $options['page']['size'] = $size;
        $result = $modelHome->atHome($options);
        if ($result->total) {
            $modelWay = $this->model('site\fe\way');
            $siteUser = $modelWay->who('platform');
            if (isset($siteUser->loginExpire)) {
                $modelSite = $this->model('site');
                foreach ($result->sites as &$site) {
                    if ($rel = $modelSite->isSubscribed($siteUser->uid, $site->siteid)) {
                        $site->_subscribed = $rel->subscribe_at ? 'Y' : 'N';
                    }
                }
            }
        }

        return new \ResponseData($result);
    }
    /**
     *
     */
    public function listApp_action($page = 1, $size = 8) {
        $modelHome = $this->model('matter\home');

        $options = [];
        $options['page']['at'] = $page;
        $options['page']['size'] = $size;
        $result = $modelHome->atHome($options);
        if (count($result->matters)) {
            foreach ($result->matters as &$matter) {
                $matter->url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
            }
        }

        return new \ResponseData($result);
    }
    /**
     *
     */
    public function listChannel_action($homeGroup = '', $page = 1, $size = 8) {
        $modelHome = $this->model('matter\home');

        $options = [];
        $options['page']['at'] = $page;
        $options['page']['size'] = $size;
        !empty($homeGroup) && $options['byHGroup'] = $homeGroup;
        $result = $modelHome->atHomeChannel($options);
        if (count($result->matters)) {
            foreach ($result->matters as &$matter) {
                $matter->url = $this->model('matter\channel')->getEntryUrl($matter->siteid, $matter->matter_id);
            }
        }

        return new \ResponseData($result);
    }
    /**
     * 发布到主页热点中的素材
     */
    public function listMatter_action($page = 1, $size = 8) {
        $modelHome = $this->model('matter\home');

        $options = [];
        $options['page']['at'] = $page;
        $options['page']['size'] = $size;
        $result = $modelHome->atHomeMatter($options);
        if (count($result->matters)) {
            foreach ($result->matters as &$matter) {
                if ($matter->matter_type === 'link') {
                    $matter->url = $this->getInviteUrl($matter);
                } else {
                    $matter->url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
                }
            }
        }

        return new \ResponseData($result);
    }
    /**
     *获取置顶活动
     */
    public function listMatterTop_action($type = 'ALL', $page = 1, $size = 3) {
        $modelHome = $this->model('matter\home');
        $options = [];
        $options['page']['at'] = $page;
        $options['page']['size'] = $size;
        //$options['type'] = $type;
        $result = $modelHome->atHomeTop($options);
        if (count($result->matters)) {
            foreach ($result->matters as &$matter) {
                if ($matter->matter_type === 'link') {
                    $matter->url = $this->getInviteUrl($matter);
                } else {
                    $matter->url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
                }
            }
        }

        return new \ResponseData($result);
    }
    /**
     * 获得当前登录账号的用户信息
     */
    private function &_accountUser() {
        $account = \TMS_CLIENT::account();
        if ($account) {
            $user = new \stdClass;
            $user->id = $account->uid;
            $user->name = $account->nickname;
            $user->src = 'A';

        } else {
            $user = false;
        }
        return $user;
    }
    /*
     * 如果素材开启了邀请机制需要获取邀请的链接
     */
    private function getInviteUrl($matter) {
        $oMatter = $this->model('matter\\' . $matter->matter_type)->byId($matter->matter_id);
        $oInvitee = new \stdClass;
        $oInvitee->id = $oMatter->siteid;
        $oInvitee->type = 'S';
        $oInvite = $this->model('invite')->byMatter($oMatter, $oInvitee, ['fields' => 'id,code,expire_at,state']);
        if ($oInvite && $oInvite->state === '1') {
            $oCreator = new \stdClass;
            $oCreator->id = $matter->siteid;
            $oCreator->name = '';
            $oCreator->type = 'S';
            $modelInv = $this->model('invite');
            $oInvite = $modelInv->byMatter($oMatter, $oCreator, ['fields' => 'id,code']);
            if ($oInvite) {
                $url = $modelInv->getEntryUrl($oInvite);
            } else {
                $url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
            }
        } else {
            $url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
        }

        return $url;
    }
}