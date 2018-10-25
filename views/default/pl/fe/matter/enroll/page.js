define(['frame', 'editor'], function(ngApp, editorProxy) {
    'use strict';
    /**
     *
     */
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'srvEnrollApp', 'srvEnrollPage', function($scope, $location, srvEnrollApp, srvAppPage) {
        var _oApp;
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
        $scope.addPage = function() {
            $('body').click();
            srvAppPage.create().then(function(page) {
                $scope.choosePage(page);
            });
        };
        $scope.updPage = function(page, props) {
            if (props.indexOf('html') !== -1) {
                /* 如果是当前编辑页面 */
                if (page === $scope.ep) {
                    page.html = editorProxy.getEditor().getContent();
                }
                editorProxy.purifyPage(page, true);
            }

            return srvAppPage.update(page, props);
        };
        $scope.cleanPage = function() {
            if (window.confirm('确定清除页面【' + $scope.ep.title + '】的所有内容？')) {
                srvAppPage.clean($scope.ep).then(function() {
                    editorProxy.getEditor().setContent('');
                });
            }
        };
        //@todo 应该检查页面是否已经被使用
        $scope.delPage = function() {
            var oPage, oActSchema, bUserd;
            $('body').click();
            for (var i = _oApp.pages.length - 1; i >= 0; i--) {
                oPage = _oApp.pages[i];
                for (var j = oPage.actSchemas.length - 1; j >= 0; j--) {
                    oActSchema = oPage.actSchemas[j];
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
                srvAppPage.remove($scope.ep).then(function(pages) {
                    $scope.choosePage(pages.length ? pages[0] : null);
                });
            }
        };
        $scope.choosePage = function(page) {
            var pages;
            if (angular.isString(page)) {
                pages = _oApp.pages;
                for (var i = pages.length - 1; i >= 0; i--) {
                    if (pages[i].name === page) {
                        page = pages[i];
                        break;
                    }
                }
                if (i === -1) return false;
            }
            return $scope.ep = page;
        };
        $scope.$on('xxt.matter.enroll.app.dataSchemas.modified', function(event, state) {
            var originator = state.originator,
                modifiedSchema = state.schema;

            _oApp.pages.forEach(function(page) {
                if (originator === $scope.ep && page !== $scope.ep) {
                    page.updateSchema(modifiedSchema);
                }
            });
        });
        /**
         * 1,检查页面中是否存在错误
         * 2,如果页面中有添加记录的操作，活动的限制填写数量应该为0或者大于1
         */
        $scope.save = function() {
            var pages, oPage, aCheckResult, updatedAppProps, bCanAddRecord;

            pages = _oApp.pages;
            updatedAppProps = ['dataSchemas'];
            bCanAddRecord = false;

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
                if (oPage.type === 'V') {
                    if (oPage.actSchemas && oPage.actSchemas.length) {
                        for (var j = oPage.actSchemas.length - 1; j >= 0; j--) {
                            if (oPage.actSchemas[j].name === 'addRecord') {
                                bCanAddRecord = true;
                                break;
                            }
                        }
                    }
                }
            }
            if (bCanAddRecord) {
                if (_oApp.count_limit == 1) {
                    _oApp.count_limit = 0;
                    updatedAppProps.push('count_limit');
                }
            } else {
                if (_oApp.count_limit != 1) {
                    _oApp.count_limit = 1;
                    updatedAppProps.push('count_limit');
                }
            }
            srvEnrollApp.update(updatedAppProps).then(function() {
                _oApp.pages.forEach(function(page) {
                    $scope.updPage(page, ['dataSchemas', 'actSchemas', 'html']);
                });
            });
        };
        $scope.gotoCode = function() {
            window.open('/rest/pl/fe/code?site=' + _oApp.siteid + '&name=' + $scope.ep.code_name, '_self');
        };
        srvEnrollApp.get().then(function(app) {
            _oApp = app;
            if (_oApp.entryRule && _oApp.entryRule.scope.group === 'Y' && _oApp.entryRule.group && _oApp.entryRule.group.id) {
                $scope.bSupportGroup = true;
            }
            $location.search().page && $scope.choosePage($location.search().page);
            if (!$scope.ep) $scope.ep = app.pages[0];
        });
    }]);
});