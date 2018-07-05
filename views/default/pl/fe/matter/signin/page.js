define(['frame', 'schema', 'page', 'editor'], function(ngApp, schemaLib, pageLib, editorProxy) {
    'use strict';
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', '$q', '$uibModal', 'http2', 'srvSigninApp', 'srvEnrollPage', function($scope, $location, $q, $uibModal, http2, srvSigninApp, srvAppPage) {
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.ep && $scope.ep.$$modified) {
                message = '已经修改的页面还没有保存，确定离开？';
                e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.ep = null;
        $scope.createPage = function() {
            var deferred = $q.defer();
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/createPage.html?_=3',
                backdrop: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    $scope.options = {};
                    $scope.ok = function() {
                        $mi.close($scope.options);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                }],
            }).result.then(function(options) {
                http2.post('/rest/pl/fe/matter/signin/page/add?site=' + $scope.app.siteid + '&app=' + $scope.app.id, options, function(rsp) {
                    var page = rsp.data;
                    pageLib.enhance(page);
                    page._arrange($scope.mapOfAppSchemas);
                    $scope.app.pages.push(page);
                    deferred.resolve(page);
                });
            });

            return deferred.promise;
        };
        $scope.addPage = function() {
            $('body').click();
            $scope.createPage().then(function(page) {
                $scope.choosePage(page);
            });
        };
        $scope.updPage = function(page, names) {
            if (names.indexOf('html') !== -1) {
                if (page === $scope.ep) {
                    page.html = editorProxy.getEditor().getContent();
                }
                editorProxy.purifyPage(page, true);
            }

            return srvAppPage.update(page, names);
        };
        $scope.delPage = function() {
            var oPage, oActSchema, bUserd;
            $('body').click();
            for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                oPage = $scope.app.pages[i];
                for (var j = oPage.act_schemas.length - 1; j >= 0; j--) {
                    oActSchema = oPage.act_schemas[j];
                    if (oActSchema.next === $scope.ep.name) {
                        bUserd = true;
                        break;
                    }
                }
                if (bUserd) break;
            }
            if (bUserd) {
                alert('页面已经被【' + oPage.title + '/' + oActSchema.label + '】使用，不能删除');
            } else if (window.confirm('确定删除页面【' + $scope.ep.title + '】？')) {
                srvAppPage.remove($scope.ep).then(function() {
                    $scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
                    if ($scope.app.pages.length) {
                        $scope.choosePage($scope.app.pages[0]);
                    } else {
                        $scope.ep = null;
                    }
                });
            }
        };
        $scope.choosePage = function(page) {
            if (angular.isString(page)) {
                for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                    if ($scope.app.pages[i].name === page) {
                        page = $scope.app.pages[i];
                        break;
                    }
                }
                if (i === -1) return;
            }
            return $scope.ep = page;
        };
        $scope.cleanPage = function() {
            if (window.confirm('确定清除页面【' + $scope.ep.title + '】的所有内容？')) {
                srvAppPage.clean($scope.ep).then(function() {
                    editorProxy.getEditor().setContent('');
                });
            }
        };
        $scope.gotoCode = function() {
            window.open('/rest/pl/fe/code?site=' + $scope.app.siteid + '&name=' + $scope.ep.code_name, '_self');
        };
        $scope.$on('xxt.matter.enroll.app.data_schemas.modified', function(event, state) {
            var originator = state.originator,
                modifiedSchema = state.schema;

            $scope.app.pages.forEach(function(page) {
                if (originator === $scope.ep && page !== $scope.ep) {
                    page.updateSchema(modifiedSchema);
                }
            });
        });
        $scope.save = function() {
            var pages, oPage, aCheckResult;

            pages = $scope.app.pages;
            /* 更新当前编辑页 */
            $scope.ep.html = editorProxy.getEditor().getContent();
            editorProxy.purifyPage($scope.ep, true);

            for (var i = pages.length - 1; i >= 0; i--) {
                oPage = pages[i];
                aCheckResult = oPage.check();
                if (aCheckResult[0] !== true) {
                    srvAppPage.repair(aCheckResult, oPage);
                    return false;
                }
            }
            // 更新应用
            srvSigninApp.update('dataSchemas').then(function() {
                // 更新页面
                pages.forEach(function(oPage) {
                    $scope.updPage(oPage, ['dataSchemas', 'actSchemas', 'html']);
                });
            });
        };
        srvSigninApp.get().then(function(app) {
            var pageName;
            if (pageName = $location.search().page) {
                $scope.choosePage(pageName);
            }
            if (!$scope.ep) $scope.ep = app.pages[0];
        });
    }]);
});