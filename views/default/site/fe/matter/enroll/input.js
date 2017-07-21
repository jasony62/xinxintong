'use strict';
require('./input.css');

require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.oUtilSubmit = require('../_module/submit.util.js');
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.controller('rest_time',['$scope','$timeout','ls','$http',function($scope,$timeout,LS,$http){
    var rest_time=document.querySelector("#rest_time");
    var SysSecond ; 
    
    function getQueryString(name) { 
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
        var r = window.location.search.substr(1).match(reg); 
        if (r != null) return unescape(r[2]); return null; 
    } 
    var url = LS.j('get', 'site', 'app');
    url += '&ek=' + getQueryString('ek');
    url += '&page=enroll';
    //console.log(url);
    $http.get(url).then(function (response) {
        var app=response.data.data.app;
        //判断是否启用轮次
        if(app.multi_rounds=='Y'){
            var activeRound=response.data.data.activeRound;
            var end_at=activeRound.end_at;
            var now=Math.floor(Date.parse(new Date())/1000);
            SysSecond=end_at-now;
            if(SysSecond<0){
                SysSecond=0;
            }
            var InterValObj = window.setInterval(SetRemainTime, 1000); //间隔函数，1秒执行
            //将时间减去1秒，计算天、时、分、秒 
            function SetRemainTime() { 
                if (SysSecond > 0) { 
                    SysSecond = SysSecond - 1; 
                    var second = Math.floor(SysSecond % 60);             // 计算秒     
                    var minite = Math.floor((SysSecond / 60) % 60);      //计算分 
                    var hour = Math.floor((SysSecond / 3600) % 24);      //计算小时 
                    var day = Math.floor((SysSecond / 3600) / 24);        //计算天 
                    rest_time.innerHTML="距离本轮次【"+app.title+"】结束还有：<b style=color:red>"+day + "</b>天<b style=color:red>" + hour + "</b>小时<b style=color:red>" + minite + "</b>分<b style=color:red>" + second + "</b>秒"; 
                } else {//剩余时间小于或等于0的时候，就停止间隔函数 
                    window.clearInterval(InterValObj); 
                    rest_time.innerHTML="<p>本轮次【"+app.title+"】已结束。</p>";
                } 
            }
            SetRemainTime(); 
        }
    });
}]);
ngApp.factory('Input', ['$q', '$timeout', 'ls', 'http2', function($q, $timeout, LS, http2) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, item, schema, value, sCheckResult;
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = dataSchemas.length - 1; i >= 0; i--) {
                item = dataSchemas[i];
                schema = item.schema;
                if (schema.id.indexOf('member.') === 0) {
                    var memberSchema = schema.id.substr(7);
                    if (memberSchema.indexOf('.') === -1) {
                        value = data.member[memberSchema];
                    } else {
                        memberSchema = memberSchema.split('.');
                        value = data.member.extattr[memberSchema[1]];
                    }
                } else {
                    value = data[schema.id];
                }
                if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(schema, value))) {
                    return sCheckResult;
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(ek, data, tags, oSupplement) {
        var defer, url, d, d2, posted, tagsByScchema;
        defer = $q.defer();
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = LS.j('record/submit', 'site', 'app');
        ek && ek.length && (url += '&ek=' + ek);
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                for (var j in d) {
                    d2 = d[j];
                    delete d2.imgSrc;
                }
            }
        }
        tagsByScchema = {};
        if (Object.keys && Object.keys(tags).length > 0) {
            for (var schemaId in tags) {
                tagsByScchema[schemaId] = [];
                tags[schemaId].forEach(function(oTag) {
                    tagsByScchema[schemaId].push(oTag.id);
                });
            }
        }
        http2.post(url, { data: posted, tag: tags, supplement: oSupplement }, { autoBreak: false }).then(function(rsp) {
            if (rsp.err_code == 0) {
                defer.resolve(rsp);
            } else {
                defer.reject(rsp);
            }
        });
        return defer.promise;
    };
    return {
        ins: function() {
            if (!_ins) {
                _ins = new Input();
            }
            return _ins;
        }
    }
}]);
ngApp.directive('tmsImageInput', ['$compile', '$q', function($compile, $q) {
    var modifiedImgFields, openPickFrom, onSubmit;
    modifiedImgFields = [];
    openPickFrom = function(scope) {
        var html;
        html = "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'camera')\">拍照</button></div>";
        html += "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'album')\">相册</button></div>";
        html = __util.makeDialog('pickImageFrom', {
            body: html
        });
        $compile(html)(scope);
    };
    onSubmit = function(data) {
        var defer = $q.defer(),
            i = 0,
            j = 0,
            nextWxImage;
        // if (window.wx !== undefined && modifiedImgFields.length) {
        //     nextWxImage = function() {
        //         var imgField, img;
        //         imgField = data[modifiedImgFields[i]];
        //         img = imgField[j];
        //         window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
        //             if (j < imgField.length - 1) {
        //                 /* next img*/
        //                 j++;
        //                 nextWxImage();
        //             } else if (i < modifiedImgFields.length - 1) {
        //                 /* next field*/
        //                 j = 0;
        //                 i++;
        //                 nextWxImage();
        //             } else {
        //                 defer.resolve('ok');
        //             }
        //         });
        //     };
        //     nextWxImage();
        // } else {
        defer.resolve('ok');
        //}
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            // $scope.beforeSubmit(function() {
            //     return onSubmit($scope.data);
            // });
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count && count != 0) {
                        $scope.$parent.notice.set('最多允许上传' + count + '张图片');
                        return;
                    }
                }
                if (window.YixinJSBridge) {
                    if (from === undefined) {
                        $scope.cachedImgFieldName = imgFieldName;
                        openPickFrom($scope);
                        return;
                    }
                    imgFieldName = $scope.cachedImgFieldName;
                    $scope.cachedImgFieldName = null;
                    angular.element('#pickImageFrom').remove();
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase, i, j, img;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                        });
                    }
                    $timeout(function() {
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').setAttribute('src', img.imgSrc);
                        }
                        $scope.$broadcast('xxt.enroll.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(imgField, index) {
                imgField.splice(index, 1);
            };
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', 'ls', 'tmsDynaPage', function($q, LS, tmsDynaPage) {
    var r, onSubmit;
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function() {
        r = new Resumable({
            target: LS.j('record/uploadFile', 'site', 'app'),
            testChunks: false,
            chunkSize: 512 * 1024
        });
    });
    onSubmit = function($scope) {
        var defer;
        defer = $q.defer();
        if (!r.files || r.files.length === 0)
            defer.resolve('empty');
        r.on('progress', function() {
            var phase, p;
            p = r.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        r.on('complete', function() {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = '完成';
                });
            }
            r.cancel();
            defer.resolve('ok');
        });
        r.upload();
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', function($scope) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            $scope.chooseFile = function(fileFieldName, count, accept) {
                var ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f;
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        r.addFile(f);
                        $scope.$apply(function() {
                            $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                            $scope.data[fileFieldName].push({
                                uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                url: ''
                            });
                            $scope.$broadcast('xxt.enroll.file.choose.done', fileFieldName);
                        });
                    }
                    ele = null;
                }, true);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlInput', ['$scope', '$q', '$uibModal', '$timeout', 'Input', 'ls', 'http2', function($scope, $q, $uibModal, $timeout, Input, LS, http2) {
    function doTask(seq, nextAction) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
        });
    }

    function doSubmit(nextAction) {
        var ek, submitData;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit(ek, $scope.data, $scope.tag, $scope.supplement).then(function(rsp) {
            var url;
            submitState.finish();
            if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data;
                location.replace(url);
            } else {
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data
                    }
                }
                $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data);
            }
        }, function(rsp) {
            // reject
            submitState.finish();
        });
    }

    function _localSave() {
        submitState.start(null, StateCacheKey);
        submitState.cache($scope.data);
        submitState.finish(true);
        $scope.$parent.notice.set('保存成功，关闭页面后，再次打开时自动恢复当前数据', 'success');
    }

    window.onbeforeunload = function(e) {
        var message;
        if (submitState.modified) {
            message = '已经修改的内容还没有保存，确定离开？';
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };

    var facInput, tasksOfBeforeSubmit, submitState, StateCacheKey;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {},
    };
    $scope.tag = {};
    $scope.supplement = {};
    $scope.submitState = submitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.$on('xxt.app.enroll.save', function() {
        _localSave();
    });
    $scope.save = function(event, nextAction) {
        _localSave();
        $scope.gotoPage(event, nextAction);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var schemasById,
            dataOfRecord, p, value;

        StateCacheKey = 'xxt.app.enroll:' + params.app.id + '.user:' + params.user.uid + '.cacheKey';
        $scope.schemasById = schemasById = params.app._schemasById;
        /* 用户已经登记过，恢复之前的数据 */
        if (params.record) {
            ngApp.oUtilSchema.loadRecord(params.app._schemasById, $scope.data, params.record.data);
            $scope.record = params.record;
            $scope.tag = params.record.data_tag;
        }
        /* 恢复用户未提交的数据 */
        if (window.localStorage) {
            submitState._cacheKey = StateCacheKey;
            var cached = submitState.fromCache(StateCacheKey);
            if (cached) {
                if (cached.member) {
                    delete cached.member;
                }
                angular.extend($scope.data, cached);
                submitState.modified = true;
            }
        }
        // 跟踪数据变化
        $scope.$watch('data', function(nv, ov) {
            if (nv !== ov) {
                submitState.modified = true;
            }
        }, true);
        // 如果页面上有保存按钮，隐藏内置的保存按钮
        if (params.page && params.page.act_schemas) {
            var actSchemas = JSON.parse(params.page.act_schemas);
            for (var i = actSchemas.length - 1; i >= 0; i--) {
                if (actSchemas[i].name === 'save') {
                    var domSave = document.querySelector('.tms-switch-save');
                    if (domSave) {
                        domSave.style.display = 'none';
                    }
                    break;
                }
            }
        }
        // 登录提示
        if (!params.user.unionid) {
            //var domTip = document.querySelector('#appLoginTip');
            //var evt = document.createEvent("HTMLEvents");
            //evt.initEvent("show", false, false);
            //domTip.dispatchEvent(evt);
        }
    });
    var hasAutoFillMember = false;
    $scope.$watch('data.member.schema_id', function(schemaId) {
        if (false === hasAutoFillMember && schemaId && $scope.user) {
            ngApp.oUtilSchema.autoFillMember($scope.user, $scope.data.member);
            hasAutoFillMember = true;
        }
    });
    $scope.submit = function(event, nextAction) {
        var checkResult;
        if (!submitState.isRunning()) {
            submitState.start(event, StateCacheKey);
            if (true === (checkResult = facInput.check($scope.data, $scope.app, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                submitState.finish();
                $scope.$parent.notice.set(checkResult);
            }
        }
    };
    $scope.tagRecordData = function(schemaId) {
        var oApp, oSchema, tagsOfData;
        oApp = $scope.app;
        oSchema = oApp._schemasById[schemaId];
        if (oSchema) {
            tagsOfData = $scope.tag[schemaId];
            $uibModal.open({
                templateUrl: 'tagRecordData.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var model;
                    $scope2.schema = oSchema;
                    $scope2.apptags = oApp.dataTags;
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
                            http2.post('/rest/site/fe/matter/enroll/tag/create?site=' + $scope.app.siteid + '&app=' + $scope.app.id, newTags).then(function(rsp) {
                                rsp.data.forEach(function(oNewTag) {
                                    $scope2.apptags.push(oNewTag);
                                });
                            });
                            $scope2.model.newtag = '';
                        }
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var tags = [];
                        model.selected.forEach(function(selected, index) {
                            if (selected) {
                                tags.push($scope2.apptags[index]);
                            }
                        });
                        $mi.close(tags);
                    };
                }],
                backdrop: 'static',
            }).result.then(function(tags) {
                $scope.tag[schemaId] = tags;
            });
        }
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress(http2, $q.defer(), LS.p.site).then(function(data) {
            $scope.data[prop] = data.address;
        });
    };
    $scope.dataBySchema = function(schemaId) {
        var app = $scope.app;
        $uibModal.open({
            templateUrl: 'dataBySchema.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close($scope2.data); };
                http2.get('/rest/site/fe/matter/enroll/repos/dataBySchema?site=' + app.siteid + '&app=' + app.id + '&schema=' + schemaId).then(function(result) {
                    $scope2.records = result.data.records;
                });
            }],
            backdrop: 'static',
        }).result.then(function(result) {
            $scope.data[schemaId] = result.selected.value;
        });
    };
    $scope.score = function(schemaId, opIndex, number) {
        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            $scope.data[schemaId] = {};
            schema.ops.forEach(function(op) {
                $scope.data[schema.id][op.v] = 0;
            });
        }

        $scope.data[schemaId][op.v] = number;
    };
    $scope.lessScore = function(schemaId, opIndex, number) {
        if (!$scope.schemasById) return false;

        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            return false;
        }

        return $scope.data[schemaId][op.v] >= number;
    };
}]);
