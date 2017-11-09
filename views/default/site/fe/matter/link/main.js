'use strict';
if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('app', []).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', function($scope, $location, $http, $q) {
    var siteId, linkId;
    siteId = $location.search().site;
    linkId = $location.search().id;
    $http.get('/rest/site/home/get?site=' + siteId).success(function(rsp) {
        $scope.siteInfo = rsp.data;
        $http.get('/rest/site/fe/matter/link/get?site=' + siteId + '&id=' + linkId).success(function(rsp) {
            $scope.link = rsp.data.link;
            document.querySelector('#link>iframe').setAttribute('src', $scope.link.fullUrl);
            $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + linkId + '&type=link&title=' + $scope.link.title, {
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
        }).error(function(content, httpCode) {});
    });
}]);