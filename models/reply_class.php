<?php
/**
 *
 */
abstract class Reply {
    /**
     *
     */
    protected $call;
    /**
     *
     */
    public function __construct($call) 
    {
        $this->call = $call;
    }
    /**
     *
     */
    protected function header() 
    {
        $r = '<ToUserName><![CDATA['.$this->call['from_user'].']]></ToUserName>';
        $r .= '<FromUserName><![CDATA['.$this->call['to_user'].']]></FromUserName>';
        $r .= '<CreateTime>'.round(microtime(true)).'</CreateTime>';
        return $r;
    }
    /**
     * 文本回复，直接显示回复的文本
     */
    protected function textResponse($content) 
    {
        $r = '<xml>';
        $r .= $this->header();
        $r .= '<MsgType><![CDATA[text]]></MsgType>';
        $r .= '<Content><![CDATA['.$this->text_reply($content).']]></Content>';
        $r .= '</xml> ';
        if ($this->call['src'] === 'qy') {
            $r = $this->encrypt($r);
        }
        return $r;
    }
    /**
     * 卡片回复，显示一个包含内容链接的卡片
     */ 
    protected function cardResponse($matters)
    {
        if (!is_array($matters))
            $matters = array($matters);
        $r = '<xml>';
        $r .= $this->header();
        $r .= '<MsgType><![CDATA[news]]></MsgType>';
        $r .= '<ArticleCount>'.count($matters).'</ArticleCount>';
        $r .= '<Articles>';
        $r .= $this->article_reply($matters);
        $r .='</Articles>';
        $r .= '</xml>'; 
        if ($this->call['src'] === 'qy') {
            $r = $this->encrypt($r);
        }
        return $r;
    }
    /**
     * 组装文本回复消息
     *
     * 文本消息中允许动态参数，需要对这些参数进行转换
     * 内置参数：
     * {{mpid}}
     * {{openid}}
     * {{src}}
     */
    private function text_reply($content)
    {
        $content = str_replace(
            array(
                '{{mpid}}', 
                '{{openid}}',
                '{{src}}'
            ),
            array(
                $this->call['mpid'],
                $this->call['from_user'], 
                $this->call['src']
            ),
            $content
        );
        return $content;
    }
    /**
     * 拼装图文回复消息
     */
    private function article_reply($matters) 
    {
        $r = '';
        foreach ($matters as $matter) {
            $matter->mpid = $this->call['mpid'];
            $runningMpid = $this->call['mpid'];
            $url = TMS_APP::model('reply')->getMatterUrl($runningMpid, $matter, $this->call['from_user'], $this->call['src']);
            $r .= '<item>';
            $r .= '<Title><![CDATA['.$matter->title.']]></Title>';
            $r .= '<Description><![CDATA['.$matter->summary.']]></Description>';
            if (!empty($matter->pic) && stripos($matter->pic, 'http') === false)
                $r .= '<PicUrl><![CDATA['.'http://'.$_SERVER['HTTP_HOST'].$matter->pic.']]></PicUrl>';
            else
                $r .= '<PicUrl><![CDATA['.$matter->pic.']]></PicUrl>';
            $r .= '<Url><![CDATA['.$url.']]></Url>';
            $r .= '</item>';
        }
        return $r;
    }
    /**
     * 企业号信息加密处理
     */
    protected function encrypt($msg)
    {
        $sEncryptMsg = ""; //xml格式的密文
        $timestamp = time();
        $nonce = uniqid(); 
        $app = TMS_APP::model('mp\mpaccount')->byId($this->call['mpid']);
        $wxcpt = new WXBizMsgCrypt($app->token, $app->qy_encodingaeskey, $app->qy_corpid);
        $errCode = $wxcpt->EncryptMsg($msg, $timestamp, $nonce, $sEncryptMsg);
        if ($errCode != 0) {
            TMS_APP::model('log')->log($this->call['mpid'], 'qy', $this->content, $errCode);
            exit;
        }
        return $sEncryptMsg;
    }
    /**
     *
     */
    abstract public function exec();
}
/**
 * 文本回复
 */
class TextReply extends Reply { 
    /**
     *
     */
    private $content;
    /**
     *
     * $call
     * $content 回复的内容
     * $referred 指明$content是直接回复的内容，还是定义的文本素材
     */
    public  function __construct($call, $content, $referred = true) 
    {
        parent::__construct($call);
        if ($referred) {
            if ($txt = TMS_APP::model('matter/text')->byId($content,'content'))
                $content = $txt->content;
            else
                $content = "文本回复【$content】不存在";
        }
        $this->content = $content;
    }
    /**
     *
     */
    public function exec() 
    {
        $r = $this->textResponse($this->content);
        die($r);
    }
}
/**
 * 图文回复
 */
abstract class MultiArticleReply extends Reply {
    /**
     * articles belong to (article/news/channel)
     */
    protected $set_id;
    /**
     *
     */
    public  function __construct($call, $set_id) 
    {
        parent::__construct($call);
        $this->set_id = $set_id;
    }
    /**
     * 生成回复消息
     */
    public function exec()
    {
        $matters = $this->loadMatters();
        $r = $this->cardResponse($matters);
        die($r);
    }
    /**
     * 回复中包含的图文信息
     */
    abstract protected function loadMatters();
}
class ArticleReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $article = TMS_APP::model('matter/article')->byId($this->set_id,'id,title,summary,pic');
        return array($article);
    }
}
class NewsReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $runningMpid = $this->call['mpid'];
        $openid = $this->call['from_user'];
        $src = $this->call['src'];

        $matters2 = array();
        $news = TMS_APP::model('matter/news')->byId($this->set_id);
        $matters = TMS_APP::model('matter/news')->getMatters($this->set_id);
        foreach ($matters as $m) {
            $m->url = TMS_APP::model('reply')->getMatterUrl($runningMpid, $m, $openid, $src);
            if ($m->access_control === 'Y' && $news->filter_by_matter_acl === 'Y') {
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
                if (!empty($actType)) {
                    $inacl = false;
                    $members = TMS_APP::model('user/member')->byOpenid($m->mpid, $openid);
                    foreach ($members as $member) {
                        if ($model->canAccessAct($m->mpid, $m->id, $actType, $member, $m->authapis)){
                            $inacl = true;
                            break;
                        }
                    }
                    if (!$inacl) continue;
                } else if (isset($matterType)) {
                    $inacl = false;
                    $members = TMS_APP::model('user/member')->byOpenid($m->mpid, $openid);
                    foreach ($members as $member) {
                        if ($model->canAccessMatter($m->mpid, $matterType, $m->id, $member, $m->authapis)){
                            $inacl = true;
                            break;
                        }
                    }
                    if (!$inacl) continue;
                }
            }
            $matters2[] = $m;
        }

        if (count($matters2) === 0) {
            $m = TMS_APP::model('matter/base')->get_by_id($news->empty_reply_type, $news->empty_reply_id);
            $m->type = $news->empty_reply_type;
            return $m;
        } else
            return $matters2;
    }
}
/**
 * 频道回复
 */
class ChannelReply extends MultiArticleReply {
    /**
     * 如果频道设置了【固定标题】，要用固定标题替换掉第一个图文的标题
     */
    protected function loadMatters() 
    {
        $model = TMS_APP::model('matter/channel');
        
        $matters = $model->getMatters($this->set_id);

        $channel = $model->byId($this->set_id, 'fixed_title');
        if (!empty($matters) && !empty($channel->fixed_title))
            $matters[0]->title = $channel->fixed_title;    

        return $matters;
    }
}
/**
 * 链接回复
 *
 * 1、通常情况下链接作为一个卡片进行回复
 * 2、如果要求将链接的执行结果进行回复，就要先执行链接，将获得的结果作为文本回复
 *
 */
class LinkReply extends Reply {

    private $link_id;

    public  function __construct($call, $link_id) 
    {
        parent::__construct($call);
        $this->link_id = $link_id;
    }
    /**
     *
     */
    public function exec()
    {
        $link = TMS_APP::model('matter/link')->byIdWithParams($this->link_id);
        $link->type = 'link';
        if ($link->return_data === 'Y') {
            /**
             * 以文字的形式响应
             */
            $rst = $this->output($link, $this->call['src'], $this->call['from_user']);
            $r = $this->textResponse($rst);
        } else {
            /**
             * 以图文卡片的形式响应
             */
            $r = $this->cardResponse($link);
        }
        die($r);
    }
    /**
     * 获得执行链接的结果
     */
    private function output($link, $src, $openid)
    {
        $url = $link->url;
        if (preg_match('/^(http:|https:)/', $url) === 0)
            $url = 'http://'.$url;

        if (isset($link->params))
            $params = TMS_APP::model('reply')->spliceParams($link->mpid, $link->params, null, $src, $openid);

        if ($link->method == 'GET' && isset($this->params)) {
            $url .= (strpos($url, '?') === false) ? '?':'&';
            $url .= $params;
        }
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置链接
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_REFERER, 1); 
        curl_setopt($ch, CURLOPT_HEADER, 1); //设置返回的信息是否包含http头
        if ($link->method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
            if (!empty($params)) {
                $header = array("Content-type: application/x-www-form-urlencoded");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
            }
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)){
            $output = curl_error($ch);
        } else {
            /**
             * 返回内容
             */
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $output = substr($response, $headerSize);
        }
        curl_close($ch);

        return $output; 
    }
}
/**
 * 通讯录信息卡片 
 */
class AddressbookReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $ab = TMS_APP::model('matter/addressbook')->byId($this->set_id,'id,title,summary,pic');
        $ab->type = 'addressbook';

        return array($ab);
    }
}
/**
 * 通用活动的信息卡片
 */
class ActivityReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $a = TMS_APP::model('activity/enroll')->byId($this->set_id);
        $a->type = 'activity';
        return array($a);
    }
}
/**
 * 讨论组的信息卡片
 */
class DiscussReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $w = TMS_APP::model('activity/wall')->byId($this->set_id);
        $w->type = 'discuss';
        return array($w);
    }
}
/**
 * 抽奖活动的信息卡片
 */
class LotteryReply extends MultiArticleReply {

    protected function loadMatters() 
    {
        $l = TMS_APP::model('activity/lottery')->byId($this->set_id);
        $l->type = 'lottery';
        return array($l);
    }
}
/**
 * 根据关键字检索单图文
 * todo 如果搜索不到是否应该给出提示呢？
 */
class FullsearchReply extends MultiArticleReply {

    private $keyword;

    public function __construct($call, $keyword) 
    {
        parent::__construct($call, null);
        $this->keyword = $keyword;
    }

    protected function loadMatters() 
    {
        $mpid = $this->call['mpid'];
        $page = 1;
        $limit = 5;
        $matters = TMS_APP::model('matter/article')->fullsearch_its($mpid, $this->keyword, $page, $limit);
        return $matters;
    }
}
/**
 * 单个活动的信息卡片
 */
class MyActivitiesReply extends Reply {
    /**
     *
     */
    public function __construct($call) 
    {
        parent::__construct($call);
    }
    /**
     * 
     */
    public function exec() 
    {
        $current = time();
        $src = $this->call['src'];
        $mpid = $this->call['mpid'];
        $openid = $this->call['from_user'];

        $acts = TMS_APP::model('activity/enroll')->byPromoter($mpid, $openid);
        $r = '<xml>';
        $r .= $this->header();
        $r .= '<MsgType><![CDATA[news]]></MsgType>';
        $r .= '<ArticleCount>'.count($acts).'</ArticleCount>';
        $r .= '<Articles>';
        foreach ($acts as $a) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/page/wbox/activity?aid='.$a->aid.'&_='.$current;
            $r .= '<item>';
            $r .= '<Title><![CDATA['.$a->title.']]></Title>';
            $r .= '<Description><![CDATA[]]></Description>';
            $r .= '<PicUrl><![CDATA[]]></PicUrl>';
            $r .= '<Url><![CDATA['.$url.']]></Url>';
            $r .= '</item>';
        }
        $r .='</Articles>';
        $r .= '</xml>'; 
        die($r);
    }
}
/**
 * 加入信息墙 
 */
class JoinwallReply extends Reply { 
    /**
     *
     */
    public function __construct($call, $wid, $keyword='') 
    {
        parent::__construct($call);
        $this->wid = $wid;
        $this->remark = trim(str_replace($keyword, '', $call['data']));
    }
    /**
     * $doResponse 是否执行相应。因为易信二维码需要通过推送客服消息的方式返回相应。  
     */
    public function exec($doResponse=true) 
    {
        /**
         * 当前用户加入活动
         */
        $wall= TMS_APP::model('activity/wall');
        $mpid = $this->call['mpid'];
        $openid = $this->call['from_user'];
        $src = $this->call['src'];
        $desc = $wall->join($mpid, $this->wid, $openid, $src, $this->remark);
        /**
         * 返回活动加入成功提示
         */
        if ($doResponse) {
            $r = $this->textResponse($desc);
            die($r);
        } else 
            return $desc;
    }
}
/**
 * 活动签到 
 */
class ActivitysigninReply extends Reply { 
    /**
     *
     */
    public function __construct($call, $aid, $directReply=true) 
    {
        parent::__construct($call);
        $this->aid = $aid;
        $this->directReply = $directReply;
    }
    /**
     * 
     */
    public function exec() 
    {
        /**
         * 当前用户活动签到
         */
        $mpid = $this->call['mpid'];
        $openid = $this->call['from_user'];
        $src = $this->call['src'];

        $model = TMS_APP::model('activity/enroll');
        $act = $model->byId($this->aid);
        $rst = $model->signin($mpid, $this->aid, $openid);
        /**
         * 回复 
         */
        if ($rst) {
            if ($act->success_matter_type && $act->success_matter_id) {
                $cls = $act->success_matter_type.'Reply';
                if ($this->directReply === true) 
                    $r = new $cls($this->call, $act->success_matter_id);
                else
                    return array('matter_type'=>$act->success_matter_type, 'matter_id'=>$act->success_matter_id);
            } else
                $r = new TextReply($this->call, "活动【$act->title】已签到，已登记", false);
        } else {
            if ($act->failure_matter_type && $act->failure_matter_id) {
                $cls = $act->failure_matter_type.'Reply';
                if ($this->directReply === true) 
                    $r = new $cls($this->call, $act->failure_matter_id);
                else
                    return array('matter_type'=>$act->failure_matter_type, 'matter_id'=>$act->failure_matter_id);
            } else
                $r = new TextReply($this->call, "活动【$act->title】已签到，未登记", false);
        }
        $r->exec();
    }
}
/**
 * 内置回复
 */
class InnerReply extends Reply {

    protected $innerId;

    protected $command;

    public  function __construct($call, $innerId, $command=null) 
    {
        parent::__construct($call);
        $this->innerId = $innerId;
        $this->command = $command;
    }
    /**
     *
     */
    public function exec() 
    {
        switch ($this->innerId) {
        case 1:
            $this->addressbookReply();
            break;
        case 3:
            $this->translateReply();
            break;
        case 4:
            $this->fullsearchReply();
            break;
        case 6:
            $this->contributeReply();
            break;
        case 7:
            $this->codesearchReply();
            break;
        case 8:
            $this->activitycodesearchReply();
            break;
        case 9:
            $this->myactivitiesReply();
            break;
        }
    }
    /**
     * 通讯录查询
     */
    private function addressbookReply() 
    {
        $mpid = $this->call['mpid'];
        $text = $this->call['data'];

        if (isset($this->command)) { // from textcall
            preg_match('/(?<='.$this->command.').+/', $text, $matches);
            $abbr = trim($matches[0]);
        } else // from othercall
            $abbr = $text;

        if (empty($abbr))
            $r = '请输入联系人的姓名或拼音缩写。';
        else {
            /**
             * todo 有多个通讯录怎么办？
             */
            $result = TMS_APP::model('matter/addressbook')->getPersonByAb($mpid, null, $abbr);
            $contacts = $result->objects;
            $r = '找到(' . count($contacts) . ')个联系人';
            foreach ($contacts as $contact) {
                $r .= "\n\n姓名：" . $contact->name;
                if (!empty($contact->email))
                    $r .= "\n邮件：" . $contact->email;
                if ($depts = TMS_APP::model('matter/addressbook')->getDeptByPerson($contact->id)) {
                    foreach ($depts as $dept) {
                        if (!empty($dept->name))
                            $r .= "\n部门：" . $dept->name;
                    }
                }
                $phones = explode(',', $contact->tels);
                foreach ($phones as $phone) {
                    if (!empty($phone))
                        $r .= "\n电话：" . $phone;
                }
            }
        }
        $tr = new TextReply($this->call, $r, false);
        $tr->exec();
    }
    /**
     * 翻译
     */
    private function translateReply() 
    {
        $text = $this->call['data'];
        $target = trim(str_replace($this->command, '', $text));

        $url = 'http://openapi.baidu.com/public/2.0/bmt/translate';
        $url .= '?client_id=wPlcmqSXEUoL91yZSQO5RuUj';
        $url .= '&from=auto&to=auto';
        $url .= "&q=$target";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //禁止直接显示获取的内容
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);

        $c = '翻译：';
        $results = $response->trans_result;
        foreach ($results as $result) {
            $c .= "\n$result->src";
            $c .= "\n$result->dst";
        }
        $tr = new TextReply($this->call, $c, false);
        $tr->exec();
    }
    /**
     * 全文检索（只针对单图文）
     */
    private function fullsearchReply() 
    {
        $mpid = $this->call['mpid'];
        $text = $this->call['data'];
        if (isset($this->command)) { // from textcall
            if (1 === preg_match('/(?<='.$this->command.').+/', $text, $matches))
                $keywords = trim($matches[0]);
        } else { // from othercall
            $keywords = $text;
        }
        if (empty($keywords)) {
            $r = '请输入检索关键字。';
            $tr = new TextReply($this->call, $r, false);
            $tr->exec();
        }
        // 获得符合条件的最多5条单图文组合成多图文
        $tr = new FullsearchReply($this->call, $keywords);
        $tr->exec();
    }
    /**
     * 获得投稿箱
     *
     * 每个粉丝在每个公众账号拥有一个投稿箱
     * 如果投稿箱不存在则创建一个投稿箱
     *
     * 如果投稿箱中包含auth_code说明正在等待输入验证码
     *
     * 1、提供投稿箱的登录地址，含一次性登录码
     * 2、提供投稿箱的验证码
     */
    private function contributeReply() 
    {
        $src = $this->call['src'];
        $mpid = $this->call['mpid'];
        $openid = $this->call['from_user'];
        /**
         * 投稿箱是否存在
         */
        $q = array(
            'code,auth_code',
            'xxt_writer_box',
            "mpid='$mpid' and src='$src' and openid='$openid'"
        );
        if (!($box = TMS_APP::model()->query_obj_ss($q))) {
            /**
             * 投稿箱代码
             */
            $box_code = rand(100000,999999);
            $q = array(
                'count(*)',
                'xxt_writer_box',
                "code='$box_code'"
            );
            while (1===(int)TMS_APP::model()->query_val_ss($q)) {
                $box_code = rand(100000,999999);
            }
            /**
             * 进入投稿箱要求输入【验证码】
             */
            $auth_code = rand(100000,999999);
            /**
             * 投稿箱不存在，创建一个投稿箱
             */
            $i = array(
                'code'=>$box_code,
                'mpid'=>$mpid,
                'src'=>$src,
                'openid'=>$openid,
                'auth_mode'=>1,
                'auth_code'=>$auth_code
            );
            TMS_APP::model()->insert('xxt_writer_box', $i, false);
            $box->code = $box_code;
            $box->auth_code = $auth_code;
        }
        if (empty($box->auth_code)) {
            /**
             * 进入投稿箱要求输入【验证码】
             */
            $box->auth_code = rand(100000,999999);
            TMS_APP::model()->update(
                'xxt_writer_box', 
                array('auth_code'=>$box->auth_code), 
                "code='$box->code'"
            );
        }
        /**
         * 投稿箱地址
         */
        $url = "http://".$_SERVER['HTTP_HOST'];
        $url .= "/page/wbox/$box->code";
        /**
         * 进入投稿箱的验证码
         */
        $r = "欢迎投稿，请进入【{$url}】进行稿件编辑工作，验证码为【{$box->auth_code}】。\r";
        $r .= "为保证信息安全，每次进入个人投稿箱都需要重新获取验证码。";
        $tr = new TextReply($this->call, $r, false);
        $tr->exec();
    }
    /**
     * 根据图文编号检索（只针对单图文）
     */
    private function codesearchReply() 
    {
        $mpid = $this->call['mpid'];
        $text = $this->call['data'];
        if (isset($this->command)) { // from textcall
            preg_match('/(?<='.$this->command.').+/', $text, $matches);
            $acode = trim($matches[0]);
        } else {
            die("invalid parameters($text).");
        }
        if (empty($acode)) {
            $r = '请输入文章的编号。';
            $tr = new TextReply($this->call, $r, false);
            $tr->exec();
        }
        if ($article = TMS_APP::model('matter/article')->byCode($mpid, $acode)) {
            $tr = new ArticleReply($this->call, $article->id); 
            $tr->exec();
        } else {
            $r = '文章编号不存在，请重新输入。';
            $tr = new TextReply($this->call, $r, false);
            $tr->exec();
        }
    }
    /**
     * 根据活动编号检索
     */
    private function activitycodesearchReply() 
    {
        $mpid = $this->call['mpid'];
        $text = $this->call['data'];
        if (isset($this->command)) { // from textcall
            preg_match('/(?<='.$this->command.').+/', $text, $matches);
            $acode = trim($matches[0]);
        } else {
            die("invalid parameters($text).");
        }
        if (empty($acode)) {
            $r = '请输入活动的编号。';
            $tr = new TextReply($this->call, $r, false);
            $tr->exec();
        }
        if ($act = TMS_APP::model('activity/enroll')->byCode($mpid, $acode)) {
            $tr = new ActivityReply($this->call, $act->aid); 
            $tr->exec();
        } else {
            $r = '活动编号不存在，请重新输入。';
            $tr = new TextReply($this->call, $r, false);
            $tr->exec();
        }
    }
    /**
     * 我发起的活动列表
     *
     * 多图文的形式返回
     * //todo 目前最多10条，以后应该支持翻页的机制
     */
    private function myactivitiesReply() 
    {
        $tr = new MyActivitiesReply($this->call); 
        $tr->exec();
    }
}
/**
 * 直接转发收到消息，并反馈
 */
class RelayReply {

    public  function __construct($call, $relayId) 
    {
        $this->relayId = $relayId;
    }

    public function exec() 
    {
        $relay = TMS_APP::model('mp\mpaccount')->getRelay($this->relayId);
        /**
         * 公众平台发过来的原始数据
         */
        $data = file_get_contents("php://input");
        $headerArr[] = 'Content-Type: text/xml; charset=utf-8';
        /**
         * 转发数据
         */
        $ch = curl_init($relay->url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER , $headerArr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (false === ($rsp = curl_exec($ch))) {
            $err = curl_error($ch);
            curl_close($ch);
            return array(false, $err);
        }
        curl_close($ch);

        die($rsp);
    }
}
