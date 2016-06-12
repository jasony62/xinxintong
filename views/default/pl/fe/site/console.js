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
app.controller('ctrlConsole', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
    $scope.matterType = 'recent';
    $scope.open = function(matter) {
        var type = $scope.matterType === 'recent' ? matter.matter_type : $scope.matterType,
            id = (matter.matter_id || matter.id);
        switch (type) {
            case 'text':
            case 'article':
            case 'custom':
            case 'news':
            case 'channel':
            case 'enroll':
            case 'signin':
            case 'group':
            case 'lottery':
            case 'contribute':
            case 'link':
            case 'mission':
            case 'merchant':
                location.href = '/rest/pl/fe/matter/' + type + '?id=' + id + '&site=' + $scope.siteId;
                break;
        }
    };
    $scope.page = {
        at: 1,
        size: 20,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    var searchMatters = function(append) {
        var url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId + $scope.page.j();
        url += '&_=' + (new Date()).getTime();
        switch ($scope.matterType) {
            case 'channel':
                url += '&cascade=N';
                break;
        }
        http2.get(url, function(rsp) {
            if (/article/.test($scope.matterType)) {
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.articles);
                } else {
                    $scope.matters = rsp.data.articles;
                }
                $scope.page.total = rsp.data.total;
            } else if (/enroll|signin|group|contribute/.test($scope.matterType)) {
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.apps);
                } else {
                    $scope.matters = rsp.data.apps;
                }
                $scope.page.total = rsp.data.total;
            } else if (/mission/.test($scope.matterType)) {
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.missions);
                } else {
                    $scope.matters = rsp.data.missions;
                }
                $scope.page.total = rsp.data.total;
            } else if (/custom/.test($scope.matterType)) {
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.customs);
                } else {
                    $scope.matters = rsp.data.customs;
                }
                $scope.page.total = rsp.data.total;
            } else {
                $scope.matters = rsp.data;
            }
        });
    };
    $scope.moreMatters = function() {
        $scope.page.at++;
        searchMatters(true);
    };
    $scope.chooseMatterType = function() {
        if ($scope.matterType === 'recent') {
            http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date()).getTime(), function(rsp) {
                $scope.matters = rsp.data.matters;
            });
        } else {
            $scope.page.at = 1;
            $scope.page.total = 0;
            searchMatters(false);
        }
    };
    $scope.addMatter = function() {
        switch ($scope.matterType) {
            case 'article':
                $scope.addArticle();
                break;
            case 'custom':
                $scope.addCustom();
                break;
            case 'news':
                $scope.addNews();
                break;
            case 'channel':
                $scope.addChannel();
                break;
            case 'enroll':
                $scope.addEnrollByTemplate();
                break;
            case 'signin':
                $scope.addSignin();
                break;
            case 'group':
                $scope.addGroup();
                break;
            case 'lottery':
                $scope.addLottery();
                break;
            case 'contribute':
                $scope.addContribute();
                break;
            case 'mission':
                $scope.addMission();
                break;
            case 'text':
                $scope.gotoText();
                break;
            case 'link':
                $scope.addLink();
                break;
            case 'merchant':
                $scope.addMerchant();
                break;
        }
    };
    $scope.gotoText = function() {
        location.href = '/rest/pl/fe/matter/text?site=' + $scope.siteId;
    };
    $scope.addLink = function() {
        http2.get('/rest/pl/fe/matter/link/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/link?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
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
        $uibModal.open({
            templateUrl: '/views/default/pl/fe/_module/enroll-template.html',
            size: 'lg',
            backdrop: 'static',
            windowClass: 'auto-height template',
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
                        url += '/rest/site/fe/matter/enroll/template';
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
        });
    };
    $scope.addSignin = function() {
        http2.get('/rest/pl/fe/matter/signin/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/signin?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addGroup = function() {
        http2.get('/rest/pl/fe/matter/group/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/group?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addLottery = function() {
        http2.get('/rest/pl/fe/matter/lottery/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/lottery?site=' + $scope.siteId + '&id=' + rsp.data;
        });
    };
    $scope.addContribute = function() {
        http2.get('/rest/pl/fe/matter/contribute/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/contribute?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addMission = function() {
        http2.get('/rest/pl/fe/matter/mission/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/mission?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addCustom = function() {
        http2.get('/rest/pl/fe/matter/custom/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/custom?site=' + $scope.siteId + '&id=' + rsp.data;
        });
    };
    $scope.addMerchant = function() {
        http2.get('/rest/pl/fe/matter/merchant/shop/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/merchant/shop?site=' + $scope.siteId + '&id=' + rsp.data;
        });
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date()).getTime(), function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);