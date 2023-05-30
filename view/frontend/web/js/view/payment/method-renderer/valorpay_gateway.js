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

        		return {
                    'method': this.getCode(),
                    'additional_data': {
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
		    },

		    getCardList: function() {
		    	
		        	var cards=[];
		        	jQuery.each(this.getStoreCard(),function(index, val){
		        		//cards.push("xxxx"+val['cc_last_4']+", "+val['cc_exp_month']+"/"+val['cc_exp_year']+", CVV-"+val['cc_cid']);
		        		cards.push("ending in "+val['cc_last_4']+" (expiry "+val['cc_exp_month']+"/"+val['cc_exp_year']+")");
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
            }
        });
    }
);