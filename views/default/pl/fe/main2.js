angular.module('app', ['ui.tms', 'ui.bootstrap', 'tmplshop.ui.xxt']).
controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
    var url = '/rest/pl/fe/user/get?_=' + (new Date() * 1);
    http2.get(url, function(rsp) {
        $scope.loginUser = rsp.data;
    });
}]).controller('ctrlRecent', ['$scope', '$uibModal', 'http2', 'templateShop', function($scope, $uibModal, http2, templateShop) {
    var _fns = {
        createSite: function() {
            var defer = $q.defer(),
                url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);

            http2.get(url, function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        },
        addLink: function(site) {
            http2.get('/rest/pl/fe/matter/link/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/link?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        //研究项目-单图文
        addArticle: function(site) {
            http2.get('/rest/pl/fe/matter/article/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/article?site=' + site.id + '&id=' + rsp.data;
            });
        },
        addNews: function(site) {
            http2.get('/rest/pl/fe/matter/news/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/news?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        addChannel: function(site) {
            http2.get('/rest/pl/fe/matter/channel/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/channel?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        //研究项目-登记活动
        addEnroll: function(site) {
            $('body').trigger('click');
            templateShop.choose(site.id, 'enroll').then(function(choice) {
                if (choice) {
                    if (choice.source === 'share') {
                        var url, data = choice.data;
                        url = '/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + data.id;
                        http2.get(url, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                        });
                    } else if (choice.source === 'platform') {
                        var url, config, data = choice.data;
                        url = '/rest/pl/fe/matter/enroll/create?site=' + site.id;
                        config = {};
                        if (data) {
                            url += '&scenario=' + data.scenario.name;
                            url += '&template=' + data.template.name;
                            if (data.simpleSchema && data.simpleSchema.length) {
                                config.simpleSchema = data.simpleSchema;
                            }
                        }
                        http2.post(url, config, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                        });
                    } else if (choice.source === 'file') {
                        var url, data = choice.data;
                        url = '/rest/pl/fe/matter/enroll/createByFile?site=' + site.id;
                        http2.post(url, data, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                        });
                    }
                } else {
                    var url;
                    url = '/rest/pl/fe/matter/enroll/create?site=' + site.id;
                    http2.post(url, {}, function(rsp) {
                        location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                    });
                }
            });
        },
        addSignin: function(site) {
            http2.get('/rest/pl/fe/matter/signin/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/signin?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        addGroup: function(site) {
            http2.get('/rest/pl/fe/matter/group/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/group?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        addLottery: function(site) {
            http2.get('/rest/pl/fe/matter/lottery/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/lottery?site=' + site.id + '&id=' + rsp.data;
            });
        },
        addContribute: function(site) {
            http2.get('/rest/pl/fe/matter/contribute/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/contribute?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        addMission: function(site) {
            http2.get('/rest/pl/fe/matter/mission/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/mission?site=' + site.id + '&id=' + rsp.data.id;
            });
        },
        addCustom: function(site) {
            http2.get('/rest/pl/fe/matter/custom/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/custom?site=' + site.id + '&id=' + rsp.data;
            });
        },
        addMerchant: function(site) {
            http2.get('/rest/pl/fe/matter/merchant/shop/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/merchant/shop?site=' + site.id + '&id=' + rsp.data;
            });
        },
        //信息墙
        addWall: function(site) {
            http2.get('/rest/pl/fe/matter/wall/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/wall?site=' + site.id + '&id=' + rsp.data;
            });
        },
        addAddressbook: function(site) {
            http2.get('/rest/pl/fe/matter/addressbook/create?site=' + site.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/addressbook?site=' + site.id + '&id=' + rsp.data;

            });
        }
    };

    function addMatter(site, matterType) {
        var fnName = 'add' + matterType[0].toUpperCase() + matterType.substr(1);
        _fns[fnName].call(_fns, site);
    }

    var url, page;
    $scope.page = page = {
        at: 1,
        size: 12,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.matterType = '';
    $scope.list = function(pageAt) {
        var url = '/rest/pl/fe/recent?' + page.j();
        if (pageAt) {
            page.at = pageAt;
        }
        if ($scope.matterType) {
            url += '&matterType=' + $scope.matterType;
        }
        http2.get(url, function(rsp) {
            $scope.matters = rsp.data.matters;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.open = function(matter) {
        location.href = location.href = '/rest/pl/fe/matter/' + matter.matter_type + '?id=' + matter.matter_id + '&site=' + matter.siteid;
    };
    $scope.addMatter = function(matterType) {
        var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
        http2.get(url, function(rsp) {
            var sites = rsp.data;
            if (sites.length === 1) {
                addMatter(sites[0], matterType);
            } else if (sites.length === 0) {
                createSite().then(function(site) {
                    addMatter(site, matterType);
                });
            } else {
                $uibModal.open({
                    templateUrl: 'addMatterSite.html',
                    dropback: 'static',
                    controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                        var data;
                        $scope2.mySites = sites;
                        $scope2.data = data = {};
                        $scope2.ok = function() {
                            if (data.index !== undefined) {
                                $mi.close(sites[data.index]);
                            } else {
                                $mi.dismiss();
                            }
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        };
                    }]
                }).result.then(function(site) {
                    addMatter(site, matterType);
                });
            }
        });
    };
    $scope.list(1);
}]).controller('ctrlSite', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.create = function() {
        var url = '/rest/pl/fe/site/create?_=' + t;;
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/site/setting?site=' + rsp.data.id;
        });
    };
    $scope.list = function() {
        var url = '/rest/pl/fe/site/list?_=' + t;
        http2.get(url, function(rsp) {
            $scope.sites = rsp.data;
        });
    };
    $scope.openHome = function(site) {
        location.href = '/rest/site/home?site=' + site.id;
    };
    $scope.openConsole = function(site) {
        location.href = '/rest/pl/fe/site?site=' + site.id;
    };
    $scope.list();
}]).controller('ctrlMission', ['$scope', 'http2', function($scope, http2) {
    var page, t = (new Date() * 1);
    $scope.page = page = {
        at: 1,
        size: 12,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.open = function(mission) {
        location.href = '/rest/pl/fe/matter/mission?id=' + mission.mission_id;
    };
    $scope.list = function() {
        var url = '/rest/pl/fe/matter/mission/list?_=' + t + '&' + page.j();
        http2.get(url, function(rsp) {
            $scope.missions = rsp.data.missions;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.list();
}]).controller('ctrlTrend', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date() * 1);
    $scope.list = function() {
        var url = '/rest/pl/fe/trends?_=' + t;
        http2.get(url, function(rsp) {
            $scope.trends = rsp.data.trends;
        });
    };
    $scope.list();
}]);