<?php
/**
 * Created by PhpStorm.
 * User: Nicolas
 * Date: 23/10/2017
 * Time: 20:05
 */

function cp_conv_cart_builder($cnv_cart_id, $env, $redirect_url, $debug) {

    global $woocommerce;

    $LOG_CHECKOUT = [];

    if ($debug) {
        ini_set('display_errors',true);
    }

    // Call clipr for cart content
    $response = clipr_curl("landing","/p/module/cart/".$cnv_cart_id."/",[],$env);
    // $response = json_decode('{"success":true,"data":{"products":[{"idProduct":"10","idVariant":"19"}]}}',true); // for debugging purpose

    if ($debug) {
        $LOG_CHECKOUT['ENV'] = $env;
        $LOG_CHECKOUT['URL'] = "/p/module/cart/".$cnv_cart_id."/";
        $LOG_CHECKOUT['GET_CART_CONTENT'] = $response;
    }

    if ($response != null && in_array("data",array_keys($response))) {

        // Get product ids
        $products = $response['data']['products'];

        // Create a new cart
        // $woocommerce->cart->empty_cart(); // Better keep old cart content here (especially for embedded clip)

        // Add product to cart one by one
        foreach ($products as $product) {
            
            $idVariant = !empty($product['idVariant']) ? intval($product['idVariant']) : null;
            $idProduct = !empty($product['idProduct']) ? intval($product['idProduct']) : null;

            if ($idProduct == $idVariant) {
                // No variant
                try {
                    $woocommerce->cart->add_to_cart($idProduct, 1);
                } catch (\Exception $e) {
                    if ($debug) {
                        $LOG_CHECKOUT['ERROR_ADD_CART_NO_VARIANT'] = $e->getMessage();
                    }
                }
            } else {
                try {
                    $woocommerce->cart->add_to_cart($idProduct, 1, $idVariant);
                } catch (\Exception $e) {
                    if ($debug) {
                        $LOG_CHECKOUT['ERROR_ADD_CART_WITH_VARIANT'] = $e->getMessage();
                    }
                }
            }
        }
    }

    if ($debug) {
        $LOG_CHECKOUT['GUEST_CHECKOUT_ENABLED'] = is_wc_guest_checkout_enabled();
        $LOG_CHECKOUT['REDIRECT_URL'] = $redirect_url;
        $LOG_CHECKOUT['CHECKOUT_URL'] = $woocommerce->cart->get_checkout_url();
        $LOG_CHECKOUT['CART_URL'] = $woocommerce->cart->get_cart_url();
        wp_send_json(['debug'=>true,'data'=>$LOG_CHECKOUT]);
    }

    // Finally, redirect user to checkout (or product page if asked for)
    if (!empty($redirect_url)) {
        wp_redirect($redirect_url);
    } else if (is_wc_guest_checkout_enabled()) {
        // can redirect to checkout
        wp_redirect($woocommerce->cart->get_checkout_url());
    } else {
        // Cannot checkout without login first. Better redirect user on cart.
        wp_redirect($woocommerce->cart->get_cart_url());
    }

    exit;
}

// WC_Checkout::is_registration_required() is available for 3.0.0 and above
// Better rewrite function by ourself to ensure compatibility with 2.2 and above
function is_wc_guest_checkout_enabled() {
    return apply_filters( 'woocommerce_checkout_registration_enabled', 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) );
}