<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 转发素材
 */
class forward extends \pl\fe\base {
    /**
     * 将指定素材转发到指定的团队主页频道
     *
     * @param string $id 素材id
     * @param string $type 素材type
     *
     */
    public function push_action($id, $type) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        if (empty($id) || empty($type) || $type !== 'article') {
            return new \ParameterError();
        }
        $aTargetSites = $this->getPostJson();
        if (empty($aTargetSites)) {
            return new \ParameterError('没有选择需要收藏素材的团队频道');
        }

        $modelMat = $this->model('matter\\' . $type);
        $oMatter = $modelMat->byId($id, ['cascaded' => 'N']);
        if (false === $oMatter) {
            return new \ObjectNotFoundError();
        }

        $modelArt = $this->model('matter\article\copy');
        $modelArt->forward($id, $aTargetSites, $user);

        return new \ResponseData('ok');
    }
    /**
     * 返回当前可以转发指定素材的团队
     *
     * @param string $site site'id
     * @param string $id 转发素材的id
     * @param string $type 转发素材的type
     *
     */
    public function sitesByUser_action($id, $type, $site = '') {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        if (empty($id) || empty($type) || $type !== 'article') {
            return new \ParameterError();
        }
        $modelMat = $this->model('matter\\' . $type);
        $oMatter = $modelMat->byId($id, ['cascaded' => 'N']);
        if (false === $oMatter) {
            return new \ObjectNotFoundError();
        }

        $fromSiteId = empty($site) ? $oMatter->siteid : $site;

        $modelSite = $this->model('site');
        $modelSp = $this->model('site\page');

        /* 当前用户管理的团队 */
        $mySites = $modelSite->byUser($user->id);
        $targets = []; // 符合条件的团队
        foreach ($mySites as &$mySite) {
            if ($mySite->id === $fromSiteId) {
                continue;
            }
            $hcs = $modelSp->homeChannelBySite($mySite->id, ['fields' => 'channel_id,title']);
            $mySite->homeChannels = $hcs;
            $targets[] = $mySite;
        }

        return new \ResponseData($targets);
    }
}