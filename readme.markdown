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
