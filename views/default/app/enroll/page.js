formApp = angular.module('formApp', ['infinite-scroll']);
formApp.config(['$locationProvider', function ($lp) {
    $lp.html5Mode(true);
}]);
formApp.factory('Round', function ($http) {
    var Round = function (mpid, aid, current) {
        this.mpid = mpid;
        this.aid = aid;
        this.current = current;
        this.list = [];
    };
    Round.prototype.nextPage = function () {
        var _this = this;
        var url = '/rest/app/enroll/rounds';
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
        $http.get(url).success(function (rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.list = rsp.data;
        });
    };
    return Round;
});
formApp.factory('Record', function ($http) {
    var Record = function (mpid, aid, rid, current, $scope) {
        this.mpid = mpid;
        this.aid = aid;
        this.rid = rid;
        this.current = current;
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.orderBy = 'time';
        this.owner = 'all';
        this.$scope = $scope;
    };
    var listGet = function (ins, owner) {
        if (ins.busy) return;
        ins.busy = true;
        var url;
        url = '/rest/app/enroll/';
        url += ins.owner === 'user' ? 'myRecords' : 'records';
        url += '?mpid=' + ins.mpid;
        url += '&aid=' + ins.aid;
        url += '&rid=' + ins.rid;
        url += '&orderby=' + ins.orderBy;
        url += '&page=' + ins.page;
        url += '&size=10';
        $http.get(url).success(function (rsp) {
            if (rsp.data[0] && rsp.data[0].length) {
                for (var i = 0; i < rsp.data[0].length; i++)
                    ins.list.push(rsp.data[0][i]);
                ins.page++;
            }
            ins.busy = false;
        });
    };
    Record.prototype.changeOrderBy = function (orderBy) {
        this.orderBy = orderBy;
        this.reset();
    };
    Record.prototype.reset = function () {
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.nextPage();
    };
    Record.prototype.nextPage = function (owner) {
        if (owner && this.owner !== owner) {
            this.owner = owner;
            this.reset();
        } else
            listGet(this);
    };
    Record.prototype.like = function (event, record) {
        event.preventDefault();
        event.stopPropagation();
        if (!record && !this.current) {
            alert('没有指定要点赞的登记记录');
            return;
        }
        var url = '/rest/app/enroll/recordScore';
        url += '?mpid=' + this.mpid;
        url += '&ek=';
        record === undefined && (record = this.current);
        url += record.enroll_key;
        $http.get(url).success(function (rsp) {
            record.myscore = rsp.data[0];
            record.score = rsp.data[1];
        });
    };
    Record.prototype.remark = function (event, newRemark) {
        event.preventDefault();
        event.stopPropagation();
        if (!newRemark || newRemark.length === 0) {
            alert('评论内容不允许为空');
            return;
        }
        var _this = this;
        if (this.current.enroll_key === undefined) {
            alert('没有指定要评论的登记记录');
            return;
        }
        var url = '/rest/app/enroll/recordRemark';
        url += '?mpid=' + this.mpid;
        url += '&ek=' + this.current.enroll_key;
        $http.post(url, { remark: newRemark }).success(function (rsp) {
            if (angular.isString(rsp)) {
                alert(rsp);
                return;
            }
            if (rsp.err_code != 0) {
                alert(rsp.err_msg);
                return;
            }
            _this.current.remarks.push(rsp.data);
        });
    };
    return Record;
});
formApp.factory('Statistic', function () {
    var Stat = function (mpid, aid, data) {
        this.mpid = mpid;
        this.aid = aid;
        this.data = null;
    };
    return Stat;
});
formApp.controller('formCtrl', ['$location', '$scope', '$http', '$timeout', '$q', 'Round', 'Record', 'Statistic', function ($location, $scope, $http, $timeout, $q, Round, Record, Statistic) {
    window.shareCounter = 0;
    window.xxt.share.options.logger = function (shareto) {
        var url = "/rest/mi/matter/logShare";
        url += "?shareid=" + window.shareid;
        url += "&mpid=" + $scope.params.mpid;
        url += "&id=" + $scope.params.enroll.id;
        url += "&type=enroll";
        url += "&shareby=" + $scope.params.shareby;
        url += "&shareto=" + shareto;
        $http.get(url);
        window.shareCounter++;
        window.onshare && window.onshare(window.shareCounter);
    };
    if (/MicroMessenger/i.test(navigator.userAgent)) {
        signPackage.jsApiList = ['hideOptionMenu', 'showOptionMenu', 'closeWindow', 'chooseImage', 'uploadImage', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'getLocation'];
        signPackage.debug = false;
        wx.config(signPackage);
        wx.ready(function () {
            wx.showOptionMenu();
        });
    } else if (/YiXin/i.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function () {
            YixinJSBridge.call('showOptionMenu');
        }, false);
    }
    document.body.addEventListener('click', function (event) {
        var url, target = event.target;
        if (target.tagName === 'A' && target.classList.contains('innerlink')) {
            event.preventDefault();
            var id = target.getAttribute('href'), type = target.getAttribute('type');
            id = id.split('/').pop();
            url = '/rest/mi/matter?mpid=' + $scope.param.mpid + 'type=' + type + '&id=' + id;
            location.href = url;
        }
    }, false);
    var openPickImageFrom = function () {
        var st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
        var ch = document.documentElement.clientHeight;
        var cw = document.documentElement.clientWidth;
        var $dlg = $('#pickImageFrom');
        $dlg.css({
            'display': 'block',
            'top': (st + (ch - $dlg.height() - 30) / 2) + 'px',
            'left': ((cw - $dlg.width() - 30) / 2) + 'px'
        });
    };
    var required = function (value, len, alerttext) {
        if (value == null || value == "" || value.length < len) {
            $scope.errmsg = alerttext; return false;
        } else { return true; }
    };
    var validatePhone = function (value, alerttext) {
        if (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) {
            $scope.errmsg = alerttext; return false;
        } else { return true; }
    };
    var validate = function () {
        if ($('[ng-model="data.name"]').length === 1) {
            if (false === required($scope.data.name, 2, '请提供您的姓名！')) {
                document.querySelector('[ng-model="data.name"]').focus();
                return false;
            }
        }
        if ($('[ng-model="data.mobile"]').length === 1) {
            if (false === validatePhone($scope.data.mobile, '请提供正确的手机号（11位数字）！')) {
                document.querySelector('[ng-model="data.mobile"]').focus();
                return false;
            }
        }
        $scope.errmsg = '';
        return true;
    };
    var modifiedImgFields = [];
    $scope.mpid = $location.search().mpid;
    $scope.aid = $location.search().aid;
    $scope.rid = $location.search().rid;
    $scope.preview = $location.search().preview;
    $scope.data = { member: {} };
    $scope.ready = false;
    $scope.errmsg = '';
    $scope.closePreviewTip = function () {
        $scope.preview = 'N';
    };
    $scope.closeWindow = function () {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            YixinJSBridge.call('closeWebView');
        }
    };
    $scope.getMyLocation = function (prop) {
        if (window.wx) {
            wx.getLocation({
                success: function (res) {
                    var url = '/rest/app/enroll/locationGet';
                    url += '?mpid=' + $scope.mpid;
                    url += '&lat=' + res.latitude;
                    url += '&lng=' + res.longitude;
                    $http.get(url).success(function (rsp) {
                        $scope.data[prop] = rsp.data.address;
                    });
                }
            });
        } else {
            var nav = window.navigator;
            if (nav !== null) {
                var geoloc = nav.geolocation;
                if (geoloc !== null) {
                    geoloc.getCurrentPosition(function (position) {
                        var url = '/rest/app/enroll/locationGet';
                        url += '?mpid=' + $scope.mpid;
                        url += '&lat=' + position.coords.latitude;
                        url += '&lng=' + position.coords.longitude;
                        $http.get(url).success(function (rsp) {
                            $scope.data[prop] = rsp.data.address;
                        });
                    }, function () { alert('获取地理位置失败'); });
                }
                else {
                    alert("无法获取地理位置");
                }
            } else {
                alert("无法获取地理位置");
            }
        }
    };
    $scope.chooseImage = function (imgFieldName, count, from) {
        if (imgFieldName !== null) {
            if (-1 === modifiedImgFields.indexOf(imgFieldName)) modifiedImgFields.push(imgFieldName);
            if ($scope.data[imgFieldName] === undefined) $scope.data[imgFieldName] = [];
            if (count !== null && $scope.data[imgFieldName].length === count) {
                $scope.errmsg = '最多允许上传' + count + '张图片';
                return;
            }
        }
        if (window.wx !== undefined) {
            wx.chooseImage({
                success: function (res) {
                    var i, img;
                    for (i in res.localIds) {
                        img = { imgSrc: res.localIds[i] };
                        $scope.data[imgFieldName].push(img);
                        $scope.$apply('data.' + imgFieldName);
                        $('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').attr('src', img.imgSrc);
                    }
                }
            });
        } else if (window.YixinJSBridge) {
            if (from === undefined) {
                $scope.cachedImgFieldName = imgFieldName;
                openPickImageFrom();
                return;
            }
            imgFieldName = $scope.cachedImgFieldName;
            $scope.cachedImgFieldName = null;
            $('#pickImageFrom').hide();
            YixinJSBridge.invoke(
                'pickImage', {
                    type: from,
                    quality: 100
                }, function (result) {
                    if (result.data && result.data.length) {
                        var img = { imgSrc: 'data:' + result.mime + ';base64,' + result.data };
                        $scope.data[imgFieldName].push(img);
                        $scope.$apply('data.' + imgFieldName);
                    }
                }
                );
        } else {
            var eleInp = document.createElement('input');
            eleInp.setAttribute('type', 'file');
            eleInp.addEventListener('change', function (evt) {
                var i, cnt, f;
                cnt = evt.target.files.length;
                for (i = 0; i < cnt; i++) {
                    f = evt.target.files[i];
                    type = { ".jp": "image/jpeg", ".pn": "image/png", ".gi": "image/gif" }[f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                    f.type2 = f.type || type;
                    var reader = new FileReader();
                    reader.onload = (function (theFile) {
                        return function (e) {
                            var img = {};
                            img.imgSrc = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                            $scope.data[imgFieldName].push(img);
                            $scope.$apply('data.' + imgFieldName);
                        };
                    })(f);
                    reader.readAsDataURL(f);
                }
            }, false);
            eleInp.click();
        }
    };
    $scope.removeImage = function (imgField, index) {
        imgField.splice(index, 1);
    };
    $scope.submit = function (event, nextAction) {
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        var btnSubmit, deferred2, promise2;
        btnSubmit = document.querySelector('#btnSubmit');
        deferred2 = $q.defer();
        promise2 = deferred2.promise;
        btnSubmit && btnSubmit.setAttribute('disabled', true);
        var uploadWxImage = function (img) {
            var deferred, promise;
            deferred = $q.defer();
            promise = deferred.promise;
            if (0 === img.imgSrc.indexOf('weixin://') || 0 === img.imgSrc.indexOf('wxLocalResource://')) {
                wx.uploadImage({
                    localId: img.imgSrc,
                    isShowProgressTips: 1,
                    success: function (res) {
                        img.serverId = res.serverId;
                        deferred.resolve(img);
                    }
                });
            } else
                deferred.resolve(img);
            return promise;
        };
        var submitWhole = function () {
            var url = '/rest/app/enroll/submit?mpid=' + $scope.mpid + '&aid=' + $scope.aid;
            if (!$scope.isNew && $scope.params.enrollKey && $scope.params.enrollKey.length)
                url += '&ek=' + $scope.params.enrollKey;
            var d, d2, posted = angular.copy($scope.data);
            for (var i in posted) {
                d = posted[i];
                if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                    for (var j in d) {
                        d2 = d[j];
                        delete d2.imgSrc;
                    }
                }
            }
            $http.post(url, posted).success(function (rsp) {
                if (typeof (rsp) === 'string') {
                    $scope.errmsg = rsp;
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                    return;
                }
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                    return;
                }
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                    return;
                }
                if (nextAction !== undefined && nextAction.length) {
                    var url = '/rest/app/enroll';
                    url += '?mpid=' + $scope.mpid;
                    url += '&aid=' + $scope.aid;
                    url += '&ek=' + rsp.data;
                    url += '&page=' + nextAction;
                    location.href = url;
                } else {
                    deferred2.resolve('ok');
                }
            }).error(function (content, httpCode) {
                if (httpCode === 401) {
                    var $el = $('#frmAuth');
                    if (content.indexOf('http') === 0) {
                        window.onAuthSuccess = function () {
                            $el.hide();
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                        };
                        $el.attr('src', content).show();
                    } else {
                        if ($el[0].contentDocument && $el[0].contentDocument.body) {
                            $el[0].contentDocument.body.innerHTML = content;
                            $el.show();
                        }
                    }
                } else {
                    $scope.errmsg = content;
                }
            });
        }
        if (window.wx !== undefined && modifiedImgFields.length) {
            var i = 0, j = 0, imgField, img;
            var nextWxImage = function () {
                imgField = $scope.data[modifiedImgFields[i]];
                img = imgField[j];
                uploadWxImage(img).then(function (data) {
                    if (j < imgField.length - 1)
                        j++;
                    else if (i < modifiedImgFields.length - 1) {
                        j = 0;
                        i++;
                    } else {
                        submitWhole();
                        return true;
                    }
                    nextWxImage();
                });
            };
            nextWxImage();
        } else {
            submitWhole();
        }
        return promise2;
    };
    $scope.gotoPage = function (event, page, ek, rid) {
        event.preventDefault();
        event.stopPropagation();
        var url = '/rest/app/enroll';
        url += '?mpid=' + $scope.mpid;
        url += '&aid=' + $scope.aid;
        if (page !== 'form' || ek !== undefined) {
            if (ek === undefined && $scope.Record.current)
                url += '&ek=' + $scope.Record.current.enroll_key;
            else if (ek !== undefined && ek.length)
                url += '&ek=' + ek;
        }
        rid !== undefined && (url += '&rid=' + rid);
        url += '&page=' + page;
        location.href = url;
    };
    $scope.addRecord = function (event) {
        $scope.gotoPage(event, 'form');
    };
    $scope.openMatter = function (id, type) {
        location.href = '/rest/mi/matter?mpid=' + $scope.mpid + '&id=' + id + '&type=' + type;
    };
    $scope.$watch('pageName', function (nv) {
        if (!nv) return;
        var url;
        url = '/rest/app/enroll/get';
        url += '?mpid=' + $scope.mpid;
        url += '&aid=' + $scope.aid;
        url += '&page=' + nv;
        $location.search().ek && (url += '&ek=' + $location.search().ek);
        $http.get(url).success(function (rsp) {
            var params = rsp.data, sharelink, summary;
            sharelink = 'http://' + location.hostname + "/rest/app/enroll";
            sharelink += "?mpid=" + $scope.mpid;
            sharelink += "&aid=" + $scope.aid;
            if (params.page.share_page && params.page.share_page === 'Y') {
                sharelink += '&page=' + params.page.name;
                sharelink += '&ek=' + params.enrollKey;
            }
            window.shareid = params.user.vid + (new Date()).getTime();
            sharelink += "&shareby=" + window.shareid;
            summary = params.enroll.summary;
            if (params.page.share_summary && params.page.share_summary.length && params.record)
                summary = params.record.data[params.page.share_summary];
            window.xxt.share.set(params.enroll.title, sharelink, summary, params.enroll.pic);

            $scope.User = params.user;
            $scope.Record = new Record($scope.mpid, $scope.aid, $scope.rid, params.record, $scope);
            $scope.Round = new Round($scope.mpid, $scope.aid);
            $scope.Statistic = new Statistic($scope.mpid, $scope.aid, params.statdata);
            $scope.params = params;
            if (params.page.name === 'form' && params.record) {
                $timeout(function () {
                    var p, type, dataOfRecord, value;
                    dataOfRecord = $scope.Record.current.data;
                    for (p in dataOfRecord) {
                        if ($('[name=' + p + ']').hasClass('img-tiles')) {
                            if (dataOfRecord[p] && dataOfRecord[p].length) {
                                value = dataOfRecord[p].split(',');
                                $scope.data[p] = [];
                                for (var i in value) $scope.data[p].push({ imgSrc: value[i] });
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
                });
            }
            if ($scope.data.member.authid && params.user.members.length) {
                for (var m in $scope.User.members) {
                    if ($scope.data.member.authid == $scope.User.members[m].authapi_id) {
                        $scope.data.member.name = $scope.User.members[m].name;
                        $scope.data.member.mobile = $scope.User.members[m].mobile;
                        $scope.data.member.email = $scope.User.members[m].email;
                        var extAttrs = JSON.parse($scope.User.members[m].extattr);
                        for (var ea in extAttrs) {
                            $scope.data.member[ea] = extAttrs[ea];
                        }
                        break;
                    }
                }
            }
            $scope.ready = true;
            console.log('page ready', $scope.params);
        });
    });
}]);
formApp.directive('runningButton', function () {
    return {
        restrict: 'EA',
        template: "<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
        scope: { isRunning: '=' },
        replace: true,
        transclude: true
    }
});
formApp.directive('flexImg', function () {
    return {
        restrict: 'A',
        replace: true,
        template: "<img src='{{img.imgSrc}}'>",
        link: function (scope, elem, attrs) {
            $(elem).on('load', function () {
                var w = $(this).width(), h = $(this).height(), sw, sh;
                if (w > h) {
                    sw = w / h * 72;
                    $(this).css({ 'height': '100%', 'width': sw + 'px', 'top': '0', 'left': '50%', 'margin-left': (-1 * sw / 2) + 'px' });
                } else {
                    sh = h / w * 72;
                    $(this).css({ 'width': '100%', 'height': sh + 'px', 'left': '0', 'top': '50%', 'margin-top': (-1 * sh / 2) + 'px' });
                }
            })
        }
    }
});
