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
        url = '/rest/app/enroll/template/record/';
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
                alert('没有指定要获得的登记记录类型（' + ins.owner + '）');
                return;
        }
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
    Schema = function() {};
    return Schema;
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', 'Round', 'Record', 'Statistic', 'Schema', function($scope, $http, $timeout, $q, Round, Record, Statistic, Schema) {
    var LS = (function() {
        function locationSearch() {
            var ls, search;
            ls = location.search;
            search = {};
            angular.forEach(['scenario', 'template', 'page'], function(q) {
                var match, pattern;
                pattern = new RegExp(q + '=([^&]*)');
                match = ls.match(pattern);
                search[q] = match ? match[1] : '';
            });
            return search;
        };
        /*join search*/
        function j(method) {
            var j, l, url = '/rest/app/enroll/template',
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
    var openAskFollow = function() {
        $http.get('/rest/app/enroll/askFollow?mpid=' + LS.p.mpid).error(function(content) {
            var body, el;;
            body = document.body;
            el = document.createElement('iframe');
            el.setAttribute('id', 'frmPopup');
            el.height = body.clientHeight;
            body.scrollTop = 0;
            body.appendChild(el);
            window.closeAskFollow = function() {
                el.style.display = 'none';
            };
            el.setAttribute('src', '/rest/app/enroll/askFollow?mpid=' + LS.p.mpid);
            el.style.display = 'block';
        });
    };
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {
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
        newRecord !== undefined && newRecord === 'Y' && (url += '&newRecord=Y');
        location.replace(url);
    };
    $scope.addRecord = function(event) {
        var first, page;
        first = PG.firstInput();
        page = first.name;
        page ? $scope.gotoPage(event, page, null, null, false, 'Y') : alert('当前活动没有包含数据登记页');
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
    $http.get(LS.j('get', 'scenario', 'template', 'page')).success(function(rsp) {
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