# Tabaix Affiliate Cards

A WordPress plugin that shows Amazon affiliate product cards — single product, product list, or comparison table — powered by your own Amazon Creators API credentials, with a manual mode that works without the API. No third-party server in between.

Built for [tabaix.com](https://tabaix.com/). Free to use and share (GPL-2.0-or-later).

## Features

- **Three layouts** — single product card, product list (grid, up to 6), comparison table (up to 4).
- **Manual mode — works today, no API needed.** In the block, type the ASIN, title, image and features once. The card links to Amazon with your tracking ID. Prices are never typed by hand — the button says "Check price on Amazon".
- **API mode — live data when your account is eligible.** Save your Creators API credentials in the settings page; the block can then fetch title/image and the page shows Amazon's live price automatically (cached 24 hours, so the API is not hammered). Keyword search in the editor also runs on the API.
- **Server (App) mode — safest, recommended.** Run the companion Tabaix Affiliate Server app on a subdomain of your own hosting. The Amazon secret lives only there; this plugin calls it with a per-site key that can fetch product data but can never reveal the secret. If a site is ever hacked, you remove its key on the server and the Amazon credentials stay safe.
- **Your keys stay yours** — credentials live only in your own WordPress database (`tac_options`). API calls go from your server straight to `api.amazon.com` and `creatorsapi.amazon`. The access token is cached briefly (transient) so logins are not repeated.

## Requirements

- WordPress. No extra libraries — the plugin only uses core WordPress functions plus the Amazon Creators API when you enable API mode.

## Installation

1. Download the [latest release](https://github.com/Tabaix/tabaix-affiliate-cards/releases) (or clone this repo).
2. Upload the `tabaix-affiliate-cards` folder to `/wp-content/plugins/`, **or** in WP Admin go to **Plugins > Add New > Upload Plugin** and install the ZIP.
3. Activate **Tabaix Affiliate Cards**.

## Usage

1. Go to **Affiliate Cards** in the wp-admin menu.
2. Choose your store, enter your **Tracking ID**.
3. For API mode: in Associates Central open **Creators API**, copy your application's **Credential ID** and **Secret**, and paste them into the settings here — yourself, on this page only. Never send them in chat or email.
4. Press **Test API connection**. It reports exactly what Amazon answers.
5. Add the "Tabaix Affiliate Card" block to a post and pick a layout.

## FAQ

**Amazon says "not eligible" when I test the API. Is the plugin broken?**
No. Amazon allows live product data (PA-API through the Creators API) only when the Associates account has at least 10 qualifying sales in the last 30 days. Returned orders do not count. Until then, Amazon answers "not eligible" — that is an account rule, not a plugin bug. Manual-mode cards keep working, and the same cards start showing live prices by themselves once the account is eligible again.

**Where do my Amazon credentials live?**
Only in your own WordPress database (`tac_options`). In App mode they never live on the site at all — only on your own Affiliate Server.

## Links

- [tabaix.com](https://tabaix.com/)
- [Tabaix agency portfolio](https://agency.tabaix.com/portfolio/)

## Changelog

### 1.1.1

- Current release, as installed on tabaix.com.

## License

GPL-2.0-or-later — see https://www.gnu.org/licenses/gpl-2.0.html
