=== Campay Woocommerce Payment Gateway ===
Tags: payments, mobile money, MTN Money, Orange Money, Woocommerce, WordPress
Requires Woocommerce at least : 2.3
Requires at least: 4.9
Tested up to: 6.1
Requires PHP: 7.0
Stable tag: 1.1.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

CamPay is a Fintech service of the company TAKWID
GROUP which launched its financial services in Cameroon
from January 2021.

We provide businesses and institutions with solutions for
collecting and transferring money online, via primarily
Mobile Money(MTN and Orange). 

With CamPay, simplify the purchasing experience for
your customers thanks to our mobile money
payment solutions, accessible via your website
and/or mobile application.

= How it functions backend =
* Install CamPay Payment Gateway in your website with Woocommerce activated
* Active the plugin
* Go into Woocommerce payment methods setting and activate CamPay Payment Gateway
* Set your App username and password (get it from https://campay.net/)
* Save your settings.

= How it function frontend =
* On Checkout page select CamPay Payment Gateway as your payment method.
* Input phone number to use for the payment (it must be a 9 digits valide MTN or Orange phone number)
* Click Command button
* On your mobile phone confirm payment 
* You will be automatically redirected if payment was successfull or receive a failure message in case payment failed.

== Installation ==

= Minimum Requirements =
* Woocommerce 2.3 or greater is recommended
* PHP 7.2 or greater is recommended
* MySQL 5.6 or greater is recommended

= Updating =
Automatic updates should work smoothly, but we still recommend you back up your site.

== Contributors & Developers ==
CamPay Payment Gateway REST API was develop by CamPay with INNO DS as contributor to develop the WordPress plugin for Woocommerce

== Changelog ==
1.1.3 2022-25-11
* Translation ready
* en_GB translation completed
* Plugin tested on latest version of WordPress
1.1.2 2022-14-07
* Credit or Debit card payment option added
1.1.1 2022-04-06
* Corrected SSL bug in live mode causing transactions now to initiate
1.1.0 2022-17-05 2:20 PM CAT
* Modal HTML and Javascript controls added only if is checkout page. 
1.0.9 2022-17-05
* jQuery replaced by javascript on checkout page to command opening of pending transaction modal
1.0.8 2022-01-05
* Correction of modal bug on checkout
1.0.7 2022-09-04
* Correction of bug on checkout page
1.0.6 2022-03-04
* Message of payment processing added.
1.0.5 2021-10-10
* manual USD and EURO added, user can now activate support of US dollar and Euro in setting. Default conversion rates are 550 for USD and 650 for Euro
* auto convertion removed.
1.0.4 2021-09-27
* token error display updated
1.0.3 2021-09-18
* number field css updated
1.0.2 2021-08-06
* Currency convertion included
* Corrected process order button being disable when different payment method selected
* Activating plugin only when woocommerce is present and active
1.0.1 2021-05-31
* Number field updated (pattern validation and text field type)
1.0.1 2021-08-03
* Changing plugin name to Campay Woocommerce Payment Gateway
1.0.0 - 2021-03-09
*Dev - Development of the plugin