<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class send extends \pl\fe\base {
    /**
     * 发送客服消息
     *
     * 需要开通高级接口
     */
    public function custom_action($openid) {
        $mpa = $this->model('mp\mpaccount')->byId($this->mpid);
        /**
         * 检查是否开通了群发接口
         */
        if ($mpa->mpsrc === 'wx') {
            $setting = $this->model('mp\mpaccount')->getFeature($this->mpid, $mpa->mpsrc . '_custom_push');
            if ($setting->{$mpa->mpsrc . '_custom_push'} === 'N') {
                return new \ResponseError('未开通群发高级接口，请检查！');
            }
        }
        /**
         * get matter.
         */
        $matter = $this->getPostJson();
        if (isset($matter->id)) {
            $message = $this->assemble_custom_message($matter);
        } else {
            $message = array(
                "msgtype" => "text",
                "text" => array(
                    "content" => $matter->text,
                ),
            );
        }
        /**
         * 发送消息
         */
        $rst = $this->sendByOpenid($this->mpid, $openid, $message);
        if (false === $rst[0]) {
            return new \ResponseError($rst[1]);
        }
        /**
         * 记录日志
         */
        if (isset($matter->id)) {
            $this->model('log')->send($this->mpid, $openid, null, $matter->title, $matter);
        } else {
            $this->model('log')->send($this->mpid, $openid, null, $matter->text, null);
        }

        return new \ResponseData('success');
    }
    /**
     * 群发消息
     */
    private function _send2group($siteId, $message, $matter, &$warning) {
        $user = $this->accountUser();

        $wxConfig = $this->model('sns\wx')->bySite($siteId);
        $proxy = $this->model("sns\wx\proxy", $wxConfig);

        $rst = $proxy->send2group($message);
        if ($rst[0] === true) {
            $msgid = isset($rst[1]->msg_id) ? $rst[1]->msg_id : 0;
            $this->model('log')->mass($user->id, $siteId, $matter->type, $matter->id, $message, $msgid, 'ok');
        } else {
            $warning[] = $rst[1];
            $this->model('log')->mass($user->id, $siteId, $matter->type, $matter->id, $message, 0, $rst[1]);
        }

        return true;
    }
    /**
     * 群发消息
     * 需要开通高级接口
     *
     * 开通了群发接口的微信和易信公众号
     * 微信企业号
     * 开通了点对点认证接口的易信公众号
     */
    public function mass_action($site) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        // 要发送的素材
        $matter = $this->getPostJson();

        if (empty($matter->groups)) {
            return new \ResponseError('请指定接收消息的用户');
        }
        // 要接收的用户
        $groups = $matter->groups;
        /**
         * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
         */
        $model = $this->model('matter\\' . $matter->type);
        if ($matter->type === 'text') {
            $message = $model->forCustomPush($site, $matter->id);
        } else if (in_array($matter->type, ['article', 'channel'])) {
            $message = $model->forWxGroupPush($site, $matter->id);
        }
        if (empty($message)) {
            return new \ResponseError('指定的素材无法向微信用户群发！');
        }
        /**
         * send
         */
        if ($groups[0]->id === -1) {
            /**
             * 发给所有用户
             */
            $message['filter'] = ['is_to_all' => true];
            $this->_send2group($site, $message, $matter, $warning);
        } else {
            /**
             * 发送给指定的关注用户组
             */
            foreach ($groups as $us) {
                $message['filter'] = [
                    'is_to_all' => false,
                    'group_id' => $us->id,
                ];
                $this->_send2group($site, $message, $matter, $warning);
            }
        }

        if (!empty($warning)) {
            return new \ResponseError(implode(';', $warning));
        } else {
            return new \ResponseData('success');
        }
    }
    /**
     * 预览消息
     *
     * 开通预览接口的微信公众号
     * 开通点对点消息的易信公众奥
     * 微信企业号
     */
    public function preview_action($matterId, $matterType, $openids) {
        $mpaccount = $this->getMpaccount();

        if ($mpaccount->mpsrc === 'wx') {
            $model = $this->model('matter\\' . $matterType);
            if ($matterType === 'text') {
                $message = $model->forCustomPush($this->mpid, $matterId);
            } else if (in_array($matterType, array('article', 'news', 'channel'))) {
                /**
                 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
                 */
                $message = $model->forWxGroupPush($this->mpid, $matterId);
            }
            $rst = $this->send2WxuserByPreview($this->mpid, $message, $openids);
        } else if ($mpaccount->mpsrc === 'qy') {
        }
        if (empty($message)) {
            return new \ResponseError('指定的素材无法向用户群发！');
        }
        if ($rst[0] === false) {
            return new \ResponseError($rst[1]);
        } else {
            return new \ResponseData('ok');
        }
    }
    /**
     * 根据指定的素材，组装客服消息
     */
    private function assemble_custom_message($matter) {
        $model = $this->model('matter\\' . $matter->type);
        $message = $model->forCustomPush($this->mpid, $matter->id);

        return $message;
    }
    /**
     * 发送模板消息
     *
     * $tid 模板消息id
     */
    public function tmplmsg_action($tid) {
        $posted = $this->getPostJson();

        if (isset($posted->matter)) {
            $url = $this->model('matter\\' . $posted->matter->type)->getEntryUrl($this->mpid, $posted->matter->id);
        } else if (isset($posted->url)) {
            $url = $posted->url;
        } else {
            $url = '';
        }

        $data = $posted->data;
        $userSet = $posted->userSet;

        $rst = $this->getOpenid($userSet);
        if ($rst[0] === false) {
            return new \ResponseError($rst[1]);
        }

        if (empty($rst[1])) {
            return new \ResponseError('没有指定消息接收人');
        }

        $openids = $rst[1];

        foreach ($openids as $openid) {
            $rst = $this->tmplmsgSendByOpenid($this->mpid, $tid, $openid, $data, $url);
            if ($rst[0] === false) {
                return new \ResponseError($rst[1]);
            }

        }

        return new \ResponseData('success');
    }
    /**
     *
     */
    public function tmplmsglog_action($tid, $page, $size) {
        $model = $this->model();
        $q = array(
            'id,template_id,msgid,openid,data,create_at,status',
            'xxt_log_tmplmsg',
            "mpid='$this->mpid' and tmplmsg_id=$tid",
        );
        $q2 = array(
            'r' => array(
                'o' => ($page - 1) * $size,
                'l' => $size,
            ),
        );
        if ($logs = $model->query_objs_ss($q, $q2)) {
            $q[0] = 'count(*)';
            $total = $model->query_val_ss($q);
        } else {
            $total = 0;
        }

        return new \ResponseData(array('logs' => $logs, 'total' => $total));
    }
    /**
     * 测试上传媒体文件接口
     */
    public function uploadPic_action($url) {
        $mpa = $this->getMpaccount();
        $mpproxy = $this->model('mpproxy/' . $mpa->mpsrc, $mpa->mpid);

        $media = $mpproxy->mediaUpload($url);
        if ($media[0] === false) {
            return new \ResponseError('上传图片失败：' . $media[1]);
        } else {
            return new \ResponseData($media[1]);
        }
    }
}