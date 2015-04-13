<?php
// yangyue sae limit
//set_time_limit(120);

class SmtpMail {
    private $host;           //主机
    private $port;           //端口 一般为25
    private $user;           //SMTP认证的帐号
    private $pass;           //认证密码
    private $debug = false;   //是否显示和服务器会话信息？
    private $conn;
    private $result_str;       //结果
    private $in;           //客户机发送的命令
    private $from;           //源信箱
    private $to;           //目标信箱
    private $subject;         //主题
    private $body;           //内容

    public function __construct($host,$port,$user,$pass,$debug=false) {
        $this->host = $host;
        $this->port = $port;
        $this->user = base64_encode($user);
        $this->pass = base64_encode($pass);
        $this->debug = $debug;
    }

    private function debug_show($str) {
        if($this->debug) {
            echo $str."<p>\r\n";
        }
    }

    public function send($from, $to, $subject, $body) {
        if($from == "" || $to == "") {
            exit("请输入信箱地址");
        }
        if($subject == "") $sebject = "hello";
        if($body  == "") $body     = "hello";
        $this->from = $from;
        $this->to = $to;
        $this->subject = "=?UTF-8?B?".base64_encode($subject)."?=";
        $this->body = $body;
        //
        $all = "Content-Type:text/html;charset=\"utf-8\"\r\n";
        $all .= "From:<".$this->from.">\r\n";
        $all .= "To:<".$this->to.">\r\n";
        $all .= "Subject:".$this->subject."\r\n\r\n";
        $all .= $this->body;
        //
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr);
        if($this->socket) {
            stream_set_blocking($this->socket, true); 
            $this->result_str = "服务器应答：<font color=#cc0000>".fgets($this->socket, 1024)."</font>";
            $this->debug_show($this->result_str);
        } else {
            die("ERROR: $errno - $errstr<br />\n");
            //exit("初始化失败，请检查您的网络连接和参数");
        }
        //以下是和服务器会话
        $this->in = "EHLO HELO\r\n";
        $this->docommand();

        $this->in = "AUTH LOGIN\r\n";
        $this->docommand();

        $this->in = $this->user."\r\n";
        $this->docommand();

        $this->in = $this->pass."\r\n";
        $this->docommand();

        $this->in = "MAIL FROM:<".$this->from.">\r\n";
        $this->docommand();

        $this->in = "RCPT TO:<".$this->to.">\r\n";
        $this->docommand();

        $this->in = "DATA\r\n";
        $this->docommand();

        $this->in = $all."\r\n.\r\n";
        $this->docommand();

        $this->in = "QUIT\r\n";
        $this->docommand();
        //
        fclose($this->socket);
    }

    private function docommand() {
        fputs($this->socket, $this->in);
        $this->debug_show("客户机命令：".$this->in);
        do {
            $lastmessage = fgets($this->socket, 512);
            $this->result_str = "服务器应答：<font color=#cc0000>$lastmessage</font>";
            $this->debug_show($this->result_str);
        } while ($lastmessage[3] !== ' ');
    }
}
