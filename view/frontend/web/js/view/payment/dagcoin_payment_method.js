/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'dagcoin',
                component: 'Dagcoin_PaymentGateway/js/view/payment/method-renderer/dagcoin_payment_method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);