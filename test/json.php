<script>
var url = '<?php echo urlencode(json_encode(array('url'=>urlencode('<div>http://xinxintong.oss-cn-hangzhou.aliyuncs.com/7bea1f911cb8d7d24f69c8c7dfb6733e/%E5%9B%BE%E7%89%87/%E4%BC%9A%E5%91%98%E6%9C%8D%E5%8A%A1/pp.gif</div>'))));?>';
console.log('url', url);
url = decodeURIComponent(url);
console.log('url', url);
url = JSON.parse(url);
console.log('url', url);
</script>