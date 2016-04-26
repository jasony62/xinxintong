window.loading = {
	finish: function() {
		var eleLoading, eleStyle;
		eleLoading = document.querySelector('.loading');
		eleLoading.parentNode.removeChild(eleLoading);
		eleStyle = document.querySelector('#loadingStyle');
		eleStyle.parentNode.removeChild(eleStyle);
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"cookie": '//cdn.bootcss.com/Cookies.js/1.2.1/cookies.min',
				"angular": "/static/js/angular.min",
				"xxt-share": "/static/js/xxt.share",
				"base": "/views/default/site/fe/matter/merchant/base",
				"directive": "/views/default/site/fe/matter/merchant/directive",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"base": {
					exports: "ngApp",
					deps: ["angular"]
				},
				"directive": {
					deps: ["base"]
				},
				"cookie": {
					exports: "Cookies"
				},
				"xxt-share": {
					exports: "xxt-share"
				},
			},
			deps: ['/views/default/site/fe/matter/merchant/product.js'],
			urlArgs: "bust=" + (new Date()).getTime()
		});
	}
};
if (/MicroMessenger/i.test(navigator.userAgent)) {
	var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
	requirejs(["http://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx) {
		window.wx = wx;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', "/rest/site/fe/matter/merchant/wxjssdksignpackage?site=" + siteId + "&url=" + encodeURIComponent(location.href.split('#')[0]), true);
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4) {
				if (xhr.status >= 200 && xhr.status < 400) {
					try {
						if (xhr.responseText && xhr.responseText.length) {
							eval("(" + xhr.responseText + ")");
							signPackage.debug = false;
							signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
							wx.config(signPackage);
						}
						window.loading.load();
					} catch (e) {
						alert('local error:' + e.toString());
					}
				} else {
					alert('http error:' + xhr.statusText);
				}
			};
		}
		xhr.send();
	});
} else {
	window.loading.load();
}