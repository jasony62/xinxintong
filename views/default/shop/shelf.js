shopApp = angular.module('shopApp', ['infinite-scroll']);
shopApp.directive('headingPic', function(){
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
shopApp.controller('shopCtrl', ['$scope', '$http', '$timeout', '$q', function ($scope, $http, $timeout, $q) {
    $scope.search = function () {
        $http.get('/rest/shop/shelf/list').success(function (rsp) {
            var matters = rsp.data;
            for (var i = 0, l = matters.length; i < l; i++) {
                $scope.matters.push(matters[i]);
            }
        });
    };
    $scope.copyMatter = function (copied) {
        $http.get('/rest/member/box/enroll/copy?mpid=' + $scope.mpid + '&shopid=' + copied.id).success(function (rsp) {
            location.href = '/rest/member/box?mpid=' + $scope.mpid;
        });
    };
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            $scope.mpid = params.mpid;
            $scope.matters = [];
            $scope.ready = true;
            console.log('ready', params);
            $scope.search();
        }
    });
}]);