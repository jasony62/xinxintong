app.factory('Record', ['$http', '$q', function($http, $q) {
    var Record, _ins;
    Record = function() {};
    Record.prototype.get = function(ek) {
        var url, deferred;
        deferred = $q.defer();
        url = LS.j('record/get', 'mpid', 'aid');
        ek && (url += '&ek=' + ek);
        $http.get(url).success(function(rsp) {
            if (rsp.err_code == 0) {
                deferred.resolve(rsp.data);
            }
        });
        return deferred.promise;
    };
    return {
        ins: function() {
            return _ins ? _ins : (new Record());
        }
    };
}]);
app.factory('Input', function($http, $q, $timeout) {
    var Input, _ins;
    var required = function(value, len) {
        return (value == null || value == "" || value.length < len) ? false : true;
    };
    var validatePhone = function(value) {
        return (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) ? false : true;
    };
    var validate = function(data) {
        var reason;
        if ($('[ng-model="data.name"]').length === 1) {
            reason = '请提供您的姓名！';
            if (false === required(data.name, 2)) {
                document.querySelector('[ng-model="data.name"]').focus();
                return reason;
            }
        }
        if ($('[ng-model="data.mobile"]').length === 1) {
            reason = '请提供正确的手机号（11位数字）！';
            if (false === validatePhone(data.mobile)) {
                document.querySelector('[ng-model="data.mobile"]').focus();
                return reason;
            }
        }
        return true;
    };
    var r = new Resumable({
        target: '/rest/app/enroll/record/uploadFile?mpid=' + LS.p.mpid + '&aid=' + LS.p.aid,
        testChunks: false,
        chunkSize: 512 * 1024
    });
    r.on('progress', function() {
        var phase, p;
        p = r.progress();
        console.log('progress', p);
        var phase = $scope.$root.$$phase;
        if (phase === '$digest' || phase === '$apply') {
            $scope.progressOfUploadFile = Math.ceil(p * 100);
        } else {
            $scope.$apply(function() {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            });
        }
    });
    var submitWhole = function(defer, data, ek) {
        var url, d, d2, posted = angular.copy(data);
        url = '/rest/app/enroll/record/submit?mpid=' + LS.p.mpid + '&aid=' + LS.p.aid;
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
        $http.post(url, posted).success(function(rsp) {
            if (typeof rsp === 'string') {
                defer.reject(rsp);
            } else if (rsp.err_code != 0) {
                defer.reject(rsp.err_msg);
                return rsp.err_msg;
            } else {
                defer.resolve(rsp);
            }
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmPopup');
                el.onload = function() {
                    this.height = document.querySelector('body').clientHeight;
                };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function() {
                        el.style.display = 'none';
                        //btnSubmit && btnSubmit.removeAttribute('disabled');
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
                deferred2.notify(httpCode);
            } else {
                deferred2.reject(content);
            }
        });
    };
    Input = function() {};
    Input.prototype.submit = function(data, ek, modifiedImgFields) {
        var reason, btnSubmit, deferred2, promise2;
        deferred2 = $q.defer();
        promise2 = deferred2.promise;
        reason = validate(data);
        if (true !== reason) {
            $timeout(function() {
                deferred2.reject(reason);
            });
            return promise2;
        }
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $timeout(function() {
                deferred2.reject('请填写必填项');
            });
            return promise2;
        }
        if (r.files && r.files.length) {
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
                $scope.submit(deferred2, event, nextAction);
            });
            r.upload();
            return;
        }
        if (window.wx !== undefined && modifiedImgFields.length) {
            var i, j, nextWxImage;
            i = 0;
            j = 0;
            nextWxImage = function() {
                var imgField, img;
                imgField = data[modifiedImgFields[i]];
                img = imgField[j];
                window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
                    if (j < imgField.length - 1)
                        j++;
                    else if (i < modifiedImgFields.length - 1) {
                        j = 0;
                        i++;
                    } else {
                        submitWhole(data, ek);
                        return true;
                    }
                    nextWxImage();
                });
            };
            nextWxImage();
        } else {
            submitWhole(deferred2, data, ek);
        }
        return promise2;
    };
    return {
        ins: function() {
            if (!_ins) {
                _ins = new Input();
            }
            return _ins;
        }
    }
});
app.controller('ctrlInput', ['$scope', '$http', '$timeout', '$q', 'Input', 'Record', function($scope, $http, $timeout, $q, Input, Record) {
    var facRecord, facInput, record, modifiedImgFields, tasksOfOnReady;
    facInput = Input.ins();
    $scope.data = {
        member: {}
    };
    $scope.$on('xxt.app.enroll.ready', function() {
        if (LS.p.ek.length || (LS.p.newRecord !== 'Y' && $scope.App.open_lastroll === 'Y')) {
            facRecord = Record.ins();
            facRecord.get(LS.p.ek).then(function(record) {
                var p, type, dataOfRecord, value;
                dataOfRecord = record.data;
                for (p in dataOfRecord) {
                    if (p === 'member') {
                        $scope.data.member = dataOfRecord.member;
                    } else if ($('[name=' + p + ']').hasClass('img-tiles')) {
                        if (dataOfRecord[p] && dataOfRecord[p].length) {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = [];
                            for (var i in value) $scope.data[p].push({
                                imgSrc: value[i]
                            });
                        }
                    } else {
                        type = $('[name=' + p + ']').attr('type');
                        if (type === 'checkbox') {
                            if (dataOfRecord[p] && dataOfRecord[p].length) {
                                value = dataOfRecord[p].split(',');
                                $scope.data[p] = {};
                                for (var i in value) $scope.data[p][value[i]] = true;
                            }
                        } else {
                            $scope.data[p] = dataOfRecord[p];
                        }
                    }
                }
                /* 无论是否有登记记录都自动填写用户认证信息 */
                PG.setMember($scope.params, $scope.data.member);
            });
        }
    });
    $scope.submit = function(event, nextAction) {
        var ek, url, btnSubmit;
        btnSubmit = document.querySelector('#btnSubmit');
        btnSubmit && btnSubmit.setAttribute('disabled', true);
        ek = record ? record.enroll_key : undefined;
        facInput.submit($scope.data, ek, modifiedImgFields).then(function(rsp) {
            if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction !== undefined && nextAction.length) {
                url = LS.j('', 'mpid', 'aid');
                url += '&ek=' + rsp.data;
                url += '&page=' + nextAction;
                location.replace(url);
            } else {
                btnSubmit && btnSubmit.removeAttribute('disabled');
            }
        }, function(reason) {
            btnSubmit && btnSubmit.removeAttribute('disabled');
            $scope.$parent.errmsg = reason;
        });
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress($http, $q.defer(), $scope.mpid).then(function(data) {
            if (data.errmsg === 'ok')
                $scope.data[prop] = data.address;
            else
                $scope.errmsg = data.errmsg;
        });
    };
    modifiedImgFields = [];
    $scope.chooseImage = function(imgFieldName, count, from) {
        if (imgFieldName !== null) {
            modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
            $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
            if (count !== null && $scope.data[imgFieldName].length === count) {
                $scope.$parent.errmsg = '最多允许上传' + count + '张图片';
                return;
            }
        }
        if (window.YixinJSBridge) {
            if (from === undefined) {
                $scope.cachedImgFieldName = imgFieldName;
                openPickImageFrom();
                return;
            }
            imgFieldName = $scope.cachedImgFieldName;
            $scope.cachedImgFieldName = null;
            $('#pickImageFrom').hide();
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
            for (i = 0, j = imgs.length; i < j; i++) {
                img = imgs[i];
                (window.wx !== undefined) && $('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').attr('src', img.imgSrc);
            }
            $scope.$broadcast('xxt.enroll.image.choose.done', imgFieldName);
        });
    };
    $scope.removeImage = function(imgField, index) {
        imgField.splice(index, 1);
    };
    $scope.progressOfUploadFile = 0;
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
                $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                $scope.data[fileFieldName].push({
                    uniqueIdentifier: r.files[0].uniqueIdentifier,
                    name: f.name,
                    size: f.size,
                    type: f.type,
                    url: ''
                });
            }
            $scope.$apply('data.' + fileFieldName);
            $scope.$broadcast('xxt.enroll.file.choose.done', fileFieldName);
        }, false);
        ele.click();
    };
    $scope.$watch('data.member.authid', function(nv) {
        if (nv && nv.length) PG.setMember($scope.params, $scope.data.member);
    });
}]);