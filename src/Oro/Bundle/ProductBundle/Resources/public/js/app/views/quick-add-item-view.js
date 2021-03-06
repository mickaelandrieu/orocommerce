define(function(require) {
    'use strict';

    var QuickAddItemView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var BaseModel = require('oroui/js/app/models/base/model');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    QuickAddItemView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
            defaultQuantity: 1,
            unitErrorText: 'oro.product.validation.unit.invalid'
        },

        elements: {
            'sku': '[data-name="field__product-display-name"]',
            'skuHiddenField': '[data-name="field__product-sku"]',
            'quantity': '[data-name="field__product-quantity"]',
            'unit': '[data-name="field__product-unit"]',
            'remove': '[data-role="row-remove"]'
        },

        modelElements: {
            sku: 'sku',
            skuHiddenField: 'skuHiddenField',
            quantity: 'quantity',
            unit: 'unit'
        },

        modelAttr: {
            sku: '',
            skuHiddenField: '',
            quantity: 0,
            unit: null
        },

        modelEvents: {
            'sku': ['change', 'onSkuChange'],
            'quantity': ['change', 'publishModelChanges'],
            'unit': ['change', 'publishModelChanges'],
            'units': ['change', 'setUnits']
        },

        listen: {
            'autocomplete:productFound mediator': 'updateModel',
            'autocomplete:productNotFound mediator': 'updateModelNotFound',
            'quick-add-item-additional-fields:model-change mediator': 'updateModel',
            'quick-add-form:rows-ready mediator': 'updateModelFromData',
            'quick-add-form:clear mediator': 'clearSku'
        },

        templates: {},

        validator: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            QuickAddItemView.__super__.initialize.apply(this, arguments);
            this.initModel(options);
            this.initializeElements(options);
            this.clearModel();
            this.clearSku();
            this.setUnits();
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                this.model = new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            delete this.templates;
            delete this.validator;
            QuickAddItemView.__super__.dispose.apply(this, arguments);
        },

        onSkuChange: function() {
            if (this.model.get('sku') === '' && this.model.get('sku_changed_manually')) {
                this.clearModel();
            } else {
                this.publishModelChanges();
            }
        },

        updateModelFromData: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            var canBeUpdated = this.canBeUpdated(data.item);
            if (this.model.get('sku') && !canBeUpdated) {
                return;
            }

            this.model.set({
                'sku': obj.sku,
                'skuHiddenField': obj.sku,
                'quantity_changed_manually': true,
                'quantity': canBeUpdated ?
                    parseFloat(this.model.get('quantity')) + parseFloat(obj.quantity) : obj.quantity,
                'unit_deferred': obj.unit
            });

            if (canBeUpdated) {
                mediator.trigger('quick-add-copy-paste-form:update-product', obj);
            }

            this.updateUI();
        },

        updateModel: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            if (data.item.sku) {
                this.model.set({
                    'sku': data.item.sku
                }, {
                    silent: true
                });

                this.model.set({
                    'skuHiddenField': data.item.sku
                });
            }

            this.model.set({
                'quantity': data.item.quantity || this.model.get('quantity') || this.options.defaultQuantity,
                'units': data.item.units,
                'product_units': data.item.units
            });
        },

        updateModelNotFound: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            this.model.set({
                'skuHiddenField': obj.sku
            });
            this.updateUI();
        },

        clearSku: function() {
            this.model.set({
                'sku_changed_manually': true,
                'sku': '',
                'skuHiddenField': ''
            });
        },

        clearModel: function() {
            this.model.set({
                sku_changed_manually: false,
                quantity: null,
                units: null,
                unit: null,
                unit_deferred: null
            });
        },

        checkEl: function($el) {
            return $el !== undefined &&
                (this.$el.attr('id') === $el.attr('id') ||
                this.$el.attr('id') === $el.parents('.quick-order-add__row').attr('id'));
        },

        canBeUpdated: function(item) {
            return this.model.get('sku') === item.sku && this.model.get('unit') === item.unit;
        },

        setUnits: function() {
            var $unitEl = this.getElement('unit');

            $unitEl
                .prop('disabled', true)
                .empty();

            var units = this.model.get('units');
            if (units) {
                _.each(units, function(unit) {
                    $unitEl.append(this.renderUnitOption(unit));
                }, this);
                $unitEl.prop('disabled', false);
            } else {
                $unitEl.prepend(this.renderUnitOption(
                    __('oro.product.frontend.quick_add.form.unit.default'))
                );
                this.model.set('unit_placeholder', $unitEl.val());
            }

            var unit = this.model.get('unit_deferred') || $unitEl.val();

            this.model.set('unit', unit);
            this.updateUI();
        },

        publishModelChanges: function() {
            mediator.trigger('quick-add-item:model-change', {item: this.model.attributes, $el: this.$el});
        },

        showUnitError: function() {
            this.getValidator().showErrors(this.getUnitError());
        },

        getValidator: function() {
            if (this.validator === null) {
                this.validator = this.$el.closest('form').validate();
            }

            return this.validator;
        },

        getUnitError: function() {
            var error = [];
            error[this.getElement('unit').attr('name')] =
                __(this.options.unitErrorText, {unit: this.model.get('unit') || this.model.get('unit_deferred')});
            return error;
        },

        renderUnitOption: function(unit) {
            if (!unit) {
                return;
            }

            if (!this.templates.unitOption) {
                this.templates.unitOption = _.template(
                    '<option value="<%= unit %>"><%= unit %></option>'
                );
            }

            return this.templates.unitOption({unit: unit});
        },

        unitInvalid: function() {
            return this.model.has('units') &&
                !_.has(this.model.get('units'), this.model.get('unit'));
        },

        updateUI: function() {
            this.getElement('sku').trigger('blur');
            this.getElement('unit').inputWidget('refresh');

            if (this.model.get('sku') && this.unitInvalid()) {
                this.showUnitError();
                _.defer(_.bind(function() {
                    mediator.trigger('quick-add-form-item:unit-invalid', {$el: this.$el});
                }, this));
            } else if (this.model.get('sku') && this.model.get('units')) {
                mediator.trigger('quick-add-form-item:item-valid', {item: this.model.attributes});
            }

            this.getElement('remove').toggle(this.model.get('sku') !== this.modelAttr.sku);
        }
    }));

    return QuickAddItemView;
});
