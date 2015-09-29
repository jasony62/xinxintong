if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage !== undefined) {
    signPackage.jsApiList = ['hideOptionMenu', 'showOptionMenu', 'closeWindow', 'chooseImage', 'uploadImage', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'getLocation'];
    signPackage.debug = false;
    wx.config(signPackage);
    wx.ready(function() {
        wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}
app = angular.module('app', ['ngSanitize', 'infinite-scroll']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.directive('tmsExec', ['$rootScope', '$timeout', function($rootScope, $timeout) {
    return {
        restrict: 'A',
        link: function(scope, elem, attrs) {
            $timeout(function() {
                if ($rootScope.$$phase) {
                    return scope.$eval(attrs.tmsExec);
                } else {
                    return scope.$apply(attrs.tmsExec);
                }
            }, 0);
        }
    };
}]);
app.factory('Round', function($http) {
    var Round = function(mpid, aid, current) {
        this.mpid = mpid;
        this.aid = aid;
        this.current = current;
        this.list = [];
    };
    Round.prototype.nextPage = function() {
        var _this = this,
            url;
        url = '/rest/app/enroll/round/list';
        url += '?mpid=' + _this.mpid;
        url += '&aid=' + _this.aid;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.list = rsp.data;
        });
    };
    return Round;
});
app.factory('Record', function($http) {
    var Record = function(mpid, aid, rid, current, $scope) {
        this.mpid = mpid;
        this.aid = aid;
        this.rid = rid;
        this.current = current;
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.size = 10;
        this.orderBy = 'time';
        this.owner = 'all';
        this.total = -1;
        this.$scope = $scope;
    };
    var listGet = function(ins) {
        if (ins.busy) return;
        if (ins.total !== -1 && ins.total <= (ins.page - 1) * ins.size) return;
        ins.busy = true;
        var url;
        url = '/rest/app/enroll/record/';
        switch (ins.owner) {
            case 'A':
                url += 'list';
                break;
            case 'U':
                url += 'mine';
                break;
            case 'I':
                url += 'myFollowers';
                break;
            default:
                alert('没有指定要获得的登记记录类型');
                return;
        }
        url += '?mpid=' + ins.mpid;
        url += '&aid=' + ins.aid;
        ins.rid !== undefined && ins.rid.length && (url += '&rid=' + ins.rid);
        url += '&orderby=' + ins.orderBy;
        url += '&page=' + ins.page;
        url += '&size=' + ins.size;
        $http.get(url).success(function(rsp) {
            var record;
            if (rsp.err_code == 0) {
                ins.total = rsp.data.total;
                if (rsp.data.records && rsp.data.records.length) {
                    for (var i = 0; i < rsp.data.records.length; i++) {
                        record = rsp.data.records[i];
                        record.data.member && (record.data.member = JSON.parse(record.data.member));
                        ins.list.push(record);
                    }
                    ins.page++;
                }
            }
            ins.busy = false;
        });
    };
    Record.prototype.changeOrderBy = function(orderBy) {
        this.orderBy = orderBy;
        this.reset();
    };
    Record.prototype.reset = function() {
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.nextPage();
    };
    Record.prototype.nextPage = function(owner) {
        if (owner && this.owner !== owner) {
            this.owner = owner;
            this.reset();
        } else
            listGet(this);
    };
    Record.prototype.like = function(event, record) {
        event.preventDefault();
        event.stopPropagation();
        if (!record && !this.current) {
            alert('没有指定要点赞的登记记录');
            return;
        }
        var url = '/rest/app/enroll/record/score';
        url += '?mpid=' + this.mpid;
        url += '&ek=';
        record === undefined && (record = this.current);
        url += record.enroll_key;
        $http.get(url).success(function(rsp) {
            record.myscore = rsp.data[0];
            record.score = rsp.data[1];
        });
    };
    Record.prototype.remark = function(event, newRemark) {
        event.preventDefault();
        event.stopPropagation();
        if (!newRemark || newRemark.length === 0) {
            alert('评论内容不允许为空');
            return false;
        }
        var _this = this;
        if (this.current.enroll_key === undefined) {
            alert('没有指定要评论的登记记录');
            return false;
        }
        var url = '/rest/app/enroll/record/remark';
        url += '?mpid=' + this.mpid;
        url += '&ek=' + this.current.enroll_key;
        $http.post(url, {
            remark: newRemark
        }).success(function(rsp) {
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
        return true;
    };
    return Record;
});
app.factory('Statistic', ['$http', function($http) {
    var Stat = function(mpid, aid, data) {
        this.mpid = mpid;
        this.aid = aid;
        this.data = null;
        this.result = {};
    };
    Stat.prototype.rankByFollower = function() {
        var _this, url;
        _this = this;
        url = '/rest/app/enroll/rankByFollower';
        url += '?mpid=' + this.mpid;
        url += '&aid=' + this.aid;
        $http.get(url).success(function(rsp) {
            _this.result.rankByFollower = rsp.data;
        });
    };
    return Stat;
}]);
app.factory('Schema', ['$http', '$q', function($http, $q) {
    var mpid, aid, schema, Schema;
    mpid = location.search.match(/mpid=([^&]*)/)[1];
    aid = location.search.match(/aid=([^&]*)/)[1];
    schema = null;
    Schema = function() {};
    Schema.prototype.get = function() {
        var deferred, promise;
        deferred = $q.defer();
        promise = deferred.promise;
        if (schema !== null)
            deferred.resolve(schema);
        else {
            $http.get('/rest/app/enroll/page/schemaGet?mpid=' + mpid + '&id=' + aid + '&byPage=N').success(function(rsp) {
                schema = rsp.data;
                deferred.resolve(schema);
            });
        }
        return promise;
    };
    return Schema;
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', 'Round', 'Record', 'Statistic', 'Schema', function($scope, $http, $timeout, $q, Round, Record, Statistic, Schema) {
    var LS = (function() {
        function locationSearch() {
            var ls, search;
            ls = location.search;
            search = {};
            angular.forEach(['mpid', 'aid', 'rid', 'page', 'ek', 'preview'], function(q) {
                var match, pattern;
                pattern = new RegExp(q + '=([^&]*)');
                match = ls.match(pattern);
                search[q] = match ? match[1] : '';
            });
            return search;
        };
        /*join search*/
        function j(method) {
            var j, l, url = '/rest/app/enroll',
                _this = this,
                search = [];
            method && method.length && (url += '/' + method);
            if (arguments.length > 1) {
                for (i = 1, l = arguments.length; i < l; i++) {
                    search.push(arguments[i] + '=' + _this.p[arguments[i]]);
                };
                url += '?' + search.join('&');
            }
            return url;
        };
        return {
            p: locationSearch(),
            j: j
        };
    })();
    var PG = (function() {
        return {
            exec: function(task) {
                var obj, fn, args, valid;
                valid = true;
                obj = $scope;
                args = task.match(/\((.*?)\)/)[1].replace(/'|"/g, "").split(',');
                angular.forEach(task.replace(/\(.*?\)/, '').split('.'), function(attr) {
                    if (fn) obj = fn;
                    if (!obj[attr]) {
                        valid = false;
                        return;
                    }
                    fn = obj[attr];
                });
                if (valid) {
                    fn.apply(obj, args);
                }
            },
            setMember: function() {
                var params;
                if ($scope.params) {
                    params = $scope.params;
                    if ($scope.data.member && $scope.data.member.authid && params.user.members && params.user.members.length) {
                        angular.forEach(params.user.members, function(member) {
                            if (member.authapi_id == $scope.data.member.authid) {
                                $("[ng-model^='data.member']").each(function() {
                                    var attr;
                                    attr = $(this).attr('ng-model');
                                    attr = attr.replace('data.member.', '');
                                    attr = attr.split('.');
                                    if (attr.length == 2) {
                                        !$scope.data.member.extattr && ($scope.data.member.extattr = {});
                                        $scope.data.member.extattr[attr[1]] = member.extattr[attr[1]];
                                    } else {
                                        $scope.data.member[attr[0]] = member[attr[0]];
                                    }
                                });
                            }
                        });
                    }
                }
            },
            firstInput: function() {
                var first;
                $scope.params.enroll.pages.some(function(oPage) {
                    if (oPage.type === 'I') {
                        first = oPage;
                        return true;
                    } else {
                        return false;
                    }
                });
                return first;
            }
        };
    })();
    var openPickImageFrom = function() {
        var st, ch, cw, $dlg;
        st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
        ch = document.documentElement.clientHeight;
        cw = document.documentElement.clientWidth;
        $dlg = $('#pickImageFrom');
        $dlg.css({
            'display': 'block',
            'top': (st + (ch - $dlg.height() - 30) / 2) + 'px',
            'left': ((cw - $dlg.width() - 30) / 2) + 'px'
        });
    };
    var required = function(value, len, alerttext) {
        if (value == null || value == "" || value.length < len) {
            $scope.errmsg = alerttext;
            return false;
        } else {
            return true;
        }
    };
    var validatePhone = function(value, alerttext) {
        if (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) {
            $scope.errmsg = alerttext;
            return false;
        } else {
            return true;
        }
    };
    var validate = function() {
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
    var modifiedImgFields = [],
        tasksOfOnReady = [];
    $scope.data = {
        member: {}
    };
    $scope.errmsg = '';
    $scope.closePreviewTip = function() {
        $scope.preview = 'N';
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            window.wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            window.YixinJSBridge.call('closeWebView');
        }
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress($http, $q.defer(), $scope.mpid).then(function(data) {
            if (data.errmsg === 'ok')
                $scope.data[prop] = data.address;
            else
                $scope.errmsg = data.errmsg;
        });
    };
    $scope.chooseImage = function(imgFieldName, count, from) {
        if (imgFieldName !== null) {
            modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
            $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
            if (count !== null && $scope.data[imgFieldName].length === count) {
                $scope.errmsg = '最多允许上传' + count + '张图片';
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
    $scope.progressOfUploadFile = 0;
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
    $scope.removeImage = function(imgField, index) {
        imgField.splice(index, 1);
    };
    $scope.submit = function(event, nextAction) {
        if (!validate()) return;
        if (document.querySelectorAll('.ng-invalid-required').length) {
            $scope.errmsg = '请填写必填项';
            return;
        }
        if (r.files && r.files.length) {
            r.on('complete', function() {
                console.log('resumable complete.');
                var phase = $scope.$root.$$phase;
                if (phase === '$digest' || phase === '$apply') {
                    $scope.progressOfUploadFile = '完成';
                } else {
                    $scope.$apply(function() {
                        $scope.progressOfUploadFile = '完成';
                    });
                }
                r.cancel();
                $scope.submit(event, nextAction);
            });
            r.upload();
            return;
        }
        var btnSubmit, deferred2, promise2;
        btnSubmit = document.querySelector('#btnSubmit');
        deferred2 = $q.defer();
        promise2 = deferred2.promise;
        btnSubmit && btnSubmit.setAttribute('disabled', true);
        var submitWhole = function() {
            var url, d, d2, posted = angular.copy($scope.data);
            url = '/rest/app/enroll/record/submit?mpid=' + LS.p.mpid + '&aid=' + LS.p.aid;
            if (!$scope.isNew && $scope.params.enrollKey && $scope.params.enrollKey.length)
                url += '&ek=' + $scope.params.enrollKey;
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
                    $scope.errmsg = rsp;
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                } else if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                } else if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                } else if (nextAction !== undefined && nextAction.length) {
                    var url = '/rest/app/enroll';
                    url += '?mpid=' + LS.p.mpid;
                    url += '&aid=' + LS.p.aid;
                    url += '&ek=' + rsp.data;
                    url += '&page=' + nextAction;
                    location.replace(url);
                } else {
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                    deferred2.resolve('ok');
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
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                        };
                        el.setAttribute('src', content);
                        el.style.display = 'block';
                    } else {
                        if (el.contentDocument && el.contentDocument.body) {
                            el.contentDocument.body.innerHTML = content;
                            el.style.display = 'block';
                        }
                    }
                } else {
                    $scope.errmsg = content;
                }
            });
        }
        if (window.wx !== undefined && modifiedImgFields.length) {
            var i = 0,
                j = 0,
                imgField, img;
            var nextWxImage = function() {
                imgField = $scope.data[modifiedImgFields[i]];
                img = imgField[j];
                window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
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
    var openAskFollow = function() {
        $http.get('/rest/app/enroll/askFollow?mpid=' + LS.p.mpid).error(function(content) {
            var body, el;;
            body = document.body;
            el = document.createElement('iframe');
            el.setAttribute('id', 'frmPopup');
            el.height = body.clientHeight;
            body.scrollTop = 0;
            body.appendChild(el);
            if (content.indexOf('http') === 0) {
                window.closeAskFollow = function() {
                    el.style.display = 'none';
                };
                el.setAttribute('src', content);
                el.style.display = 'block';
            } else {
                if (el.contentDocument && el.contentDocument.body) {
                    el.contentDocument.body.innerHTML = content;
                    el.style.display = 'block';
                }
            }
        });
    };
    $scope.gotoPage = function(event, page, ek, rid, fansOnly) {
        event.preventDefault();
        event.stopPropagation();
        if (fansOnly && !$scope.User.fan) {
            openAskFollow();
            return;
        }
        var url = '/rest/app/enroll';
        url += '?mpid=' + LS.p.mpid;
        url += '&aid=' + LS.p.aid;
        if (ek !== undefined && ek !== null && ek.length) {
            url += '&ek=' + ek;
        }
        rid !== undefined && rid !== null && rid.length && (url += '&rid=' + rid);
        page !== undefined && page !== null && page.length && (url += '&page=' + page);
        location.replace(url);
    };
    $scope.addRecord = function(event) {
        var first, page;
        first = PG.firstInput();
        page = first.name;
        page ? $scope.gotoPage(event, page) : alert('当前活动没有包含数据登记页');
    };
    $scope.editRecord = function(event, page) {
        var first;
        if (page === undefined && (first = PG.firstInput()))
            page = first.name;
        page ? $scope.gotoPage(event, page, $scope.Record.current.enroll_key) : alert('当前活动没有包含数据登记页');
    };
    $scope.likeRecord = function(event) {
        $scope.Record.like(event);
    };
    $scope.newRemark = '';
    $scope.remarkRecord = function(event) {
        if ($scope.Record.remark(event, $scope.newRemark))
            $scope.newRemark = '';
    };
    $scope.openMatter = function(id, type) {
        location.replace('/rest/mi/matter?mpid=' + LS.p.mpid + '&id=' + id + '&type=' + type);
    };
    $scope.acceptInvite = function(event, nextAction) {
        var inviter, url;
        if (!$scope.Record.current) {
            alert('未进行登记，无效的邀请');
            return;
        }
        if ($scope.Record.current.openid === $scope.User.fan.openid) {
            alert('不能自己邀请自己');
            return;
        }
        inviter = $scope.Record.current.enroll_key;
        url = '/rest/app/enroll/record/acceptInvite';
        url += '?mpid=' + LS.p.mpid;
        url += '&aid=' + LS.p.aid;
        url += '&inviter=' + inviter;
        $http.get(url).success(function(rsp) {
            if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction !== undefined && nextAction.length) {
                var url = '/rest/app/enroll';
                url += '?mpid=' + LS.p.mpid;
                url += '&aid=' + LS.p.aid;
                url += '&ek=' + rsp.data.ek;
                url += '&page=' + nextAction;
                location.replace(url);
            }
        });
    };
    $scope.followMp = function(event, page) {
        if (/YiXin/i.test(navigator.userAgent)) {
            location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
        } else if (page !== undefined && page.length) {
            $scope.gotoPage(event, page);
        } else {
            alert('请在易信中打开页面');
        }
    };
    $scope.$watch('data.member.authid', function(nv) {
        if (nv && nv.length) PG.setMember();
    });
    $scope.onReady = function(task) {
        if ($scope.params) {
            PG.exec(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    (new Schema()).get().then(function(data) {});
    $http.get(LS.j('get', 'mpid', 'aid', 'rid', 'page', 'ek')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        params.record && params.record.data && params.record.data.member && (params.record.data.member = JSON.parse(params.record.data.member));
        $scope.params = params;
        $scope.mpa = params.mpaccount;
        $scope.App = params.enroll;
        $scope.Page = params.page;
        $scope.User = params.user;
        $scope.Round = new Round(LS.p.mpid, LS.p.aid);
        $scope.Record = new Record(LS.p.mpid, LS.p.aid, LS.p.rid, params.record, $scope);
        $scope.Statistic = new Statistic(LS.p.mpid, LS.p.aid, params.statdata);
        (function setShareData() {
            try {
                var sharelink, summary;
                sharelink = 'http://' + location.hostname + LS.j('', 'mpid', 'aid');
                if (params.page.share_page && params.page.share_page === 'Y') {
                    sharelink += '&page=' + params.page.name;
                    sharelink += '&ek=' + params.enrollKey;
                }
                window.shareid = params.user.vid + (new Date()).getTime();
                sharelink += "&shareby=" + window.shareid;
                summary = params.enroll.summary;
                if (params.page.share_summary && params.page.share_summary.length && params.record)
                    summary = params.record.data[params.page.share_summary];
                $scope.shareData = {
                    title: params.enroll.title,
                    link: sharelink,
                    desc: summary,
                    pic: params.enroll.pic
                };
                window.xxt.share.set(params.enroll.title, sharelink, summary, params.enroll.pic);
                window.shareCounter = 0;
                window.xxt.share.options.logger = function(shareto) {
                    var app, url;
                    app = $scope.App;
                    url = "/rest/mi/matter/logShare";
                    url += "?shareid=" + window.shareid;
                    url += "&mpid=" + LS.p.mpid;
                    url += "&id=" + app.id;
                    url += "&type=enroll";
                    url += "&title=" + app.title;
                    url += "&shareby=" + $scope.params.shareby;
                    url += "&shareto=" + shareto;
                    $http.get(url);
                    window.shareCounter++;
                    /* 是否需要自动登记 */
                    if (app.can_autoenroll === 'Y' && $scope.Page.autoenroll_onshare === 'Y') {
                        $http.get(LS.j('emptyGet', 'mpid', 'aid') + '&once=Y');
                    }
                    window.onshare && window.onshare(window.shareCounter);
                };
            } catch (e) {
                alert(e.message);
            }
        })();
        (function setPage(page) {
            if (page.ext_css && page.ext_css.length) {
                angular.forEach(page.ext_css, function(css) {
                    var link, head;
                    link = document.createElement('link');
                    link.href = css.url;
                    link.rel = 'stylesheet';
                    head = document.querySelector('head');
                    head.appendChild(link);
                });
            }
            if (page.ext_js && page.ext_js.length) {
                angular.forEach(page.ext_js, function(js) {
                    $.getScript(js.url);
                });
            }
            if (page.js && page.js.length) {
                (function dynamicjs() {
                    eval(page.js);
                })();
            }
            if (page.type === 'I') {
                $timeout(function setPageDate() {
                    if ($scope.Record.current) {
                        var p, type, dataOfRecord, value;
                        dataOfRecord = $scope.Record.current.data;
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
                    }
                    /* 无论是否有登记记录都自动填写用户认证信息 */
                    PG.setMember();
                });
            }
        })(params.page);
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, PG.exec);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
    });
}]);
app.filter('value2Label', ['Schema', function(Schema) {
    var schemas;
    (new Schema()).get().then(function(data) {
        schemas = data;
    });
    return function(val, key) {
        var i, j, s, aVal, aLab = [];
        if (val === undefined) return '';
        //if (!schemas) return '';
        for (i = 0, j = schemas.length; i < j; i++) {
            s = schemas[i];
            if (schemas[i].id === key) {
                s = schemas[i];
                break;
            }
        }
        if (s && s.ops && s.ops.length) {
            aVal = val.split(',');
            for (i = 0, j = s.ops.length; i < j; i++) {
                aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].label);
            }
            if (aLab.length) return aLab.join(',');
        }
        return val;
    };
}]);
app.directive('runningButton', function() {
    return {
        restrict: 'EA',
        template: "<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
        scope: {
            isRunning: '='
        },
        replace: true,
        transclude: true
    }
});
app.directive('flexImg', function() {
    return {
        restrict: 'A',
        replace: true,
        template: "<img src='{{img.imgSrc}}'>",
        link: function(scope, elem, attrs) {
            $(elem).on('load', function() {
                var w = $(this).width(),
                    h = $(this).height(),
                    sw, sh;
                if (w > h) {
                    sw = w / h * 72;
                    $(this).css({
                        'height': '100%',
                        'width': sw + 'px',
                        'top': '0',
                        'left': '50%',
                        'margin-left': (-1 * sw / 2) + 'px'
                    });
                } else {
                    sh = h / w * 72;
                    $(this).css({
                        'width': '100%',
                        'height': sh + 'px',
                        'left': '0',
                        'top': '50%',
                        'margin-top': (-1 * sh / 2) + 'px'
                    });
                }
            })
        }
    }
});
app.directive('dynamicHtml', function($compile) {
    return {
        restrict: 'EA',
        replace: true,
        link: function(scope, ele, attrs) {
            scope.$watch(attrs.dynamicHtml, function(html) {
                if (html && html.length) {
                    ele.html(html);
                    $compile(ele.contents())(scope);
                }
            });
        }
    };
});