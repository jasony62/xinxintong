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
				"enroll-directive": "/views/default/app/enroll/directive",
				"enroll-common": "/views/default/app/enroll/common",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"angular-sanitize": {
					deps: ['angular'],
					exports: "angular-sanitize"
				},
				"resumable": {
					exports: "resumbale"
				},
				"xxt-share": {
					exports: "xxt-share"
				},
				"xxt-image": {
					exports: "xxt-image"
				},
				"xxt-geo": {
					exports: "xxt-geo"
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
			deps: ['/views/default/app/enroll/input.js?_=1'],
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
						eval("(" + xhr.responseText + ')');
						signPackage.debug = false;
						signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage'];
						wx.config(signPackage);
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