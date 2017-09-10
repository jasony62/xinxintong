angular.module('tmplshop.ui.xxt', ['ui.bootstrap']).
service('templateShop', ['$uibModal', 'http2', '$q', function($uibModal, http2, $q) {
    this.choose = function(siteId, type, assignedScenario) {
        var deferred;
        deferred = $q.defer();
        $uibModal.open({
            templateUrl: '/static/template/templateShop.html?_=10',
            backdrop: 'static',
            //size: 'lg',
            controller: ['$scope', '$uibModalInstance', '$timeout', function($scope, $mi, $timeout) {
                function _excelLoader() {
                    if (Resumable) {
                        var ele, r;
                        ele = document.getElementById('btnCreateByExcel');
                        r = new Resumable({
                            target: '/rest/pl/fe/matter/enroll/uploadExcel4Create?site=' + siteId,
                            testChunks: false,
                        });
                        r.assignBrowse(ele);
                        r.on('fileAdded', function(file, event) {
                            r.upload();
                        });
                        r.on('complete', function() {
                            var f, lastModified, posted;
                            f = r.files.pop().file;
                            lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                            posted = {
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                lastModified: lastModified,
                                uniqueIdentifier: f.uniqueIdentifier,
                            };
                            http2.post('/rest/pl/fe/matter/enroll/createByExcel?site=' + siteId, posted, function(rsp) {
                                $mi.close({ source: 'file', app: rsp.data });
                            });
                        });
                    }
                }
                $scope.source = 'platform';
                $scope.criteria = {
                    scope: 'P'
                };
                $scope.page = {
                    size: 10,
                    at: 1,
                    total: 0
                };
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
                        case 'file':
                            choice.data = $scope.fileTemplate;
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
                    var url = '/rest/pl/fe/template/site/list?matterType=' + type + '&scope=P' + '&site=' + siteId;

                    http2.get(url, function(rsp) {
                        $scope.templates = rsp.data.templates;
                        $scope.page.total = rsp.data.total;
                    });
                };
                $scope.searchShare2Me = function() {
                    var url = '/rest/pl/fe/template/platform/share2Me?matterType=' + type;

                    http2.get(url, function(rsp) {
                        $scope.templates = rsp.data.templates;
                        $scope.page.total = rsp.data.total;
                    });
                };
                $scope.searchBySite = function() {
                    var url = '/rest/pl/fe/template/site/list?site=' + siteId + '&matterType=' + type + '&scope=S';

                    http2.get(url, function(rsp) {
                        $scope.templates = rsp.data.templates;
                        $scope.page.total = rsp.data.total;
                    });
                };
                window.chooseFile = function(file) {
                    var fReader;
                    fReader = new FileReader();
                    fReader.onload = function(evt) {
                        var template, url;
                        template = evt.target.result;
                        template = JSON.parse(template);
                        url = '/rest/pl/fe/matter/enroll/createByConfig?site=' + siteId;
                        http2.post(url, template, function(rsp) {
                            $mi.close({ source: 'file', app: rsp.data });
                        });
                    };
                    fReader.readAsText(file);
                };
                $scope.$watch('source', function(source) {
                    if (source === 'file') {
                        $timeout(function() {
                            _excelLoader();
                        });
                    }
                });
                /*系统模版*/
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
            templateUrl: '/static/template/templateShare.html?_=11',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.data = {};
                $scope.params = {};
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
                http2.get('/rest/pl/fe/template/byMatter?type=' + matter.type + '&id=' + matter.id, function(rsp) {
                    if (rsp.data) {
                        $scope.data = rsp.data;
                    } else {
                        $scope.data.matter_type = matter.type;
                        $scope.data.matter_id = matter.id;
                        matter.scenario && ($scope.data.scenario = matter.scenario);
                        $scope.data.title = matter.title;
                        $scope.data.summary = matter.summary;
                        $scope.data.pic = matter.pic;
                    }
                });
            }],
            backdrop: 'static'
        }).result.then(function(data) {
            http2.post('/rest/pl/fe/template/put?site=' + siteId, data, function(rsp) {
                deferred.resolve(rsp.data);
            });
        });

        return deferred.promise;
    };
}]);