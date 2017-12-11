'use strict';
var utilSubmit = {};
utilSubmit.state = {
    modified: false,
    state: 'waiting',
    _cacheKey: false,
    start: function(event, cacheKey) {
        var submitButton;
        if (event) {
            submitButton = event.target;
            if (submitButton.tagName === 'BUTTON' || ((submitButton = submitButton.parentNode) && submitButton.tagName === 'BUTTON')) {
                if (/submit\(.*\)/.test(submitButton.getAttribute('ng-click'))) {
                    var span;
                    this.button = submitButton;
                    span = submitButton.querySelector('span');
                    span.setAttribute('data-label', span.innerHTML);
                    span.innerHTML = '正在提交数据...';
                    this.button.classList.add('submit-running');
                }
            }
        }
        this.state = 'running';
        this._cacheKey = cacheKey ? cacheKey : (new Date * 1);
    },
    finish: function(keep) {
        this.state = 'waiting';
        this.modified = false;
        if (this.button) {
            var span;
            span = this.button.querySelector('span');
            span.innerHTML = span.getAttribute('data-label');
            span.removeAttribute('data-label');
            this.button.classList.remove('submit-running');
            this.button = null;
        }
        if (window.localStorage && !keep) {
            window.localStorage.removeItem(this._cacheKey);
        }
    },
    isRunning: function() {
        return this.state === 'running';
    },
    cache: function(cachedData) {
        if (window.localStorage) {
            var key, val;
            key = this._cacheKey;
            val = angular.copy(cachedData);
            val._cacheAt = (new Date * 1);
            val = JSON.stringify(val);
            window.localStorage.setItem(key, val);
        }
    },
    fromCache: function(keep) {
        if (window.localStorage) {
            var key, val;
            key = this._cacheKey;
            val = window.localStorage.getItem(key);
            if (!keep) window.localStorage.removeItem(key);
            if (val) {
                val = JSON.parse(val);
                /*if (val._cacheAt && (val._cacheAt + 1800000) < (new Date * 1)) {
                    val = false;
                }
                delete val._cacheAt;*/
            }
        }
        return val;
    }
};
module.exports = utilSubmit;
