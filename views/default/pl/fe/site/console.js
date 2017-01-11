var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt']);
ngApp.config(['$locationProvider', '$uibTooltipProvider', function($lp, $uibTooltipProvider) {
    $lp.html5Mode(true);
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
}]);
ngApp.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
ngApp.controller('ctrlConsole', ['$scope', '$uibModal', 'http2', 'templateShop', function($scope, $uibModal, http2, templateShop) {
    function searchMatters(append) {
        var url;
        if ($scope.matterType === 'addressbook') {
            url = '/rest/pl/fe/matter/' + $scope.matterType + '/get?site=' + $scope.siteId + page.j();
        } else {
            url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId + page.j();
        }
        url += '&_=' + (new Date() * 1);
        switch ($scope.matterType) {
            case 'channel':
                url += '&cascade=N';
                break;
        }
        if (/mission/.test($scope.matterType)) {
            http2.post(url, filter2, function(rsp) {
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.missions);
                } else {
                    $scope.matters = rsp.data.missions;
                }
                page.total = rsp.data.total;
            });
        } else {
            http2.get(url, function(rsp) {
                if (/article/.test($scope.matterType)) {
                    if (append) {
                        $scope.matters = $scope.matters.concat(rsp.data.articles);
                    } else {
                        $scope.matters = rsp.data.articles;
                    }
                    page.total = rsp.data.total;
                } else if (/enroll|signin|group|contribute/.test($scope.matterType)) {
                    if (append) {
                        $scope.matters = $scope.matters.concat(rsp.data.apps);
                    } else {
                        $scope.matters = rsp.data.apps;
                    }
                    page.total = rsp.data.total;
                } else if (/custom/.test($scope.matterType)) {
                    if (append) {
                        $scope.matters = $scope.matters.concat(rsp.data.customs);
                    } else {
                        $scope.matters = rsp.data.customs;
                    }
                    page.total = rsp.data.total;
                } else {
                    $scope.matters = rsp.data;
                }
            });
        }
    };
    var filter2, page;
    $scope.matterType = 'recent';
    $scope.filter2 = filter2 = {};
    $scope.page = page = {
        at: 1,
        size: 21,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
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
            case 'addressbook':
            case 'merchant':
            case 'wall':
                location.href = '/rest/pl/fe/matter/' + type + '?id=' + id + '&site=' + $scope.siteId;
                break;
            case 'mission':
                location.href = '/rest/pl/fe/matter/' + type + '?id=' + (matter.mission_id || id) + '&site=' + matter.siteid;
                break;
        }
    };
    $scope.moreMatters = function() {
        $scope.page.at++;
        searchMatters(true);
    };
    $scope.chooseMatterType = function(matterType) {
        matterType && ($scope.matterType = matterType);
        if ($scope.matterType === 'recent') {
            http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date() * 1), function(rsp) {
                $scope.matters = rsp.data.matters;
                //$scope.page.total = rsp.data.total;
            });
        } else if ($scope.matterType === 'recycle') {
            http2.get('/rest/pl/fe/site/console/recycle?site=' + $scope.siteId + '&_=' + (new Date() * 1), function(rsp) {
                $scope.matters = rsp.data.matters;
            });
        } else {
            $scope.page.at = 1;
            $scope.page.total = 0;
            searchMatters(false);
        }
    };
    $scope.doFilter = function() {
        page.at = 1;
        page.total = 0;
        searchMatters();
        $('body').click();
    };
    $scope.cleanFilter = function() {
        filter2.byTitle = '';
        page.at = 1;
        page.total = 0;
        searchMatters();
        $('body').click();
    };
    $scope.removeMatter = function(evt, matter) {
        var type = (matter.matter_type || matter.type || $scope.matterType),
            id = (matter.matter_id || matter.id),
            title = (matter.title || matter.matter_title),
            url = '/rest/pl/fe/matter/';

        evt.stopPropagation();
        if (window.confirm('确定删除：' + title + '？')) {
            switch (type) {
                case 'article':
                case 'addressbook':
                    url += type + '/remove?id=' + id + '&site=' + $scope.siteId;
                    break;
                case 'enroll':
                case 'signin':
                case 'group':
                    url += type + '/remove?app=' + id + '&site=' + $scope.siteId;
                    break;
                case 'news':
                    url += type + '/delete?site=' + $scope.siteId + '&id=' + id;
            }
            http2.get(url, function(rsp) {
                $scope.matters.splice($scope.matters.indexOf(matter), 1);
            });
        }
    };
    $scope.copyMatter = function(evt, matter) {
        var type = (matter.matter_type || matter.type || $scope.matterType),
            id = (matter.matter_id || matter.id),
            url = '/rest/pl/fe/matter/';

        evt.stopPropagation();
        switch (type) {
            case 'article':
                url += type + '/copy?id=' + id + '&site=' + $scope.siteId;
                break;
            case 'enroll':
                url += 'enroll/copy?app=' + id + '&site=' + $scope.siteId;
                break;
            case 'signin':
            case 'group':
                url += type + '/copy?app=' + id + '&site=' + $scope.siteId;
                break;
            default:
                alert('程序错误');
                return;
        }
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/matter/' + type + '?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.restoreMatter = function(matter) {
        var url = '/rest/pl/fe/matter/' + matter.matter_type + '/restore' + '?site=' + $scope.siteId + '&id=' + matter.matter_id;
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/matter/' + matter.matter_type + '?site=' + $scope.siteId + '&id=' + matter.matter_id;
        });
    };
    $scope.gotoText = function() {
        location.href = '/rest/pl/fe/matter/text?site=' + $scope.siteId;
    };
    $scope.addLink = function() {
        http2.get('/rest/pl/fe/matter/link/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/link?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    //研究项目-单图文
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
    //研究项目-登记活动
    $scope.addEnrollByTemplate = function() {
        $('body').trigger('click');
        templateShop.choose($scope.siteId, 'enroll').then(function(choice) {
            if (choice) {
                if (choice.source === 'share') {
                    var url, data = choice.data;
                    url = '/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.siteId + '&template=' + data.id;
                    http2.get(url, function(rsp) {
                        location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
                    });
                } else if (choice.source === 'platform') {
                    var url, config, data = choice.data;
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
                } else if (choice.source === 'file') {
                    var url, data = choice.data;
                    url = '/rest/pl/fe/matter/enroll/createByFile?site=' + $scope.siteId;
                    http2.post(url, data, function(rsp) {
                        location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
                    });
                }
            } else {
                var url;
                url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.siteId;
                http2.post(url, {}, function(rsp) {
                    location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
                });
            }
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
    //信息墙
    $scope.addWall = function() {
        http2.get('/rest/pl/fe/matter/wall/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/wall?site=' + $scope.siteId + '&id=' + rsp.data;
        });
    };
    $scope.addAddressbook = function() {
        http2.get('/rest/pl/fe/matter/addressbook/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/addressbook?site=' + $scope.siteId + '&id=' + rsp.data;

        });
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date() * 1), function(rsp) {
        $scope.matters = rsp.data.matters;
        //$scope.page.total = rsp.data.total;
    });
}]);
