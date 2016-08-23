ngApp = angular.module('xxtApp', ['ngRoute', 'ui.tms']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlContribute', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    $scope.appId = $location.search().app;
    http2.get('/rest/site/fe/matter/contribute/entry/list?site=' + $scope.siteId + '&app=' + $scope.appId, function(rsp) {
        $scope.entries = rsp.data.entries;
        //mysef
        var setMpShare = function(xxtShare) {
            var shareid, sharelink;
            //shareid = $scope.user.uid + (new Date()).getTime();
            xxtShare.options.logger = function(shareto) {
                /*var url = "/rest/mi/matter/logShare";
                 url += "?shareid=" + shareid;
                 url += "&site=" + siteId;
                 url += "&id=" + id;
                 url += "&type=article";
                 url += "&title=" + $scope.article.title;
                 url += "&shareto=" + shareto;
                 //url += "&shareby=" + shareby;
                 $http.get(url);*/
            };
            //∑÷œÌ¡¥Ω”
            //sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
            //sharelink += '?site=' + siteId;
            //sharelink += '&type=article';
            //sharelink += '&id=' + id;
            //sharelink += "&shareby=" + shareid;
            //???
            xxtShare.set($scope.entries[0].title, sharelink,$scope.entries[0].summary,$scope.entries[0].pic);

        };

    })
    $scope.initiate = function(entry) {
        var url = '/rest/site/fe/matter/contribute/initiate';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.review = function(entry) {
        var url = '/rest/site/fe/matter/contribute/review';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.typeset = function(entry) {
        var url = '/rest/site/fe/matter/contribute/typeset';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
}]);