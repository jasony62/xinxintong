define(['angular'], function(angular) {
    'use strict';

    function openPlugin(content, cb) {
        var frag, wrap, frm;
        frag = document.createDocumentFragment();
        wrap = document.createElement('div');
        wrap.setAttribute('id', 'frmPlugin');
        frm = document.createElement('iframe');
        wrap.appendChild(frm);
        wrap.onclick = function() {
            wrap.parentNode.removeChild(wrap);
        };
        frag.appendChild(wrap);
        document.body.appendChild(frag);
        if (content.indexOf('http') === 0) {
            window.onClosePlugin = function() {
                wrap.parentNode.removeChild(wrap);
                cb && cb();
            };
            frm.setAttribute('src', content);
        } else {
            if (frm.contentDocument && frm.contentDocument.body) {
                frm.contentDocument.body.innerHTML = content;
            }
        }
    }

    var ngMod = angular.module('favor.ui.xxt', []);
    ngMod.service('tmsFavor', function($http, $q) {
        function byUser(siteId, matter) {
            var url, defer;
            defer = $q.defer();
            url = 'http://' + location.host;
            url += '/rest/site/fe/user/favor/byUser';
            url += "?site=" + siteId;
            url += "&id=" + matter.id;
            url += "&type=" + matter.type;
            url += "&title=" + matter.title;
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }

        function favor(siteId, matter) {
            var url, defer;
            defer = $q.defer();

            url = 'http://' + location.host;
            url += '/rest/site/fe/user/favor/add';
            url += "?site=" + siteId;
            url += "&id=" + matter.id;
            url += "&type=" + matter.type;
            url += "&title=" + matter.title;
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }

        function unfavor(siteId, matter) {
            var url, defer;
            defer = $q.defer();
            url = 'http://' + location.host;
            url += '/rest/site/fe/user/favor/remove';
            url += "?site=" + siteId;
            url += "&id=" + matter.id;
            url += "&type=" + matter.type;
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;
        }

        this.showSwitch = function(siteId, matter) {
            var eSwitch;
            eSwitch = document.createElement('div');
            eSwitch.classList.add('tms-favor-switch');
            eSwitch.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                if (eSwitch.classList.contains('favored')) {
                    unfavor(siteId, matter).then(function(log) {
                        eSwitch.classList.remove('favored');
                    });
                } else {
                    favor(siteId, matter).then(function(log) {
                        eSwitch.classList.add('favored');
                    });
                }
            }, true);
            document.body.appendChild(eSwitch);
            byUser(siteId, matter).then(function(log) {
                if (log) {
                    eSwitch.classList.add('favored');
                }
            });
        };
    });
    
    return ngMod;
});