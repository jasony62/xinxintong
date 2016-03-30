var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', function($lp) {
    $lp.html5Mode(true);
}]);
app.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
app.controller('ctrlConsole', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    $scope.open = function(matter) {
        if (matter.matter_type === 'article') {
            location.href = '/rest/pl/fe/matter/article?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'news') {
            location.href = '/rest/pl/fe/matter/news?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'channel') {
            location.href = '/rest/pl/fe/matter/channel?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'enroll') {
            location.href = '/rest/pl/fe/matter/enroll?id=' + matter.matter_id + '&site=' + $scope.siteId;
        } else if (matter.matter_type === 'mission') {
            location.href = '/rest/pl/fe/matter/mission?id=' + matter.matter_id + '&site=' + $scope.siteId;
        }
    };
    $scope.addArticle = function() {
        http2.get('/rest/pl/fe/matter/article/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + rsp.data;
        });
    };
    $scope.addNews = function() {
        http2.get('/rest/pl/fe/matter/news/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/news?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addChannel = function() {
        http2.get('/rest/pl/fe/matter/channel/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/channel?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addEnrollByTemplate = function() {
        $modal.open({
            templateUrl: 'templatePicker.html',
            size: 'lg',
            backdrop: 'static',
            windowClass: 'auto-height',
            controller: ['$scope', '$modalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
                $scope2.blank = function() {
                    $mi.close();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.data);
                };
                $scope2.chooseScenario = function() {};
                $scope2.chooseTemplate = function() {
                    if (!$scope2.data.template) return;
                    var url;
                    url = '/rest/pl/fe/matter/enroll/template/config';
                    url += '?scenario=' + $scope2.data.scenario.name;
                    url += '&template=' + $scope2.data.template.name;
                    http2.get(url, function(rsp) {
                        var elSimulator, url;
                        $scope2.data.simpleSchema = rsp.data.simpleSchema ? rsp.data.simpleSchema : '';
                        $scope2.pages = rsp.data.pages;
                        $scope2.data.selectedPage = $scope2.pages[0];
                        elSimulator = document.querySelector('#simulator');
                        url = 'http://' + location.host;
                        url += '/rest/app/enroll/template';
                        url += '?scenario=' + $scope2.data.scenario.name;
                        url += '&template=' + $scope2.data.template.name;
                        url += '&_=' + (new Date()).getTime();
                        elSimulator.src = url;
                        elSimulator.onload = function() {
                            $scope.$apply(function() {
                                $scope2.choosePage();
                            });
                        };
                    });
                };
                $scope2.choosePage = function() {
                    var elSimulator, page;
                    elSimulator = document.querySelector('#simulator');
                    config = {
                        simpleSchema: $scope2.data.simpleSchema
                    };
                    page = $scope2.data.selectedPage.name;
                    elSimulator.contentWindow.renew(page, config);
                };
                http2.get('/rest/pl/fe/matter/enroll/template/list', function(rsp) {
                    $scope2.templates = rsp.data;
                });
            }]
        }).result.then(function(data) {
            var url, config;
            url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.siteId;
            config = {};
            if (data) {
                url += '&scenario=' + data.scenario.name;
                url += '&template=' + data.template.name;
                if (data.simpleSchema && data.simpleSchema.length) {
                    config.simpleSchema = data.simpleSchema;
                }
            }
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
            });
        })
    };
    $scope.addTask = function() {
        http2.get('/rest/pl/fe/matter/mission/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/mission?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date()).getTime(), function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);