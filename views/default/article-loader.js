window.loading = {
	finish: function() {
		var eleLoading, eleStyle;
		eleLoading = document.querySelector('.loading');
		if (eleLoading) {
			eleLoading.parentNode.removeChild(eleLoading);
			eleStyle = document.querySelector('#loadingStyle');
			eleStyle.parentNode.removeChild(eleStyle);
		}
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "/static/js/angular.min",
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
			deps: ['/views/default/article.js?_=5'],
			urlArgs: "bust=" + (new Date()).getTime()
		});
	}
};
if (/MicroMessenger/i.test(navigator.userAgent)) {
	var mpid = location.search.match(/[\?&]mpid=([^&]*)/)[1];
	requirejs(["http://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx) {
		window.wx = wx;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', "/rest/mi/matter/wxjssdksignpackage?mpid=" + mpid + "&url=" + encodeURIComponent(location.href.split('#')[0]), true);
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4) {
				if (xhr.status >= 200 && xhr.status < 400) {
					if (xhr.responseText && xhr.responseText.length) {
						try {
							eval("(" + xhr.responseText + ')');
							signPackage.debug = false;
							signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
							wx.config(signPackage);
							window.loading.load();
						} catch (e) {
							alert('local error:' + e.toString());
						}
					} else {
						//alert('http error: response is empty.');
						window.loading.load();
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