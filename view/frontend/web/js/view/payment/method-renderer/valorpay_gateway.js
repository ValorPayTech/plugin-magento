/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'jquery/validate',
    'mage/validation',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/action/get-totals'
], function ($, jqueryValidate, validation, Component, getTotalsAction) {
        'use strict';
        var config=window.checkoutConfig.payment.ccform;
        return Component.extend({
            defaults: {
                template: 'ValorPay_CardPay/payment/form',
                save: config ? config.canSaveCard && config.showSaveCard : false,
            },

            getData: function() {
				var avs_zipcode = '';
				var avs_address = '';
				var card_saved=0;
				var terms_checked = 0;
				var selected;
				var vault_token= '';
				var cc_last_4= '';

				if( this.hasAVSZip() && jQuery("#valorpay_gateway_avs_zipcode") !== 'undefined' ) {
					avs_zipcode = jQuery("#valorpay_gateway_avs_zipcode").val();
				}
				if( this.hasAVSAddress() && jQuery("#valorpay_gateway_avs_address") !== 'undefined' ) {
					avs_address = jQuery("#valorpay_gateway_avs_address").val();
				}
				if(jQuery("#valorpay_gateway-save").is(":checked")){
					card_saved=1;
				}
				if(jQuery("#valorpay_gateway_terms_checked").is(":checked")){
				    terms_checked=1;
				}

				selected=jQuery("#valorpay_gateway_cc_id").children("option:selected").val();

				if(selected > this.getStoreCard().length || selected < 0){
					return false;
				}

				var cc_type = this.creditCardType();
        		if(selected && selected < this.getStoreCard().length)
        		{
        			vault_token = this.getStoreCard()[selected]['token'];
        			cc_type = this.getStoreCard()[selected]['cc_type'];
        			cc_last_4 = this.getStoreCard()[selected]['cc_last_4'];
        		}
				if(window.checkoutConfig.payment.valorpay && window.checkoutConfig.payment.valorpay.enableAch){
					var selectedMethod = jQuery('input[name="payment_method"]:checked').val();
					var data = {
					method: this.getCode(),
					additional_data: {
						payment_method_type: selectedMethod || 'card'
					}
					};
					if (selectedMethod === 'card') {
						data.method = this.getCode();
						data.additional_data = {
						'payment_method_type': 'card',
						'cc_cid': this.creditCardVerificationNumber(),
							'cc_ss_start_month': this.creditCardSsStartMonth(),
							'cc_ss_start_year': this.creditCardSsStartYear(),
							'cc_ss_issue': this.creditCardSsIssue(),
							'cc_type': cc_type,
							'cc_exp_year': this.creditCardExpYear(),
							'cc_exp_month': this.creditCardExpMonth(),
							'cc_number': this.creditCardNumber(),
							'avs_zipcode': avs_zipcode,
							'avs_address': avs_address,
							'save': card_saved,
							'terms_checked' : terms_checked,
							'vault_token' : vault_token,
							'cc_last_4' : cc_last_4

						};
					}

					// ACH
					if (selectedMethod === 'ach') {
						data.method = this.getCode();
						data.additional_data = {
							'payment_method_type': 'ach',
							routing_number: jQuery('#routing').val() || '',
							account_number: jQuery('#account').val() || '',
							name_on_account: jQuery('#name_on_account').val() || '',
							account_type: jQuery('#account_type').val() || '',
							entry_class: jQuery('#entry_class').val() || '',
							phone: jQuery('#phone_number').val() || '',
							email: jQuery('#email').val() || ''
						};
					}

					return data;
				}
				else{
					return {
						'method': this.getCode(),
						'additional_data': {
							'payment_method_type': 'card',
							'cc_cid': this.creditCardVerificationNumber(),
							'cc_ss_start_month': this.creditCardSsStartMonth(),
							'cc_ss_start_year': this.creditCardSsStartYear(),
							'cc_ss_issue': this.creditCardSsIssue(),
							'cc_type': cc_type,
							'cc_exp_year': this.creditCardExpYear(),
							'cc_exp_month': this.creditCardExpMonth(),
							'cc_number': this.creditCardNumber(),
							'avs_zipcode': avs_zipcode,
							'avs_address': avs_address,
							'save': card_saved,
							'terms_checked' : terms_checked,
							'vault_token' : vault_token,
							'cc_last_4' : cc_last_4,
						}
					};
				}
                
            },    
            

            getCode: function() {
                return 'valorpay_gateway';
            },

            isActive: function() {
                var self = this;
                if (!window.checkoutConfig.payment.valorpay || !window.checkoutConfig.payment.valorpay.enableAch) {
		            this.updatePaymentMethodForSurcharge('card');
		        }
                return true;
            },

            updatePaymentMethodForSurcharge: function(method) {
                $.ajax({
                    url: '/valor/checkout/updatePaymentMethod',
                    type: 'POST',
                    data: {
                        payment_method_type: method || 'card'
                    },
                    success: function(response) {
                        console.log('Payment method updated:', method);
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);
                    }
                });
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

        	canSaveCard: function () {
        		return window.checkoutConfig.payment.ccform.canSaveCard[this.getCode()];
        	},

        	showSaveCard: function () {
				return window.checkoutConfig.payment.ccform.showSaveCard[this.getCode()];
        	},

        	getStoreCard: function() {
		        return  window.checkoutConfig.payment.ccform.storedCards[this.getCode()];
		    },

		    showCards: function() {
		        if(this.getStoreCard().length && this.showSaveCard()){
		        	jQuery(".hide-if-cards-available").hide();
		        	return true;
		        }
		        return false;
		    },

		    addNewCard: function() {
		    	
		        var selected=jQuery("#valorpay_gateway_cc_id").children("option:selected").val();

		        if(selected && selected==this.getStoreCard().length)
		        {
		        	jQuery(".hide-if-cards-available").show();
		        	jQuery("#card_logo").html("");

		        }else if(selected && selected != this.getStoreCard().length){

		        	jQuery(".hide-if-cards-available").hide();
		        	var cardLogo="<img src='"+this.getStoreCard()[selected]['type_url']+"' width='"+this.getStoreCard()[selected]['type_width']+"' height='"+this.getStoreCard()[selected]['type_height']+"'/>";
		    		jQuery("#card_logo").html(cardLogo);

		        }else{

		        	jQuery(".hide-if-cards-available").hide();
		        	jQuery("#card_logo").html("");
		        	
		        }
			  	jQuery(".hide-if-cards-available input").val('').keyup();
		        jQuery('.hide-if-cards-available select').prop('selectedIndex',0);

		        if (!window.checkoutConfig.payment.valorpay || !window.checkoutConfig.payment.valorpay.enableAch) {
		            this.updatePaymentMethodForSurcharge('card');
		        }
		    },

		    getCardList: function() {
		    	
		        	var cards=[];
		        	jQuery.each(this.getStoreCard(),function(index, val){
		        		//cards.push("xxxx"+val['cc_last_4']+", "+val['cc_exp_month']+"/"+val['cc_exp_year']+", CVV-"+val['cc_cid']);
		        		cards.push("ending in "+val['cc_last_4']+" ("+val['cc_name']+")");
		        	});
		        	cards.push("New Card");

		            return _.map(cards, function(value, key) {
		        		return {
		        			'value':key,
		        			'type' : value
		        		}
		        	});
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
            },

            initObservable: function () {
                this._super();

                jQuery.validator.addMethod('validate-card-type', function(value, element, params) {
                    return true;
                }, jQuery.mage.__('Please select a valid card type.'));

                jQuery.validator.addMethod('validate-card-number', function(value, element, params) {
                    return true;
                }, jQuery.mage.__('Please enter a valid card number.'));

                jQuery.validator.addMethod('validate-card-cvv', function(value, element, params) {
                    return true;
                }, jQuery.mage.__('Please enter a valid CVV.'));

                jQuery.validator.addMethod('validate-phone-or-email', function(value, element, params) {
                    var otherField = jQuery(params);
                    return value.trim() !== '' || otherField.val().trim() !== '';
                }, jQuery.mage.__('Please enter either phone number or email.'));

                return this;
            },

            initialize: function() {
                this._super();
                var self = this;

                jQuery(document).ready(function() {
                    jQuery(document).on('change', 'input[name="payment_method"]', function() {
                        setTimeout(function() {
                            var button = jQuery('.action.primary.checkout');
                            if (button.length) {
                                button.prop('disabled', self.isPlaceOrderButtonDisabled());
                            }
                        }, 100);
                    });
                    jQuery(document).on('change', '#valorpay_gateway_cc_id', function() {
                        setTimeout(function() {
                            var button = jQuery('.action.primary.checkout');
                            if (button.length) {
                                button.prop('disabled', self.isPlaceOrderButtonDisabled());
                            }
                        }, 100);
                    });
                });

                return this;
            },

            isPlaceOrderButtonDisabled: function() {
                if (window.checkoutConfig.payment.valorpay && window.checkoutConfig.payment.valorpay.enableAch) {

                    var cardSelected = jQuery('input[name="payment_method"][value="card"]').is(':checked');
                    var achSelected = jQuery('input[name="payment_method"][value="ach"]').is(':checked');

                    if (!cardSelected && !achSelected) {
                        return true;
                    }

                    if (cardSelected) {
                        var dropdown = jQuery('#valorpay_gateway_cc_id');
                        if (dropdown.length && dropdown.is(':visible')) {
                            var selected = dropdown.val();
                            return !selected || selected === '' || selected === undefined;
                        }
                        return false;
                    }

                    return false;
                } else {
                    var dropdown = jQuery('#valorpay_gateway_cc_id');
                    if (dropdown.length && dropdown.is(':visible')) {
                        var selected = dropdown.val();
                        return !selected || selected === '' || selected === undefined;
                    }
                    return false;
                }
            }
        });
    }
);
