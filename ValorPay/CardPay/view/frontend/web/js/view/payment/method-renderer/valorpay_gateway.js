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

            getCode: function() {
                return 'valorpay_gateway';
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);