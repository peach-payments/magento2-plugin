# PeachPayments Hosted Magento2 module

All-in-One payment solution for emerging African markets.

## Installation (composer)

  * Install __Composer__ - [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

  * Install __PeachPayments Hosted module__

    * Install Payment Module

        ```sh
        $ composer require peachpayments/magento2-plugin
        ```

    * Enable Payment Module

        ```sh
        $ php bin/magento module:enable PeachPayments_Hosted
        ```

        ```sh
        $ php bin/magento setup:upgrade
        ```
    * Deploy Magento Static Content (__Execute If needed__)

        ```sh
        $ php bin/magento setup:static-content:deploy
        ```

## Configuration

  * Login to the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
  * If the Payment Module Panel ```Peach Payments``` is not visible in the list of available Payment Methods,
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
  * Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```Peach Payments``` to expand the available settings
  * Set ```Enabled``` to ```Yes```, set the correct credentials, select your preferred settings and click ```Save config```

## PHP Compatibility

Module only support these PHP versions: `7.3` and `7.4` (PHP 7.1 and 7.2 has been depreciated, please use version 1.0.7, this excludes GraphQL)

## Events

There are two events available for usage to supplement your 3rd party tracking. On order success you can use `peachpayments_order_succeed`, on order failure use `peachpayments_order_failed`. Both events will have a data object as: `result`. Please take special care to prevent duplicates in your observer. These events will be dispatched on both the customer facing and webhook controllers.

## GraphQL

To allow for a graphql flow, please use the core magento `setPaymentMethodOnCart` and `placeOrder` methods.

After a successful order id has been returned use the method `getPeachHostedRedirectUrl` to redirect the customer to
the checkout page (at this stage the order should be on the pending state). Deconstruct the json object from `form_data`
and submit as _POST parameters to the specified `form_link` url.

After the customer has returned to the `return_url` you specified, do a call to the method `getPeachHostedOrderStatus`
to get a success (1) or declined (2) status, retry on an interval if the status code is 3.

### Examples:
```graphql

# set peach as payment method
mutation setPaymentMethodOnCart($cartId: String!){
  setPaymentMethodOnCart(input: {
      cart_id: $cartId
      payment_method: {
          code: "peachpayments_hosted_card"
      }
  }) {
    cart {
      selected_payment_method {
        code
      }
    }
  }
}

# place order
mutation placeOrder($cartId: String!) {
  placeOrder(input: {cart_id: $cartId}) {
    order {
      order_number
    }
  }
}

# Get redirect url and data
query getPeachHostedRedirectUrl($cartId: String!){
  getPeachHostedRedirectUrl(input: {
    cart_id: $cartId,
    return_url: "https://my.app.pwa/payment/welcome-back.html"
  }) {
    form_data
    form_link
  }
}

# Get staus
query getPeachHostedOrderStatus($orderId: String!){
  getPeachHostedOrderStatus(input: { order_id: $orderId }) {
    status
  }
}

```

