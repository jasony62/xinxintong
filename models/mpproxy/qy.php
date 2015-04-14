<?php
require_once dirname(__FILE__).'/base.php';
/**
 * 企业号代理类
 */
class qy_model extends mpproxy_base {
    /**
     *
     * $mpid
     */
    public function __construct($mpid)
    {
        parent::__construct($mpid);
    }
    /**
     * 获得与公众平台进行交互的token
     */
    protected function accessToken($newAccessToken=false) 
    {
        /**
         * 不重用之前保留的access_token
         */
        $whichToken = "qy_corpid,qy_secret,qy_token,qy_token_expire_at";
        if ($newAccessToken === false) {
            if (isset($this->qy_token) && time()<$this->qy_token['expire_at']-60) {
                /**
                 * 在同一次请求中可以重用
                 */
                return array(true, $this->qy_token['value']);
            }
            /**
             * 从数据库中获取之前保留的token
             */
            $app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
            if (!empty($app->qy_token) && time() < (int)$app->qy_token_expire_at-60) {
                /**
                 * 数据库中保存的token可用
                 */
                $this->qy_token = array(
                    'value'=>$app->qy_token,
                    'expire_at'=>$app->qy_token_expire_at
                );
                return array(true, $app->qy_token);
            }
        } else {
            /**
             * 从数据库中获取之前保留的token
             */
            $app = TMS_APP::model('mp\mpaccount')->byId($this->mpid, $whichToken);
        }
        /**
         * 重新获取token
         */
        $url_token = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
        $url_token .= "?corpid=$app->qy_corpid&corpsecret=$app->qy_secret";
        $ch = curl_init($url_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (false === ($response = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return array(false, $err);
        }
        curl_close($ch);
        $token = json_decode($response);
        if (isset($token->errcode))
            return array(false, $token->errmsg);
        /**
         * 保存获得的token
         */
        $u["qy_token"] = $token->access_token;
        $u["qy_token_expire_at"] = 7200 + time();
        TMS_APP::model()->update('xxt_mpaccount', $u, "mpid='$this->mpid'");

        $this->qy_token = array(
            'value'=>$u["qy_token"],
            'expire_at'=>$u["qy_token_expire_at"]
        );

        return array(true, $token->access_token);
    }
    /**
     *
     */
    public function oauthUrl($mpid, $redirect, $state=null)
    {
        $mpa = TMS_APP::model('mp\mpaccount')->byId($mpid, 'qy_corpid');

        $oauth = "https://open.weixin.qq.com/connect/oauth2/authorize";
        $oauth .= "?appid=$mpa->qy_corpid";
        $oauth .= "&redirect_uri=".urlencode($redirect);
        $oauth .= "&response_type=code";
        $oauth .= "&scope=snsapi_base";
        !empty($state) && $oauth .= "&state=$state";
        $oauth .= "#wechat_redirect";

        return $oauth;
    }
    /**
     *
     * $userId
     * $data
     *  name
     *  mobile
     *  eamil
     *  position
     *  department
     *  extattr
     */
    public function userCreate($userId, $data)
    {
        is_array($data) && $data = (object)$data;

        $posted = array(
            'userid'=>$userId,
        );

        !empty($data->name) && $posted['name'] = urlencode($data->name);
        !empty($data->mobile) && $posted['mobile'] = $data->mobile;
        !empty($data->email) && $posted['email'] = $data->email;
        !empty($data->position) && $posted['position'] = urlencode($data->position);
        !empty($data->department) && $posted['department'] = $data->department;
        !empty($data->extattr) && $posted['extattr'] = $data->extattr;

        $posted = urldecode(json_encode($posted));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/create";
        $rst = $this->httpPost($cmd, $posted);

        return $rst;
    }
    /**
     *
     * $userId
     *
     * $data
     *  name
     *  mobile
     *  eamil
     *  position
     *  department
     *  extattr
     *
     */
    public function userUpdate($userId, $data)
    {
        is_array($data) && $data = (object)$data;

        $posted = array(
            'userid'=>$userId,
        );

        !empty($data->name) && $posted['name'] = urlencode($data->name);
        !empty($data->mobile) && $posted['mobile'] = $data->mobile;
        !empty($data->email) && $posted['email'] = $data->email;
        !empty($data->position) && $posted['position'] = urlencode($data->position);
        !empty($data->department) && $posted['department'] = $data->department;
        !empty($data->extattr) && $posted['extattr'] = $data->extattr;

        $posted = urldecode(json_encode($posted));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/update";
        $rst = $this->httpPost($cmd, $posted);

        return $rst;
    }
    /**
     * 
     * $userId
     *
     */
    public function userDelete($userId)
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/delete";
        $rst = $this->httpGet($cmd, array('userid'=>$userId));

        return $rst;
    }
    /**
     *
     * $userId
     */
    public function userGet($userId)
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/get";
        $rst = $this->httpGet($cmd, array('userid'=>$userId));

        return $rst;
    }
    /**
     *
     * $deptId
     * $fetchChild
     * $status
     */
    public function userSimpleList($deptId, $fetchChild=1, $status=0)
    {
        $params = array(
            'department_id'=>$deptId,
            'fetch_child'=>$fetchChild,
            'status'=>$status
        );
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist";
        $rst = $this->httpGet($cmd, $params);

        return $rst;
    }
    /**
     * 获得用户列表
     *
     * $departmentId 获取的部门id
     * $fetchChild 1/0：是否递归获取子部门下面的成员
     * $status 0获取全部员工，1获取已关注成员列表，2获取禁用成员列表，4获取未关注成员列表。status可叠加
     */
    public function userList($departmentId, $fetchChild=0, $status=0)
    {
        $params = array(
            'department_id'=>$departmentId,
            'fetch_child'=>$fetchChild,
            'status'=>$status
        );
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/user/list";
        $result = $this->httpGet($cmd, $params);

        return $result;
    }
    /**
     *
     * $name
     * $parentid
     * $order
     */
    public function departmentCreate($name, $parentid, $order, $id=null)
    {
        $newDept = array(
            'name'=>urlencode($name),
            'parentid'=>$parentid,
            'order'=>$order
        );
        !empty($id) && $newDept['id'] = $id;

        $posted = urldecode(json_encode($newDept));
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/create";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     * $id
     */
    public function departmentDelete($id)
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/delete";
        $result = $this->httpGet($cmd, array('id'=>$id));

        return $result;
    }
    /**
     *
     */
    public function departmentUpdate($id, $name)
    {
        $posted = urldecode(json_encode(array(
            'id'=>$id,
            'name'=>urlencode($name),
        )));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/update";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     * 获得部门列表
     */
    public function departmentList($pdid=null)
    {
        $params = array();
        $pdid && $params['id'] = $pdid;

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/department/list";
        $result = $this->httpGet($cmd, $params);

        return $result;
    }
    /**
     * 获得标签列表
     */
    public function tagList()
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/list";
        $result = $this->httpGet($cmd);

        return $result;
    }
    /**
     * $name
     */
    public function tagCreate($name)
    {
        $posted = urldecode(json_encode(array(
            'tagname'=>urlencode($name),
        )));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/create";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     *
     * $tagid
     * $name
     */
    public function tagUpdate($tagid, $name)
    {
        $posted = urldecode(json_encode(array(
            'tagid'=>$tagid,
            'tagname'=>urlencode($name)
        )));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/update";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     *
     * $tagid
     */
    public function tagDelete($tagid)
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/delete";
        $result = $this->httpGet($cmd, array('tagid'=>$tagid));

        return $result;
    }
    /**
     * $tagid
     * $userlist
     */
    public function tagAddUser($tagid, $userlist)
    {
        $posted = json_encode(array(
            'tagid'=>$tagid,
            'userlist'=>$userlist
        ));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/addtagusers";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     * $tagid
     * $userlist
     */
    public function tagDeleteUser($tagid, $userlist)
    {
        $posted = json_encode(array(
            'tagid'=>$tagid,
            'userlist'=>$userlist
        ));

        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/deltagusers";
        $result = $this->httpPost($cmd, $posted);

        return $result;
    }
    /**
     * 获得标签下的用户列表
     *
     * $tagid
     */
    public function tagUserList($tagid)
    {
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/tag/get";
        $result = $this->httpGet($cmd, array('tagid'=>$tagid));

        return $result;
    }
    /**
     * upload menu.
     */
    public function menuCreate($menu)
    {
        $app = $this->model('mp\mpaccount')->byId($this->mpid, 'qy_agentid');
        $cmd = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?agentid=$app->qy_agentid&access_token=";

        $rst = $this->httpPost($cmd, $menu);

        return $rst;
    }
}
