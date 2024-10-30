<?php

/*
Plugin Name: Clipr
Description: Mobile experience that converts. Connect your Woocommerce e-shop with Clipr platform now !
Version: 1.2.3
Author: Clipr
Author URI: https://clipr.co
License: GPLv2 or later
Text Domain: clipr
*/

define( 'CLIPR__VERSION',            '1.2.3' );
define( 'CLIPR__PLUGIN_DIR',         __DIR__ );
define( 'CLIPR__PLUGIN_FILE',        __FILE__ );


require_once( CLIPR__PLUGIN_DIR . '/inc/functions.product-data.php'  );
require_once( CLIPR__PLUGIN_DIR . '/inc/functions.cart-builder.php'  );
require_once( CLIPR__PLUGIN_DIR . '/inc/functions.clipr-toolbox.php'  );

/*
 * This is our SEO-friendly way to construct our custom URLs
 */
add_action('parse_request', 'cp_conv_define_action');
function cp_conv_define_action() {

    $module = isset($_GET['module']) ? htmlspecialchars( $_GET['module'] ) : "";
    $action = isset($_GET['controller']) ? htmlspecialchars($_GET['controller']) : "";
    $env = isset($_GET['env']) ? htmlspecialchars($_GET['env']) : "prod";

    if ($module == "clipr") {
        switch ($action) {
            case "checkConfig":
                // Just to let clipr know if module is installed
                wp_send_json(['success'=>true]);
                exit;
                break;
            case "cartBuilder":
                // Add products to cart then redirect to checkout
                $cnv_cart_id = isset($_GET['cnv_cart']) ? htmlspecialchars($_GET['cnv_cart']) : "";
                $redirect_url = isset($_GET['redirect_url']) ? htmlspecialchars($_GET['redirect_url']) : "";
                $debug = isset($_GET['debug']) ? intval(htmlspecialchars($_GET['debug'])) : 0;
                cp_conv_cart_builder($cnv_cart_id,$env, $redirect_url, $debug);
                break;
            case "productData":
                // Get product data from product id
                $id_product = isset($_GET['id_product']) ? htmlspecialchars($_GET['id_product']) : "";
                $token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : "";
                $debug = isset($_GET['debug']) ? intval(htmlspecialchars($_GET['debug'])) : 0;
                cp_conv_product_data($id_product,$token,$env, $debug);
                break;
        }
    }
}

/*
 * Get product id in a JSON when calling product URL with cnv_cap_id GET param
 * get_the_ID() won't be know in previous action. Need to call it just before page rendering
 */
add_action('template_redirect', 'cp_conv_get_product_id');
function cp_conv_get_product_id() {

    $cnv_cap_id = isset($_GET['cnv_cap_id']) ? htmlspecialchars( $_GET['cnv_cap_id'] ) : "";
    if (!empty($cnv_cap_id)) {

        // Get current product id
        $id = get_the_ID();

        // Return product id and die
        wp_send_json(['success'=>true,"data"=>['id_product'=>$id]]);
        exit;
    }
}

/*
 * Add script for embedded clip support
 */

// API Key
add_action ( 'wp_head', 'clipr_js_variables' );
function clipr_js_variables(){

    $cliprSettings = get_option( 'CLIP_EMBEDDED' );
    $API_KEY = isset($cliprSettings['API_KEY']) ? $cliprSettings['API_KEY'] : "";
    ?>
    <script type="text/javascript">
        var CLIP_EMBEDDED_API_KEY = "<?php echo($API_KEY); ?>";
    </script><?php
}

// Framework
add_action( 'wp_enqueue_scripts', 'wpb_adding_clipr_scripts' );
function wpb_adding_clipr_scripts() {

    // Path to script ?
    $domain = "https://m.clipr.co";
    $clipr_env = isset($_GET['clipr_env']) ? htmlspecialchars($_GET['clipr_env']) : "";
    if ($clipr_env != null && $clipr_env == "staging") {
        $domain = "https://m.aws.clipr.co";
    }

    // Version number ? Force refresh once per day
    $version = date('Y.m.d');

    $scriptPath = $domain."/bundles/cnvpublicdisplay/js/clip-embedded.min.js?v=".$version;

    // Add to page
    wp_register_script('clipr_script', $scriptPath);
    wp_enqueue_script('clipr_script');
}

/**
 * SETTINGS PAGE
 */

// First step : add submenu "Clipr" into "Settings" menu
add_action( 'admin_menu', 'Clipr_add_admin_menu' );
function Clipr_add_admin_menu(  ) {
    add_options_page( 'Clipr', 'Clipr', 'manage_options', 'clipr', 'Clipr_options_page' );
}

// Second step : Content template
function Clipr_options_page(  ) {

    ?>
    <form <?php echo("action='options.php'"); ?> method='post'>

        <h2>Clipr configuration page</h2>

        <?php
        settings_fields( 'CLIPR_SETTINGS' );
        do_settings_sections( 'Clipr_settings_page' );
        submit_button();
        ?>


    </form>
    <?php
}

// Third step : init data form
add_action( 'admin_init', 'Clipr_settings_init' );
function Clipr_settings_init(  ) {

    // Group name, Option name, Callback to ensure right data type
    register_setting('CLIPR_SETTINGS',  'CLIP_EMBEDDED', 'CLIP_EMBEDDED_callback' );
    function CLIP_EMBEDDED_callback($input) {

        return strval($input);
    }

    // Prepare fields for our form

    // Add section

    add_settings_section(
        'Clipr_first_section',              // Section id
        '',                                 // Section Title
        'Clipr_first_section_description',  // Section description callback
        'Clipr_settings_page'               // Page name
    );

    function Clipr_first_section_description(  ) {
        echo 'Please enter the API Key provided by your Clip configuration page.';
    }

    // Add field

    add_settings_field(
        'API_KEY',                          // Field id
        'API Key',                          // Field title
        'Clipr_text_field_render',          // Field render callback
        'Clipr_settings_page',              // page name
        'Clipr_first_section'               // Section id
    );

    function Clipr_text_field_render(  ) {

        // Display saved key if any
        $cliprSettings = get_option( 'CLIP_EMBEDDED' );
        ?>
        <input type='text' name='CLIP_EMBEDDED[API_KEY]' value='<?php echo $cliprSettings['API_KEY']; ?>'>
        <?php
    }
}

?>