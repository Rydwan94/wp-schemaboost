<?php
/*
Plugin Name: SchemaBoost Lite
Description: Article, FAQ, (Woo) Product + PRO: HowTo, LocalBusiness. Ekran licencji, paywall PRO, eksport/import i widżet diagnostyczny.
Version: 0.6.4
*/
define('SBL_VERSION', '0.6.4');


if (!defined('ABSPATH'))
    exit;

// [DODANE] I18N + szybki link do ustawień
add_action('init', function () {
    load_plugin_textdomain('schema-boost-lite', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $url = admin_url('admin.php?page=schema-boost-lite');
    array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'schema-boost-lite') . '</a>');
    return $links;
});

// =======================
// KONFIGURACJA / STAŁE
// =======================

// --- Core/Lite ---
const SBL_OPT_FAQ_JSON = 'schema_boost_faq';
const SBL_OPT_ENABLE_PRODUCT = 'schema_boost_enable_product';

// --- LocalBusiness (PRO) ---
const SBL_OPT_ENABLE_HOWTO = 'schema_boost_enable_howto';
const SBL_OPT_HOWTO_NAME = 'schema_boost_howto_name';
const SBL_OPT_HOWTO_TOTALTIME = 'schema_boost_howto_totaltime';
const SBL_OPT_HOWTO_STEPS_JSON = 'schema_boost_howto_steps';
const SBL_OPT_LB_ENABLE = 'schema_boost_lb_enable';
const SBL_OPT_LB_SITEWIDE = 'schema_boost_lb_sitewide';
const SBL_OPT_LB_TYPE = 'schema_boost_lb_type';
const SBL_OPT_LB_NAME = 'schema_boost_lb_name';
const SBL_OPT_LB_URL = 'schema_boost_lb_url';
const SBL_OPT_LB_PHONE = 'schema_boost_lb_phone';
const SBL_OPT_LB_EMAIL = 'schema_boost_lb_email';
const SBL_OPT_LB_PRICE = 'schema_boost_lb_price_range';
const SBL_OPT_LB_STREET = 'schema_boost_lb_street';
const SBL_OPT_LB_LOCALITY = 'schema_boost_lb_locality';
const SBL_OPT_LB_REGION = 'schema_boost_lb_region';
const SBL_OPT_LB_POSTAL = 'schema_boost_lb_postal';
const SBL_OPT_LB_COUNTRY = 'schema_boost_lb_country';
const SBL_OPT_LB_LAT = 'schema_boost_lb_lat';
const SBL_OPT_LB_LNG = 'schema_boost_lb_lng';
const SBL_OPT_LB_SAMEAS = 'schema_boost_lb_sameas';
const SBL_OPT_LB_HOURS = 'schema_boost_lb_hours';  // JSON: {Mo:{open,close,closed},...}
// --- Organization (No-Location, PRO) ---
const SBL_OPT_ORG_ENABLE = 'schema_boost_org_enable'; // homepage only
const SBL_OPT_ORG_NAME = 'schema_boost_org_name';
const SBL_OPT_ORG_URL = 'schema_boost_org_url';
const SBL_OPT_ORG_PHONE = 'schema_boost_org_phone';
const SBL_OPT_ORG_EMAIL = 'schema_boost_org_email';
const SBL_OPT_ORG_SAMEAS = 'schema_boost_org_sameas';
// --- Dev/PRO tools ---
const SBL_OPT_INSPECTOR_ENABLE = 'schema_boost_inspector_enable';

const SBL_ACTION_EXPORT = 'sbl_export_settings';
const SBL_ACTION_IMPORT = 'sbl_import_settings';
const SBL_LB_DAY_KEYS = 'Mo,Tu,We,Th,Fr,Sa,Su';

// --- Licencje / PRO ---
if (!defined('SBL_LICENSE_API_BASE')) {
    // PODMIEŃ na swój serwer licencji (REST)
    define('SBL_LICENSE_API_BASE', 'https://lic.schemaboost.pl/wp-json/schemaboost/v1');
}
if (!defined('SBL_LICENSE_SHARED_SECRET')) {
    // PODMIEŃ na swój tajny klucz współdzielony (taki sam po stronie serwera licencji)
    define('SBL_LICENSE_SHARED_SECRET', 'sH4t!q9@Xr82Lp%aVd7mZ#jNwF6eYbK0uT5g^oR1cQ8zJ2lM3pB$hD*G9nA7kC');
}
// Twój link Stripe (TEST) – podany przez Ciebie:
if (!defined('SBL_PRO_PAYMENT_LINK')) {
    define('SBL_PRO_PAYMENT_LINK', 'https://buy.stripe.com/test_dRmcN4dQGad2gm4ezygYU02');
}

// Lokalne opcje licencji
const SBL_OPT_LICENSE_KEY = 'sbl_license_key';
const SBL_OPT_LICENSE_STATUS = 'sbl_license_status';       // active|past_due|canceled|inactive
const SBL_OPT_LICENSE_EXPIRES = 'sbl_license_expires';      // timestamp (0 = bezterminowo)
const SBL_OPT_LICENSE_ENTITLEMENTS = 'sbl_license_entitlements'; // JSON array, np. ["localbusiness"]
const SBL_OPT_LICENSE_PORTAL = 'sbl_license_portal_url';   // opcjonalnie
const SBL_OPT_LICENSE_LASTCHECK = 'sbl_license_lastcheck';

// =======================
// POCZĄTKOWY STAN (aktywacja wtyczki)
// =======================
register_activation_hook(__FILE__, function () {
    // Bezpieczne, przewidywalne domyślne wartości
    add_option(SBL_OPT_ENABLE_PRODUCT, '0');
    add_option(SBL_OPT_ENABLE_HOWTO, '0');
    add_option(SBL_OPT_HOWTO_NAME, '');
    add_option(SBL_OPT_HOWTO_TOTALTIME, '');
    add_option(SBL_OPT_HOWTO_STEPS_JSON, wp_json_encode([]));

    add_option(SBL_OPT_FAQ_JSON, wp_json_encode([]));

    add_option(SBL_OPT_LB_ENABLE, '0');
    add_option(SBL_OPT_LB_SITEWIDE, '0');
    add_option(SBL_OPT_LB_TYPE, 'LocalBusiness');
    add_option(SBL_OPT_LB_NAME, '');
    add_option(SBL_OPT_LB_URL, home_url('/'));
    add_option(SBL_OPT_LB_PHONE, '');
    add_option(SBL_OPT_LB_EMAIL, '');
    add_option(SBL_OPT_LB_PRICE, '');
    add_option(SBL_OPT_LB_STREET, '');
    add_option(SBL_OPT_LB_LOCALITY, '');
    add_option(SBL_OPT_LB_REGION, '');
    add_option(SBL_OPT_LB_POSTAL, '');
    add_option(SBL_OPT_LB_COUNTRY, '');
    add_option(SBL_OPT_LB_LAT, '');
    add_option(SBL_OPT_LB_LNG, '');
    add_option(SBL_OPT_LB_SAMEAS, '');
    // Godziny puste (brak efektu w JSON-LD dopóki nie ustawisz)
    add_option(SBL_OPT_LB_HOURS, wp_json_encode([]));
    // Dev tools (PRO) defaults
    add_option(SBL_OPT_INSPECTOR_ENABLE, '0');
    // Organization (No-Location, PRO)
    add_option(SBL_OPT_ORG_ENABLE, '0');
    add_option(SBL_OPT_ORG_NAME, '');
    add_option(SBL_OPT_ORG_URL, home_url('/'));
    add_option(SBL_OPT_ORG_PHONE, '');
    add_option(SBL_OPT_ORG_EMAIL, '');
    add_option(SBL_OPT_ORG_SAMEAS, '');
});

// =======================
// POMOC: LICENCJE (klient)
// =======================
function sbl_is_pro_active(): bool
{
    $status = get_option(SBL_OPT_LICENSE_STATUS, 'inactive');
    $exp = (int) get_option(SBL_OPT_LICENSE_EXPIRES, 0);
    return ($status === 'active') && ($exp === 0 || $exp >= (time() - 7 * DAY_IN_SECONDS));
}


// Prosty opis pod polem
function sbl_desc(string $text): string
{
    return '<p class="description sbl-desc">' . wp_kses_post($text) . '</p>';
}

// Ikonka „i” z dymkiem (tooltip)
function sbl_help(string $text): string
{
    $label = esc_attr(wp_strip_all_tags($text));
    return '<span class="sbl-help" tabindex="0" aria-label="' . $label . '">
                <span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
                <span class="sbl-help-popover" role="tooltip">' . $label . '</span>
            </span>';
}


function sbl_license_has(string $cap): bool
{
    $raw = get_option(SBL_OPT_LICENSE_ENTITLEMENTS, '[]');
    $arr = json_decode($raw, true);
    return is_array($arr) && in_array($cap, $arr, true);
}
function sbl_license_remote(string $path, array $payload, int $timeout = 15)
{
    $url = trailingslashit(SBL_LICENSE_API_BASE) . ltrim($path, '/');
    $payload['ts'] = time();
    $body = wp_json_encode($payload);
    $sig = base64_encode(hash_hmac('sha256', $body, SBL_LICENSE_SHARED_SECRET, true));
    $resp = wp_remote_post($url, [
        'timeout' => $timeout,
        'headers' => ['Content-Type' => 'application/json', 'X-SBL-Signature' => $sig],
        'body' => $body,
    ]);
    if (is_wp_error($resp))
        return $resp;
    $code = (int) wp_remote_retrieve_response_code($resp);
    $json = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code >= 200 && $code < 300 && is_array($json))
        return $json;
    return new WP_Error('sbl_http', 'Błąd serwera licencji', ['code' => $code, 'body' => wp_remote_retrieve_body($resp)]);
}
function sbl_license_status_label(): string
{
    $status = get_option(SBL_OPT_LICENSE_STATUS, 'inactive');
    $exp = (int) get_option(SBL_OPT_LICENSE_EXPIRES, 0);
    $when = $exp ? ' (do ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $exp) . ')' : '';
    $map = [
        'active' => 'Aktywna' . $when,
        'past_due' => 'Płatność zaległa' . $when,
        'canceled' => 'Anulowana',
        'inactive' => 'Nieaktywna',
    ];
    return $map[$status] ?? $status;
}

// =======================
// FRONTEND: SCHEMAS
// (zachowane i rozbudowane z Twojej wersji)
// =======================

// Article
add_action('wp_head', function () {
    if (!is_single())
        return;
    global $post;
    if (!$post)
        return;
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Article",
        "headline" => get_the_title($post),
        "datePublished" => get_the_date('c', $post),
        "dateModified" => get_the_modified_date('c', $post),
        "author" => ["@type" => "Person", "name" => get_the_author_meta('display_name', $post->post_author)],
        "publisher" => ["@type" => "Organization", "name" => get_bloginfo('name'), "logo" => ["@type" => "ImageObject", "url" => get_site_icon_url()]],
        "mainEntityOfPage" => get_permalink($post),
    ];

    $thumb = get_the_post_thumbnail_url($post, 'full');
    if ($thumb) {
        $schema['image'] = $thumb;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// FAQ
add_action('wp_head', function () {
    if (!is_single())
        return;
    $faqs = json_decode(get_option(SBL_OPT_FAQ_JSON, '[]'), true);
    if (empty($faqs) || !is_array($faqs))
        return;
    $entities = [];
    foreach ($faqs as $row) {
        $q = isset($row['question']) ? wp_strip_all_tags($row['question']) : '';
        $a = isset($row['answer']) ? wp_strip_all_tags($row['answer']) : '';
        if ($q !== '' && $a !== '') {
            $entities[] = ["@type" => "Question", "name" => $q, "acceptedAnswer" => ["@type" => "Answer", "text" => $a]];
        }
    }
    if (!$entities)
        return;
    $schema = ["@context" => "https://schema.org", "@type" => "FAQPage", "mainEntity" => $entities];
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// Product (Woo)
add_action('wp_head', function () {
    if (get_option(SBL_OPT_ENABLE_PRODUCT, '0') !== '1')
        return;
    if (!class_exists('WooCommerce') || !function_exists('is_product') || !is_product())
        return;
    global $product;
    if (!$product || !is_a($product, \WC_Product::class))
        return;

    $name = wp_strip_all_tags($product->get_name());
    $url = get_permalink($product->get_id());
    $sku = $product->get_sku();
    $img = wp_get_attachment_image_url($product->get_image_id(), 'full');
    $desc = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());
    $price = $product->get_price();
    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';

    $availability_map = ['instock' => 'https://schema.org/InStock', 'outofstock' => 'https://schema.org/OutOfStock', 'onbackorder' => 'https://schema.org/PreOrder'];
    $stock_status = method_exists($product, 'get_stock_status') ? $product->get_stock_status() : 'instock';
    $offers = ["@type" => "Offer", "url" => $url, "price" => ($price !== '' ? (string) $price : null), "priceCurrency" => $currency, "availability" => $availability_map[$stock_status] ?? 'https://schema.org/InStock', "itemCondition" => "https://schema.org/NewCondition"];
    if ($sku)
        $offers['sku'] = $sku;

    $rating_value = (float) $product->get_average_rating();
    $rating_count = (int) $product->get_review_count();

    $schema = ["@context" => "https://schema.org", "@type" => "Product", "name" => $name, "description" => $desc, "url" => $url, "image" => $img ?: null, "offers" => $offers];
    if ($rating_value > 0 && $rating_count > 0)
        $schema['aggregateRating'] = ["@type" => "AggregateRating", "ratingValue" => (string) $rating_value, "reviewCount" => (string) $rating_count];

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// HowTo
add_action('wp_head', function () {
    if (get_option(SBL_OPT_ENABLE_HOWTO, '0') !== '1')
        return;

    // [NOWE – gate PRO dla HowTo]
    if (!sbl_is_pro_active() || !sbl_license_has('howto'))
        return;

    $howto_gate_ok = (sbl_is_pro_active() && sbl_license_has('howto'));
    if (!$howto_gate_ok)
        return;

    if (!is_single())
        return;
    global $post;
    if (!$post)
        return;
    $steps = json_decode(get_option(SBL_OPT_HOWTO_STEPS_JSON, '[]'), true);
    if (empty($steps) || !is_array($steps))
        return;

    $entities = [];
    foreach ($steps as $row) {
        $n = isset($row['name']) ? wp_strip_all_tags($row['name']) : '';
        $t = isset($row['text']) ? wp_strip_all_tags($row['text']) : '';
        if ($n !== '' || $t !== '') {
            $entities[] = ["@type" => "HowToStep", "name" => $n ?: null, "text" => $t ?: null];
        }
    }
    if (!$entities)
        return;

    $name = sanitize_text_field(get_option(SBL_OPT_HOWTO_NAME, ''));
    if ($name === '')
        $name = get_the_title($post);
    $tt = strtoupper(trim(get_option(SBL_OPT_HOWTO_TOTALTIME, '')));
    if ($tt !== '' && !preg_match('/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/', $tt))
        $tt = '';

    $schema = ["@context" => "https://schema.org", "@type" => "HowTo", "name" => $name, "step" => $entities];
    if ($tt !== '')
        $schema['totalTime'] = $tt;
    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// LocalBusiness (GATED – PRO)
add_action('wp_head', function () {
    if (get_option(SBL_OPT_LB_ENABLE, '0') !== '1')
        return;

    // Twardy gate: tylko przy aktywnej licencji i uprawnieniu
    if (!sbl_is_pro_active() || !sbl_license_has('localbusiness'))
        return;

    $sitewide = get_option(SBL_OPT_LB_SITEWIDE, '0') === '1';
    if (!$sitewide && !is_front_page())
        return;

    $type = sanitize_text_field(get_option(SBL_OPT_LB_TYPE, 'LocalBusiness'));
    $name = sanitize_text_field(get_option(SBL_OPT_LB_NAME, ''));
    $url = esc_url_raw(get_option(SBL_OPT_LB_URL, home_url('/')));
    $phone = sanitize_text_field(get_option(SBL_OPT_LB_PHONE, ''));
    $email = sanitize_text_field(get_option(SBL_OPT_LB_EMAIL, ''));
    $price = sanitize_text_field(get_option(SBL_OPT_LB_PRICE, ''));
    $street = sanitize_text_field(get_option(SBL_OPT_LB_STREET, ''));
    $locality = sanitize_text_field(get_option(SBL_OPT_LB_LOCALITY, ''));
    $region = sanitize_text_field(get_option(SBL_OPT_LB_REGION, ''));
    $postal = sanitize_text_field(get_option(SBL_OPT_LB_POSTAL, ''));
    $country = strtoupper(sanitize_text_field(get_option(SBL_OPT_LB_COUNTRY, '')));
    $lat_raw = get_option(SBL_OPT_LB_LAT, '');
    $lng_raw = get_option(SBL_OPT_LB_LNG, '');
    $lat = is_numeric($lat_raw) ? (float) $lat_raw : null;
    $lng = is_numeric($lng_raw) ? (float) $lng_raw : null;

    // sameAs
    $sameas_raw = (string) get_option(SBL_OPT_LB_SAMEAS, '');
    $sameas = [];
    foreach (array_filter(array_map('trim', preg_split('/[\n,]+/', $sameas_raw))) as $u) {
        if (filter_var($u, FILTER_VALIDATE_URL))
            $sameas[] = $u;
    }

    // OpeningHours
    $hours_cfg = json_decode(get_option(SBL_OPT_LB_HOURS, '{}'), true) ?: [];
    $map = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];
    $oh = [];
    foreach ($map as $dk => $dn) {
        $r = isset($hours_cfg[$dk]) ? (array) $hours_cfg[$dk] : [];
        $closed = !empty($r['closed']);
        $o = isset($r['open']) ? preg_replace('/[^0-9:]/', '', $r['open']) : '';
        $c = isset($r['close']) ? preg_replace('/[^0-9:]/', '', $r['close']) : '';
        if (!$closed && $o && $c && preg_match('/^\d{2}:\d{2}$/', $o) && preg_match('/^\d{2}:\d{2}$/', $c)) {
            $oh[] = ["@type" => "OpeningHoursSpecification", "dayOfWeek" => $dn, "opens" => $o, "closes" => $c];
        }
    }

    $address = ["@type" => "PostalAddress", "streetAddress" => $street ?: null, "addressLocality" => $locality ?: null, "addressRegion" => $region ?: null, "postalCode" => $postal ?: null, "addressCountry" => $country ?: null];
    $schema = ["@context" => "https://schema.org", "@type" => $type ?: 'LocalBusiness', "name" => $name ?: get_bloginfo('name'), "url" => $url ?: home_url('/'), "telephone" => $phone ?: null, "email" => $email ?: null, "image" => get_site_icon_url() ?: null, "address" => $address];
    if ($oh)
        $schema['openingHoursSpecification'] = $oh;
    if ($sameas)
        $schema['sameAs'] = $sameas;
    if ($price !== '')
        $schema['priceRange'] = $price;
    if ($lat !== null && $lng !== null)
        $schema['geo'] = ["@type" => "GeoCoordinates", "latitude" => $lat, "longitude" => $lng];

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// Organization (No-Location, PRO) – homepage only
add_action('wp_head', function () {
    if (get_option(SBL_OPT_ORG_ENABLE, '0') !== '1')
        return;
    // Gate behind PRO (reuse 'localbusiness' entitlement)
    if (!sbl_is_pro_active() || !sbl_license_has('localbusiness'))
        return;
    if (!is_front_page())
        return;

    $name = sanitize_text_field(get_option(SBL_OPT_ORG_NAME, ''));
    if ($name === '') $name = get_bloginfo('name');
    $url = esc_url_raw(get_option(SBL_OPT_ORG_URL, home_url('/')));
    $phone = sanitize_text_field(get_option(SBL_OPT_ORG_PHONE, ''));
    $email = sanitize_text_field(get_option(SBL_OPT_ORG_EMAIL, ''));
    $logo = get_site_icon_url();

    // sameAs list (newline or comma separated)
    $sameas_raw = (string) get_option(SBL_OPT_ORG_SAMEAS, '');
    $sameas = [];
    foreach (array_filter(array_map('trim', preg_split('/[\n,]+/', $sameas_raw))) as $u) {
        if (filter_var($u, FILTER_VALIDATE_URL)) $sameas[] = $u;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $name,
        'url' => $url ?: home_url('/'),
        'telephone' => $phone ?: null,
        'email' => $email ?: null,
        'logo' => $logo ?: null,
    ];
    if ($sameas) $schema['sameAs'] = $sameas;

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

// Znacznik życia
add_action('wp_footer', function () {
    echo "<!-- SchemaBoost Lite v" . esc_html(SBL_VERSION) . " aktywne -->"; });

// PRO: On‑page Schema Inspector for admins
add_action('wp_footer', function () {
    if (!sbl_is_pro_active() || !sbl_license_has('inspector')) return;
    if (!current_user_can('manage_options')) return;
    if (get_option(SBL_OPT_INSPECTOR_ENABLE, '0') !== '1') return;

    // Minimal, self-contained overlay (no external calls)
    ?>
    <style>
        .sbl-inspector-btn{position:fixed;right:16px;bottom:16px;z-index:99999;background:#111827;color:#fff;border:none;border-radius:999px;padding:10px 14px;box-shadow:0 8px 20px rgba(0,0,0,.2);cursor:pointer}
        .sbl-inspector-panel{position:fixed;right:16px;bottom:64px;z-index:99999;width:360px;max-height:60vh;overflow:auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 18px 40px rgba(0,0,0,.18);display:none}
        .sbl-inspector-panel header{padding:10px 12px;border-bottom:1px solid #eef2f7;font-weight:600}
        .sbl-inspector-list{list-style:none;margin:0;padding:8px 12px}
        .sbl-inspector-list li{display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px dashed #f0f2f5}
        .sbl-pill{padding:2px 8px;border-radius:999px;font-size:12px}
        .ok{background:#ecfdf5;color:#065f46}.warn{background:#fff7ed;color:#9a3412}.err{background:#fef2f2;color:#991b1b}
        .sbl-inspector-pre{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;font-size:12px;background:#0b1020;color:#a8e1ff;padding:10px;border-radius:10px;margin:8px 12px;white-space:pre-wrap;word-break:break-word}
    </style>
    <button class="sbl-inspector-btn" type="button" aria-expanded="false">Schema Inspector</button>
    <div class="sbl-inspector-panel" role="dialog" aria-label="Schema Inspector">
        <header>Wykryte schematy</header>
        <ul class="sbl-inspector-list"></ul>
        <pre class="sbl-inspector-pre" hidden></pre>
    </div>
    <script>
    (function(){
        const btn=document.querySelector('.sbl-inspector-btn');
        const panel=document.querySelector('.sbl-inspector-panel');
        const list=panel.querySelector('.sbl-inspector-list');
        const pre=panel.querySelector('.sbl-inspector-pre');
        function flatten(x){return Array.isArray(x)?x:[x];}
        function assess(item){
            const t = Array.isArray(item['@type']) ? item['@type'][0] : (item['@type']||'Unknown');
            let s='ok', m='OK';
            try{
                if(t==='Article' && !item.headline){s='warn';m='Brak headline';}
                if(t==='FAQPage' && !(item.mainEntity && item.mainEntity.length)){s='warn';m='Brak pytań (mainEntity)';}
                if(t==='Product' && (!item.name || !item.offers)){s='warn';m='Brak name/offers';}
                if(t==='HowTo' && !(item.step && item.step.length)){s='warn';m='Brak kroków (step)';}
                if(t==='LocalBusiness'){
                    const a=item.address||{}; if(!item.name || !(a.streetAddress||a.addressLocality)){s='warn';m='Uzupełnij name/adres';}
                }
            }catch(e){s='err';m='Błąd analizy';}
            return {type:t,status:s,msg:m};
        }
        function scan(){
            const found=[]; const raw=[];
            document.querySelectorAll('script[type="application/ld+json"]').forEach(s=>{
                try{
                    const data=JSON.parse(s.textContent.trim());
                    flatten(data).forEach(it=>{found.push(assess(it)); raw.push(it);});
                }catch(e){found.push({type:'Unknown',status:'err',msg:'Niepoprawny JSON'});}
            });
            list.innerHTML='';
            found.forEach((r,i)=>{
                const li=document.createElement('li');
                li.innerHTML = '<span>'+r.type+'</span>'+
                    '<span class="sbl-pill '+(r.status==='ok'?'ok':(r.status==='warn'?'warn':'err'))+'">'+r.msg+'</span>';
                li.addEventListener('click',()=>{ pre.hidden=false; pre.textContent=JSON.stringify(raw[i]||{},null,2); });
                list.appendChild(li);
            });
            if(!found.length){
                const li=document.createElement('li');
                li.textContent='Brak <script type="application/ld+json"> na stronie';
                list.appendChild(li);
            }
        }
        btn.addEventListener('click',()=>{
            const open = panel.style.display==='block';
            if(open){ panel.style.display='none'; btn.setAttribute('aria-expanded','false'); }
            else { scan(); panel.style.display='block'; btn.setAttribute('aria-expanded','true'); }
        });
    })();
    </script>
    <?php
});

// =======================
// ADMIN: MENU
// =======================
add_action('admin_menu', function () {
    add_menu_page(
        __('SchemaBoost Lite', 'schema-boost-lite'),
        'SchemaBoost',
        'manage_options',
        'schema-boost-lite',
        'sbl_render_admin_page',
        'dashicons-store',
        65
    );

    add_submenu_page('schema-boost-lite', 'Licencja', 'Licencja', 'manage_options', 'schema-boost-license', 'sbl_render_license_page');
});

// =======================
// ADMIN: EKRAN LICENCJI (wow + aktywacja)
// =======================
add_action('admin_post_sbl_license_activate', function () {
    if (!current_user_can('manage_options'))
        wp_die('Brak uprawnień');
    check_admin_referer('sbl_license_save');

    $key = isset($_POST['sbl_license_key']) ? sanitize_text_field($_POST['sbl_license_key']) : '';
    if (!$key) {
        wp_redirect(add_query_arg('sbl_msg', 'empty', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }

    // Normalizacja hosta (bez www.)
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $host = preg_replace('/^www\./i', '', (string) $host);

    // Anti-replay: znacznik czasu
    $payload = [
        'key' => $key,
        'domain' => $host,
        'ts' => time(),
    ];

    $res = sbl_license_remote('license/activate', $payload);

    if (is_wp_error($res)) {
        wp_redirect(add_query_arg('sbl_msg', 'error', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }

    // Weryfikacja sygnatury z serwera licencji
    $server_sig = $res['signature'] ?? '';
    $copy = $res;
    unset($copy['signature']);
    $calc = base64_encode(hash_hmac('sha256', wp_json_encode($copy), SBL_LICENSE_SHARED_SECRET, true));
    if (!hash_equals($server_sig, $calc)) {
        wp_redirect(add_query_arg('sbl_msg', 'bad_sig', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }

    // Jeśli licencja już dawno wygasła → jasny komunikat
    if (!empty($res['expires_at']) && (int) $res['expires_at'] < (time() - 7 * DAY_IN_SECONDS)) {
        wp_redirect(add_query_arg('sbl_msg', 'expired', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }

    // Zapis stanu lokalnego
    update_option(SBL_OPT_LICENSE_KEY, $key);
    update_option(SBL_OPT_LICENSE_STATUS, sanitize_text_field($res['status'] ?? 'inactive'));
    update_option(SBL_OPT_LICENSE_EXPIRES, (int) ($res['expires_at'] ?? 0));

    $ents = $res['entitlements'] ?? [];
    if (!is_array($ents)) {
        $ents = [];
    }
    update_option(SBL_OPT_LICENSE_ENTITLEMENTS, wp_json_encode($ents));

    if (!empty($res['portal_url'])) {
        update_option(SBL_OPT_LICENSE_PORTAL, esc_url_raw($res['portal_url']));
    }

    update_option(SBL_OPT_LICENSE_LASTCHECK, time());

    wp_redirect(add_query_arg('sbl_msg', 'activated', admin_url('admin.php?page=schema-boost-license')));
    exit;
});

// Zapis stanu lokalnego (z drobnym utwardze

add_action('admin_post_sbl_license_deactivate', function () {
    if (!current_user_can('manage_options'))
        wp_die('Brak uprawnień');
    check_admin_referer('sbl_license_save');

    $key = get_option(SBL_OPT_LICENSE_KEY, '');

    if ($key) {
        // [DODANE] Normalizacja hosta: usuwamy "www." i bierzemy sam host z home_url()
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        $host = preg_replace('/^www\./i', '', (string) $host);

        // [DODANE – zalecane] znacznik czasu dla anti-replay (działa też, jeśli serwer jeszcze go nie wymaga)
        $payload = [
            'key' => $key,
            'domain' => $host,
            'ts' => time(), // można zostawić nawet jeśli serwer jeszcze nie weryfikuje
        ];

        sbl_license_remote('license/deactivate', $payload);
    }

    // Czyszczenie stanu lokalnego – bez zmian
    delete_option(SBL_OPT_LICENSE_KEY);
    delete_option(SBL_OPT_LICENSE_STATUS);
    delete_option(SBL_OPT_LICENSE_EXPIRES);
    delete_option(SBL_OPT_LICENSE_ENTITLEMENTS);
    delete_option(SBL_OPT_LICENSE_PORTAL);
    delete_option(SBL_OPT_LICENSE_LASTCHECK);

    wp_redirect(add_query_arg('sbl_msg', 'deactivated', admin_url('admin.php?page=schema-boost-license')));
    exit;
});

// --- Portal subskrypcji (Stripe Billing Portal) ---
add_action('admin_post_sbl_license_portal', function () {
    if (!current_user_can('manage_options'))
        wp_die('Brak uprawnień');
    check_admin_referer('sbl_license_save');

    $key = get_option('sbl_license_key', '');
    if (!$key) {
        wp_redirect(add_query_arg('sbl_msg', 'no_key', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }

    // Poproś serwer licencji o świeży link do Billing Portal
    if (!function_exists('sbl_license_remote')) {
        // jeżeli helper jest w innym pliku – upewnij się, że jest załadowany
    }
    $res = sbl_license_remote('license/portal', [
        'key' => $key,
        'domain' => wp_parse_url(home_url(), PHP_URL_HOST),
        'return_url' => admin_url('admin.php?page=schema-boost-license'),
    ]);
    if (is_wp_error($res) || empty($res['portal_url'])) {
        wp_redirect(add_query_arg('sbl_msg', 'portal_error', admin_url('admin.php?page=schema-boost-license')));
        exit;
    }
    update_option('sbl_license_portal_url', esc_url_raw($res['portal_url']));
    wp_redirect($res['portal_url']); // Go!
    exit;
});

function sbl_render_license_page()
{
    if (!current_user_can('manage_options'))
        return;
    $key = get_option(SBL_OPT_LICENSE_KEY, '');
    $status = sbl_license_status_label();
    $portal = get_option(SBL_OPT_LICENSE_PORTAL, '');
    $is_on = sbl_is_pro_active();

    // [DODANE] 24h cooldown na odświeżanie statusu (opcjonalny mechanizm, pod kolejny przycisk/cron)
    function sbl_license_maybe_refresh(callable $fetch_cb)
    {
        $last = (int) get_option(SBL_OPT_LICENSE_LASTCHECK, 0);
        if ($last > (time() - DAY_IN_SECONDS))
            return; // odświeżaj max 1x/dobę
        $key = get_option(SBL_OPT_LICENSE_KEY, '');
        if (!$key)
            return;
        $host = preg_replace('/^www\./i', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
        $res = $fetch_cb($key, $host);
        if (is_wp_error($res))
            return;
        if (!empty($res['status']))
            update_option(SBL_OPT_LICENSE_STATUS, sanitize_text_field($res['status']));
        if (isset($res['expires_at']))
            update_option(SBL_OPT_LICENSE_EXPIRES, (int) $res['expires_at']);
        if (!empty($res['entitlements']))
            update_option(SBL_OPT_LICENSE_ENTITLEMENTS, wp_json_encode($res['entitlements']));
        update_option(SBL_OPT_LICENSE_LASTCHECK, time());
    }



    if (isset($_GET['sbl_msg'])) {
        $msg = sanitize_text_field($_GET['sbl_msg']);
        $map = [
            'activated' => ['updated', '🎉 Licencja aktywowana – funkcje PRO odblokowane!'],
            'deactivated' => ['updated', 'Licencja dezaktywowana.'],
            'empty' => ['error', 'Wpisz klucz licencyjny.'],
            'bad_sig' => ['error', 'Nieprawidłowa sygnatura odpowiedzi serwera licencji.'],
            'error' => ['error', 'Nie udało się skontaktować z serwerem licencji.'],
            'no_key' => ['error', 'Brak aktywnego klucza – nie można otworzyć portalu rozliczeń.'],
            'portal_error' => ['error', 'Nie udało się pobrać linku do portalu rozliczeń. Spróbuj ponownie.'],
            'expired' => [
                'error',
                'Licencja wygasła – odnów subskrypcję w <a href="' . esc_url(
                    wp_nonce_url(admin_url('admin-post.php?action=sbl_license_portal'), 'sbl_license_save')
                ) . '">Billing Portal</a>.'
            ],

        ];
        if (isset($map[$msg]))
            echo '<div class="' . $map[$msg][0] . ' notice is-dismissible"><p>' . $map[$msg][1] . '</p></div>';
    }
    ?>
    <div class="wrap sbl-lic-wrap">
        <h1>SchemaBoost – Licencja</h1>

        <div class="sbl-hero">
            <div class="sbl-hero__content">
                <h2>Odblokuj PRO 🚀</h2>
                <p>Zyskaj LocalBusiness z pełnym formularzem, JSON-LD na froncie oraz wsparcie premium.</p>
                <p class="sbl-cta">
                    <a class="button button-primary button-hero" href="<?php echo esc_url(SBL_PRO_PAYMENT_LINK); ?>"
                        target="_blank" rel="noopener">Kup PRO</a>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=schema-boost-license')); ?>">Mam
                        klucz – aktywuj</a>
                </p>
            </div>
        </div>

        <?php
        $portal_saved = get_option('sbl_license_portal_url', '');
        $portal_href = $portal_saved ? $portal_saved : wp_nonce_url(admin_url('admin-post.php?action=sbl_license_portal'), 'sbl_license_save');
        $portal_attr = $portal_saved ? ' target="_blank" rel="noopener"' : '';
        ?>
        <a class="button sbl-portal-btn" href="<?php echo esc_url($portal_href); ?>" <?php echo $portal_attr; ?>>
            Zarządzaj subskrypcją / Rezygnacja
        </a>

        <div class="sbl-trust">
            <b>Pełna kontrola:</b> przejście do <em>Stripe Billing Portal</em> pozwala w każdej chwili
            zmienić plan, zaktualizować płatność lub zrezygnować. Zawsze wrócisz tutaj po zamknięciu portalu.
        </div>

        <style>
            .sbl-portal-btn {
                background: linear-gradient(135deg, #ef4444, #f97316);
                border: none;
                color: #fff !important;
                box-shadow: 0 8px 18px rgba(239, 68, 68, .35);
                color: black !important;
                transition: transform .08s ease, box-shadow .2s ease, filter .2s ease;
            }

            .sbl-portal-btn:hover {
                transform: translateY(-1px);
                filter: brightness(1.02);
                box-shadow: 0 10px 22px rgba(249, 115, 22, .4);
            }

            .sbl-trust {
                margin-top: 14px;
                padding: 12px 14px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: linear-gradient(180deg, #f8fafc, #ffffff);
            }

            .sbl-trust b {
                color: #0f172a
            }

            .sbl-desc {
                margin: .35rem 0 0;
                color: #556;
                opacity: .95
            }

            .sbl-help {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-left: .4rem;
                cursor: pointer;
                position: relative
            }

            .sbl-help .dashicons {
                font-size: 16px;
                width: 18px;
                height: 18px;
                line-height: 18px;
                border-radius: 999px;
                background: #f3f4f6
            }

            .sbl-help:focus .dashicons,
            .sbl-help:hover .dashicons {
                background: #e5e7eb
            }

            .sbl-help-popover {
                position: absolute;
                left: 50%;
                transform: translateX(-50%) translateY(8px);
                min-width: 240px;
                max-width: 360px;
                padding: .6rem .75rem;
                border: 1px solid #e5e7eb;
                background: #fff;
                color: #111;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
                border-radius: 10px;
                font-size: 12.5px;
                line-height: 1.45;
                z-index: 9999;
                display: none;
            }

            .sbl-help:focus .sbl-help-popover,
            .sbl-help:hover .sbl-help-popover {
                display: block
            }

            @media (prefers-reduced-motion:no-preference) {
                .sbl-help-popover {
                    transition: opacity .12s ease, transform .12s ease;
                    opacity: 0
                }

                .sbl-help:focus .sbl-help-popover,
                .sbl-help:hover .sbl-help-popover {
                    opacity: 1;
                    transform: translateX(-50%) translateY(6px)
                }
            }

            .sbl-field {
                background: #fff;
                border: 1px solid #eaecee;
                border-radius: 14px;
                padding: 14px 16px;
                margin: 12px 0
            }

            .sbl-field h3 {
                margin: 0 0 .25rem;
                font-size: 13.5px;
                display: flex;
                align-items: center;
                gap: .4rem
            }

            .sbl-field input[type="text"],
            .sbl-field input[type="url"],
            .sbl-field input[type="email"],
            .sbl-field input[type="number"],
            .sbl-field textarea {
                width: 100%;
            }
        </style>

        <script>
            document.addEventListener('click', (e) => {
                const open = document.querySelectorAll('.sbl-help-popover');
                open.forEach(p => { if (!p.parentElement.contains(e.target)) { p.style.display = 'none'; } });
                if (e.target.closest('.sbl-help')) {
                    const p = e.target.closest('.sbl-help').querySelector('.sbl-help-popover');
                    if (p) { p.style.display = 'block'; }
                }
            });
        </script>


        <h2>Status</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th>Licencja</th>
                <td>
                    <strong><?php echo esc_html($status); ?></strong>
                    <?php if ($is_on): ?>
                        <span class="dashicons dashicons-yes" style="color:#46b450;margin-left:6px;"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-no-alt" style="color:#dc3232;margin-left:6px;"></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sbl-lic-form">
            <?php wp_nonce_field('sbl_license_save'); ?>
            <input type="hidden" name="action"
                value="<?php echo esc_attr($key ? 'sbl_license_deactivate' : 'sbl_license_activate'); ?>">
            <p>
                <label><strong>Klucz licencyjny</strong><br>
                    <input type="text" name="sbl_license_key" class="regular-text" value="<?php echo esc_attr($key); ?>"
                        placeholder="XXXX-XXXX-XXXX-XXXX" <?php disabled($key !== ''); ?>>
                </label>
            </p>
            <p>
                <?php if (!$key): ?>
                    <button class="button button-primary" type="submit">Aktywuj licencję</button>
                    <a class="button" href="<?php echo esc_url(SBL_PRO_PAYMENT_LINK); ?>" target="_blank" rel="noopener"
                        style="margin-left:6px;">Kup PRO</a>
                <?php else: ?>
                    <button class="button" type="submit">Dezaktywuj licencję</button>
                <?php endif; ?>
                <?php if ($portal): ?>
                    <a class="button" href="<?php echo esc_url($portal); ?>" target="_blank" rel="noopener"
                        style="margin-left:6px;">Zarządzaj subskrypcją</a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <style>
        .sbl-hero {
            margin: 14px 0 22px;
            border-radius: 14px;
            padding: 24px;
            background:
                radial-gradient(1000px 400px at 10% -20%, #c7d2fe, transparent 40%),
                radial-gradient(900px 500px at 110% 10%, #bbf7d0, transparent 35%),
                linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 1px solid #e2e8f0;
            box-shadow: 0 6px 24px rgba(30, 41, 59, .08)
        }

        .sbl-hero h2 {
            margin: 0 0 6px;
            font-size: 24px
        }

        .sbl-hero p {
            margin: 6px 0 0;
            font-size: 14px
        }

        .sbl-cta .button-hero {
            font-size: 14px;
            padding: 6px 14px
        }
    </style>
    <?php if (isset($_GET['sbl_msg']) && $_GET['sbl_msg'] === 'activated'): ?>
        <canvas id="sbl-confetti" style="position:fixed;pointer-events:none;inset:0;z-index:99999"></canvas>
        <script>
            (function () {
                const c = document.getElementById('sbl-confetti'); const x = c.getContext('2d'); let w = window.innerWidth, h = window.innerHeight; c.width = w; c.height = h;
                const P = Array.from({ length: 180 }).map(() => ({ x: Math.random() * w, y: -10 - Math.random() * h, vx: (Math.random() - 0.5) * 2, vy: 2 + Math.random() * 3, s: 2 + Math.random() * 4, r: Math.random() * Math.PI }));
                let t = 0; function draw() { x.clearRect(0, 0, w, h); P.forEach(p => { p.x += p.vx; p.y += p.vy; p.r += 0.03; if (p.y > h + 20) { p.y = -10; p.x = Math.random() * w; } x.save(); x.translate(p.x, p.y); x.rotate(p.r); x.fillStyle = 'hsl(' + ((t + p.x) % 360) + ',90%,60%)'; x.fillRect(-p.s / 2, -p.s / 2, p.s, p.s); x.restore(); }); t += 2; requestAnimationFrame(draw); }
                draw(); setTimeout(() => { c.remove(); }, 2400);
                window.addEventListener('resize', () => { w = window.innerWidth; h = window.innerHeight; c.width = w; c.height = h; });
            })();
        </script>
    <?php endif; ?>
<?php
}

// =======================
// ADMIN: PANEL USTAWIEŃ (z Twojej bazy, z dodanym gatingiem PRO)
// =======================
add_action('admin_menu', function () { /* już dodane wyżej */});

function sbl_render_admin_page()
{
    if (!current_user_can('manage_options'))
        return;

    // --- zapis ustawień (z Twojej wersji) ---
    if (isset($_POST['sbl_save_settings'])) {
        check_admin_referer('sbl_save_settings');

        // FAQ
        $new_faq = [];
        $qs = (array) ($_POST['sbl_faq_q'] ?? []);
        $as = (array) ($_POST['sbl_faq_a'] ?? []);
        $count = max(count($qs), count($as));
        for ($i = 0; $i < $count; $i++) {
            $q = isset($qs[$i]) ? sanitize_text_field($qs[$i]) : '';
            $a = isset($as[$i]) ? sanitize_textarea_field($as[$i]) : '';
            if ($q !== '' && $a !== '')
                $new_faq[] = ["question" => $q, "answer" => $a];
        }
        update_option(SBL_OPT_FAQ_JSON, wp_json_encode($new_faq));

        // Product
        update_option(SBL_OPT_ENABLE_PRODUCT, (isset($_POST['sbl_enable_product']) && $_POST['sbl_enable_product'] === '1') ? '1' : '0');

        // HowTo
        $howto_on = (isset($_POST['sbl_enable_howto']) && $_POST['sbl_enable_howto'] === '1') ? '1' : '0';
        $howto_name = sanitize_text_field($_POST['sbl_howto_name'] ?? '');
        $howto_tt = strtoupper(sanitize_text_field($_POST['sbl_howto_totaltime'] ?? ''));
        $step_names = (array) ($_POST['sbl_step_name'] ?? []);
        $step_texts = (array) ($_POST['sbl_step_text'] ?? []);
        $steps = [];
        $sc = max(count($step_names), count($step_texts));
        for ($i = 0; $i < $sc; $i++) {
            $sn = sanitize_text_field($step_names[$i] ?? '');
            $st = sanitize_textarea_field($step_texts[$i] ?? '');
            if ($sn !== '' || $st !== '')
                $steps[] = ['name' => $sn, 'text' => $st];
        }
        update_option(SBL_OPT_ENABLE_HOWTO, $howto_on);
        update_option(SBL_OPT_HOWTO_NAME, $howto_name);
        update_option(SBL_OPT_HOWTO_TOTALTIME, $howto_tt);
        update_option(SBL_OPT_HOWTO_STEPS_JSON, wp_json_encode($steps));

        // LocalBusiness (wartości – uwaga: UI może być zablokowane, ale gdy ktoś obejdzie, to i tak zapisze; gating jest UX-em)
        update_option(SBL_OPT_LB_ENABLE, (isset($_POST['sbl_lb_enable']) && $_POST['sbl_lb_enable'] === '1') ? '1' : '0');
        update_option(SBL_OPT_LB_SITEWIDE, (isset($_POST['sbl_lb_sitewide']) && $_POST['sbl_lb_sitewide'] === '1') ? '1' : '0');
        update_option(SBL_OPT_LB_TYPE, sanitize_text_field($_POST['sbl_lb_type'] ?? 'LocalBusiness'));
        update_option(SBL_OPT_LB_NAME, sanitize_text_field($_POST['sbl_lb_name'] ?? ''));
        update_option(SBL_OPT_LB_URL, esc_url_raw($_POST['sbl_lb_url'] ?? home_url('/')));
        update_option(SBL_OPT_LB_PHONE, sanitize_text_field($_POST['sbl_lb_phone'] ?? ''));
        update_option(SBL_OPT_LB_EMAIL, sanitize_text_field($_POST['sbl_lb_email'] ?? ''));
        update_option(SBL_OPT_LB_PRICE, sanitize_text_field($_POST['sbl_lb_price'] ?? ''));
        update_option(SBL_OPT_LB_STREET, sanitize_text_field($_POST['sbl_lb_street'] ?? ''));
        update_option(SBL_OPT_LB_LOCALITY, sanitize_text_field($_POST['sbl_lb_locality'] ?? ''));
        update_option(SBL_OPT_LB_REGION, sanitize_text_field($_POST['sbl_lb_region'] ?? ''));
        update_option(SBL_OPT_LB_POSTAL, sanitize_text_field($_POST['sbl_lb_postal'] ?? ''));
        update_option(SBL_OPT_LB_COUNTRY, strtoupper(sanitize_text_field($_POST['sbl_lb_country'] ?? '')));
        $lat = trim($_POST['sbl_lb_lat'] ?? '');
        $lng = trim($_POST['sbl_lb_lng'] ?? '');
        update_option(SBL_OPT_LB_LAT, is_numeric($lat) ? $lat : '');
        update_option(SBL_OPT_LB_LNG, is_numeric($lng) ? $lng : '');
        update_option(SBL_OPT_LB_SAMEAS, (string) ($_POST['sbl_lb_sameas'] ?? ''));
        $days = explode(',', SBL_LB_DAY_KEYS);
        $hours = [];
        foreach ($days as $dk) {
            $hours[$dk] = ['closed' => !empty($_POST["sbl_lb_{$dk}_closed"]) ? 1 : 0, 'open' => preg_replace('/[^0-9:]/', '', $_POST["sbl_lb_{$dk}_open"] ?? ''), 'close' => preg_replace('/[^0-9:]/', '', $_POST["sbl_lb_{$dk}_close"] ?? '')];
        }
        update_option(SBL_OPT_LB_HOURS, wp_json_encode($hours));

        // Organization (no-location, PRO)
        update_option(SBL_OPT_ORG_ENABLE, (isset($_POST['sbl_org_enable']) && $_POST['sbl_org_enable'] === '1') ? '1' : '0');
        update_option(SBL_OPT_ORG_NAME, sanitize_text_field($_POST['sbl_org_name'] ?? ''));
        update_option(SBL_OPT_ORG_URL, esc_url_raw($_POST['sbl_org_url'] ?? home_url('/')));
        update_option(SBL_OPT_ORG_PHONE, sanitize_text_field($_POST['sbl_org_phone'] ?? ''));
        update_option(SBL_OPT_ORG_EMAIL, sanitize_text_field($_POST['sbl_org_email'] ?? ''));
        update_option(SBL_OPT_ORG_SAMEAS, (string) ($_POST['sbl_org_sameas'] ?? ''));

        // Dev tools (PRO): On-page Schema Inspector
        update_option(
            SBL_OPT_INSPECTOR_ENABLE,
            (isset($_POST['sbl_inspector_enable']) && $_POST['sbl_inspector_enable'] === '1') ? '1' : '0'
        );

        echo '<div class="updated notice is-dismissible"><p>Ustawienia zapisane.</p></div>';
    }

    // --- dane do widoku (skrócone z Twojej wersji, bez utraty pól) ---
    $faq_rows = json_decode(get_option(SBL_OPT_FAQ_JSON, '[]'), true);
    if (!is_array($faq_rows))
        $faq_rows = [];
    $wc_present = class_exists('WooCommerce');
    $product_on = get_option(SBL_OPT_ENABLE_PRODUCT, '0') === '1';
    $howto_on = get_option(SBL_OPT_ENABLE_HOWTO, '0') === '1';
    $howto_name = get_option(SBL_OPT_HOWTO_NAME, '');
    $howto_tt = get_option(SBL_OPT_HOWTO_TOTALTIME, '');
    $howto_steps = json_decode(get_option(SBL_OPT_HOWTO_STEPS_JSON, '[]'), true);
    if (!is_array($howto_steps))
        $howto_steps = [];
    $lb_on = get_option(SBL_OPT_LB_ENABLE, '0') === '1';
    $lb_sw = get_option(SBL_OPT_LB_SITEWIDE, '0') === '1';
    $lb_type = get_option(SBL_OPT_LB_TYPE, 'LocalBusiness');
    $hours_current = json_decode(get_option(SBL_OPT_LB_HOURS, '{}'), true);
    if (!is_array($hours_current))
        $hours_current = [];
    $inspector_on = (get_option(SBL_OPT_INSPECTOR_ENABLE, '0') === '1');
    // Organization (no-location)
    $org_on = get_option(SBL_OPT_ORG_ENABLE, '0') === '1';
    $daysLabels = ['Mo' => 'Poniedziałek', 'Tu' => 'Wtorek', 'We' => 'Środa', 'Th' => 'Czwartek', 'Fr' => 'Piątek', 'Sa' => 'Sobota', 'Su' => 'Niedziela'];
    ?>
    <div class="wrap">
        <h1>SchemaBoost Lite</h1>

        <?php
        // ... w funkcji sbl_render_admin_page(), zaraz po <h1>SchemaBoost Lite</h1>:
    
        $faq_count = is_array($faq_rows) ? count($faq_rows) : 0;
        $steps_count = is_array($howto_steps) ? count($howto_steps) : 0;
        $lb_gate_ok = (sbl_is_pro_active() && sbl_license_has('localbusiness'));
        $howto_gate_ok = (sbl_is_pro_active() && sbl_license_has('howto'));
        $inspector_gate_ok = (sbl_is_pro_active() && sbl_license_has('inspector'));

        ?>
        <style>
            .sbl-status {
                margin: 14px 0 18px;
                padding: 12px 14px;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
            }

            .sbl-status ul {
                margin: 0;
                padding-left: 18px;
                line-height: 1.9;
            }

            .sbl-ok {
                color: #137333;
                font-weight: 600;
            }

            .sbl-warn {
                color: #b06f00;
                font-weight: 600;
            }

            .sbl-bad {
                color: #b00020;
                font-weight: 600;
            }
        </style>

        <div class="sbl-hero">
            <div class="sbl-hero__left">
                <h2>Witaj w SchemaBoost 👋</h2>
                <p>Dodajemy do Twojej witryny poprawny <b>schema.org</b>, aby Google lepiej rozumiało treści i częściej
                    pokazywało <em>rozszerzone wyniki</em> (gwiazdki, FAQ, instrukcje, Rich Results).</p>
                <ul>
                    <li>📰 <b>Article</b> – automatycznie na wpisach</li>
                    <li>❓ <b>FAQ</b> – pytania i odpowiedzi w SERP</li>
                    <li>🛒 <b>Product</b> (WooCommerce) – cena, dostępność, oceny</li>
                    <li>🧑‍🍳 <b>HowTo</b> (PRO) – kroki i czas wykonania</li>
                    <li>📍 <b>LocalBusiness</b> (PRO) – dane firmy do wyników lokalnych/Map</li>
                </ul>
                <p class="sbl-hero__cta">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=schema-boost-license')); ?>"
                        class="button button-primary">Aktywuj PRO</a>
                    <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener"
                        class="button">Sprawdź w Rich Results Test</a>
                </p>
            </div>
            <div class="sbl-hero__right">
                <div class="sbl-illus">★ ★ ★</div>
                <p>Lepsze zrozumienie treści → większa widoczność → więcej kliknięć.</p>
            </div>
        </div>
        <style>
            .sbl-hero {
                margin: 10px 0 18px;
                display: flex;
                gap: 18px;
                align-items: stretch;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 16px 18px;
                background: radial-gradient(900px 280px at 10% -10%, #c7d2fe, transparent 40%),
                    radial-gradient(800px 300px at 110% 0, #bbf7d0, transparent 35%),
                    linear-gradient(180deg, #f8fafc, #ffffff);
            }

            .sbl-hero__left {
                flex: 1
            }

            .sbl-hero__left h2 {
                margin: 2px 0 6px
            }

            .sbl-hero__left ul {
                margin: 8px 0 10px 18px
            }

            .sbl-hero__cta .button.button-primary {
                margin-right: 6px
            }

            .sbl-hero__right {
                width: 280px;
                min-width: 240px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }

            .sbl-illus {
                font-size: 32px;
                letter-spacing: 6px;
                margin: 10px 0 4px
            }
        </style>


        <div class="sbl-status">
            <h2 style="margin:0 0 6px;">Stan wtyczki (postęp)</h2>
            <ul>
                <li>✅ <strong>Article Schema:</strong> <span class="sbl-ok">włączone</span> <em>(na stronach pojedynczych
                        wpisów)</em></li>

                <li>
                    <?php if ($faq_count > 0): ?>
                        ✅ <strong>FAQ Schema:</strong> <span class="sbl-ok">skonfigurowane</span> <em>(pozycji:
                            <?php echo (int) $faq_count; ?>)</em>
                    <?php else: ?>
                        ⚠️ <strong>FAQ Schema:</strong> <span class="sbl-warn">brak pozycji</span>
                    <?php endif; ?>
                </li>

                <li>
                    <?php if ($wc_present && $product_on): ?>
                        ✅ <strong>Product Schema:</strong> <span class="sbl-ok">włączone</span> <em>(WooCommerce wykryty)</em>
                    <?php elseif ($wc_present && !$product_on): ?>
                        ⏸️ <strong>Product Schema:</strong> wyłączone <em>(WooCommerce wykryty)</em>
                    <?php else: ?>
                        ❌ <strong>Product Schema:</strong> <span class="sbl-bad">WooCommerce nie wykryty</span>
                    <?php endif; ?>
                </li>


                <?php if ($howto_on): ?>
                    <?php if ($howto_gate_ok): ?>
                        ✅ <strong>HowTo (PRO):</strong> <span class="sbl-ok">włączone</span>
                        <em>(kroków:
                            <?php echo (int) $steps_count; ?>            <?php echo $howto_name ? ', nazwa: ' . esc_html($howto_name) : ''; ?>            <?php echo $howto_tt ? ', totalTime: ' . esc_html($howto_tt) : ''; ?>)</em>
                    <?php else: ?>
                        🔒 <strong>HowTo (PRO):</strong> włączone w ustawieniach,
                        <span class="sbl-warn">ale zablokowane – aktywuj licencję</span>
                    <?php endif; ?>
                <?php else: ?>
                    ⏸️ <strong>HowTo (PRO):</strong> wyłączone
                <?php endif; ?>
                </li>

                <li>
                    <?php if ($lb_on): ?>
                        <?php if ($lb_gate_ok): ?>
                            ✅ <strong>LocalBusiness (PRO):</strong> <span class="sbl-ok">włączone</span>
                            — typ: <?php echo esc_html($lb_type); ?> | <?php echo $lb_sw ? 'sitewide' : 'strona główna'; ?>
                        <?php else: ?>
                            🔒 <strong>LocalBusiness (PRO):</strong> włączone w ustawieniach,
                            <span class="sbl-warn">ale zablokowane – aktywuj licencję</span>
                            (typ: <?php echo esc_html($lb_type); ?> | <?php echo $lb_sw ? 'sitewide' : 'strona główna'; ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        ⏸️ <strong>LocalBusiness (PRO):</strong> wyłączone
                    <?php endif; ?>
                </li>
            </ul>
        </div>


        <div class="notice notice-info is-dismissible">
            <p><strong>Wskazówka:</strong> w sekcji <em>LocalBusiness (PRO)</em> edycja pól jest zablokowana do czasu
                aktywacji licencji. Kliknij „Kup PRO” lub „Mam klucz – aktywuj”.</p>
        </div>

        <form method="post">
            <?php wp_nonce_field('sbl_save_settings'); ?>

            <!-- FAQ -->
            <h2>FAQ Schema</h2>
            <table class="widefat striped" style="max-width:1000px;" id="sbl-faq-table">
                <thead>
                    <tr>
                        <th style="width:40%;">Pytanie</th>
                        <th style="width:55%;">Odpowiedź</th>
                        <th style="width:5%;">Usuń</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $min_faq_rows = max(3, count($faq_rows));
                    for ($i = 0; $i < $min_faq_rows; $i++) {
                        $q = esc_attr($faq_rows[$i]['question'] ?? '');
                        $a = esc_textarea($faq_rows[$i]['answer'] ?? '');
                        echo '<tr><td><input type="text" name="sbl_faq_q[]" value="' . $q . '" style="width:100%"></td><td><textarea name="sbl_faq_a[]" rows="2" style="width:100%">' . $a . '</textarea></td><td style="text-align:center;"><button class="button sbl-remove-row" type="button">✕</button></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="sbl-add-faq-row">+ Dodaj wiersz</button></p>

            <hr>

            <!-- PRODUCT -->
            <h2>WooCommerce Product Schema</h2>
            <?php if (!$wc_present): ?>
                <p style="color:#cc0000;">WooCommerce nie jest aktywny – Product Schema włączy się po instalacji WooCommerce.</p>
            <?php endif; ?>
            <p>
                <label class="sbl-switch">
                    <input type="checkbox" name="sbl_enable_product" value="1" <?php checked(get_option(SBL_OPT_ENABLE_PRODUCT), '1'); ?> />
                    <span><?php echo esc_html__('Włącz na stronach produktów', 'schema-boost-lite'); ?></span>
                </label>
            </p>

                <hr>

                <!-- HOWTO -->
                <h2>HowTo (PRO)</h2>

                <?php if (!$howto_gate_ok): ?>
                    <div class="sbl-pro-upsell sbl-pro-benefits">
                        <h3>🔒 HowTo jest funkcją PRO</h3>
                        <p>Dzięki odblokowaniu <b>HowTo Schema</b> Twoje instrukcje i poradniki zyskają <span
                                class="sbl-highlight">widoczność w Google</span> w formie atrakcyjnych <em>Rich Results</em>.
                        </p>

                        <ul class="sbl-benefits-list">
                            <li>📈 Więcej wejść z wyników wyszukiwania (lepszy CTR).</li>
                            <li>⭐ Twoje treści mogą pojawiać się z <b>gwiazdkami i krokami</b>.</li>
                            <li>⏱️ Pokaż <b>czas wykonania</b> zadania – Google to uwielbia.</li>
                            <li>📋 Instrukcje krok po kroku → większe zaufanie i lepsze SEO.</li>
                        </ul>

                        <p class="sbl-cta">
                            <a class="button button-primary button-hero" href="<?php echo esc_url(SBL_PRO_PAYMENT_LINK); ?>"
                                target="_blank" rel="noopener">🚀 Odblokuj PRO</a>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=schema-boost-license')); ?>">🔑
                                Mam już klucz</a>
                        </p>
                    </div>
                <?php endif; ?>

                <div id="sbl-howto-wrapper" class="<?php echo $howto_gate_ok ? '' : 'sbl-pro-locked-howto'; ?>">
                    <label style="display:block;margin:8px 0;">
                        <input type="checkbox" name="sbl_enable_howto" value="1" <?php checked($howto_on, true);
                        echo $howto_gate_ok ? '' : ' disabled'; ?>>
                        Włącz HowTo Schema (na stronach pojedynczych wpisów)
                    </label>

                    <p><input type="text" id="sbl_howto_name" name="sbl_howto_name"
                            value="<?php echo esc_attr($howto_name); ?>" style="width:600px;"
                            placeholder="Nazwa instrukcji (opcjonalnie)" <?php echo $howto_gate_ok ? '' : 'disabled'; ?>>
                    </p>
                    <p><input type="text" id="sbl_howto_totaltime" name="sbl_howto_totaltime"
                            value="<?php echo esc_attr($howto_tt); ?>" style="width:300px;" placeholder="PT45M (ISO 8601)"
                            <?php echo $howto_gate_ok ? '' : 'disabled'; ?>></p>

                    <table class="widefat striped" style="max-width:1000px;" id="sbl-howto-table">
                        <thead>
                            <tr>
                                <th style="width:35%;">Nazwa kroku</th>
                                <th style="width:60%;">Opis kroku</th>
                                <th style="width:5%;">Usuń</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $min_step_rows = max(3, count($howto_steps));
                            for ($i = 0; $i < $min_step_rows; $i++) {
                                $sn = esc_attr($howto_steps[$i]['name'] ?? '');
                                $st = esc_textarea($howto_steps[$i]['text'] ?? '');
                                echo '<tr><td><input type="text" name="sbl_step_name[]" value="' . $sn . '" style="width:100%" ' . ($howto_gate_ok ? '' : 'disabled') . '></td><td><textarea name="sbl_step_text[]" rows="2" style="width:100%" ' . ($howto_gate_ok ? '' : 'disabled') . '>' . $st . '</textarea></td><td style="text-align:center;"><button class="button sbl-remove-row" type="button" ' . ($howto_gate_ok ? '' : 'disabled') . '>✕</button></td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <p><button type="button" class="button" id="sbl-add-step-row" <?php echo $howto_gate_ok ? '' : 'disabled'; ?>>+ Dodaj krok</button></p>
                </div>


                <hr>

                <!-- LOCALBUSINESS (PRO) -->
                <h2>LocalBusiness (PRO)</h2>

                <?php if (!$lb_gate_ok): ?>
                    <div class="sbl-pro-upsell sbl-pro-benefits">
                        <h3>🏬 LocalBusiness jest funkcją PRO</h3>
                        <p>Z <b>LocalBusiness Schema</b> Twoja firma zyskuje lepszą <span class="sbl-highlight">widoczność
                                lokalną</span> i bogatszy wygląd w Google.</p>
                        <ul class="sbl-benefits-list">
                            <li>📍 Pojawiaj się wyżej w wynikach lokalnych i na Mapach Google.</li>
                            <li>⏰ Pokaż <b>godziny otwarcia</b>, telefon i adres prosto w SERP.</li>
                            <li>🔗 Dodaj profile (sameAs) → <b>większe zaufanie</b> i spójny branding.</li>
                            <li>🚀 Wyróżnij się na tle konkurencji w Twojej okolicy.</li>
                        </ul>
                        <p class="sbl-cta">
                            <a class="button button-primary button-hero" href="<?php echo esc_url(SBL_PRO_PAYMENT_LINK); ?>"
                                target="_blank" rel="noopener">🚀 Odblokuj PRO</a>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=schema-boost-license')); ?>">🔑
                                Mam już klucz</a>
                        </p>
                    </div>
                <?php endif; ?>

                <div id="sbl-pro-wrapper" class="sbl-card">
                    <div class="sbl-row sbl-row--toggles">
                        <label class="sbl-switch"><input type="checkbox" name="sbl_lb_enable" value="1" <?php checked($lb_on, true); ?>> <span>Włącz LocalBusiness JSON‑LD</span></label>
                        <label class="sbl-switch"><input type="checkbox" name="sbl_lb_sitewide" value="1" <?php checked($lb_sw, true); ?>> <span>Emituj na całej witrynie</span></label>
                    </div>
                    <p>
                        <label><strong>Typ</strong>:
                            <select name="sbl_lb_type" style="width:340px;">
                                <?php
                                $types = ['LocalBusiness', 'Store', 'Restaurant', 'CafeOrCoffeeShop', 'Bakery', 'BarOrPub', 'Dentist', 'MedicalBusiness', 'BeautySalon', 'AutoRepair', 'Hotel', 'RealEstateAgent', 'LegalService', 'AccountingService', 'AutomotiveBusiness', 'HomeAndConstructionBusiness', 'HealthAndBeautyBusiness'];
                                foreach ($types as $t) {
                                    echo '<option value="' . esc_attr($t) . '" ' . selected($lb_type, $t, false) . '>' . esc_html($t) . '</option>';
                                }
                                ?>
                            </select>
                        </label>
                    </p>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="sbl_lb_name">Nazwa firmy</label></th>
                            <td><input type="text" id="sbl_lb_name" name="sbl_lb_name" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_NAME, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_url">Adres URL</label></th>
                            <td><input type="url" id="sbl_lb_url" name="sbl_lb_url" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_URL, home_url('/'))); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_phone">Telefon</label></th>
                            <td><input type="text" id="sbl_lb_phone" name="sbl_lb_phone" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_PHONE, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_email">E-mail</label></th>
                            <td><input type="email" id="sbl_lb_email" name="sbl_lb_email" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_EMAIL, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_price">Zakres cen</label></th>
                            <td><input type="text" id="sbl_lb_price" name="sbl_lb_price" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_PRICE, '')); ?>" placeholder="$$"></td>
                        </tr>
                    </table>
                    <h3>Adres</h3>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="sbl_lb_street">Ulica i numer</label></th>
                            <td><input type="text" id="sbl_lb_street" name="sbl_lb_street" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_STREET, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_locality">Miejscowość</label></th>
                            <td><input type="text" id="sbl_lb_locality" name="sbl_lb_locality" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_LOCALITY, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_region">Województwo</label></th>
                            <td><input type="text" id="sbl_lb_region" name="sbl_lb_region" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_REGION, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_postal">Kod pocztowy</label></th>
                            <td><input type="text" id="sbl_lb_postal" name="sbl_lb_postal" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_POSTAL, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_country">Kraj (ISO-2)</label></th>
                            <td><input type="text" id="sbl_lb_country" name="sbl_lb_country" class="small-text"
                                    maxlength="2" value="<?php echo esc_attr(get_option(SBL_OPT_LB_COUNTRY, '')); ?>"
                                    placeholder="PL"></td>
                        </tr>
                    </table>
                    <h3>Współrzędne (opcjonalnie)</h3>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="sbl_lb_lat">Szerokość (lat)</label></th>
                            <td><input type="text" id="sbl_lb_lat" name="sbl_lb_lat" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_LAT, '')); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sbl_lb_lng">Długość (lng)</label></th>
                            <td><input type="text" id="sbl_lb_lng" name="sbl_lb_lng" class="regular-text"
                                    value="<?php echo esc_attr(get_option(SBL_OPT_LB_LNG, '')); ?>"></td>
                        </tr>
                    </table>
                    <h3>Godziny otwarcia</h3>
                    <table class="widefat striped" style="max-width:900px;">
                        <thead>
                            <tr>
                                <th>Dzień</th>
                                <th>Otwarcie</th>
                                <th>Zamknięcie</th>
                                <th>Zamknięte</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $dk) {
                                $row = $hours_current[$dk] ?? ['open' => '', 'close' => '', 'closed' => 0];
                                $open = esc_attr($row['open'] ?? '');
                                $close = esc_attr($row['close'] ?? '');
                                $closed = !empty($row['closed']);
                                echo '<tr><td style="width:30%;">' . esc_html($daysLabels[$dk]) . '</td><td style="width:20%;"><input type="text" name="sbl_lb_' . $dk . '_open" value="' . $open . '" placeholder="09:00" class="regular-text" style="max-width:120px"></td><td style="width:20%;"><input type="text" name="sbl_lb_' . $dk . '_close" value="' . $close . '" placeholder="17:00" class="regular-text" style="max-width:120px"></td><td style="width:15%;"><label><input type="checkbox" name="sbl_lb_' . $dk . '_closed" value="1" ' . checked($closed, true, false) . '> Zamknięte</label></td></tr>';
                            } ?>
                        </tbody>
                    </table>
                    <h3>Profile / Linki (sameAs)</h3>
                    <textarea name="sbl_lb_sameas" rows="4" style="width:100%;max-width:900px;"
                        placeholder="https://facebook.com/twojafirma&#10;https://instagram.com/twojafirma"><?php echo esc_textarea(get_option(SBL_OPT_LB_SAMEAS, '')); ?></textarea>
                </div>


                <hr>

                <!-- Organization (no location, PRO) -->
                <h2>Organization – działalność bez lokalizacji (PRO)</h2>
                <div id="sbl-org-wrapper" class="sbl-card">
                    <label class="sbl-switch" style="display:block;margin:8px 0 16px;">
                        <input type="checkbox" name="sbl_org_enable" value="1" <?php checked($org_on, true); ?>> <span>Włącz Organization JSON-LD na stronie głównej</span>
                    </label>
                    <div class="sbl-field-grid">
                        <div class="sbl-col">
                            <label for="sbl_org_name" class="sbl-label">Nazwa organizacji</label>
                            <input type="text" id="sbl_org_name" name="sbl_org_name" class="sbl-input" value="<?php echo esc_attr(get_option(SBL_OPT_ORG_NAME, '')); ?>" placeholder="Nazwa marki / firmy">
                        </div>
                        <div class="sbl-col">
                            <label for="sbl_org_url" class="sbl-label">Adres URL</label>
                            <input type="url" id="sbl_org_url" name="sbl_org_url" class="sbl-input" value="<?php echo esc_attr(get_option(SBL_OPT_ORG_URL, home_url('/'))); ?>" placeholder="<?php echo esc_attr(home_url('/')); ?>">
                        </div>
                        <div class="sbl-col">
                            <label for="sbl_org_phone" class="sbl-label">Telefon</label>
                            <input type="text" id="sbl_org_phone" name="sbl_org_phone" class="sbl-input" value="<?php echo esc_attr(get_option(SBL_OPT_ORG_PHONE, '')); ?>" placeholder="+48 123 456 789">
                        </div>
                        <div class="sbl-col">
                            <label for="sbl_org_email" class="sbl-label">E-mail</label>
                            <input type="email" id="sbl_org_email" name="sbl_org_email" class="sbl-input" value="<?php echo esc_attr(get_option(SBL_OPT_ORG_EMAIL, '')); ?>" placeholder="kontakt@twojadomena.pl">
                        </div>
                    </div>
                    <h3 class="sbl-subtitle">Profile / Linki (sameAs)</h3>
                    <textarea name="sbl_org_sameas" rows="4" class="sbl-input" style="max-width:900px;" placeholder="https://facebook.com/twojafirma&#10;https://instagram.com/twojafirma"><?php echo esc_textarea(get_option(SBL_OPT_ORG_SAMEAS, '')); ?></textarea>
                    <?php echo sbl_desc('Wpisz po jednym adresie URL w każdej linii.'); ?>
                </div>

                <!-- Dev Tools (PRO) -->
                <h2>Inspektor schematów (PRO)</h2>
                <div id="sbl-inspector-toggle" class="<?php echo $inspector_gate_ok ? '' : 'sbl-pro-locked-howto'; ?>">
                    <label style="display:block;margin:8px 0;">
                        <input type="checkbox" name="sbl_inspector_enable" value="1" <?php checked($inspector_on, true); echo $inspector_gate_ok ? '' : ' disabled'; ?>>
                        Włącz inspektor na froncie (tylko dla administratorów)
                    </label>
                    <p class="description">Przycisk w prawym dolnym rogu pokaże wykryte JSON‑LD i szybkie walidacje na bieżącej stronie.</p>
                </div>


                <p class="submit">
                    <button type="submit" name="sbl_save_settings" value="1" class="button button-primary">Zapisz
                        ustawienia</button>
                    <button type="button" class="button" id="sbl-fill-lb-sample" style="margin-left:6px;">Wypełnij
                        przykładowymi danymi (LocalBusiness)</button>
                </p>
        </form>

        <hr>

        <!-- Eksport / Import -->
        <h2>Eksport / Import ustawień</h2>
        <p>
            <a class="button button-secondary"
                href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . SBL_ACTION_EXPORT), SBL_ACTION_EXPORT)); ?>">⬇️
                Eksportuj ustawienia (JSON)</a>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data"
            style="margin-top:10px;">
            <input type="hidden" name="action" value="<?php echo esc_attr(SBL_ACTION_IMPORT); ?>">
            <?php wp_nonce_field(SBL_ACTION_IMPORT); ?>
            <label><strong>Plik .json</strong> do importu: <input type="file" name="sbl_import_file"
                    accept="application/json"></label>
            <p><button type="submit" class="button">⬆️ Importuj ustawienia</button></p>
        </form>
    </div>

    <style>
        /* Overlay + blokada PRO */
        .sbl-pro-locked {
            position: relative
        }

        .sbl-pro-locked::after {
            content: "PRO – odblokuj, aby edytować LocalBusiness";
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .78);
            border: 2px dashed #ccd0d4;
            border-radius: 8px;
            color: #333;
            font-weight: 600;
            text-align: center
        }

        .sbl-pro-upsell {
            background: #f6f7f7;
            padding: 12px 14px;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            margin: 12px 0
        }

        /* Cleaner LocalBusiness layout */
        #sbl-pro-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
            background: #fff;
        }
        #sbl-pro-wrapper > label { display:inline-flex; align-items:center; gap:8px; margin-right:16px; }
        .sbl-switch { display:inline-flex; align-items:center; gap:8px; font-weight:600; }
        .sbl-switch input { margin-right:4px; }
        #sbl-pro-wrapper h3 { margin-top:18px; }
        #sbl-pro-wrapper .form-table th { width:220px; }
        #sbl-pro-wrapper .form-table .regular-text,
        #sbl-pro-wrapper .form-table .small-text,
        #sbl-pro-wrapper textarea { max-width: 420px; width: 100%; }
        #sbl-pro-wrapper .widefat th, #sbl-pro-wrapper .widefat td { vertical-align: middle; }
        #sbl-pro-wrapper .widefat input.regular-text { max-width:120px; }

        /* Shared small UI elements */
        .sbl-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; background:#fff; }
        .sbl-label { display:block; margin-bottom:6px; font-weight:600; }
        .sbl-input { width:100%; max-width:420px; }
        .sbl-row--toggles { display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
        .sbl-field-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:14px 18px; }

        .sbl-sticky-actions {
            position: sticky;
            bottom: 0;
            padding: 10px 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), #fff 35%);
        }

        .sbl-pro-locked-howto {
            position: relative
        }

        .sbl-pro-locked-howto::after {
            content: "PRO – odblokuj, aby edytować HowTo";
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .78);
            border: 2px dashed #ccd0d4;
            border-radius: 8px;
            color: #333;
            font-weight: 600;

            text-align:center .sbl-pro-benefits {
                background: linear-gradient(135deg, #fef3c7, #fef9c3);
                border: 2px solid #fcd34d;
                border-radius: 14px;
                padding: 20px;
                margin: 16px 0;
                box-shadow: 0 6px 20px rgba(250, 204, 21, .25);
                animation: sblFadeIn .5s ease-out;
            }

            .sbl-pro-benefits h3 {
                margin: 0 0 10px;
                font-size: 18px;
                font-weight: 700;
                color: #b45309;
            }

            .sbl-benefits-list {
                margin: 12px 0;
                padding-left: 22px;
                list-style: none;
            }

            .sbl-benefits-list li {
                margin: 6px 0;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .sbl-benefits-list li::before {
                content: "✨";
                margin-right: 6px;
            }

            .sbl-pro-benefits .sbl-cta {
                margin-top: 14px;
            }

            .sbl-pro-benefits .button-primary {
                background: linear-gradient(135deg, #f59e0b, #facc15);
                border: none;
                color: #111 !important;
                font-weight: 600;
                padding: 8px 18px;
                box-shadow: 0 6px 18px rgba(251, 191, 36, .35);
                transition: all .15s ease;
            }

            .sbl-pro-benefits .button-primary:hover {
                transform: translateY(-2px);
                filter: brightness(1.05);
            }

            @keyframes sblFadeIn {
                from {
                    opacity: 0;
                    transform: translateY(8px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

        }
    </style>

    <script>
        (function () {
            // Add/remove rows (FAQ/HowTo)
            function addRow(btnId, tbodySel, rowHtml) {
                const btn = document.getElementById(btnId);
                if (!btn) return;
                btn.addEventListener('click', () => { const tbody = document.querySelector(tbodySel); if (!tbody) return; const tr = document.createElement('tr'); tr.innerHTML = rowHtml; tbody.appendChild(tr); });
            }
            addRow('sbl-add-faq-row', '#sbl-faq-table tbody', '<td><input type="text" name="sbl_faq_q[]" style="width:100%" placeholder="Pytanie"></td><td><textarea name="sbl_faq_a[]" rows="2" style="width:100%" placeholder="Krótka odpowiedź"></textarea></td><td style="text-align:center;"><button class="button sbl-remove-row" type="button">✕</button></td>');
            addRow('sbl-add-step-row', '#sbl-howto-table tbody', '<td><input type="text" name="sbl_step_name[]" style="width:100%" placeholder="Nazwa kroku"></td><td><textarea name="sbl_step_text[]" rows="2" style="width:100%" placeholder="Co należy zrobić w tym kroku?"></textarea></td><td style="text-align:center;"><button class="button sbl-remove-row" type="button">✕</button></td>');
            document.addEventListener('click', function (e) { if (e.target && e.target.classList.contains('sbl-remove-row')) { const row = e.target.closest('tr'); if (row) row.remove(); } });

            // Wypełnij przykładowe LB
            const btn = document.getElementById('sbl-fill-lb-sample');
            if (btn) {
                btn.addEventListener('click', function () {
                    const q = s => document.querySelector(s); const set = (s, v) => { const el = q(s); if (el && !el.value) el.value = v; };
                    const on = q('input[name="sbl_lb_enable"]'); if (on) on.checked = true;
                    const type = q('select[name="sbl_lb_type"]'); if (type) type.value = 'Restaurant';
                    set('#sbl_lb_name', 'Bistro Pod Dębem'); set('#sbl_lb_url', '<?php echo esc_js(home_url('/')); ?>'); set('#sbl_lb_phone', '+48 123 456 789'); set('#sbl_lb_email', 'kontakt@bistropoddebem.pl'); set('#sbl_lb_price', '$$');
                    set('#sbl_lb_street', 'ul. Smaczna 5'); set('#sbl_lb_locality', 'Warszawa'); set('#sbl_lb_region', 'Mazowieckie'); set('#sbl_lb_postal', '00-001'); set('#sbl_lb_country', 'PL');
                    set('#sbl_lb_lat', '52.2297'); set('#sbl_lb_lng', '21.0122');
                    const sameAs = document.querySelector('textarea[name="sbl_lb_sameas"]'); if (sameAs && !sameAs.value.trim()) sameAs.value = 'https://facebook.com/bistropoddebem\nhttps://instagram.com/bistropoddebem';
                    const SH = (d, o, c, x) => { const O = q('input[name="sbl_lb_' + d + '_open"]'); const C = q('input[name="sbl_lb_' + d + '_close"]'); const X = q('input[name="sbl_lb_' + d + '_closed"]'); if (O) O.value = o; if (C) C.value = c; if (X) X.checked = !!x; };
                    SH('Mo', '09:00', '17:00', false); SH('Tu', '09:00', '17:00', false); SH('We', '09:00', '17:00', false); SH('Th', '09:00', '17:00', false); SH('Fr', '09:00', '17:00', false); SH('Sa', '10:00', '14:00', false); SH('Su', '', '', true);
                });
            }

            // GATING PRO (UX): jeśli brak licencji, blokujemy sekcję LB
            var proActive = <?php echo json_encode(sbl_is_pro_active() && sbl_license_has('localbusiness')); ?>;
            if (!proActive) {
                ['sbl-pro-wrapper','sbl-org-wrapper'].forEach(function(id){
                    var wrap = document.getElementById(id);
                    if (wrap) {
                        wrap.classList.add('sbl-pro-locked');
                        wrap.querySelectorAll('input,select,textarea,button').forEach(el => { if (el.name !== 'sbl_save_settings') el.disabled = true; });
                    }
                });
            }
        })();

        (function () {
            const submitRow = document.querySelector('p.submit');
            if (submitRow) {
                const wrap = document.createElement('div');
                wrap.className = 'sbl-sticky-actions';
                submitRow.parentNode.insertBefore(wrap, submitRow);
                wrap.appendChild(submitRow);
            }
        })();
    </script>
    <?php
}

// =======================
// PRO: EKSPORT / IMPORT (jak u Ciebie)
// =======================
add_action('admin_post_' . SBL_ACTION_EXPORT, function () {
    if (!current_user_can('manage_options'))
        wp_die('Brak uprawnień.');
    check_admin_referer(SBL_ACTION_EXPORT);
    $payload = [
        'version' => SBL_VERSION,
        SBL_OPT_FAQ_JSON => json_decode(get_option(SBL_OPT_FAQ_JSON, '[]'), true),
        SBL_OPT_ENABLE_PRODUCT => get_option(SBL_OPT_ENABLE_PRODUCT, '0'),
        SBL_OPT_ENABLE_HOWTO => get_option(SBL_OPT_ENABLE_HOWTO, '0'),
        SBL_OPT_HOWTO_NAME => get_option(SBL_OPT_HOWTO_NAME, ''),
        SBL_OPT_HOWTO_TOTALTIME => get_option(SBL_OPT_HOWTO_TOTALTIME, ''),
        SBL_OPT_HOWTO_STEPS_JSON => json_decode(get_option(SBL_OPT_HOWTO_STEPS_JSON, '[]'), true),
        SBL_OPT_LB_ENABLE => get_option(SBL_OPT_LB_ENABLE, '0'),
        SBL_OPT_LB_SITEWIDE => get_option(SBL_OPT_LB_SITEWIDE, '0'),
        SBL_OPT_LB_TYPE => get_option(SBL_OPT_LB_TYPE, 'LocalBusiness'),
        SBL_OPT_LB_NAME => get_option(SBL_OPT_LB_NAME, ''),
        SBL_OPT_LB_URL => get_option(SBL_OPT_LB_URL, home_url('/')),
        SBL_OPT_LB_PHONE => get_option(SBL_OPT_LB_PHONE, ''),
        SBL_OPT_LB_EMAIL => get_option(SBL_OPT_LB_EMAIL, ''),
        SBL_OPT_LB_PRICE => get_option(SBL_OPT_LB_PRICE, ''),
        SBL_OPT_LB_STREET => get_option(SBL_OPT_LB_STREET, ''),
        SBL_OPT_LB_LOCALITY => get_option(SBL_OPT_LB_LOCALITY, ''),
        SBL_OPT_LB_REGION => get_option(SBL_OPT_LB_REGION, ''),
        SBL_OPT_LB_POSTAL => get_option(SBL_OPT_LB_POSTAL, ''),
        SBL_OPT_LB_COUNTRY => get_option(SBL_OPT_LB_COUNTRY, ''),
        SBL_OPT_LB_LAT => get_option(SBL_OPT_LB_LAT, ''),
        SBL_OPT_LB_LNG => get_option(SBL_OPT_LB_LNG, ''),
        SBL_OPT_LB_SAMEAS => get_option(SBL_OPT_LB_SAMEAS, ''),
        SBL_OPT_LB_HOURS => json_decode(get_option(SBL_OPT_LB_HOURS, '{}'), true),
        // Organization (no-location)
        SBL_OPT_ORG_ENABLE => get_option(SBL_OPT_ORG_ENABLE, '0'),
        SBL_OPT_ORG_NAME => get_option(SBL_OPT_ORG_NAME, ''),
        SBL_OPT_ORG_URL => get_option(SBL_OPT_ORG_URL, home_url('/')),
        SBL_OPT_ORG_PHONE => get_option(SBL_OPT_ORG_PHONE, ''),
        SBL_OPT_ORG_EMAIL => get_option(SBL_OPT_ORG_EMAIL, ''),
        SBL_OPT_ORG_SAMEAS => get_option(SBL_OPT_ORG_SAMEAS, ''),
    ];
    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="schema-boost-settings-' . date('Ymd-His') . '.json"');
    echo $json;
    exit;
});

add_action('admin_post_' . SBL_ACTION_IMPORT, function () {
    if (!current_user_can('manage_options'))
        wp_die('Brak uprawnień.');
    check_admin_referer(SBL_ACTION_IMPORT);

    // [DODANE] — bezpieczne sprawdzenie uploadu
    $fh = $_FILES['sbl_import_file'] ?? null;
    if (!$fh || empty($fh['tmp_name'])) {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }

    // [DODANE] — tylko .json
    $ft = wp_check_filetype($fh['name'], ['json' => 'application/json']);
    if (empty($ft['ext']) || $ft['ext'] !== 'json') {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }

    // [DODANE] — limit rozmiaru (200 KB)
    if (filesize($fh['tmp_name']) > 200 * 1024) {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }

    // [DODANE] — wczytanie i dekodowanie JSON z wyjątkami
    $content = file_get_contents($fh['tmp_name']);
    if ($content === false) {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }

    try {
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $e) {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }
    if (!is_array($data)) {
        wp_redirect(add_query_arg('sbl_import', 'fail', admin_url('admin.php?page=schema-boost-lite')));
        exit;
    }

    // ===== mapowanie opcji (jak w Twojej wersji) =====

    // JSON: FAQ
    if (isset($data[SBL_OPT_FAQ_JSON])) {
        update_option(SBL_OPT_FAQ_JSON, wp_json_encode($data[SBL_OPT_FAQ_JSON]));
    }

    // Booleany
    foreach ([SBL_OPT_ENABLE_PRODUCT, SBL_OPT_ENABLE_HOWTO, SBL_OPT_LB_ENABLE, SBL_OPT_LB_SITEWIDE, SBL_OPT_ORG_ENABLE] as $b) {
        if (array_key_exists($b, $data)) {
            update_option($b, !empty($data[$b]) ? '1' : '0');
        }
    }

    // Teksty/liczby — delikatne oczyszczenie
    $text_keys = [
        SBL_OPT_HOWTO_NAME,
        SBL_OPT_HOWTO_TOTALTIME,
        SBL_OPT_LB_TYPE,
        SBL_OPT_LB_NAME,
        SBL_OPT_LB_URL,
        SBL_OPT_LB_PHONE,
        SBL_OPT_LB_EMAIL,
        SBL_OPT_LB_PRICE,
        SBL_OPT_LB_STREET,
        SBL_OPT_LB_LOCALITY,
        SBL_OPT_LB_REGION,
        SBL_OPT_LB_POSTAL,
        SBL_OPT_LB_COUNTRY,
        SBL_OPT_LB_LAT,
        SBL_OPT_LB_LNG,
        SBL_OPT_LB_SAMEAS,
        // Organization
        SBL_OPT_ORG_NAME,
        SBL_OPT_ORG_URL,
        SBL_OPT_ORG_PHONE,
        SBL_OPT_ORG_EMAIL,
        SBL_OPT_ORG_SAMEAS,
    ];
    foreach ($text_keys as $k) {
        if (isset($data[$k])) {
            $val = (string) $data[$k];

            // proste dopieszczenia dla wybranych pól
            if ($k === SBL_OPT_LB_URL || $k === SBL_OPT_ORG_URL) {
                $val = esc_url_raw($val);
            } elseif ($k === SBL_OPT_LB_EMAIL || $k === SBL_OPT_ORG_EMAIL) {
                $val = sanitize_email($val);
            } elseif (
                $k === SBL_OPT_LB_LAT
                || $k === SBL_OPT_LB_LNG
            ) {
                $val = (string) floatval($val);
            } else {
                $val = sanitize_text_field($val);
            }

            update_option($k, $val);
        }
    }

    // JSON: kroki HowTo
    if (isset($data[SBL_OPT_HOWTO_STEPS_JSON])) {
        update_option(SBL_OPT_HOWTO_STEPS_JSON, wp_json_encode($data[SBL_OPT_HOWTO_STEPS_JSON]));
    }

    // JSON: godziny otwarcia (tablica)
    if (isset($data[SBL_OPT_LB_HOURS]) && is_array($data[SBL_OPT_LB_HOURS])) {
        update_option(SBL_OPT_LB_HOURS, wp_json_encode($data[SBL_OPT_LB_HOURS]));
    }

    wp_redirect(add_query_arg('sbl_import', 'success', admin_url('admin.php?page=schema-boost-lite')));
    exit;
});


// =======================
// KOKPIT: widżet diagnostyczny (jak u Ciebie)
// =======================
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('sbl_diag_widget', 'SchemaBoost – diagnostyka', 'sbl_render_diag_widget'); });
function sbl_render_diag_widget()
{
    $faq_rows = json_decode(get_option(SBL_OPT_FAQ_JSON, '[]'), true);
    $faq_count = is_array($faq_rows) ? count($faq_rows) : 0;
    $howto_on = get_option(SBL_OPT_ENABLE_HOWTO, '0') === '1';
    $howto_steps = json_decode(get_option(SBL_OPT_HOWTO_STEPS_JSON, '[]'), true);
    $howto_count = is_array($howto_steps) ? count($howto_steps) : 0;
    $product_on = get_option(SBL_OPT_ENABLE_PRODUCT, '0') === '1';
    $wc_present = class_exists('WooCommerce');
    $lb_on = get_option(SBL_OPT_LB_ENABLE, '0') === '1';
    $lb_type = get_option(SBL_OPT_LB_TYPE, 'LocalBusiness');
    $lb_site = get_option(SBL_OPT_LB_SITEWIDE, '0') === '1';
    $lb_street = get_option(SBL_OPT_LB_STREET, '');
    $lb_phone = get_option(SBL_OPT_LB_PHONE, '');
    $lb_name = get_option(SBL_OPT_LB_NAME, '');
    $lb_ok = $lb_on && ($lb_name || $lb_street || $lb_phone);
    $pcounts = wp_count_posts('post');
    $posts_published = (int) ($pcounts->publish ?? 0);
    $products_published = 0;
    if ($wc_present) {
        $pc = wp_count_posts('product');
        $products_published = (int) ($pc->publish ?? 0);
    }
    $settings_url = admin_url('admin.php?page=schema-boost-lite');
    $rrt_url = 'https://search.google.com/test/rich-results';
    ?>
    <div class="sbl-diag">
        <ul style="margin:0 0 10px 18px; line-height:1.8;">
            <li>Article: <strong>włączone</strong> (single)</li>
            <li>FAQ:
                <?php echo $faq_count > 0 ? '✅ <strong>skonfigurowane</strong> (pozycji: ' . (int) $faq_count . ')' : '⚠️ <strong>brak pozycji</strong>'; ?>
            </li>
            <li>HowTo:
                <?php echo $howto_on ? '✅ <strong>włączone</strong> (kroków: ' . (int) $howto_count . ')' : '⏸️ <strong>wyłączone</strong>'; ?>
            </li>
            <li>Product:
                <?php echo $wc_present ? ($product_on ? '✅ <strong>włączone</strong> (Woo wykryty)' : '⏸️ <strong>wyłączone</strong> (Woo wykryty)') : '❌ <strong>WooCommerce nie wykryty</strong>'; ?>
            </li>
            <li>LocalBusiness (PRO):
                <?php echo $lb_on ? '✅ <strong>włączone</strong> — typ: ' . esc_html($lb_type) . ' | ' . ($lb_site ? 'sitewide' : 'strona główna') . ' | ' . ($lb_ok ? 'dane: OK' : 'uzupełnij dane') : '⏸️ <strong>wyłączone</strong>'; ?>
            </li>
        </ul>
        <p style="margin:8px 0;">
            <span style="display:inline-block;margin-right:12px;">Wpisy opublikowane:
                <strong><?php echo (int) $posts_published; ?></strong></span>
            <?php if ($wc_present): ?><span style="display:inline-block;margin-right:12px;">Produkty opublikowane:
                    <strong><?php echo (int) $products_published; ?></strong></span><?php endif; ?>
        </p>
        <p style="margin-top:12px;">
            <a class="button button-primary" href="<?php echo esc_url($rrt_url); ?>" target="_blank"
                rel="noopener noreferrer">Google Rich Results Test</a>
            <a class="button" style="margin-left:6px;" href="<?php echo esc_url($settings_url); ?>">Przejdź do ustawień
                SchemaBoost</a>
        </p>
    </div>
    <?php
}
