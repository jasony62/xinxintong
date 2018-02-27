angular.module('service.article', ['ui.bootstrap', 'ui.xxt']).provider('srvLog', function() {
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            list: function(article, page, type) {
                var defer = $q.defer(),
                    url;
                if (!page || !page._j) {
                    angular.extend(page, {
                        at: 1,
                        size: 30,
                        orderBy: 'time',
                        _j: function() {
                            var p;
                            p = '&page=' + this.at + '&size=' + this.size;
                            p += '&orderby=' + this.orderBy;
                            return p;
                        }
                    });
                }
                //收藏接口
                url = type === 'log' ? '/rest/pl/fe/matter/article/log/list?id=' + article.id : '/rest/pl/fe/matter/article/favor/list?site=' + article.siteid + '&id=' + article.id;
                url += page._j();
                http2.get(url, function(rsp) {
                    rsp.data.total && (page.total = rsp.data.total);
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            }
        };
    }];
}).provider('srvApp', function() {
    var siteId, articleId, edit;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        articleId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', 'srvSite', function($q, http2, noticebox, srvSite) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;
                url = '/rest/pl/fe/matter/article/get?id=' + articleId;
                http2.get(url, function(rsp) {
                    edit = rsp.data;
                    defer.resolve(edit);
                });
                return defer.promise;
            },
            update: function(names) {
                var defer = $q.defer(),
                    modifiedData = {},
                    url;

                angular.isString(names) && (names = [names]);
                names.forEach(function(name) {
                    if (name === 'tags') {
                        modifiedData.tags = edit.tags.join(',');
                    } else {
                        modifiedData[name] = edit[name];
                    }
                });
                url = '/rest/pl/fe/matter/article/update?site=' + siteId + '&id=' + articleId;
                http2.post(url, modifiedData, function(rsp) {
                    noticebox.success('完成保存');
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            assignMission: function() {
                var _this = this,
                    defer = $q.defer();
                srvSite.openGallery({
                    matterTypes: [{
                        value: 'mission',
                        title: '项目',
                        url: '/rest/pl/fe/matter'
                    }],
                    singleMatter: true
                }).then(function(missions) {
                    var matter;
                    if (missions.matters.length === 1) {
                        matter = {
                            id: articleId,
                            type: 'article'
                        };
                        http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + siteId + '&id=' + missions.matters[0].id, matter, function(rsp) {
                            var mission = rsp.data,
                                updatedFields = ['mission_id'];

                            edit.mission = mission;
                            edit.mission_id = mission.id;
                            if (!edit.pic || edit.pic.length === 0) {
                                edit.pic = mission.pic;
                                updatedFields.push('pic');
                            }
                            if (!edit.summary || edit.summary.length === 0) {
                                edit.summary = mission.summary;
                                updatedFields.push('summary');
                            }
                            _this.update(updatedFields).then(function() {
                                defer.resolve(mission);
                            });
                        });
                    }
                });
                return defer.promise;
            },
            quitMission: function() {
                var _this = this,
                    matter = {
                        id: edit.id,
                        type: 'article',
                        title: edit.title
                    },
                    defer = $q.defer();
                http2.post('/rest/pl/fe/matter/mission/matter/remove?site=' + siteId + '&id=' + edit.mission_id, matter, function(rsp) {
                    delete edit.mission;
                    edit.mission_id = null;
                    _this.update(['mission_id']).then(function() {
                        defer.resolve();
                    });
                });
                return defer.promise;
            },
        };
    }];
}).provider('srvCoin', function() {
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            list: function(articleSiteId, articleId, page) {
                var defer = $q.defer(),
                    url;
                if (!page || !page._j) {
                    angular.extend(page, {
                        at: 1,
                        size: 30,
                        orderBy: 'time',
                        _j: function() {
                            var p;
                            p = '&page=' + this.at + '&size=' + this.size;
                            p += '&orderby=' + this.orderBy;
                            return p;
                        }
                    });
                }
                url = '/rest/pl/fe/matter/article/coin/logs?site=' + articleSiteId + '&id=' + articleId + page._j();
                http2.get(url, function(rsp) {
                    rsp.data.total && (page.total = rsp.data.total);
                    defer.resolve(rsp.data.logs);
                });

                return defer.promise;
            }
        };
    }];
});