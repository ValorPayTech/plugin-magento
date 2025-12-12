require(
    [
        'jquery',
        'mage/validation',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/action/get-totals',
    ], function($, validation, quote, totals,getTotalsAction){

    function togglePaymentDetails(method, skipAjax) {
        $('.payment-details').hide();

        if(method === 'card') {
            $('#card_details').show().find(':input').prop('disabled', false);
            $('#card_details .required').addClass('required-entry');
        }
        if(method === 'ach') {
            $('#ach_details').show().find(':input').prop('disabled', false);
            $('#ach_details .required').addClass('required-entry');
        }

        if (!skipAjax) {
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
        }
    }

     $(document).on('change', 'input[name="payment_method"]', function () {

        if ($(this).val() === 'card') {
            $('#card_details').show();
            $('#ach_details').hide();
        }

        if ($(this).val() === 'ach') {
            $('#ach_details').show();
            $('#card_details').hide();
        }
    });

    $(document).ready(function() {
        $('#card_details').hide();
        $('#ach_details').hide();
    });

    $(document).ready(function() {
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        if (selectedMethod) {
            togglePaymentDetails(selectedMethod, true);
        } else {
            togglePaymentDetails('card', true);
        }
    });

    // On change
    $(document).on('change', 'input[name="payment_method"]', function(){
        togglePaymentDetails($(this).val(), false);
    });
});
