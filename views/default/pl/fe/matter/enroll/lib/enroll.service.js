angular.module('service.enroll', ['ui.bootstrap', 'ui.xxt']).
provider('srvApp', function() {
    var siteId, appId, app;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', function($q, http2, noticebox) {
        return {
            get: function() {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/matter/enroll/get?site=' + siteId + '&id=' + appId;
                http2.get(url, function(rsp) {
                    app = rsp.data;
                    app.tags = (!app.tags || app.tags.length === 0) ? [] : app.tags.split(',');
                    app.type = 'enroll';
                    app.entry_rule.scope === undefined && (app.entry_rule.scope = 'none');
                    try {
                        app.data_schemas = app.data_schemas && app.data_schemas.length ? JSON.parse(app.data_schemas) : [];
                    } catch (e) {
                        console.log('data invalid', e, app.data_schemas);
                        app.data_schemas = [];
                    }

                    defer.resolve(app);
                });

                return defer.promise;
            },
            update: function(names) {
                var defer = $q.defer(),
                    modifiedData = {},
                    url;

                angular.isString(names) && (names = [names]);
                angular.forEach(names, function(name) {
                    if (['entry_rule'].indexOf(name) !== -1) {
                        modifiedData[name] = encodeURIComponent(app[name]);
                    } else if (name === 'tags') {
                        modifiedData.tags = app.tags.join(',');
                    } else {
                        modifiedData[name] = app[name];
                    }
                });

                url = '/rest/pl/fe/matter/enroll/update?site=' + siteId + '&app=' + appId;
                http2.post(url, modifiedData, function(rsp) {
                    noticebox.success('完成保存');
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
}).provider('srvPage', function() {
    var siteId, appId;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.setAppId = function(id) {
        appId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', function($q, http2, noticebox) {
        return {
            update: function(page, names) {
                var defer = $q.defer(),
                    updated = {},
                    url;

                angular.isString(names) && (names = [names]);
                angular.forEach(names, function(name) {
                    if (name === 'html') {
                        updated.html = encodeURIComponent(page.html);
                    } else {
                        updated[name] = page[name];
                    }
                });
                url = '/rest/pl/fe/matter/enroll/page/update';
                url += '?site=' + siteId;
                url += '&app=' + appId;
                url += '&pid=' + page.id;
                url += '&cname=' + page.code_name;
                http2.post(url, updated, function(rsp) {
                    page.$$modified = false;
                    defer.resolve();
                    noticebox.success('完成保存');
                });

                return defer.promise;
            },
            remove: function(page) {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/matter/enroll/page/remove';

                url += '?site=' + siteId;
                url += '&app=' + appId;
                url += '&pid=' + page.id;
                url += '&cname=' + page.code_name;
                http2.get(url, function(rsp) {
                    defer.resolve();
                    noticebox.success('完成删除');
                });
            }
        };
    }];
});