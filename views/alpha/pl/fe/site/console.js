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
        url = '/rest/pl/fe/matter/' + $scope.matterType + '/list?site=' + $scope.siteId + page.j();
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
                if (append) {
                    $scope.matters = $scope.matters.concat(rsp.data.docs || rsp.data.apps);
                } else {
                    $scope.matters = rsp.data.docs || rsp.data.apps;
                }
                page.total = rsp.data.total;
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
    $scope.open = function(matter, subView) {
        var url = '/rest/pl/fe/matter/',
            type = $scope.matterType === 'recent' ? matter.matter_type : $scope.matterType,
            id = (matter.matter_id || matter.id);

        url += type;
        if (subView) {
            url += '/' + subView;
        }
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
            case 'link':
            case 'merchant':
            case 'wall':
                location.href = url + '?id=' + id + '&site=' + $scope.siteId;
                break;
            case 'mission':
                location.href = url + '?id=' + (matter.mission_id || id) + '&site=' + matter.siteid;
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
                case 'link':
                case 'news':
                case 'channel':
                    url += type + '/remove?id=' + id + '&site=' + $scope.siteId;
                    break;
                case 'enroll':
                case 'signin':
                case 'group':
                case 'wall':
                case 'lottery':
                    url += type + '/remove?app=' + id + '&site=' + $scope.siteId;
                    break;
                default:
                    alert('指定素材不支持删除');
                    return;
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
            case 'wall':
            case 'signin':
            case 'group':
                url += type + '/copy?app=' + id + '&site=' + $scope.siteId;
                break;
            default:
                alert('指定素材不支持复制');
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
            location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.createArticleByPptx = function() {
        var siteId = $scope.siteId;
        $uibModal.open({
            templateUrl: 'createArticleByPptx.html',
            controller: ['$scope', '$uibModalInstance', '$timeout', function($scope, $mi) {
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    var r = new Resumable({
                        target: '/rest/pl/fe/matter/article/uploadAndCreate?site=' + siteId,
                        testChunks: false,
                    });
                    r.on('fileAdded', function(file, event) {
                        console.log('file Added and begin upload.');
                        r.upload();
                    });
                    r.on('progress', function() {
                        console.log('progress.');
                    });
                    r.on('complete', function() {
                        console.log('complete.');
                        var f, lastModified, posted;
                        f = r.files[0].file;
                        lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                        posted = {
                            file: {
                                uniqueIdentifier: r.files[0].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                lastModified: lastModified,
                                uniqueIdentifier: f.uniqueIdentifier,
                            }
                        };
                        http2.post('/rest/pl/fe/matter/article/uploadAndCreate?site=' + siteId + '&state=done', posted, function(rsp) {
                            $mi.close(rsp.data);
                        });
                    });
                    r.addFile(document.querySelector('#fileUpload').files[0]);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            location.href = '/rest/pl/fe/matter/article?site=' + siteId + '&id=' + data.id;
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
    $scope.addEnrollByTemplate = function(scenario) {
        templateShop.choose($scope.siteId, 'enroll', scenario).then(function(choice) {
            if (choice) {
                if (choice.source === 'share') {
                    var url, data = choice.data;
                    url = '/rest/pl/fe/template/purchase?template=' + data.id + '&site=' + $scope.siteId;
                    http2.get(url, function(rsp) {
                        http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.siteId + '&template=' + data.id, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + $scope.siteId;
                        });
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
        http2.get('/rest/pl/fe/matter/group/create?site=' + $scope.siteId + '&scenario=split', function(rsp) {
            location.href = '/rest/pl/fe/matter/group/main?site=' + $scope.siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.addLottery = function() {
        http2.get('/rest/pl/fe/matter/lottery/create?site=' + $scope.siteId, function(rsp) {
            location.href = '/rest/pl/fe/matter/lottery?site=' + $scope.siteId + '&id=' + rsp.data;
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
        location.href = '/rest/pl/fe/matter/wall/shop?site=' + $scope.siteId;
    };
    http2.get('/rest/pl/fe/site/console/recent?site=' + $scope.siteId + '&_=' + (new Date() * 1), function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);