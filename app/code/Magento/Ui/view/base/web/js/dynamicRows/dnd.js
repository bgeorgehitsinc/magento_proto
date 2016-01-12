/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'uiRegistry',
    'uiElement'
], function (ko, $, _, registry, Element) {
    'use strict';

    /**
     * Get element context
     */
    function getContext(elem) {
        return ko.contextFor(elem);
    }

    return Element.extend({
        defaults: {
            rootSelector: '${ $.recordsProvider }:div.admin__field',
            tableSelector: '${ $.rootSelector } -> table.admin__dynamic-rows',
            recordsCache: [],
            draggableElement: {},
            draggableElementClass: '_dragged',
            listens: {
                '${ $.recordsProvider }:elems': 'setCacheRecords'
            }
        },

        /**
         * Initialize component
         *
         * @returns {Object} Chainable.
         */
        initialize: function () {
            _.bindAll(
                this,
                'initTable',
                'mousedownHandler',
                'mousemoveHandler',
                'mouseupHandler'
            );

            this._super();

            $.async(this.tableSelector, this.initTable);

            return this;
        },

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe([
                    'recordsCache'
                ]);

            return this;
        },

        /**
         * Initialize table
         *
         * @param {Object} table - table element
         */
        initTable: function (table) {
            if (!this.table) {
                this.table = $(table);
                this.tableWrapper = this.table.parent();
            }
        },

        /**
         * Mouse down handler
         *
         * @param {Object} data - element data
         * @param {Object} elem - element
         * @param {Object} event - key down event
         */
        mousedownHandler: function (data, elem, event) {
            var recordNode = this.getRecordNode(elem),
                originRecord = $(elem).parents('tr'),
                body = $('body');

            $(recordNode).addClass(this.draggableElementClass);
            $(originRecord).addClass(this.draggableElementClass);
            this.draggableElement.originRow = originRecord;
            this.draggableElement.instance = recordNode = this.processingStyles(recordNode, elem);
            this.draggableElement.instanceCtx = this.getRecord(originRecord[0]);
            this.draggableElement.eventMousedownY = event.pageY;
            this.draggableElement.minYpos =
                this.table.offset().top - originRecord.offset().top +
                this.table.outerHeight() - this.table.find('tbody').outerHeight();
            this.draggableElement.maxYpos =
                this.draggableElement.minYpos +
                this.table.find('tbody').outerHeight() - originRecord.outerHeight();
            this.tableWrapper.append(recordNode);

            body.bind('mousemove', this.mousemoveHandler);
            body.bind('mouseup', this.mouseupHandler);
        },

        /**
         * Mouse move handler
         *
         * @param {Object} event - mouse move event
         */
        mousemoveHandler: function (event) {
            var positionY = event.pageY - this.draggableElement.eventMousedownY,
                processingPositionY = positionY + 'px',
                processingMaxYpos = this.draggableElement.maxYpos + 'px',
                processingMinYpos = this.draggableElement.minYpos + 'px';

            if (positionY > this.draggableElement.minYpos && positionY < this.draggableElement.maxYpos) {
                $(this.draggableElement.instance).css(
                    'transform',
                    'translateY(' + processingPositionY + ')'
                );
            } else if (positionY < this.draggableElement.minYpos) {
                $(this.draggableElement.instance).css(
                    'transform',
                    'translateY(' + processingMinYpos + ')'
                );
            } else if (positionY >= this.draggableElement.maxYpos) {
                $(this.draggableElement.instance).css(
                    'transform',
                    'translateY(' + processingMaxYpos + ')'
                );
            }
        },

        /**
         * Mouse up handler
         */
        mouseupHandler: function () {
            var body = $('body'),
                depElement = this._getDepElement(this.draggableElement.instance),
                depElementCtx = this.getRecord(depElement[0]),
                path = this.draggableElement.instanceCtx.dataScope + '.' +
                       this.draggableElement.instanceCtx.sortNamespace;

            this.draggableElement.instanceCtx.source.set(path, depElementCtx.curSortOrder);
            this.draggableElement.originRow.removeClass(this.draggableElementClass);

            body.unbind('mousemove', this.mousemoveHandler);
            body.unbind('mouseup', this.mouseupHandler);

            this.draggableElement.instance.remove();
            this.draggableElement = {};
        },

        /**
         * Get dependency element
         *
         * @param {Object} curInstance - current element instance
         */
        _getDepElement: function (curInstance) {
            var recordsCollection = this.table.find('tbody tr'),
                curInstancePosition = $(curInstance).position().top,
                i = 0,
                length = recordsCollection.length,
                result,
                rangeStart,
                rangeEnd;

            for (i; i < length; i++) {
                rangeStart = recordsCollection.eq(i).position().top;
                rangeEnd = rangeStart + recordsCollection.eq(i).height();

                if (curInstancePosition > rangeStart && curInstancePosition < rangeEnd) {

                    result = recordsCollection.eq(i);
                }
            }

            return result;
        },

        /**
         * Set default position of draggable element
         *
         * @param {Object} elem - current element instance
         * @param {Object} data - current element data
         */
        _setDefaultPosition: function (elem, data) {
            var originRecord = $(elem).parents('tr'),
                position = originRecord.position();

            position.position = 'absolute';
            position.top += 1;
            position['z-index'] = '999';
            $(data).css(position);
        },

        /**
         * Set records to cache
         *
         * @param {Object} records - record instance
         */
        setCacheRecords: function (records) {
            this.recordsCache(records);
        },

        /**
         * Set styles to draggable element
         *
         * @param {Object} data - data
         * @param {Object} elem - elem instance
         * @returns {Object} instance data.
         */
        processingStyles: function (data, elem) {
            var table = this.table,
                columns = table.find('th'),
                recordColumns = $(data).find('td');

            this._setDefaultPosition(elem, $(data));
            this._setColumnsWidth(columns, recordColumns);
            this._setTableWidth(table, $(data));

            return data;
        },

        /**
         * Set table width.
         *
         * @param {Object} originalTable - original record instance
         * @param {Object} recordTable - draggable record instance
         */
        _setTableWidth: function (originalTable, recordTable) {
            recordTable.outerWidth(originalTable.outerWidth());
        },

        /**
         * Set columns width.
         *
         * @param {Object} originColumns - original record instance
         * @param {Object} recordColumns - draggable record instance
         */
        _setColumnsWidth: function (originColumns, recordColumns) {
            var i = 0,
                length = originColumns.length;

            for (i; i < length; i++) {
                recordColumns.eq(i).outerWidth(originColumns.eq(i).outerWidth());
            }
        },

        /**
         * Get copy original record
         *
         * @param {Object} record - original record instance
         * @returns {Object} draggable record instance
         */
        getRecordNode: function (record) {
            var table = this.table[0].cloneNode(true);

            $(table).find('tr').remove();
            $(table).append($(record).parents('tr')[0].cloneNode(true));

            return table;
        },

        /**
         * Get record context by element
         *
         * @param {Object} elem - original element
         * @returns {Object} draggable record context
         */
        getRecord: function (elem) {
            return this.recordsCache()[getContext(elem).$index()];
        }

    });
});
