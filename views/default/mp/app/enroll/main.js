xxtApp.controller('enrollCtrl', ['$scope', '$uibModal', 'http2', 'templateShop', function($scope, $uibModal, http2, templateShop) {
    $scope.page = {
        at: 1,
        size: 28
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/app/enroll/list?page=' + $scope.page.at + '&size=' + $scope.page.size;
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '&src=p');
        http2.get(url, function(rsp) {
            $scope.apps = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.create = function() {
        var url, config;
        url = '/rest/mp/app/enroll/create';
        http2.post(url, {}, function(rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.createByInner = function() {
        $uibModal.open({
            templateUrl: 'templatePicker.html',
            size: 'lg',
            backdrop: 'static',
            windowClass: 'auto-height',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
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
                    url = '/rest/mp/app/enroll/template/config';
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
                http2.get('/rest/mp/app/enroll/template/list', function(rsp) {
                    $scope2.templates = rsp.data;
                });
            }]
        }).result.then(function(data) {
            var url, config;
            url = '/rest/mp/app/enroll/create';
            config = {};
            if (data) {
                url += '?scenario=' + data.scenario.name;
                url += '&template=' + data.template.name;
                if (data.simpleSchema && data.simpleSchema.length) {
                    config.simpleSchema = data.simpleSchema;
                }
            }
            http2.post(url, config, function(rsp) {
                location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
            });
        })
    };
    $scope.createByShare = function() {
        templateShop.choose('enroll').then(function(data) {
            var url;
            url = '/rest/mp/app/enroll/createByOther?template=' + data.id;
            http2.get(url, function(rsp) {
                location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
            });
        });
    };
    $scope.copy = function(copied, event) {
        event.preventDefault();
        event.stopPropagation();
        var url;
        url = '/rest/mp/app/enroll/copy?';
        url += 'aid=' + copied.id;
        http2.get(url, function(rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.open = function(aid) {
        location.href = '/rest/mp/app/enroll/detail?aid=' + aid;
    };
    $scope.remove = function(act, event) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/app/enroll/remove?aid=' + act.id, function(rsp) {
            var i = $scope.apps.indexOf(act);
            $scope.apps.splice(i, 1);
        });
    };
    $scope.doSearch();
}]);