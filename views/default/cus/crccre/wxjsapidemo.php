<?php
$mpid = $_GET['mpid'];
$url = urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
?>
<!doctype html>
<html>
    <head>
        <meta charset='utf-8'/>
        <style>
        </style>
        <title>WX JSAPI Demo</title>
    </head>
    <body>
    <button onclick='chooseImage()'>chooseImage</button>
    </body>
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script src="http://mi.crccre.com/rest/cus/crccre/mpaccount/wxjssdksignpackage?mpid=<?php echo $mpid;?>&url=<?php echo $url;?>"></script>
<script type="text/javascript">
signPackage.debug = true;
signPackage.jsApiList = ['hideOptionMenu','chooseImage'];
wx.config(signPackage);
wx.ready(function(){
    wx.hideOptionMenu();
});
var chooseImage = function() {
    wx.chooseImage({
        success: function (res) {
            var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
        }
    });
};
</script>
</html>
