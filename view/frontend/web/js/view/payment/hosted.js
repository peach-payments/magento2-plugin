/*
 * Copyright (c) 2020 Peach Payments. All rights reserved. Developed by Francois Raubenheimer
 */

define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (Component, rendererList) {
    'use strict';

    var availableHostedMethods = [
      'card',
      'eftsecure',
      'masterpass',
      'mobicred',
      'mpesa',
      'ozow',
    ];

    for ( var k = 0; k < availableHostedMethods.length; k++) {
      rendererList.push(
        {
          type: 'peachpayments_hosted_' + availableHostedMethods[k],
          component: 'PeachPayments_Hosted/js/view/payment/method-renderer/hosted'
        }
      );
    }
    return Component.extend({});
  }
);
