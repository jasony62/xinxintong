window.loading = {
	finish: function() {
		eleLoading = document.querySelector('.loading');
		eleLoading.parentNode.removeChild(eleLoading);
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "/static/js/angular.min",
				"angular-sanitize": "/static/js/angular-sanitize.min",
				"resumable": "/static/js/resumable.min",
				"xxt-share": "/static/js/xxt.share",
				"xxt-image": "/static/js/xxt.image",
				"xxt-geo": "/static/js/xxt.geo",
				"enroll-directive": "/views/default/site/fe/matter/signin/directive",
				"enroll-common": "/views/default/site/fe/matter/signin/common",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"angular-sanitize": {
					deps: ['angular'],
					exports: "angular-sanitize"
				},
				"xxt-share": {
					exports: "xxt-share"
				},
				"enroll-common": {
					deps: ['angular-sanitize'],
					exports: "enroll-common"
				},
				"enroll-directive": {
					deps: ['enroll-common'],
					exports: "enroll-directive"
				},
			},
			deps: ['/views/default/site/fe/matter/signin/view.js'],
			urlArgs: "bust=" + (new Date()).getTime()
		});
	}
};
if (/MicroMessenger/i.test(navigator.userAgent)) {
	var site = location.search.match(/[\?&]site=([^&]*)/)[1];
	requirejs(["http://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx) {
		var xhr = new XMLHttpRequest();
		xhr.open('GET', "/rest/site/fe/matter/signin/wxjssdksignpackage?site=" + site + "&url=" + encodeURIComponent(location.href.split('#')[0]), true);
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