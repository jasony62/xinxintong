define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMassmsg', ['$scope', 'http2', function($scope, http2) {
        $scope.matterType = 'text';
        $scope.page = {
            at: 1,
            size: 30
        };
        $scope.selectMatter = function(matter) {
            $scope.selectedMatter = matter;
        };
        $scope.fetchMatter = function(page) {
            $scope.selectedMatter = null;
            var url = '/rest/pl/fe/matter/' + $scope.matterType,
                params = {};;
            !page && (page = $scope.page.at);
            url += '/list?site=' + $scope.siteId;
            url += '&page=' + page + '&size=' + $scope.page.size;
            http2.post(url, params, function(rsp) {
                if ('article' === $scope.matterType) {
                    $scope.matters = rsp.data.articles;
                    $scope.page.total = rsp.data.total;
                } else
                    $scope.matters = rsp.data;
            });
        };
        $scope.send = function(evt) {
            var data = {
                id: $scope.selectedMatter.id,
                type: $scope.matterType,
                groups: [{
                    id: -1
                }],
            };
            http2.post('/rest/pl/fe/site/sns/wx/send/mass?site=' + $scope.siteId, data, function(rsp) {
                $scope.massStatus = {
                    result: 'ok'
                };
            });
        };
        $scope.fetchMatter();

        http2.get('/rest/pl/fe/site/sns/wx/group/list?site=' + $scope.siteId, function(rsp) {
            $scope.groups = rsp.data;
        });
    }]);
});