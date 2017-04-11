ngApp.provider.controller('ctrlBasic', ['$scope', 'PageUrl', function($scope, PageUrl) {
    var oRecord, oBeforeRecord, PU, params = location.search.match('site=(.*)')[1];
    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);

    $scope.editRecord = function(record) {
        var url;
        url = '/rest/pl/fe/matter/enroll/editor';
        url += '?site=' + PU.params.app.site;
        url += '&id=' + PU.params.app;
        url += '&ek=' + record.enroll_key;
        location.href = url;
    };
}]);
