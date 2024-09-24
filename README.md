# Paymento Gateway for WooCommerce

This plugin integrates the Paymento cryptocurrency payment gateway with WooCommerce, allowing store owners to accept cryptocurrency payments easily and securely.

## Features

- Accept cryptocurrency payments in your WooCommerce store
- Support for multiple confirmation types
- Automatic order status updates based on payment status
- Detailed logging for easy troubleshooting

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.0 or higher
- A Paymento merchant account

## Installation

1. Download the plugin zip file from the GitHub repository.
2. Log in to your WordPress admin panel and navigate to Plugins > Add New.
3. Click on the "Upload Plugin" button at the top of the page.
4. Choose the downloaded zip file and click "Install Now".
5. After installation, click "Activate Plugin".

## Configuration

1. Go to WooCommerce > Settings > Payments.
2. Find "Paymento" in the list and click "Manage".
3. Enable the payment method by checking the "Enable/Disable" box.
4. Fill in the following required fields:
   - Title: The name of the payment method displayed to customers
   - Description: A brief description of the payment method displayed to customer, you can mention the assets that you want to accept.
   - API Key: Your Paymento API Key (available in your Paymento merchant dashboard)
   - Secret Key: Your Paymento Secret Key (available in your Paymento merchant dashboard)
5. Choose your preferred Confirmation Type:
   - Redirect Immediately and Hold Invoice (Recommended)
   - Wait for Payment Confirmation
6. Optionally enable Debug Log for troubleshooting.
7. Click "Save changes" to apply your settings.

## Usage

Once configured, the Paymento payment option will appear on your WooCommerce checkout page. Customers can select this option to pay with cryptocurrency.

### Order Process

1. Customer selects Paymento as the payment method and completes the order.
2. They are redirected to the Paymento payment page to complete the transaction.
3. After payment, the customer is redirected back to your store.
4. The order status is updated based on the payment status and your chosen confirmation type.

## Troubleshooting

If you encounter any issues:

1. Enable Debug Log in the plugin settings.
2. Check the WooCommerce status report (WooCommerce > Status > Logs) for detailed error messages.
3. Ensure your API Key and Secret Key are correct.
4. Verify that your server can receive incoming webhooks from Paymento.

## Support

For support, please open an issue on the GitHub repository or contact Paymento support for gateway-specific questions.

## Contributing

Contributions to improve the plugin are welcome. Please fork the repository and submit a pull request with your changes.

## License

This plugin is released under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) license.

