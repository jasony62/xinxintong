<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)).'/mp_controller.php';

class tag extends \mp\mp_controller {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     *
     */
    public function index_action()
    {
        $this->view_action('/mp/user/tags');    
    }
    /**
     * 获得所有标签
     *
     * $authid 每个认证接口下可以定义标签
     *
     * todo 如何排序？
     */
    public function get_action($authid)
    {
        $tags = $this->model('user/tag')->byMpid($this->mpid, $authid);

        return new \ResponseData($tags);
    }
    /**
     * 更新标签
     *
     * $id
     */
    public function update_action()
    {
        $tag = $this->getPostJson();
        $mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);

        if ($mpapis->mpsrc === 'qy' && $mpapis->qy_joined === 'N')
            return new \ResponseError('企业号未开通，不能同步标签');

        if (isset($tag->id)) {
            if ($mpapis->qy_joined === 'Y') {
                $extattr = json_decode($tag->extattr);
                $result = $this->model('mpproxy/qy', $this->mpid)->tagUpdate($extattr->tagid, $tag->name);
                if ($result[0] === false)
                    return new \ResponseError($result[1]);
            }
            $tagid = $tag->id;
            unset($tag->id);
            $rst = $this->model()->update(
                'xxt_member_tag', 
                (array)$tag, 
                "mpid='$this->mpid' and id=$tagid"
            );
            return new \ResponseData($rst);
        } else {
            if ($mpapis->qy_joined === 'Y') {
                $result = $this->model('mpproxy/qy', $this->mpid)->tagCreate($tag->name);
                if ($result[0] === false)
                    return new \ResponseError($result[1]);
                $extattr['tagid'] = $result[1]->tagid;
                $tag->extattr = json_encode($extattr);
            }
            $tag = $this->model('user/tag')->create($this->mpid, $tag);

            return new \ResponseData($tag);
        }
    }
    /**
     * 删除标签
     *
     * 如果存在标签成员不允许删除
     */
    public function remove_action($id)
    {
        $mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);

        if ($mpapis->qy_joined === 'Y') {
            $tag = $this->model('user/tag')->byId($id, 'extattr');
            $extattr = json_decode($tag->extattr);
            /**
             * 与企业号同步
             */
            $result = $this->model('mpproxy/qy', $this->mpid)->tagDelete($extattr->tagid);
            if ($result[0] === false)
                return new \ResponseError($result[1]);
        }
        /**
         * 提交到本地
         */
        $rst = $this->model('user/tag')->remove($this->mpid, $id);

        if ($rst[0] === false)
            return new \ResponseError($rst[1]);
        else
            return new \ResponseData(true);
    }
}
