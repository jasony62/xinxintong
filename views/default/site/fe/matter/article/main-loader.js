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
				"angular": "/static/js/angular.min",
				"xxt-page": "/static/js/xxt.ui.page",
				"xxt-share": "/static/js/xxt.share",
				"hammer": "/static/js/hammer.min",
				"picviewer": "/views/default/picviewer",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"picviewer": {
					deps: ['hammer']
				}
			},
			urlArgs: "bust=" + (new Date()).getTime()
		});
		require(['xxt-page'], function(loader) {
			loader.bootstrap('/views/default/site/fe/matter/article/main.js');
		});
	}
};
if (/MicroMessenger/i.test(navigator.userAgent)) {
	var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
	requirejs(["http://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx) {
		var xhr = new XMLHttpRequest();
		xhr.open('GET', "/rest/site/fe/matter/article/wxjssdksignpackage?site=" + siteId + "&url=" + encodeURIComponent(location.href.split('#')[0]), true);
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4) {
				if (xhr.status >= 200 && xhr.status < 400) {
					try {
						eval("(" + xhr.responseText + ')');
						if (signPackage) {
							window.wx = wx;
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