var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt', 'tmplshop.ui.xxt']);
ngApp.config(['$locationProvider', function($lp) {
    $lp.html5Mode(true);
}]);
ngApp.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
        $scope.site = rsp.data;
    });
}]);

ngApp.controller('ctrlConsole', ['$scope', '$uibModal', 'http2', 'templateShop', function($scope, $uibModal, http2, templateShop) {

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
    $scope.page = {
        at: 1,
        size: 20,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    var searchMatters = function(append) {
        if ($scope.matterType === 'addressbook') {
            var url = '/rest/pl/fe/matter/' + $scope.matterType + '/get?site=' + $scope.siteId + $scope.page.j();
        } else {
            var url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId + $scope.page.j();
        }
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

            case 'wall':
                $scope.addWall();
                break;
            case 'addressbook':
                $scope.addAddressbook();
                break;
        }
    };
    $scope.removeMatter = function(evt, matter) {
        var type = (matter.matter_type || $scope.matterType),
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
            }
            http2.get(url, function(rsp) {
                $scope.matters.splice($scope.matters.indexOf(matter), 1);
            });
        }
    };
    $scope.copyMatter = function(evt, matter) {
        var type = (matter.matter_type || $scope.matterType),
            id = (matter.matter_id || matter.id),
            url = '/rest/pl/fe/matter/';

        evt.stopPropagation();
        switch (type) {
            case 'article':
                url += type + '/copy?id=' + id + '&site=' + $scope.siteId;
                break;
            case 'enroll':
            case 'signin':
            case 'group':
                url += type + '/copy?app=' + id + '&site=' + $scope.siteId;
                break;
        }
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/matter/' + type + '?site=' + $scope.siteId + '&id=' + rsp.data.id;
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
        templateShop.choose('enroll').then(function(choice) {
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
            /*location.href = '/rest/pl/fe/matter/addressbook/edit?id='+ rsp.data + '&site=' + $scope.siteId;*/
            location.href = '/rest/pl/fe/matter/addressbook?site=' + $scope.siteId + '&id=' + rsp.data;

        });
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date()).getTime(), function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);