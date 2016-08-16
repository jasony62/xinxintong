angular.module('tmplshop.ui.xxt', ['ui.bootstrap']).
service('templateShop', ['$uibModal', 'http2', '$q', function($uibModal, http2, $q) {
    this.choose = function(type, callback) {
        var deferred;
        deferred = $q.defer();
        $uibModal.open({
            templateUrl: '/static/template/templateShop.html?_=2',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.source = 'share';
                $scope.page = {
                    size: 10,
                    at: 1,
                    total: 0
                }
                $scope.data = {
                    choose: -1
                };
                $scope.switchSource = function(source) {
                    $scope.source = source;
                };
                $scope.ok = function() {
                    var choice;
                    choice = {
                        source: $scope.source
                    };
                    switch ($scope.source) {
                        case 'platform':
                            choice.data = $scope.data2;
                            break;
                        case 'share':
                            choice.data = $scope.templates[$scope.data.choose];
                            break;
                    }
                    if (choice.data) {
                        $mi.close(choice);
                    }
                };
                $scope.blank = function() {
                    $mi.close();
                };
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.data2 = {};
                $scope.chooseScenario = function() {};
                $scope.chooseTemplate = function() {
                    if (!$scope.data2.template) return;
                    var url;
                    url = '/rest/pl/fe/matter/enroll/template/config';
                    url += '?scenario=' + $scope.data2.scenario.name;
                    url += '&template=' + $scope.data2.template.name;
                    http2.get(url, function(rsp) {
                        var elSimulator, url;
                        $scope.data2.simpleSchema = rsp.data.simpleSchema ? rsp.data.simpleSchema : '';
                        $scope.pages = rsp.data.pages;
                        $scope.data2.selectedPage = $scope.pages[0];
                        elSimulator = document.querySelector('#simulator');
                        url = 'http://' + location.host;
                        url += '/rest/site/fe/matter/enroll/template';
                        url += '?scenario=' + $scope.data2.scenario.name;
                        url += '&template=' + $scope.data2.template.name;
                        url += '&page=' + $scope.data2.selectedPage.name;
                        url += '&_=' + (new Date() * 1);
                        elSimulator.src = url;
                    });
                };
                $scope.choosePage = function() {
                    var elSimulator, page;
                    elSimulator = document.querySelector('#simulator');
                    config = {
                        simpleSchema: $scope.data2.simpleSchema
                    };
                    page = $scope.data2.selectedPage.name;
                    elSimulator.contentWindow.renew(page, config);
                };
                http2.get('/rest/pl/fe/template/shop/list?matterType=' + type, function(rsp) {
                    $scope.templates = rsp.data.templates;
                    $scope.page.total = rsp.data.total;
                });
                http2.get('/rest/pl/fe/matter/enroll/template/list', function(rsp) {
                    $scope.templates2 = rsp.data;
                });
            }],
        }).result.then(function(data) {
            deferred.resolve(data);
        });
        return deferred.promise;
    };
    this.share = function(siteId, matter) {
        var deferred;
        deferred = $q.defer();
        $uibModal.open({
            templateUrl: '/static/template/templateShare.html?_=2',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.data = {};
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
                http2.get('/rest/pl/fe/template/shop/get?matterType=' + matter.type + '&matterId=' + matter.id, function(rsp) {
                    if (rsp.data) {
                        $scope.data = rsp.data;
                    } else {
                        $scope.data.matter_type = matter.type;
                        $scope.data.matter_id = matter.id;
                        $scope.data.title = matter.title;
                        $scope.data.summary = matter.summary;
                        $scope.data.pic = matter.pic;
                        $scope.data.visible_scope = 'U';
                    }
                });
            }],
            backdrop: 'static'
        }).result.then(function(data) {
            http2.post('/rest/pl/fe/template/shop/put?site=' + siteId, data, function(rsp) {
                deferred.resolve(rsp.data);
            });
        });

        return deferred.promise;
    };
}]);