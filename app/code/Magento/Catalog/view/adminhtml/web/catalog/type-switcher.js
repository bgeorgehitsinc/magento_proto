/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery"
], function($){

    /**
     * Type Switcher
     *
     * @param {object} data
     * @constructor
     */
    var TypeSwitcher = function (data) {
        this.$type = $('#product_type_id');
        this.$weight = $('#' + data.weight_id);
        this.$weight_switcher = $(data.weight_switcher);
        this.$tab = $('#' + data.tab_id);
        this.productHasWeight = function () {
            return $('input:checked', this.$weight_switcher).val() == data.product_has_weight_flag;
        };
        this.notifyProductWeightIsChanged = function () {
            return $('input:checked', this.$weight_switcher).trigger('change');
        };

        if (!this.productHasWeight()) {
            this.baseType = {
                virtual: this.$type.val(),
                real: 'simple'
            };
        } else {
            this.baseType = {
                virtual: 'virtual',
                real: this.$type.val()
            };
        }
    };
    $.extend(TypeSwitcher.prototype, {
        /** @lends {TypeSwitcher} */

        /**
         * Bind event
         */
        bindAll: function () {
            var self = this,
                $type = this.$type;
            $type.on('change', function() {
                self._switchToType($type.val());
            });

            $('[data-form=edit-product] [data-role=tabs]').on('contentUpdated', function() {
                self._switchToType($type.val());
                self.notifyProductWeightIsChanged();
            });

            $("#product_info_tabs").on("beforePanelsMove tabscreate tabsactivate", function() {
                self._switchToType($type.val());
                self.notifyProductWeightIsChanged();
            });

            $('input', this.$weight_switcher).on('change click', function() {
                if (!self.productHasWeight()) {
                    $type.val(self.baseType.virtual).trigger('change');
                    if ($type.val() != 'bundle') { // @TODO move this check to Magento_Bundle after refactoring as widget
                        self.disableElement(self.$weight);
                    }
                    self.$tab.show().closest('li').removeClass('removed');
                } else {
                    $type.val(self.baseType.real).trigger('change');
                    if ($type.val() != 'bundle') { // @TODO move this check to Magento_Bundle after refactoring as widget
                        self.enableElement(self.$weight);
                    }
                    self.$tab.hide();
                }
            }).trigger('change');
        },

        /**
         * Disable element
         * @param {jQuery|HTMLElement} element
         */
        disableElement: function (element) {
            if (!this._isLocked(element)) {
                element.addClass('ignore-validate').prop('disabled', true);
            }
        },

        /**
         * Enable element
         * @param {jQuery|HTMLElement} element
         */
        enableElement: function (element) {
            if (!this._isLocked(element)) {
                element.removeClass('ignore-validate').prop('disabled', false);
            }
        },

        /**
         * Is element locked
         *
         * @param {jQuery|HTMLElement} element
         * @returns {Boolean}
         * @private
         */
        _isLocked: function (element) {
            return element.is('[data-locked]');
        },

        /**
         * Get element bu code
         * @param {string} code
         * @return {jQuery|HTMLElement}
         */
        getElementByCode: function(code) {
            return $('#attribute-' + code + '-container');
        },

        /**
         * Show/hide elements based on type
         *
         * @param {string} typeCode
         * @private
         */
        _switchToType: function(typeCode) {
            $('[data-apply-to]:not(.removed)').each(function(index, element) {
                var attrContainer = $(element),
                    applyTo = attrContainer.data('applyTo') || [];
                var $inputs = attrContainer.find('select, input, textarea');
                if (applyTo.length === 0 || $.inArray(typeCode, applyTo) !== -1) {
                    attrContainer.removeClass('not-applicable-attribute');
                    $inputs.removeClass('ignore-validate');
                } else {
                    attrContainer.addClass('not-applicable-attribute');
                    $inputs.addClass('ignore-validate');
                }
            });
        }
    });
    // export to global scope
    window.TypeSwitcher = TypeSwitcher;

});
