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
				var avs_zipcode = '';
				var avs_address = '';
				if( this.hasAVSZip() && jQuery("#valorpay_gateway_avs_zipcode") !== 'undefined' ) {
					avs_zipcode = jQuery("#valorpay_gateway_avs_zipcode").val();
				}
				if( this.hasAVSAddress() && jQuery("#valorpay_gateway_avs_address") !== 'undefined' ) {
					avs_address = jQuery("#valorpay_gateway_avs_address").val();
				}
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
                        'avs_zipcode': avs_zipcode,
                        'avs_address': avs_address
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

				var zipcode = '';
				jQuery("input").each(function() {
					if( jQuery(this).attr('name') == 'postcode' ) {
						zipcode = jQuery(this).val();
					}
				});

				if( zipcode != '' ) return zipcode;

				try {

					return window.checkoutConfig.billingAddressFromData.postcode;

				} catch(e) {

					try {

						return window.checkoutConfig.payment.ccform.getPostcode[this.getCode()];

					} catch(e) {

						return '';

					}

				}

        	},

        	getAvsAddress: function () {

				var streetaddress = '';
				jQuery("input").each(function() {
					if( jQuery(this).attr('name') == 'street[0]' ) {
						streetaddress = jQuery(this).val();
					}
				});

				if( streetaddress != '' ) return streetaddress;

				try {

					return window.checkoutConfig.billingAddressFromData.street[0];

				} catch(e) {

					try {

						return window.checkoutConfig.payment.ccform.getStreet[this.getCode()];

					} catch(e) {

						return '';

					}

				}

        	},

            validate: function() {
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);