xxtApp = angular.module('xxt', ['ui.tms', 'matters.xxt']);
xxtApp.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
xxtApp.controller('boxCtrl', ['$scope', '$location', 'http2', function ($scope, $location, http2) {
	var mpid = $location.search().mpid;
	$scope.search = function() {
		var url = '/rest/member/box/get';
		url += '?mpid=' + mpid;
		url += '&_='+(new Date()).getTime();
		http2.get(url, function (rsp) {
			$scope.matters = rsp.data;
		});
	};
	$scope.createMatter = function () {
		var url = '/rest/member/box/enroll/create';
		url += '?mpid=' + mpid;
		http2.get(url, function (rsp) {
			location.href = '/rest/member/box/enroll?mpid=' + mpid + '&id=' + rsp.data.id;
		});
	};
	$scope.openMatter = function (matter) {
		var url = '/rest/member/box/';
		url += matter.matter_type;
		url += '?mpid=' + mpid;
		url += '&id=' + matter.matter_id;
		location.href = url;
	};
	$scope.$watch('jsonParams', function (nv) {
		if (nv && nv.length) {
			var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
			console.log('params', params);
			$scope.user = params.user;
			$scope.search();
		}
	});
	$scope.openShop = function () {
		location.href = '/rest/shop/shelf?mpid='+mpid;
    };
}]);
xxtApp.directive('headingPic', function(){
	return {
		restrict: 'A',
        link: function (scope, elem, attrs) {
			var w,h;
			w = $(elem).width();
			h = w / 9 * 5;
            $(elem).css('max-height', h);
        }
	};
});
