/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'mage/url',
    ],
    function (Component, placeOrderAction, url) {
        'use strict';
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Dagcoin_PaymentGateway/payment/dagcoin_payment_method'
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('dagcoin/redirect/'));
            },
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            }
        });
    }
);
