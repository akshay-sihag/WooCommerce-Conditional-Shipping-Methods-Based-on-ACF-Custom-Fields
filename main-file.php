// Clear Redis cache and WooCommerce transients when the cart or product is updated
add_action('woocommerce_cart_updated', 'clear_redis_cache_on_cart_update');
add_action('woocommerce_cart_item_removed', 'clear_redis_cache_on_cart_update');
add_action('woocommerce_after_cart_item_quantity_update', 'clear_redis_cache_on_cart_update');
add_action('woocommerce_checkout_order_processed', 'clear_redis_cache_on_checkout');
add_action('save_post_product', 'clear_redis_cache_on_product_update');

function clear_redis_cache_on_cart_update() {
    global $wpdb;
    // Clear WooCommerce transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");

    // Clear Redis cache
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379); // Adjust Redis server connection details as needed
    $redis->flushDb();
    error_log('Redis cache cleared and WooCommerce transients deleted on cart update');
}

function clear_redis_cache_on_checkout() {
    global $wpdb;
    // Clear WooCommerce transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");

    // Clear Redis cache
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379); // Adjust Redis server connection details as needed
    $redis->flushDb();
    error_log('Redis cache cleared on checkout');
}

function clear_redis_cache_on_product_update($post_id) {
    if (get_post_type($post_id) === 'product') {
        global $wpdb;
        // Clear WooCommerce transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");

        // Clear Redis cache
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379); // Adjust Redis server connection details as needed
        $redis->flushDb();
        error_log('Redis cache cleared on product update');
    }
}

// Conditionally hide shipping methods based on ACF fields
add_filter('woocommerce_package_rates', 'conditionally_hide_shipping_methods_based_on_acf', 10, 2);

function conditionally_hide_shipping_methods_based_on_acf($rates, $package) {
    // Initialize flags to track custom field values
    $show_delivery = false;
    $show_pickup = false;

    // Loop through each product in the cart
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $delivery_fee = get_field('delivery_fee', $product_id);  //replace delivery_fee with your own custom field slug
        $pickup_fee = get_field('pickup_fee', $product_id);     //replace pickup_fee with your own custom field slug

        // Check the custom field values
        if (!empty($delivery_fee)) {
            $show_delivery = true;
        }

        if (!empty($pickup_fee)) {
            $show_pickup = true;
        }

        // Break loop if both conditions are met
        if ($show_delivery && $show_pickup) {
            break;
        }
    }

    // Loop through the rates and remove those that do not match the conditions
    foreach ($rates as $rate_key => $rate) {
        if ($rate->method_id === 'flat_rate' && !$show_delivery) {
            unset($rates[$rate_key]);
        }

        if ($rate->method_id === 'local_pickup' && !$show_pickup) {
            unset($rates[$rate_key]);
        }
    }

    return $rates;
}
