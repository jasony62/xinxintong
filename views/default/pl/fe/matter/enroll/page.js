define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'http2', '$uibModal', '$timeout', '$q', function($scope, $location, http2, $uibModal, $timeout, $q) {
        $scope.innerlinkTypes = [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }];
        $scope.onPageChange = function(page) {
            var i, old;
            for (i = $scope.persisted.pages.length - 1; i >= 0; i--) {
                old = $scope.persisted.pages[i];
                if (old.name === page.name)
                    break;
            }
            page.$$modified = page.html !== old.html;
        };
        $scope.updPage = function(page, names) {
            var editor, defer = $q.defer(),
                url, p = {};
            angular.isString(names) && (names = [names]);
            if (names.indexOf('html') !== -1) {
                editor = tinymce.get(page.name);
                if ($(editor.getBody()).find('.active').length) {
                    $(editor.getBody()).find('.active').removeClass('active');
                    $scope.hasActiveWrap = false;
                }
                page.html = $(editor.getBody()).html();
            }
            $scope.$root.progmsg = '正在保存页面...';
            angular.forEach(names, function(name) {
                p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
            });
            url = '/rest/pl/fe/matter/enroll/page/update';
            url += '?site=' + $scope.siteId;
            url += '&app=' + $scope.id;
            url += '&pid=' + page.id;
            url += '&cname=' + page.code_name;
            http2.post(url, p, function(rsp) {
                page.$$modified = false;
                $scope.$root.progmsg = '';
                defer.resolve();
            });
            return defer.promise;
        };
        $scope.delPage = function() {
            if (window.confirm('确定删除？')) {
                var url = '/rest/pl/fe/matter/enroll/page/remove';
                url += '?site=' + $scope.siteId;
                url += '&app=' + $scope.id;
                url += '&pid=' + $scope.ep.id;
                http2.get(url, function(rsp) {
                    $scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
                    history.back();
                });
            }
        };
        window.onbeforeunload = function(e) {
            var i, p, message, modified;
            modified = false;
            for (i in $scope.app.pages) {
                p = $scope.app.pages[i];
                if (p.$$modified) {
                    modified = true;
                    break;
                }
            }
            if (modified) {
                message = '已经修改的页面还没有保存',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.$watch('app.pages', function(pages) {
            var current = $location.search().page,
                dataSchemas, others = [];
            if (!pages || pages.length === 0) return;
            angular.forEach(pages, function(p) {
                if (p.name === current) {
                    $scope.ep = p;
                    if (angular.isString($scope.ep.data_schemas)) {
                        dataSchemas = $scope.ep.data_schemas;
                        $scope.ep.data_schemas = dataSchemas && dataSchemas.length ? JSON.parse(dataSchemas) : [];
                    }
                    if (angular.isString($scope.ep.act_schemas)) {
                        actSchemas = $scope.ep.act_schemas;
                        $scope.ep.act_schemas = actSchemas && actSchemas.length ? JSON.parse(actSchemas) : [];
                    }
                    if (angular.isString($scope.ep.user_schemas)) {
                        userSchemas = $scope.ep.user_schemas;
                        $scope.ep.user_schemas = userSchemas && userSchemas.length ? JSON.parse(userSchemas) : [];
                    }
                } else {
                    p !== $scope.ep && others.push(p);
                }
            });
            $scope.others = others;
        });
    }]);
    ngApp.provider.controller('ctrlPageSchema', ['$scope', '$uibModal', function($scope, $uibModal) {}]);
    ngApp.provider.controller('ctrlInputSchema', ['$scope', '$uibModal', function($scope, $uibModal) {}]);
    ngApp.provider.controller('ctrlViewSchema', ['$scope', '$uibModal', function($scope, $uibModal) {
        $scope.$watch('ep', function(ep) {
            if (!ep) return;
            $scope.dataSchemas = ep.data_schemas;
            $scope.actSchemas = ep.act_schemas;
        });
    }]);
    ngApp.provider.controller('ctrlPageEditor', ['$scope', '$uibModal', '$q', 'mattersgallery', 'mediagallery', function($scope, $uibModal, $q, mattersgallery, mediagallery) {
        $scope.gotoCode = function(codeid) {
            //window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeid, '_self');
        };
        $scope.$on('tinymce.multipleimage.open', function(event, callback) {
            var options = {
                callback: callback,
                multiple: true,
                setshowname: true
            };
            mediagallery.open($scope.siteId, options);
        });
    }]);
});