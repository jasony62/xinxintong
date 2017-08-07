ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter', 'member.xxt', 'channel.fe.pl']);
ngApp.config(['$routeProvider', '$locationProvider', 'srvSiteProvider', function($routeProvider, $locationProvider, srvSiteProvider) {
    $routeProvider.otherwise({
        templateUrl: '/views/default/pl/fe/matter/link/main.html?_=2',
        controller: 'ctrlMain'
    });
    var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
    srvSiteProvider.config(siteId);
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlLink', ['$scope', '$location', 'http2', 'srvSite', function($scope, $location, http2, srvSite) {
    var ls = $location.search();
    $scope.id = ls.id;
    $scope.siteId = ls.site;
    srvSite.get().then(function(oSite) {
        $scope.site = oSite;
    });
    srvSite.tagList().then(function(oTag) {
        $scope.oTag = oTag;
    });
    http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
        $scope.editing = rsp.data;
        $scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter/link?site=' + $scope.siteId + '&id=' + $scope.id;
    });
}]);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'mediagallery', '$uibModal', function($scope, http2, mediagallery, $uibModal) {
    var modifiedData = {};
    $scope.modified = false;
    $scope.urlsrcs = {
        '0': '外部链接',
        '1': '多图文',
        '2': '频道',
        '3': '内置回复',
    };
    $scope.linkparams = {
        '{{openid}}': '用户标识(openid)',
        '{{site}}': '公众号标识',
    };
    var getInitData = function() {
        http2.get('/rest/pl/fe/matter/link/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
            editLink(rsp.data);
        });
    };
    var editLink = function(link) {
        if (link.params) {
            var p;
            for (var i in link.params) {
                p = link.params[i];
                p.customValue = $scope.linkparams[p.pvalue] ? false : true;
            }
        }
        if(link.matter_mg_tag !== ''){
            link.matter_mg_tag.forEach(function(cTag,index){
                $scope.oTag.forEach(function(oTag){
                    if(oTag.id === cTag){
                        link.matter_mg_tag[index] = oTag;
                    }
                });
            });
        }
        $scope.editing = link;
        $scope.persisted = angular.copy(link);
        $('[ng-model="editing.title"]').focus();
    };
    window.onbeforeunload = function(e) {
        var message;
        if ($scope.modified) {
            message = '修改还没有保存，是否要离开当前页面？',
                e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.remove = function() {
        http2.get('/rest/pl/fe/matter/link/remove?site=' + $scope.siteId + '&id=' + $scope.id, function() {
            location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;
        });
    };
    $scope.submit = function() {
        http2.post('/rest/pl/fe/matter/link/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
            modifiedData = {};
            $scope.modified = false;
        });
    };
    $scope.update = function(n) {
        modifiedData[n] = $scope.editing[n];
        if (n === 'urlsrc' && $scope.editing.urlsrc != 0) {
            $scope.editing.open_directly = 'N';
            modifiedData.open_directly = 'N';
        } else if (n === 'method' && $scope.editing.method === 'POST') {
            $scope.editing.open_directly = 'N';
            modifiedData.open_directly = 'N';
        } else if (n === 'open_directly' && $scope.editing.open_directly == 'Y') {
            $scope.editing.access_control = 'N';
            modifiedData.access_control = 'N';
            modifiedData.authapis = '';
        } else if (n === 'access_control' && $scope.editing.access_control == 'N') {
            var p;
            for (var i in $scope.editing.params) {
                p = $scope.editing.params[i];
                if (p.pvalue == '{{authed_identity}}') {
                    window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
                    $scope.editing.access_control = 'Y';
                    modifiedData.access_control = 'Y';
                    return false;
                }
            }
            modifiedData.authapis = '';
        }
        $scope.modified = true;
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date() * 1);
                $scope.update('pic');
            }
        };
        mediagallery.open($scope.siteId, options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.addParam = function() {
        http2.get('/rest/pl/fe/matter/link/paramAdd?site=' + $scope.siteId + '&linkid=' + $scope.editing.id, function(rsp) {
            var oNewParam = {
                id: rsp.data,
                pname: 'newparam',
                pvalue: ''
            };
            if ($scope.editing.urlsrc === '3' && $scope.editing.url === '9') oNewParam.pname = 'channelid';
            $scope.editing.params.push(oNewParam);
        });
    };
    $scope.updateParam = function(updated, name) {
        if (updated.pvalue === '{{authed_identity}}' && $scope.editing.access_control === 'N') {
            window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
            updated.pvalue = '';
        }
        if (updated.pvalue !== '{{authed_identity}}')
            updated.authapi_id = 0;
        // 参数中有额外定义，需清除
        var p = {
            pname: updated.pname,
            pvalue: encodeURIComponent(updated.pvalue),
            authapi_id: updated.authapi_id
        };
        http2.post('/rest/pl/fe/matter/link/paramUpd?site=' + $scope.siteId + '&id=' + updated.id, p);
    };
    $scope.removeParam = function(removed) {
        http2.get('/rest/mp/matter/link/removeParam?id=' + removed.id, function(rsp) {
            var i = $scope.editing.params.indexOf(removed);
            $scope.editing.params.splice(i, 1);
        });
    };
    $scope.changePValueMode = function(p) {
        p.pvalue = '';
    };
    $scope.tagMatterLink = function(subType) {
        var oApp, oTags, tagsOfData;
        oApp = $scope.editing;
        oTags = $scope.oTag;
        $uibModal.open({
            templateUrl: 'tagMatterData.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var model;
                $scope2.apptags = oTags;

                if(subType === 'C'){
                    tagsOfData = oApp.matter_cont_tag;
                    $scope2.tagTitle = '内容标签';
                }else{
                    tagsOfData = oApp.matter_mg_tag;
                    $scope2.tagTitle = '管理标签';
                }
                $scope2.model = model = {
                    selected: []
                };
                if (tagsOfData) {
                    tagsOfData.forEach(function(oTag) {
                        var index;
                        if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                            model.selected[$scope2.apptags.indexOf(oTag)] = true;
                        }
                    });
                }
                $scope2.createTag = function() {
                    var newTags;
                    if ($scope2.model.newtag) {
                        newTags = $scope2.model.newtag.replace(/\s/, ',');
                        newTags = newTags.split(',');
                        http2.post('/rest/pl/fe/matter/tag/create?site=' + oApp.siteid, newTags, function(rsp) {
                            rsp.data.forEach(function(oNewTag) {
                                $scope2.apptags.push(oNewTag);
                            });
                        });
                        $scope2.model.newtag = '';
                    }
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var addMatterTag = [];
                    model.selected.forEach(function(selected, index) {
                        if (selected) {
                            addMatterTag.push($scope2.apptags[index]);
                        }
                    });
                    var url = '/rest/pl/fe/matter/tag/add?site=' + oApp.siteid + '&resId=' + oApp.id + '&resType=' + oApp.type + '&subType=' + subType;
                    http2.post(url, addMatterTag, function(rsp) {
                        if(subType === 'C'){
                            $scope.editing.matter_cont_tag = addMatterTag;
                        }else{
                            $scope.editing.matter_mg_tag = addMatterTag;
                        }
                    });
                    $mi.close();
                };
            }],
            backdrop: 'static',
        });
    };
    $scope.$watch('editing.urlsrc', function(nv) {
        switch (nv) {
            case '1':
                if ($scope.news === undefined) {
                    http2.get('/rest/pl/fe/matter/news/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
                        $scope.news = rsp.data;
                    });
                }
                break;
            case '2':
                if ($scope.channels === undefined) {
                    http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&cascade=N', function(rsp) {
                        $scope.channels = rsp.data;
                    });
                }
                break;
            case '3':
                if ($scope.inners === undefined) {
                    http2.get('/rest/pl/fe/matter/inner/list?site=' + $scope.siteId, function(rsp) {
                        $scope.inners = rsp.data;
                    });
                }
                break;
        }
    });
    getInitData();
    (function() {
        new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
    })();
}]);
