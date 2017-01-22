angular.module('service.group', ['ui.bootstrap', 'ui.xxt']).
provider('srvApp', function() {
    var siteId, appId, oApp;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', '$uibModal', 'http2', 'noticebox', 'mattersgallery', function($q, $uibModal, http2, noticebox, mattersgallery) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/get?site=' + siteId + '&app=' + appId;
                http2.get(url, function(rsp) {
                    var app, url;
                    oApp = rsp.data;
                    oApp.tags = (!oApp.tags || oApp.tags.length === 0) ? [] : oApp.tags.split(',');
                    try {
                        oApp.group_rule = oApp.group_rule && oApp.group_rule.length ? JSON.parse(oApp.group_rule) : {};
                        oApp.data_schemas = oApp.data_schemas && oApp.data_schemas.length ? JSON.parse(oApp.data_schemas) : [];
                    } catch (e) {
                        console.error('error', e);
                    }
                    oApp.opUrl = 'http://' + location.host + '/rest/site/op/matter/group?site=' + siteId + '&app=' + appId;
                    if (oApp.page_code_id == 0 && oApp.scenario.length) {
                        url = '/rest/pl/fe/matter/group/page/create?site=' + siteId + '&app=' + appId + '&scenario=' + oApp.scenario;
                        http2.get(url, function(rsp) {
                            oApp.page_code_id = rsp.data;
                            defer.resolve(oApp);
                        });
                    } else {
                        defer.resolve(oApp);
                    }
                });

                return defer.promise;
            },
            roundList: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/group/round/list?site=' + siteId + '&app=' + appId;
                http2.get(url, function(rsp) {
                    var rounds = rsp.data;
                    angular.forEach(rounds, function(round) {
                        round.extattrs = (round.extattrs && round.extattrs.length) ? JSON.parse(round.extattrs) : {};
                    });
                    defer.resolve(rounds);
                });

                return defer.promise;
            },
            update: function(names) {
                var defer = $q.defer(),
                    modifiedData = {};

                angular.isString(names) && (names = [names]);
                names.forEach(function(name) {
                    if (name === 'tags') {
                        modifiedData.tags = oApp.tags.join(',');
                    } else {
                        modifiedData[name] = oApp[name];
                    }
                });

                http2.post('/rest/pl/fe/matter/group/update?site=' + siteId + '&app=' + appId, modifiedData, function(rsp) {
                    modifiedData = {};
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            remove: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/enroll/remove?site=' + siteId + '&app=' + appId;
                http2.get(url, function(rsp) {
                    defer.resolve();
                });

                return defer.promise;
            }
        };
    }];
});
