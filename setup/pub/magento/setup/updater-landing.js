/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

'use strict';
angular.module('updater-landing', ['ngStorage'])
    .controller('updaterLandingController', [
        '$scope',
        '$location',
        '$localStorage',
        function ($scope, $location, $localStorage) {
            $localStorage.$reset();
            $scope.selectLanguage = function () {
                $localStorage.lang = $scope.modelLanguage;
                window.location = 'index.php/' + $scope.modelLanguage + '/index';
            };
        }
    ]);
