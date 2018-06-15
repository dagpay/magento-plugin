# dagpay-magento-plugin

Accept dagcoin payments on your Magento 2 store

DagPay helps you to accept lightning fast dagcoin payments directly from your eCommerce store. Start accepting DagPay payments for your business today and say goodbye to the slow transactions times, fraudulent chargebacks and to the enormous transaction fees.

### Key features of DagPay
* Checkout with DagPay and accept dagcoin payments on your Magento store;
* Prices in your local currency, let customers pay with dagcoin;
* Wallet to wallet transactions - DagPay does not have access to your dagcoins and/or your private keys. Your funds move safely directly to your provided DagWallet address;
* Overview of all your dagcoin payments in the DagPay merchant dashboard at [https://dagpay.io/](https://dagpay.io/) 

## Manual installation (via FTP)

1. Download the [Magento2 extension .zip file](#).
2. Login to your hosting space via a FTP client and navigate to the Magento root directory.
3. Create a new directory app/code if it does not exist and unzip the extension .zip file there.
4. After installing the plugin, make sure to run the following command ```setup:upgrade``` at the command line to edit one of the database tables. If you don't run it, magento will not store the DagPay invoice IDs in the database and the invoice will be unusable.
5. Login to the Magento Admin Panel and from the side navigation go to **System** > **Web Setup Wizard** and select **Module Manager**.
6. Locate the extension in the list and enable it from the Action select dropdown on the right column. Go through the steps as instructed.
7. The extension should now be ready for configuration.

## Setup & Configuration

After installing and activating the DagPay extension in your Magento Admin Panel, complete the setup according to the following instructions:

1. Log in to your DagPay account and head over to **Merchant Tools** > **Integrations** and click **ADD INTEGRATION**.
2. Add your environment Name, Description and choose your Wallet for receiving payments.
3. Add the status URL for server-to-server communication and redirect URLs.
	* The status URL for Magento is [https://`store_base_path`/dagcoin/response](https://store_base_path/dagcoin/response) ( change `store_base_path` with your store domain address, for example [https://mymagentostore.com/dagcoin/response](https://mymagentostore.com/dagcoin/response);
	* Redirect URLs to redirect back to your store from the payment view depending on the final outcome of the transaction (can be set the same for all states). For example [https://mymagentostore.com/success/](https://mymagentostore.com/success/) 
4. Save the environment and copy the generated environment ID, user ID and secret keys and in your Magento Admin panel navigate to **Stores** > **Configuration** > **Sales** > **Payment methods**, look for DagPay payment gateway and enter the keys to the corresponding fields.
	* If you wish to use DagPay test environment, which enables you to test DagPay payments using Testnet Dags, enable Test Mode. Please note, for Test Mode you must create a separate account on [test.dagpay.io](https://test.dagpay.io/), create an integration and generate environment credentials there. Environment credentials generated on [dagpay.io](https://dagpay.io/) are 'Live' credentials and will not work for Test Mode.
5. Save the changes and DagPay should be working on your Magento store.

## Plugin limitations

* This plugin has been developed to work with Magento 2. It will not work for older Magento versions.
* Current plugin version supports USD & EUR currencies in your Magento store. If your store base currency is setup as anything else, the DagPay will return an error and you cannot proceed with payment. We are adding support also in the near future for all other currencies.
