var pic = 'http://xxt.ctsi.com.cn/kcfinder/upload/ad483481fb907d53d74130cd88e11d86/图片/抗战日历/12/122801.jpg';
if (/MicroMessenger/i.test(navigator.userAgent)) {
    signPackage.jsApiList = ['onMenuShareTimeline', 'onMenuShareAppMessage'];
    signPackage.debug = true;
    wx.config(signPackage);
}
window.xxt.share.set('指定的标题', location.href, '指定的摘要', encodeURI(pic));