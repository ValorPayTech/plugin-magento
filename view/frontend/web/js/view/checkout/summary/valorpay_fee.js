/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'knockout'
    ],
    function (Component, quote, priceUtils, totals, ko) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'ValorPay_CardPay/fee/form',
                feeLabel: ko.observable('Surcharge Fee'),
                value: ko.observable(0),
                shouldDisplay: ko.observable(false)
            },
            initialize: function() {
                this._super();
                quote.totals.subscribe((function (newTotals) {
                    this.feeLabel(this.getTitleValue(newTotals));
                    this.value(this.getFormattedTotalValue(newTotals));
                    this.shouldDisplay(this.isTotalDisplayed(newTotals));
                }).bind(this));
            },
            isTotalDisplayed: function(totals) {
                return this.getTotalValue(totals) > 0;
            },
            getTotalValue: function(totals) {
				if (typeof totals.total_segments === 'undefined' || !totals.total_segments instanceof Array) {
                    return 0.0;
                }
                return totals.total_segments.reduce(function (valorpayGatewayTotalValue, currentTotal) {
					return currentTotal.code === 'valorpay_gateway_fee' ? currentTotal.value : valorpayGatewayTotalValue
                }, 0.0);
            },
            getFormattedTotalValue: function(totals) {
                return this.getFormattedPrice(this.getTotalValue(totals));
            },
            getTitleValue: function(totals) {
				if (typeof totals.total_segments === 'undefined' || !totals.total_segments instanceof Array) {
					return '';
				}
				return totals.total_segments.reduce(function (valorpayGatewayTotalValue, currentTotal) {
					return currentTotal.code === 'valorpay_gateway_fee' ? currentTotal.title : valorpayGatewayTotalValue
                }, '');
			}
        });
    }
);