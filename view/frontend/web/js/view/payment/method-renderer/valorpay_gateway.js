/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
		'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'ValorPay_CardPay/payment/form'
            },

            getData: function() {
                return {
                    'method': this.getCode(),
                    'additional_data': {
						'cc_cid': this.creditCardVerificationNumber(),
						'cc_ss_start_month': this.creditCardSsStartMonth(),
						'cc_ss_start_year': this.creditCardSsStartYear(),
						'cc_ss_issue': this.creditCardSsIssue(),
						'cc_type': this.creditCardType(),
						'cc_exp_year': this.creditCardExpYear(),
						'cc_exp_month': this.creditCardExpMonth(),
						'cc_number': this.creditCardNumber(),
                        'avs_zipcode': (this.hasAVSZip() ? document.getElementById("valorpay_gateway_avs_zipcode").value : ''),
                        'avs_address': (this.hasAVSAddress() ? document.getElementById("valorpay_gateway_avs_address").value : '')
                    }
                };
            },

            getCode: function() {
                return 'valorpay_gateway';
            },

            isActive: function() {
                return true;
            },

            hasAVSZip: function () {
				return window.checkoutConfig.payment.ccform.hasAVSZip[this.getCode()];
        	},

            hasAVSAddress: function () {
				return window.checkoutConfig.payment.ccform.hasAVSAddress[this.getCode()];
        	},

            showLogo: function () {
				return window.checkoutConfig.payment.ccform.showLogo[this.getCode()];
        	},

            showTitle: function () {
				return !window.checkoutConfig.payment.ccform.showLogo[this.getCode()];
        	},

            logoImage: function () {
				return window.checkoutConfig.payment.ccform.logoImage[this.getCode()];
        	},

        	getAvsZip: function () {
				return window.checkoutConfig.billingAddressFromData.postcode;
        	},

        	getAvsAddress: function () {
				return window.checkoutConfig.billingAddressFromData.street[0];
        	},

            validate: function() {
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);