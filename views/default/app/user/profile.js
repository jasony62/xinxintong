if (/MicroMessenger/.test(navigator.userAgent)) {
	if (window.signPackage) {
		//signPackage.debug = true;
		signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
		wx.config(signPackage);
	}
}
lotApp = angular.module('app', ["ngSanitize"]).
controller('userCtrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
	var mpid;
	mpid = location.search.match(/mpid=([^&]*)/)[1];
	$http.get('/rest/app/user/get?mpid=' + mpid).success(function(rsp) {
		var data, user;
		data = rsp.data;
		user = data.user;
		user._styleAvatar = user.fan.headimgurl ? {
			"background-image": "url('" + user.fan.headimgurl + "')"
		} : '';
		$scope.user = user;
		$scope.stat = data.stat;
	});
}]);