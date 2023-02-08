# ValorPayTech Payment Module for Magento 2 CE

![Packagist Version](https://img.shields.io/packagist/v/valorpay/plugin-magento?label=stable) [![License](http://poser.pugx.org/valorpay/plugin-magento/license)](https://packagist.org/packages/valorpay/plugin-magento)

This is a Payment Module for Magento 2 Community Edition, that gives you the ability to process payments through payment service providers running on ValorPayTech platform.

## Requirements

  * Magento Community Edition (CE) versions (Tested on 2.3.5, 2.3.7, 2.4.2, 2.4.4 and 2.4.5-p1)
  * PHP Versions >= 7.3
  * [ValorPayTech NODE API library] (https://github.com/ValorPay/API.Plugin.PaymentGateway) - (API Url used in Module)

*Note:* this module has been tested only with Magento 2 __Community Edition__, it may not work as intended with Magento 2 __Enterprise Edition__

## Installation (composer)

  * Install __Composer__ - [Composer Download Instructions](https://getcomposer.org/doc/00-intro.md)

  * Install Payment Module

    ```sh
    $ composer require valorpay/plugin-magento
    ```

  * Enable Payment Module

    ```sh
    $ php bin/magento module:enable ValorPay_CardPay
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

  * If you are not running your Magento installation in compiled mode, skip to the next step. If you are running in compiled mode, complete this step:

    ```sh
    $ php bin/magento setup:di:compile
    ```

  * Deploy Magento Static Content (__Execute If needed__)

    ```sh
    $ php bin/magento setup:static-content:deploy
    OR
    $ php bin/magento setup:static-content:deploy -f 
    ```

    To see the full list of [ISO-636](http://www.loc.gov/standards/iso639-2/php/code_list.php) language codes, run:

    ```sh
    $ php magento info:language:list  
    ```

## Installation (manual)

  * [Download the Payment Module archive](https://github.com/ValorPay/plugin-magento/archive/refs/heads/main.zip), unpack it and upload its contents to a new folder ```<root>/app/code/valorpay/cardpay/``` of your Magento 2 installation

  * Enable Payment Module

    ```sh
    $ php bin/magento module:enable ValorPay_CardPay --clear-static-content
    ```

    ```sh
    $ php bin/magento setup:upgrade
    ```

  * Deploy Magento Static Content (__Execute If needed__)

    ```sh
    $ php bin/magento setup:static-content:deploy     
    OR
    $ php bin/magento setup:static-content:deploy -f 

    ```   
    To see the full list of [ISO-636](http://www.loc.gov/standards/iso639-2/php/code_list.php) language codes, run:

    ```sh
    $ php magento info:language:list  
    ```

## Configuration

  * Login inside the __Admin Panel__ and go to ```Stores``` -> ```Configuration``` -> ```Sales``` -> ```Payment Methods```
  * If the Payment Module Panel ```ValorPay``` is not visible in the list of available Payment Methods,
    go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
  * Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```ValorPay POS``` to expand the available settings
  * Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered payment method and additional settings and click ```Save config```

## Test data

If you setup the module with default values, you can use the test data to make a test payment:

  * API Id ```rPWqbGUwUOH37S2IeLa8GYu9tK3K7jNY```
  * API Key ```LjTjMu6Asd6ZfNgnQRIBOr54UFYKF6Pi```
  * EPI ```2235560406```
  * Use Sandbox ``Yes``

### Test card details

Use the following test cards to make successful test payment:

  Test Cards:

    * Visa - 4012881888818888- CVV 999
    * Master- 5146315000000055- CVV 998
    * Amex- 371449635392376 -CVV 9997
    * Discover- 6011000993026909-  CVV 996
    * Diners - 3055155515160018 -CVV 996
    * Jcb - 3530142019945859 -cVV 996
    * Visa-4111 1111 1111 1111 -CVV 999
    * MAESTRO-5044 3393 2466 1725 266 -CVV 998

    Expiry Date - 12/25
    Street Address - 8320
    Zip - 85284

  * AVS (Address Verification Service): Zip or Address or Both
