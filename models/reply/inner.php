<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 内置回复
 */
class inner_model extends Reply {

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
    public function exec($doResponse=true) 
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
            default:
            $tr = \TMS_APP::model('reply\text', $this->call, "指定的内置回复($this->innerId)不存在", false);
            $tr->exec();
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
            $result = \TMS_APP::model('matter\addressbook')->getPersonByAb($mpid, null, $abbr);
            $contacts = $result->objects;
            $r = '找到(' . count($contacts) . ')个联系人';
            foreach ($contacts as $contact) {
                $r .= "\n\n姓名：" . $contact->name;
                if (!empty($contact->email))
                    $r .= "\n邮件：" . $contact->email;
                if ($depts = \TMS_APP::model('matter\addressbook')->getDeptByPerson($contact->id)) {
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
        $tr = \TMS_APP::model('reply\text', $this->call, $r, false);
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
        $tr = \TMS_APP::model('reply\text', $this->call, $c, false);
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
            $tr = \TMS_APP::model('reply\text', $this->call, $r, false);
            $tr->exec();
        }
        // 获得符合条件的最多5条单图文组合成多图文
        $tr = \TMS_APP::model('reply\fullsearch', $this->call, $keywords);
        $tr->exec();
    }
}
