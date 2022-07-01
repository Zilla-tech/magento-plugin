define(
        [
            "jquery",
            'mage/url',
            "Magento_Checkout/js/view/payment/default",
            "Magento_Checkout/js/action/place-order",
            "Magento_Checkout/js/model/payment/additional-validators",
            "Magento_Checkout/js/model/quote",
            "Magento_Checkout/js/model/full-screen-loader",
            "Magento_Checkout/js/action/redirect-on-success",
        ],
        function (
                $,
                mageUrl,
                Component,
                placeOrderAction,
                additionalValidators,
                quote,
                fullScreenLoader,
                redirectOnSuccessAction
                ) {
            'use strict';

            return Component.extend({
                defaults: {
                    template: 'Zilla_Payments/payment/zilla_payments'
                },

                redirectAfterPlaceOrder: false,

                isActive: function () {
                    return true;
                },

                /**
                 * Provide redirect to page
                 */
                redirectToCustomAction: function (url) {
                    fullScreenLoader.startLoader();
                    window.location.replace(mageUrl.build(url));
                },

                /**
                 * @override
                 */
                afterPlaceOrder: function () {

                    var checkoutConfig = window.checkoutConfig;
                    var paymentData = quote.billingAddress();
                    var zillaConfiguration = checkoutConfig.payment.zilla_payments;

                    if (checkoutConfig.isCustomerLoggedIn) {
                        var customerData = checkoutConfig.customerData;
                        paymentData.email = customerData.email;
                    } else {
                        paymentData.email = quote.guestEmail;
                    }

                    var quoteId = checkoutConfig.quoteItemData[0].quote_id;
                    var quoteName = checkoutConfig.quoteItemData[0].name;
                    var quoteReference = 'MAGE_' + Math.floor((Math.random() * 1000000000000) + 1) + '_' + quoteId;

                    var _this = this;
                    _this.isPlaceOrderActionAllowed(false);

                    const connect = new Connect();
                    let isSuccessful = false;

                    connect.openNew({
                        publicKey: zillaConfiguration.public_key,
                        clientOrderReference: quoteReference,
                        amount: Math.ceil(quote.totals().grand_total),
                        title: quoteName,
                        onSuccess: function (response) {
                            isSuccessful = true;
                            fullScreenLoader.startLoader();
                            $.ajax({
                                method: "GET",
                                url: zillaConfiguration.api_url + "V1/zilla/verify/" + response.zillaOrderCode + "_-~-_" + quoteId
                            }).success(function (data) {
                                data = JSON.parse(data);
                                console.log(data)
                                if (data.success) {
                                    if (data.data.status === "SUCCESSFUL") {
                                        redirectOnSuccessAction.execute();
                                        return;
                                    }
                                }
                                fullScreenLoader.stopLoader();

                                _this.isPlaceOrderActionAllowed(true);
                                _this.messageContainer.addErrorMessage({
                                    message: "Error, please try again"
                                });
                            });
                        },
                        onClose: function(){
                            if (!isSuccessful) {
                                _this.redirectToCustomAction(zillaConfiguration.recreate_quote_url);
                            }
                        }
                    });
                },

            });
        }
);
