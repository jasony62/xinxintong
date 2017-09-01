define(['frame', 'editor'], function(ngApp, editorProxy) {
    'use strict';
    /**
     *
     */
    ngApp.provider.controller('ctrlPage', ['$scope', '$location', 'srvEnrollApp', 'srvEnrollPage', function($scope, $location, srvEnrollApp, srvEnrollPage) {
        $scope.ep = null;
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
        $scope.addPage = function() {
            $('body').click();
            srvEnrollPage.create().then(function(page) {
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

            return srvEnrollPage.update(page, props);
        };
        $scope.cleanPage = function() {
            $('body').click();
            if (window.confirm('确定清除页面【' + $scope.ep.title + '】的所有内容？')) {
                srvEnrollPage.clean($scope.ep).then(function() {
                    editorProxy.getEditor().setContent('');
                });
            }
        };
        //@todo 应该检查页面是否已经被使用
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
                srvEnrollPage.remove($scope.ep).then(function(pages) {
                    $scope.choosePage(pages.length ? pages[0] : null);
                });
            }
        };
        $scope.choosePage = function(page) {
            var pages;
            if (angular.isString(page)) {
                pages = $scope.app.pages;
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
        $scope.$on('xxt.matter.enroll.app.data_schemas.modified', function(event, state) {
            var originator = state.originator,
                modifiedSchema = state.schema;

            $scope.app.pages.forEach(function(page) {
                if (originator === $scope.ep && page !== $scope.ep) {
                    page.updateSchema(modifiedSchema);
                }
            });
        });
        // @todo 提交前如何检查数据的一致性？所有的页面都需要保存吗？
        // 如果页面中有添加记录的操作，活动的限制填写数量应该为0或者大于1
        $scope.save = function() {
            var updatedAppProps = ['data_schemas'],
                bCanAddRecord = false,
                oAppPage;

            for (var i = $scope.app.pages.length - 1; i >= 0; i--) {
                oAppPage = $scope.app.pages[i];
                if (oAppPage.type === 'V') {
                    if (oAppPage.act_schemas && oAppPage.act_schemas.length) {
                        for (var j = oAppPage.act_schemas.length - 1; j >= 0; j--) {
                            if (oAppPage.act_schemas[j].name === 'addRecord') {
                                bCanAddRecord = true;
                                break;
                            }
                        }
                    }
                }
                if (bCanAddRecord) break;
            }
            if (bCanAddRecord) {
                if ($scope.app.count_limit == 1) {
                    $scope.app.count_limit = 0;
                    updatedAppProps.push('count_limit');
                }
            } else {
                if ($scope.app.count_limit != 1) {
                    $scope.app.count_limit = 1;
                    updatedAppProps.push('count_limit');
                }
            }
            srvEnrollApp.update(updatedAppProps).then(function() {
                $scope.app.pages.forEach(function(page) {
                    $scope.updPage(page, ['data_schemas', 'act_schemas', 'html']);
                });
            });
        };
        $scope.gotoCode = function() {
            window.open('/rest/pl/fe/code?site=' + $scope.app.siteid + '&name=' + $scope.ep.code_name, '_self');
        };
        srvEnrollApp.get().then(function(app) {
            var pageName;
            if (pageName = $location.search().page) {
                $scope.choosePage(pageName);
            }
            if (!$scope.ep) $scope.ep = app.pages[0];
        });
    }]);
});