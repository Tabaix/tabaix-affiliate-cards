<?php
/**
 * Plugin Name:       Tabaix Affiliate Cards
 * Plugin URI:        https://tabaix.com
 * Description:       Amazon affiliate product cards (single, list, comparison table) powered by your own Amazon Creators API credentials, with a manual mode that works without the API. No third-party server in between.
 * Version:           1.1.1
 * Author:            Tabaix Enterprises
 * Text Domain:       tabaix-affiliate-cards
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TAC_VERSION', '1.1.1');
define('TAC_TOKEN_URL', 'https://api.amazon.com/auth/o2/token');
define('TAC_API_BASE', 'https://creatorsapi.amazon');
define('TAC_SCOPE', 'creatorsapi::default');

/* -------------------------------------------------------------------------
 * Options
 * ---------------------------------------------------------------------- */

function tac_default_options() {
    return array(
        'store'       => 'www.amazon.sa',
        'tracking_id' => '',
        'mode'        => 'direct',
        'app_url'     => '',
        'site_key'    => '',
        'client_id'   => '',
        'client_secret' => '',
    );
}

function tac_options() {
    $saved = get_option('tac_options', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return wp_parse_args($saved, tac_default_options());
}

function tac_stores() {
    return array(
        'www.amazon.sa'  => 'Saudi Arabia — amazon.sa',
        'www.amazon.ae'  => 'UAE — amazon.ae',
        'www.amazon.com' => 'USA — amazon.com',
        'www.amazon.co.uk' => 'UK — amazon.co.uk',
        'www.amazon.in'  => 'India — amazon.in',
        'www.amazon.com.au' => 'Australia — amazon.com.au',
        'www.amazon.ca'  => 'Canada — amazon.ca',
        'www.amazon.de'  => 'Germany — amazon.de',
    );
}

add_action('admin_init', function () {
    register_setting('tac_settings', 'tac_options', array(
        'sanitize_callback' => 'tac_sanitize_options',
    ));
});

function tac_sanitize_options($input) {
    $out = tac_default_options();
    if (is_array($input)) {
        $stores = tac_stores();
        if (isset($input['store']) && isset($stores[$input['store']])) {
            $out['store'] = $input['store'];
        }
        if (isset($input['tracking_id'])) {
            $out['tracking_id'] = sanitize_text_field(wp_unslash($input['tracking_id']));
        }
        if (isset($input['mode']) && in_array($input['mode'], array('direct', 'app'), true)) {
            $out['mode'] = $input['mode'];
        }
        if (isset($input['app_url'])) {
            $out['app_url'] = esc_url_raw(wp_unslash($input['app_url']));
        }
        if (isset($input['site_key'])) {
            $out['site_key'] = sanitize_text_field(wp_unslash($input['site_key']));
        }
        if (isset($input['client_id'])) {
            $out['client_id'] = sanitize_text_field(wp_unslash($input['client_id']));
        }
        if (isset($input['client_secret'])) {
            $secret = sanitize_text_field(wp_unslash($input['client_secret']));
            // Keep the old secret when the field is submitted empty.
            if ($secret !== '') {
                $out['client_secret'] = $secret;
            } else {
                $old = tac_options();
                $out['client_secret'] = $old['client_secret'];
            }
        }
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Admin settings page
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_menu_page(
        'Tabaix Affiliate Cards',
        'Affiliate Cards',
        'manage_options',
        'tabaix-affiliate-cards',
        'tac_render_settings_page',
        'dashicons-cart',
        58
    );
});

function tac_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $o = tac_options();
    $test = get_transient('tac_test_result');
    ?>
    <div class="wrap">
        <h1>Tabaix Affiliate Cards</h1>
        <p>Product cards that call <strong>your own</strong> Amazon Creators API credentials directly. Nothing goes through any third-party server.</p>

        <?php if (is_array($test)) : ?>
            <div class="notice <?php echo $test['ok'] ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                <p><strong>API test:</strong> <?php echo esc_html($test['message']); ?></p>
            </div>
            <?php delete_transient('tac_test_result'); ?>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('tac_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="tac_store">Amazon store</label></th>
                    <td>
                        <select id="tac_store" name="tac_options[store]">
                            <?php foreach (tac_stores() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($o['store'], $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_tracking">Tracking ID</label></th>
                    <td>
                        <input id="tac_tracking" type="text" class="regular-text" name="tac_options[tracking_id]" value="<?php echo esc_attr($o['tracking_id']); ?>" placeholder="e.g. tabaix-21" />
                        <p class="description">Your Associates tracking ID. It is added to every product link automatically.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_mode">Data mode</label></th>
                    <td>
                        <select id="tac_mode" name="tac_options[mode]">
                            <option value="direct" <?php selected($o['mode'], 'direct'); ?>>Direct — API keys on this site</option>
                            <option value="app" <?php selected($o['mode'], 'app'); ?>>Tabaix App — keys stay on your server app (safer)</option>
                        </select>
                        <p class="description">App mode is recommended: the Amazon secret never lives in this site's database. The plugin calls your own Tabaix Affiliate Server with a site key — if this site is ever compromised, you remove its key on the server and the Amazon credentials stay safe.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_app_url">App URL</label></th>
                    <td>
                        <input id="tac_app_url" type="url" class="regular-text" name="tac_options[app_url]" value="<?php echo esc_attr($o['app_url']); ?>" placeholder="https://affiliate-api.tabaix.com" />
                        <p class="description">Only used in App mode. The address where you installed the Tabaix Affiliate Server.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_site_key">Site key</label></th>
                    <td>
                        <input id="tac_site_key" type="text" class="regular-text" name="tac_options[site_key]" value="<?php echo esc_attr($o['site_key']); ?>" autocomplete="off" />
                        <p class="description">Must match one of the <code>site_keys</code> in the server app's config.php. This key can only fetch product data — it can never reveal the Amazon secret.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_client">Creators API — Credential ID</label></th>
                    <td>
                        <input id="tac_client" type="text" class="regular-text" name="tac_options[client_id]" value="<?php echo esc_attr($o['client_id']); ?>" placeholder="amzn1.application-oa2-client.…" autocomplete="off" />
                        <p class="description">From Associates Central → Creators API → your application. Enter it here yourself — never share it in chat or email.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tac_secret">Creators API — Secret</label></th>
                    <td>
                        <input id="tac_secret" type="password" class="regular-text" name="tac_options[client_secret]" value="" placeholder="<?php echo $o['client_secret'] !== '' ? 'Saved — leave blank to keep it' : 'amzn1.oa2-cs.v1.…'; ?>" autocomplete="new-password" />
                        <p class="description">Stored in your own WordPress database only. Leave blank to keep the saved secret.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save settings'); ?>
        </form>

        <hr />
        <h2>API status</h2>
        <p>Run a live check: the plugin asks Amazon for one product using your credentials and reports exactly what Amazon answers.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('tac_test_api'); ?>
            <input type="hidden" name="action" value="tac_test_api" />
            <?php submit_button('Test API connection', 'secondary', 'submit', false); ?>
        </form>
        <p class="description">Note: Amazon only allows live product data when the Associates account is eligible — currently that means at least 10 qualifying sales in the last 30 days. If Amazon answers "not eligible", the plugin is fine; the account has to reach the sales threshold first. Manual-mode cards keep working either way.</p>
    </div>
    <?php
}

add_action('admin_post_tac_test_api', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed.');
    }
    check_admin_referer('tac_test_api');
    // A real product ASIN on amazon.sa, used only to test the connection.
    $result = tac_get_item('B0CZ9M6X8Q', true);
    if (is_wp_error($result)) {
        $msg = $result->get_error_message();
        if (stripos($msg, 'NotEligible') !== false || stripos($msg, 'AssociateNotEligible') !== false) {
            $msg = 'Amazon answered, but this account is not eligible for live data yet (needs 10 qualifying sales in the last 30 days). Credentials are working — sales are the remaining step. Raw answer: ' . $msg;
        }
        set_transient('tac_test_result', array('ok' => false, 'message' => $msg), 120);
    } else {
        set_transient('tac_test_result', array(
            'ok' => true,
            'message' => 'Live. Amazon returned: ' . ($result['title'] !== '' ? $result['title'] : 'product data') . ($result['price'] !== '' ? ' — ' . $result['price'] : ''),
        ), 120);
    }
    wp_safe_redirect(admin_url('admin.php?page=tabaix-affiliate-cards'));
    exit;
});

/* -------------------------------------------------------------------------
 * Amazon Creators API client (your own credentials, direct calls)
 * ---------------------------------------------------------------------- */

function tac_get_access_token($force = false) {
    if (!$force) {
        $cached = get_transient('tac_lwa_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
    }
    $o = tac_options();
    if ($o['client_id'] === '' || $o['client_secret'] === '') {
        return new WP_Error('tac_no_creds', 'Creators API credentials are not saved yet (Affiliate Cards settings).');
    }
    $response = wp_remote_post(TAC_TOKEN_URL, array(
        'timeout' => 15,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'),
        'body' => array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $o['client_id'],
            'client_secret' => $o['client_secret'],
            'scope'         => TAC_SCOPE,
        ),
    ));
    if (is_wp_error($response)) {
        return new WP_Error('tac_http', 'Could not reach Amazon token service: ' . $response->get_error_message());
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200 || !is_array($data) || empty($data['access_token'])) {
        $detail = is_array($data) && isset($data['error_description']) ? $data['error_description'] : ('HTTP ' . $code);
        return new WP_Error('tac_token', 'Amazon token request failed: ' . $detail);
    }
    $ttl = isset($data['expires_in']) ? max(60, (int) $data['expires_in'] - 120) : 3000;
    set_transient('tac_lwa_token', $data['access_token'], $ttl);
    return $data['access_token'];
}

function tac_api_post($path, $payload) {
    $token = tac_get_access_token();
    if (is_wp_error($token)) {
        return $token;
    }
    $o = tac_options();
    $response = wp_remote_post(TAC_API_BASE . $path, array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'x-marketplace' => $o['store'],
            'Content-Type'  => 'application/json',
        ),
        'body' => wp_json_encode($payload),
    ));
    if (is_wp_error($response)) {
        return new WP_Error('tac_http', 'Could not reach the Creators API: ' . $response->get_error_message());
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($code !== 200 || !is_array($data)) {
        return new WP_Error('tac_api', 'Creators API error (HTTP ' . $code . '): ' . tac_extract_api_error($data));
    }
    if (isset($data['errors']) && is_array($data['errors']) && !empty($data['errors'])) {
        return new WP_Error('tac_api', 'Creators API error: ' . tac_extract_api_error($data));
    }
    return $data;
}

function tac_extract_api_error($data) {
    if (is_array($data)) {
        if (isset($data['errors'][0])) {
            $e = $data['errors'][0];
            $code = isset($e['code']) ? $e['code'] : 'Error';
            $msg  = isset($e['message']) ? $e['message'] : '';
            return trim($code . ' ' . $msg);
        }
        if (isset($data['__type']) || isset($data['message'])) {
            $type = isset($data['__type']) ? $data['__type'] : 'Error';
            $msg  = isset($data['message']) ? $data['message'] : '';
            return trim($type . ' ' . $msg);
        }
    }
    return 'Unknown error';
}

function tac_resources() {
    return array(
        'ItemInfo.Title',
        'ItemInfo.Features',
        'Images.Primary.Large',
        'Images.Primary.Medium',
        'Offers.Listings.Price',
        'OffersV2.Listings.Price',
    );
}

/**
 * Fetch one product. Uses a 24h positive cache and a 1h negative cache so a
 * page full of cards never hammers the API (and an ineligible account is not
 * re-asked on every page load).
 */
function tac_get_item($asin, $force = false) {
    $asin = strtoupper(sanitize_text_field($asin));
    if (!$force) {
        $hit = get_transient('tac_item_' . $asin);
        if (is_array($hit)) {
            return $hit;
        }
        $err = get_transient('tac_item_err_' . $asin);
        if (is_string($err) && $err !== '') {
            return new WP_Error('tac_api_cached', $err);
        }
    }
    if (tac_mode() === 'app') {
        return tac_app_get_item($asin, $force);
    }
    $o = tac_options();
    $data = tac_api_post('/catalog/v1/getItems', array(
        'itemIds'     => array($asin),
        'partnerTag'  => $o['tracking_id'],
        'partnerType' => 'Associates',
        'marketplace' => $o['store'],
        'resources'   => tac_resources(),
    ));
    if (is_wp_error($data)) {
        set_transient('tac_item_err_' . $asin, $data->get_error_message(), HOUR_IN_SECONDS);
        return $data;
    }
    $item = null;
    if (isset($data['itemsResult']['items'][0])) {
        $item = $data['itemsResult']['items'][0];
    } elseif (isset($data['ItemsResult']['Items'][0])) {
        $item = $data['ItemsResult']['Items'][0];
    }
    if (!is_array($item)) {
        $msg = 'Amazon returned no product for ASIN ' . $asin . '.';
        set_transient('tac_item_err_' . $asin, $msg, HOUR_IN_SECONDS);
        return new WP_Error('tac_empty', $msg);
    }
    $normalized = tac_normalize_item($item, $asin);
    set_transient('tac_item_' . $asin, $normalized, DAY_IN_SECONDS);
    delete_transient('tac_item_err_' . $asin);
    return $normalized;
}

function tac_search_items($keywords) {
    if (tac_mode() === 'app') {
        $res = tac_app_request('search', array('keywords' => $keywords));
        if (is_wp_error($res)) {
            return $res;
        }
        return isset($res['items']) && is_array($res['items']) ? $res['items'] : array();
    }
    $o = tac_options();
    $data = tac_api_post('/catalog/v1/searchItems', array(
        'keywords'    => $keywords,
        'partnerTag'  => $o['tracking_id'],
        'partnerType' => 'Associates',
        'marketplace' => $o['store'],
        'itemCount'   => 10,
        'resources'   => tac_resources(),
    ));
    if (is_wp_error($data)) {
        return $data;
    }
    $items = array();
    if (isset($data['searchResult']['items']) && is_array($data['searchResult']['items'])) {
        $items = $data['searchResult']['items'];
    } elseif (isset($data['SearchResult']['Items']) && is_array($data['SearchResult']['Items'])) {
        $items = $data['SearchResult']['Items'];
    }
    $out = array();
    foreach ($items as $item) {
        if (is_array($item) && isset($item['asin'])) {
            $out[] = tac_normalize_item($item, $item['asin']);
        }
    }
    return $out;
}

/** Dig through the known Creators/PA-API response shapes defensively. */
function tac_normalize_item($item, $asin_fallback = '') {
    $get = function ($array, $keys) {
        foreach ($keys as $key) {
            if (isset($array[$key]) && is_array($array[$key])) {
                return $array[$key];
            }
        }
        return array();
    };
    $asin = isset($item['asin']) ? $item['asin'] : (isset($item['ASIN']) ? $item['ASIN'] : $asin_fallback);

    $info  = $get($item, array('itemInfo', 'ItemInfo'));
    $title = $get($info, array('title', 'Title'));
    $title = isset($title['displayValue']) ? $title['displayValue'] : (isset($title['DisplayValue']) ? $title['DisplayValue'] : '');

    $images  = $get($item, array('images', 'Images'));
    $primary = $get($images, array('primary', 'Primary'));
    $image   = '';
    foreach (array('large', 'Large', 'medium', 'Medium') as $size) {
        if (isset($primary[$size]['url'])) {
            $image = $primary[$size]['url'];
            break;
        }
        if (isset($primary[$size]['URL'])) {
            $image = $primary[$size]['URL'];
            break;
        }
    }

    $price = '';
    $offers_v2 = $get($item, array('offersV2', 'OffersV2'));
    $listings  = $get($offers_v2, array('listings', 'Listings'));
    if (isset($listings[0]['price']['displayAmount'])) {
        $price = $listings[0]['price']['displayAmount'];
    } elseif (isset($listings[0]['Price']['DisplayAmount'])) {
        $price = $listings[0]['Price']['DisplayAmount'];
    }
    if ($price === '') {
        $offers   = $get($item, array('offers', 'Offers'));
        $listings = $get($offers, array('listings', 'Listings'));
        if (isset($listings[0]['price']['displayAmount'])) {
            $price = $listings[0]['price']['displayAmount'];
        } elseif (isset($listings[0]['Price']['DisplayAmount'])) {
            $price = $listings[0]['Price']['DisplayAmount'];
        }
    }

    $url = '';
    if (isset($item['detailPageURL'])) {
        $url = $item['detailPageURL'];
    } elseif (isset($item['DetailPageURL'])) {
        $url = $item['DetailPageURL'];
    }
    if ($url === '' && $asin !== '') {
        $url = tac_product_url($asin);
    }

    return array(
        'asin'  => $asin,
        'title' => (string) $title,
        'image' => (string) $image,
        'price' => (string) $price,
        'url'   => (string) $url,
    );
}

function tac_product_url($asin) {
    $o = tac_options();
    $url = 'https://' . $o['store'] . '/dp/' . rawurlencode(strtoupper($asin));
    if ($o['tracking_id'] !== '') {
        $url .= '?tag=' . rawurlencode($o['tracking_id']);
    }
    return $url;
}

/* -------------------------------------------------------------------------
 * REST routes used by the block editor
 * ---------------------------------------------------------------------- */

add_action('rest_api_init', function () {
    register_rest_route('tac/v1', '/fetch', array(
        'methods' => 'POST',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback' => function ($request) {
            $asin = strtoupper(sanitize_text_field($request->get_param('asin')));
            if (!preg_match('/^[A-Z0-9]{10}$/', $asin)) {
                return new WP_Error('tac_asin', 'That does not look like a valid ASIN (10 characters).', array('status' => 400));
            }
            $item = tac_get_item($asin, true);
            if (is_wp_error($item)) {
                return new WP_Error('tac_fetch', $item->get_error_message(), array('status' => 400));
            }
            return rest_ensure_response($item);
        },
    ));
    register_rest_route('tac/v1', '/search', array(
        'methods' => 'POST',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback' => function ($request) {
            $keywords = sanitize_text_field($request->get_param('keywords'));
            if ($keywords === '') {
                return new WP_Error('tac_kw', 'Type a keyword first.', array('status' => 400));
            }
            $items = tac_search_items($keywords);
            if (is_wp_error($items)) {
                return new WP_Error('tac_search', $items->get_error_message(), array('status' => 400));
            }
            return rest_ensure_response($items);
        },
    ));
});

/* -------------------------------------------------------------------------
 * Block: tabaix/affiliate-cards (server-rendered)
 *
 * Attributes carry the manual data (asin, title, image, features). The price
 * is NEVER typed by hand — at render time it comes from the Creators API
 * cache when live data exists, so prices are always Amazon's own.
 * ---------------------------------------------------------------------- */

add_action('init', function () {
    wp_register_script(
        'tac-editor',
        plugins_url('assets/editor.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-i18n'),
        TAC_VERSION,
        true
    );
    wp_register_style(
        'tac-style',
        plugins_url('assets/style.css', __FILE__),
        array(),
        TAC_VERSION
    );
    wp_register_style(
        'tac-editor-style',
        plugins_url('assets/editor.css', __FILE__),
        array(),
        TAC_VERSION
    );
    register_block_type(__DIR__ . '/block.json', array(
        'render_callback' => 'tac_render_block',
    ));
});

function tac_render_block($attributes) {
    $layout   = isset($attributes['layout']) ? $attributes['layout'] : 'single';
    $cta      = isset($attributes['ctaText']) && $attributes['ctaText'] !== '' ? $attributes['ctaText'] : 'Check price on Amazon';
    $products = isset($attributes['products']) && is_array($attributes['products']) ? $attributes['products'] : array();
    $products = array_values(array_filter($products, function ($p) {
        return is_array($p) && !empty($p['asin']);
    }));
    if (empty($products)) {
        return '';
    }
    if ($layout === 'single') {
        $products = array_slice($products, 0, 1);
    } elseif ($layout === 'table') {
        $products = array_slice($products, 0, 4);
    } else {
        $products = array_slice($products, 0, 6);
        $layout = 'list';
    }

    $cards = array();
    foreach ($products as $p) {
        $cards[] = tac_prepare_card($p);
    }

    ob_start();
    if ($layout === 'single') {
        echo tac_card_html($cards[0], $cta, 'tac-single');
    } elseif ($layout === 'table') {
        echo tac_table_html($cards, $cta);
    } else {
        echo '<div class="tac-grid">';
        foreach ($cards as $card) {
            echo tac_card_html($card, $cta, 'tac-mini');
        }
        echo '</div>';
    }
    return ob_get_clean();
}

/** Merge manual attributes with live API data (price/image/title refresh). */
function tac_prepare_card($p) {
    $asin  = strtoupper(sanitize_text_field($p['asin']));
    $title = isset($p['title']) ? (string) $p['title'] : '';
    $image = isset($p['image']) ? (string) $p['image'] : '';
    $price = '';
    $live  = tac_get_item($asin);
    if (!is_wp_error($live)) {
        if ($live['price'] !== '') {
            $price = $live['price'];
        }
        if ($image === '' && $live['image'] !== '') {
            $image = $live['image'];
        }
        if ($title === '' && $live['title'] !== '') {
            $title = $live['title'];
        }
    }
    $features = array();
    if (!empty($p['features'])) {
        foreach (preg_split('/\r\n|\r|\n/', (string) $p['features']) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $features[] = $line;
            }
        }
    }
    return array(
        'asin'     => $asin,
        'title'    => $title !== '' ? $title : $asin,
        'image'    => $image,
        'price'    => $price,
        'features' => $features,
        'rating'   => isset($p['rating']) ? (string) $p['rating'] : '',
        'badge'    => isset($p['badge']) ? (string) $p['badge'] : '',
        'url'      => tac_product_url($asin),
    );
}

function tac_card_html($c, $cta, $extra_class) {
    $html  = '<div class="tac-card ' . esc_attr($extra_class) . '">';
    if ($c['badge'] !== '') {
        $html .= '<span class="tac-badge">' . esc_html($c['badge']) . '</span>';
    }
    if ($c['image'] !== '') {
        $html .= '<a class="tac-img" href="' . esc_url($c['url']) . '" target="_blank" rel="sponsored nofollow noopener">'
            . '<img src="' . esc_url($c['image']) . '" alt="' . esc_attr($c['title']) . '" loading="lazy" /></a>';
    }
    $html .= '<div class="tac-body">';
    $html .= '<a class="tac-title" href="' . esc_url($c['url']) . '" target="_blank" rel="sponsored nofollow noopener">' . esc_html($c['title']) . '</a>';
    if ($c['rating'] !== '') {
        $html .= '<div class="tac-rating">' . esc_html($c['rating']) . '</div>';
    }
    if (!empty($c['features'])) {
        $html .= '<ul class="tac-features">';
        foreach ($c['features'] as $f) {
            $html .= '<li>' . esc_html($f) . '</li>';
        }
        $html .= '</ul>';
    }
    if ($c['price'] !== '') {
        $html .= '<div class="tac-price">' . esc_html($c['price']) . ' <span class="tac-on">on Amazon</span></div>';
    }
    $html .= '<a class="tac-btn" href="' . esc_url($c['url']) . '" target="_blank" rel="sponsored nofollow noopener">' . esc_html($cta) . '</a>';
    $html .= '</div></div>';
    return $html;
}

function tac_table_html($cards, $cta) {
    $html  = '<div class="tac-table-wrap"><table class="tac-table"><thead><tr><th></th>';
    foreach ($cards as $c) {
        $html .= '<th>' . esc_html($c['title']) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    $html .= '<tr><td class="tac-row-label">Product</td>';
    foreach ($cards as $c) {
        $html .= '<td>';
        if ($c['image'] !== '') {
            $html .= '<a href="' . esc_url($c['url']) . '" target="_blank" rel="sponsored nofollow noopener"><img src="' . esc_url($c['image']) . '" alt="' . esc_attr($c['title']) . '" loading="lazy" /></a>';
        }
        if ($c['badge'] !== '') {
            $html .= '<div class="tac-badge tac-badge-inline">' . esc_html($c['badge']) . '</div>';
        }
        $html .= '</td>';
    }
    $html .= '</tr>';

    $html .= '<tr><td class="tac-row-label">Highlights</td>';
    foreach ($cards as $c) {
        $html .= '<td>';
        if (!empty($c['features'])) {
            $html .= '<ul class="tac-features">';
            foreach ($c['features'] as $f) {
                $html .= '<li>' . esc_html($f) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '—';
        }
        $html .= '</td>';
    }
    $html .= '</tr>';

    $html .= '<tr><td class="tac-row-label">Price</td>';
    foreach ($cards as $c) {
        $html .= '<td>' . ($c['price'] !== '' ? '<span class="tac-price">' . esc_html($c['price']) . '</span>' : '—') . '</td>';
    }
    $html .= '</tr>';

    $html .= '<tr><td></td>';
    foreach ($cards as $c) {
        $html .= '<td><a class="tac-btn" href="' . esc_url($c['url']) . '" target="_blank" rel="sponsored nofollow noopener">' . esc_html($cta) . '</a></td>';
    }
    $html .= '</tr>';

    $html .= '</tbody></table></div>';
    return $html;
}

/* -------------------------------------------------------------------------
 * App mode — the Amazon secret stays on your own Tabaix Affiliate Server.
 * The plugin identifies itself with a per-site key that can only fetch
 * product data; it can never reveal the Amazon credentials.
 * ---------------------------------------------------------------------- */

function tac_mode() {
    $o = tac_options();
    return isset($o['mode']) && $o['mode'] === 'app' ? 'app' : 'direct';
}

function tac_app_request($route, $payload) {
    $o = tac_options();
    if ($o['app_url'] === '' || $o['site_key'] === '') {
        return new WP_Error('tac_app_config', 'App mode is on, but the App URL or Site key is missing (Affiliate Cards settings).');
    }
    $url = rtrim($o['app_url'], '/') . '/api/' . rawurlencode($route);
    $response = wp_remote_post($url, array(
        'timeout' => 20,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-TAC-Site-Key' => $o['site_key'],
        ),
        'body' => wp_json_encode($payload),
    ));
    if (is_wp_error($response)) {
        return new WP_Error('tac_app_http', 'Could not reach the Tabaix Affiliate Server: ' . $response->get_error_message());
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($data)) {
        return new WP_Error('tac_app_bad', 'The server app answered something unreadable (HTTP ' . $code . ').');
    }
    if ($code !== 200 || isset($data['error'])) {
        $msg = isset($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $code);
        return new WP_Error('tac_app_error', 'Server app: ' . $msg);
    }
    return $data;
}

function tac_app_get_item($asin, $force = false) {
    $res = tac_app_request('fetch', array('asin' => $asin));
    if (is_wp_error($res)) {
        set_transient('tac_item_err_' . $asin, $res->get_error_message(), HOUR_IN_SECONDS);
        return $res;
    }
    set_transient('tac_item_' . $asin, $res, DAY_IN_SECONDS);
    delete_transient('tac_item_err_' . $asin);
    return $res;
}
