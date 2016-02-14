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
				"base": "/views/default/app/merchant/base",
				"directive": "/views/default/app/merchant/directive",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"base": {
					exports: "app",
					deps: ["angular"]
				},
				"directive": {
					deps: ["base"]
				},
				"cookie": {
					exports: "Cookies"
				},
			},
			deps: ['/views/default/app/merchant/pay/wx.js'],
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
					try {
						if (xhr.responseText && xhr.responseText.length) {
							eval("(" + xhr.responseText + ")");
							signPackage.debug = false;
							signPackage.jsApiList = ['hideOptionMenu'];
							wx.config(signPackage);
							wx.ready(function() {
								wx.hideOptionMenu();
							});
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