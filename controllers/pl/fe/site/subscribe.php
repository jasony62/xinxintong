<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 团队关注团队
 */
class subscribe extends \pl\fe\base {
    /**
     * 关注指定团队
     */
    public function do_action($site, $subscriber) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelSite = $this->model('site');

        if (false === ($target = $modelSite->byId($site))) {
            return new \ResponseError('数据不存在');
        }

        $siteIds = explode(',', $subscriber);
        foreach ($siteIds as $siteId) {
            $subscriber = $modelSite->byId($siteId);
            $modelSite->subscribeBySite($user, $target, $subscriber);
        }

        return new \ResponseData('ok');
    }
    /**
     * 取消关注指定团队
     */
    public function undo_action($site, $friend) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelSite = $this->model('site');

        if (false === ($target = $modelSite->byId($site))) {
            return new \ObjectNotFoundError();
        }
        if (false === ($target = $modelSite->byId($friend))) {
            return new \ObjectNotFoundError();
        }

        $rst = $modelSite->unsubscribeBySite($site, $friend);

        return new \ResponseData($rst);
    }
    /**
     * 返回当前用户可以关注指定团队的团队
     *
     * @param string $site site'id
     */
    public function sitesByUser_action($site) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelSite = $this->model('site');
        if (false === ($site = $modelSite->byId($site))) {
            return new \ResponseError('数据不存在');
        }

        /* 当前用户管理的团队 */
        $mySites = $modelSite->byUser($user->id);
        $targets = []; // 符合条件的团队
        foreach ($mySites as &$mySite) {
            if ($mySite->id === $site->id) {
                continue;
            }
            $rel = $modelSite->isFriend($site->id, $mySite->id);
            $mySite->_subscribed = !empty($rel->subscribe_at) ? 'Y' : 'N';
            $targets[] = $mySite;
        }

        return new \ResponseData($targets);
    }
}