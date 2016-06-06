/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'ko',
    './abstract',
    'Magento_Variable/variables'
], function ($, _, ko, Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementSelector: 'textarea',
            value: '',
            buttonClass: '.action-wysiwyg',
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            template: 'ui/form/field',
            elementTmpl: 'ui/form/element/wysiwyg',
            content:        '',
            showSpinner:    false,
            loading:        false
        },

        /**
         *
         * @returns {} Chainable.
         */
        initialize: function () {
            this._super()
                .initNodeListener();

            return this;
        },

        /**
         *
         * @returns {exports}
         */
        initObservable: function () {
            this._super()
                .observe('value');

            return this;
        },

        /**
         *
         * @returns {} Chainable.
         */
        initNodeListener: function () {
            $.async({
                component: this,
                selector: this.elementSelector
            }, this.setElementNode.bind(this));

            return this;
        },

        /**
         *
         * @param {HTMLElement} node
         */
        setElementNode: function (node) {
            $(node).bindings({
                value: this.value
            });
        },

        /**
         *
         * @returns {exports}
         */
        disable: function () {
            var $button = $(this.buttonClass);

            this.disabled(true);
            $button.attr('disabled', true);
            tinymce.activeEditor.getBody().setAttribute('contenteditable', false);

            return this;
        },

        /**
         *
         * @returns {exports}
         */
        enable: function () {
            var $button = $(this.buttonClass);

            this.disabled(false);
            $button.attr('disabled', false);
            tinymce.activeEditor.getBody().setAttribute('contenteditable', true);

            return this;
        }
    });
});
