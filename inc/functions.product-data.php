<?php
/**
 * Created by PhpStorm.
 * User: Nicolas
 * Date: 23/10/2017
 * Time: 20:05
 */

function cp_conv_product_data($id_product, $token, $env, $debug) {

    // Class ref : http://woocommerce.wp-a2z.org/oik_class/wc_product/

    if ($debug) {
        ini_set('display_errors',true);
    }
    $LOG_PRODUCT_DATA = [];

    // Call clipr for token validation - Is it really Clipr who asked for product data ?
    $response = clipr_curl("back-office","/p/module/validate/".$token."/",null,$env);
    // $response = json_decode('{"success":true}',true);

    if ($debug) {
        $LOG_PRODUCT_DATA['MODULE_VALIDATE'] = $response;
    }

    $data = [];
    if ($response != null && in_array("success",array_keys($response)) && !empty($response['success'])) {

        // Get data
        $product = wc_get_product($id_product);

        if ($debug) {
            $LOG_PRODUCT_DATA['WC_GET_PRODUCT'] = $product;
        }

        // Fields relative to product
        // No native field for vendor !
        // For description, we could have use $product->get_short_description() but not compatible with WC 2.2
        $description = !empty($product->get_post_data()->post_excerpt) ?
            $product->get_post_data()->post_excerpt :
            $product->get_post_data()->post_content;
        $productTitle = $product->get_title();
        $data = [
            "title" => $product->get_title(),
            "content" => html_entity_decode(strip_tags($description)),
            "idProduct" =>  $id_product
        ];

        $currency = get_option('woocommerce_currency');
        $defaultImage = wp_get_attachment_image_src( get_post_thumbnail_id($id_product));
        $defaultImageUrl = !empty($defaultImage) ? $defaultImage[0] : "";

        // Get variants if existing (Which are considered as products entities too !)
        if ( $product->is_type( 'variable' ) ) {

            // Get configurable attributes id and their position
            $variantsGroup = cp_conv_format_variants_group($product);

            // Get Variants
            $variants =  $product->get_available_variations();

            // Get all configurable attributes code
            $allAttributesCode = array_keys($variantsGroup);

            // Get variants data
            $formattedVariants  = cp_conv_format_variants($variants,[
                'currency' => $currency,
                'title' => $productTitle,
                'attributes' => $allAttributesCode,
                'defaultImage' => $defaultImageUrl
            ]);
            $data['variantsList'] = $formattedVariants['variants'];

            // Remove attribute that are not part from variations
            $attributesNotSet = $formattedVariants['attributesNotSet'];
            foreach ($attributesNotSet as $attr_to_remove) {
                if (in_array($attr_to_remove,array_keys($variantsGroup))) {
                    unset($variantsGroup[$attr_to_remove]);
                }
            }

            // Save attributes
            $data['variantsGroup'] = $variantsGroup;

        } else {

            // No variants, so let's build our own unique variant
            // is_in_stock is perfect for stock status
            // Ex : will be 1 if qty = 0 but checkout allowed. And 0 if qty = 0 + checkout not allowed if qty = 0
            $data['variantsList'] = [
                "_".$id_product => [
                    'idVariant' => $id_product,
                    'title' => $productTitle,
                    'price' => $product->get_display_price(),
                    'currency' => $currency,
                    'stock' => intval($product->is_in_stock()),
                    'imageUrl' => $defaultImageUrl
                ]
            ];
        }
    }

    wp_send_json([
        'success'=>true,
        "data"=>$data,
        "debug"=>$LOG_PRODUCT_DATA
    ]);
    exit;
}

function cp_conv_format_variants_group($product) {

    // Class ref : https://docs.woocommerce.com/wc-apidocs/class-WC_Product_Attribute.html

    /*
     * Two type of attributes :
     * - Those created specific to product (is_taxonomy=0) : values are title & id
     * - Those created in Attributes page (is_taxonomy=1) : values are id. Need to get attribute object
     */

    $configurables = $product->get_attributes();
    $attributes = [];

    foreach($configurables as $attribute){

        // $attribute is an object for WC 3.0/
        // And an array for versions below
        if (is_array($attribute)) {
            $attr_priority =  $attribute['position']+1;
            $attr_id = $attribute['name'];
            $options = explode(' | ',$attribute['options']);
            $is_taxonomy = intval($attribute['is_taxonomy']);
        } else {
            $attr_priority =  intval($attribute->get_position())+1;
            $attr_id = $attribute->get_name();
            $options = $attribute->get_options();
            $is_taxonomy = intval($attribute->is_taxonomy());
        }

        if (strlen($attr_id) > 0) {
            // Common to two types
            $attr_title = wc_attribute_label( $attr_id, $product );
            $key = "_".$attr_id;
            $values = [];

            if (!$is_taxonomy) {


                // Name is title & id, values are title & id

                // Slug from id
                $attr_id = sanitize_title($attr_id);
                $key = "_".$attr_id;

                // Parse options
                $i=1;
                foreach($options as $value){
                    $idOption = "_".sanitize_title($value);
                    $values[$idOption] = [
                        'id' => $idOption,
                        'title' => $value,
                        'priority' => $i
                    ];
                    $i++;
                }

            } else {

                // Name is id, values are id. Need to get attribute object

                // Attribute values
                $options = get_terms($attr_id);

                // Class ref : https://developer.wordpress.org/reference/classes/wp_term/
                $i=1;
                foreach($options as $term){
                    $idOption = "_".$term->slug;
                    $values[$idOption] = [
                        'id' => $idOption,
                        'title' => $term->name,
                        'priority' => $i
                    ];
                    $i++;
                }
            }

            $attributes[$key] = [
                'id' => $key,
                'title' => $attr_title,
                'priority' => $attr_priority,
                'values' => $values
            ];
        }
    }
    return $attributes;

}

function cp_conv_format_variants($variants,$common) {


    $formatVariantsData = [];
    $attributesCode = $common['attributes'];
    $currency = $common["currency"];
    $title = $common["title"];
    $defaultImageUrl = $common['defaultImage'];

    // Class ref : https://docs.woocommerce.com/wc-apidocs/class-WC_Product_Variation.html

    // Attributes not set for none of these products
    $attributesNotSet = $attributesCode;

    foreach ($variants as $variant) {

        $idVariant = $variant['variation_id'];
        $keyVariant = "variant".$idVariant;

        $variation = new WC_Product_Variation($idVariant);
        $price = property_exists(get_class($variation),"get_display_price") ?
            $variation->get_display_price() :
            $variation->get_price();

        if (!empty($price)) {

            // A variant without price cannot be add to cart with woocommerce
            // Basic info
            $formatVariantsData['variants'][$keyVariant] = [
                'idVariant' => $idVariant,
                'sku' => $variation->get_sku(),
                'title' => $title,
                'price' => $price,
                'currency' => $currency,
                'stock' => intval($variation->is_in_stock())
            ];

            // Image
            if (!empty($image = $variant['image']) && !empty($src = $image['thumb_src'])) {
                $formatVariantsData['variants'][$keyVariant]['imageUrl'] = $src;
            } else {
                $formatVariantsData['variants'][$keyVariant]['imageUrl'] = $defaultImageUrl;
            }

            // Attributes
            $attributes = $variation->get_variation_attributes();
            if (!empty($attributes)) {
                foreach ($attributesCode as $attributeCode) {
                    // substr to remove our _ at the beginning
                    $attr_id = 'attribute'.$attributeCode;
                    if (in_array($attr_id,array_keys($attributes))) {

                        // Record val
                        $val = !empty($attributes[$attr_id]) ? "_".sanitize_title($attributes[$attr_id]) : "";
                        $formatVariantsData['variants'][$keyVariant]['attributes'][$attributeCode] = $val;

                        // Validate this attribute code
                        if (in_array($attributeCode,$attributesNotSet)) {
                            $attributesNotSet = array_diff($attributesNotSet,[$attributeCode]);
                        }
                    }
                }
            }
        }
    }

    $formatVariantsData['attributesNotSet'] = $attributesNotSet;

    return  $formatVariantsData;
}