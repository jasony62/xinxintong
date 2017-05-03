'use strict';
var ngMod = angular.module('modal.ui.xxt', []);
ngMod.service('tmsModal', ['$rootScope', '$compile', '$q', '$controller', function($rootScope, $compile, $q, $controller) {
    this.open = function(modalOptions) {
        var modalResultDeferred = $q.defer(),
            modalClosedDeferred = $q.defer();

        var modalInstance = {
            result: modalResultDeferred.promise,
            closed: modalClosedDeferred.promise,
            close: function(result) {
                document.body.removeChild(modalDomEl[0]);
                modalResultDeferred.resolve(result);
            },
            dismiss: function(reason) {
                document.body.removeChild(modalDomEl[0]);
                modalClosedDeferred.resolve(reason);
            }
        };

        var modalScope;
        modalScope = $rootScope.$new(true);
        if (modalOptions.controller) {
            $controller(modalOptions.controller, { $scope: modalScope, $tmsModalInstance: modalInstance });
        }

        var contentDomEl, dialogDomEl, backdropDomEl, modalDomEl;
        /* content */
        contentDomEl = angular.element('<div></div>');
        contentDomEl.attr({
            'class': 'modal-content',
            'ng-style': '{\'z-index\':1060}'
        }).append(modalOptions.template);

        /* dialog */
        dialogDomEl = angular.element('<div></div>');
        dialogDomEl.attr({
            'class': 'modal-dialog'
        }).append(contentDomEl);

        /* backdrop */
        backdropDomEl = angular.element('<div></div>');
        backdropDomEl.attr({
            'class': 'modal-backdrop',
            'ng-style': '{\'z-index\':1040}'
        });

        /* modal */
        modalDomEl = angular.element('<div></div>');
        modalDomEl.attr({
            'class': 'modal',
            'ng-style': '{\'z-index\':1050}',
            'tabindex': -1
        }).append(dialogDomEl).append(backdropDomEl);

        $compile(modalDomEl)(modalScope);
        document.body.appendChild(modalDomEl[0]);

        return modalInstance;
    };
}]);
