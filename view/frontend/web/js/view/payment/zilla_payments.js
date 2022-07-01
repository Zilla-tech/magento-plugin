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
                type: 'zilla_payments',
                component: 'Zilla_Payments/js/view/payment/method-renderer/zilla_payments-method'
            }
        );

        /** Add view logic here if needed */
        
        return Component.extend({});
    }
);