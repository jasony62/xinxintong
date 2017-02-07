define(['angular'], function(angular) {
    'use strict';
    var ngMod = angular.module('discuss.ui.xxt', []);
    ngMod.service('tmsDiscuss', function() {
        this.showSwitch = function(siteId, threadKey, title, url) {
            var eSwitch;
            eSwitch = document.createElement('div');
            eSwitch.classList.add('tms-switch', 'tms-switch-discuss');
            eSwitch.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                location.href = '/rest/site/fe/discuss?site=' + siteId + '&threadKey=' + threadKey + '&title=' + title;
            }, true);
            document.body.appendChild(eSwitch);
        }
    });
    return ngMod;
});
