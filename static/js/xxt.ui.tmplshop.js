angular.module('tmplshop.ui.xxt', ['ui.bootstrap']).
service('templateShop', ['$uibModal', 'http2', '$q', function($uibModal, http2, $q) {
    this.choose = function(type, assignedScenario) {
        var deferred;
        deferred = $q.defer();
        $uibModal.open({
            templateUrl: '/static/template/templateShop.html?_=5',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.source = 'platform';
                $scope.criteria = {
                    scope: 'A'
                };
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
                    if (source === 'platform') {
                        $scope.chooseTemplate();
                    }
                };
                $scope.ok = function() {
                    var choice;
                    choice = {
                        source: $scope.source
                    };
                    switch ($scope.source) {
                        case 'platform':
                            choice.data = $scope.result;
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
                $scope.result = {}; // 用户选择结果
                $scope.chooseScenario = function() {
                    var oTemplates, keys;

                    oTemplates = $scope.result.scenario.templates;
                    keys = Object.keys(oTemplates);
                    $scope.result.template = oTemplates[keys[0]];
                    $scope.chooseTemplate();
                };
                $scope.chooseTemplate = function() {
                    if (!$scope.result.template) return;
                    var url;
                    url = '/rest/pl/fe/matter/enroll/template/config';
                    url += '?scenario=' + $scope.result.scenario.name;
                    url += '&template=' + $scope.result.template.name;
                    http2.get(url, function(rsp) {
                        var elSimulator, url;
                        $scope.pages = rsp.data.pages;
                        $scope.result.selectedPage = $scope.pages[0];
                        elSimulator = document.querySelector('#simulator');
                        url = 'http://' + location.host;
                        url += '/rest/site/fe/matter/enroll/template';
                        url += '?scenario=' + $scope.result.scenario.name;
                        url += '&template=' + $scope.result.template.name;
                        url += '&page=' + $scope.result.selectedPage.name;
                        url += '&_=' + (new Date() * 1);
                        elSimulator.src = url;
                    });
                };
                $scope.choosePage = function() {
                    var elSimulator, page;
                    elSimulator = document.querySelector('#simulator');
                    config = {};
                    page = $scope.result.selectedPage.name;
                    if (elSimulator.contentWindow.renew) {
                        elSimulator.contentWindow.renew(page, config);
                    }
                };
                $scope.searchTemplate = function() {
                    var url = '/rest/pl/fe/template/shop/list?matterType=' + type + '&scope=' + $scope.criteria.scope;
                    http2.get(url, function(rsp) {
                        $scope.templates = rsp.data.templates;
                        $scope.page.total = rsp.data.total;
                    });
                };
                switch (type) {
                    case 'enroll':
                        http2.get('/rest/pl/fe/matter/enroll/template/list', function(rsp) {
                            var oScenarioes = rsp.data,
                                oTemplates;

                            $scope.templates2 = oScenarioes;
                            if (assignedScenario && assignedScenario.length) {
                                if (oScenarioes[assignedScenario]) {
                                    $scope.result.scenario = oScenarioes[assignedScenario];
                                    $scope.fixedScenario = true;
                                    oTemplates = $scope.result.scenario.templates;
                                    $scope.result.template = oTemplates[Object.keys(oTemplates)];
                                    $scope.chooseTemplate();
                                }
                            }
                        });
                        break;
                }
                $scope.searchTemplate();
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
            templateUrl: '/static/template/templateShare.html?_=5',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.data = {};
                $scope.params = {};
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
                $scope.addReceiver = function() {
                    http2.get('/rest/pl/fe/template/acl/add?label=' + $scope.params.label + '&matter=' + matter.id + ',' + matter.type, function(rsp) {
                        if ($scope.data.acls === undefined) {
                            $scope.data.acls = [];
                        }
                        $scope.data.acls.push(rsp.data);
                        $scope.params.label = '';
                    });
                };
                $scope.removeReceiver = function(acl) {
                    if (acl.id) {
                        http2.get('/rest/pl/fe/template/acl/remove?acl=' + acl.id, function(rsp) {
                            $scope.data.acls.splice($scope.data.acls.indexOf(acl));
                        });
                    } else {
                        $scope.data.acls.splice($scope.data.acls.indexOf(acl));
                    }
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