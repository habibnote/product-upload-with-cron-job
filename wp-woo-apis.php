<?php
/*
 * Plugin Name:       WP Woo API
 * Description:       Plugin For add products in woocommerce with API
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Md. Habibur Rahman
 * Author URI:        https://me.habibnote.com/
 * Text Domain:       woo-apis
*/

// If this file is access directly, abort!!!
defined( 'ABSPATH' ) or die( 'Unauthorized Access' );
define("INC", plugin_dir_path(__FILE__) . "/inc");


require_once( INC . "/upload-image.php");
require_once( INC . "/metaboxs/woo-apies-cuztm-meta-field.php");
require_once( INC . "/helper/all-helper-functions.php");

/**
 * Enqueue all admin panel stylesheet & assets
 */
function woo_apis_assets(){
    wp_enqueue_style('woo-apis-main-css', plugin_dir_url(__FILE__) . 'assets/admin/css/style.css', null, time(),);
}
add_action('admin_enqueue_scripts', 'woo_apis_assets');


/**
 * THIS IS MAIN FUNCTION 
 * ====================================
 * 
 * get product from api 
 * create product as woocommerce product
 * check existing products
 * add meta value
 */
function woo_apis_main($btnClick = false){
    $url = 'https://dummyjson.com/products'; 
    $arguments = array(
        'method' => 'GET'
    );

    $is_product_added = '';

    $response = wp_remote_get( $url, $arguments );
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: {$error_message}";
    } else {
            $woo_apis_all_products = json_decode(wp_remote_retrieve_body($response));
            $woo_apis_all_products = $woo_apis_all_products->products;
            foreach($woo_apis_all_products as $_product){
            if( ! woo_apis_is_product_have($_product->id)){

                #UPLOAD AND GET PRODUCT THUMBNAIL ID
                $product_thumbnail_id = woo_apis_upload_file_by_url($_product->thumbnail);
                
                #ADD CATEGORY 
                wp_insert_term(
                    $_product->category, 
                    'product_cat', 
                    array(
                        'slug' => $_product->category
                    )
                );

                #TO GET REGULAR PRICE & DISCOUNT 
                $regular_price = $_product->price;
                $discountPercentage = $_product->discountPercentage;

                #CREATE PRODUCT
                $post_id = wp_insert_post( array(
                    'ID' => $_product->id,
                    'post_title' => $_product->title,
                    'post_content' => $_product->description,
                    'post_status' => 'publish',
                    'post_type' => "product",
                ) );
                wp_set_object_terms( $post_id, $_product->category, 'product_cat');

                /**
                 * ADDING ALL PRODUCT META FORM JSON DATA
                 */
                update_post_meta( $post_id, '_regular_price', $regular_price);
                update_post_meta( $post_id, '_sale_price', woo_apis_sale_price( $regular_price, $discountPercentage));
                update_post_meta( $post_id, '_stock', $_product->stock);

                
                #Set thumbnail
                set_post_thumbnail($post_id , $product_thumbnail_id);
                #add product gallery
                woo_apis_add_product_gallery($post_id, $_product->images );
                #update post meta as a meta field and value
                update_post_meta( $post_id, 'wooapi_brand', $_product->brand);
                
                //count how many product upload through manually
                $is_product_added++;
            }
        }
    }

    //if its from plugin setting page
    if($btnClick){
        if($is_product_added){
            if(1 == $is_product_added){
                echo "<p> {$is_product_added} Product Added Successfully.</p>";
            }else{
                echo "<p> {$is_product_added} Products Added Successfully.</p>";
            }
        }else{
            echo "<p>There are no new products to add</p>";
        }
    }
}


//WORDPRESS HOOK FOR ADD A CRON JOB EVERY 12 HOURS
function woo_apis_cron_schedules($schedules){
    if(!isset($schedules['every_twelve_hours'])){
        $schedules['every_twelve_hours'] = array(
            'interval' => 12*60*60, // Every 12 hours
            'display' => __('Every 12 hours'));
    }
    return $schedules;
}
add_filter('cron_schedules','woo_apis_cron_schedules');

// activation Hook
function woo_apis_plugin_activate(){
    if (!wp_next_scheduled('woo_apis_product_add_hook')) {
        wp_schedule_event(time(), 'every_twelve_hours', 'woo_apis_product_add_hook');
    }
}
register_activation_hook(__FILE__, 'woo_apis_plugin_activate');

// Deactivation Hook
function woo_apis_plugin_deactivation(){
    wp_clear_scheduled_hook('woo_apis_product_add_hook');
}
register_deactivation_hook(__FILE__, 'woo_apis_plugin_deactivation');


//call main function every 12hours automatically
function woo_apis_add_product_by_cron_job(){
    woo_apis_main();
}
add_action('woo_apis_product_add_hook', 'woo_apis_add_product_by_cron_job');


/**
 * Add submenu for adding woocommerce products mmanually and also handle this plugin.
 */
function woo_apis_setting_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Add/Update Product From API', 'woo-apis' ),
        __( 'WP Woo APIs', 'woo-apis' ),
        'manage_options',
        'woo_add_product_from_api',
        'woo_apis_product_ref_page_callback'
    );
}
add_action( 'admin_menu', 'woo_apis_setting_page');


/**
 * call back for WP api submenu
 */
function woo_apis_product_ref_page_callback(){

    //Display add/update button WP woo apies plugin setting page
    $woo_apies_add_update_product_btn = <<<EOD
<form method="POST" class="woo-apis-form-warpper">
    <h2>Update Wooocomarce prodcuts from api</h2>
    <button type="submit" name="woo_apis_btn">Add/Update Products</button>
</form>
<br>
EOD; 
    echo $woo_apies_add_update_product_btn;

    if(isset($_POST['woo_apis_btn'])){
        woo_apis_main(true);
    }
}

//FOR PLUGIN SETTING LINK
function woo_apis_plugin_setting_page_link($links){
    $newlink = sprintf("<a href='%s'>%s<a>", 'edit.php?post_type=product&page=woo_add_product_from_api', __('Open Plugin Page', 'woo-apis'));
    $links[] = $newlink;
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woo_apis_plugin_setting_page_link');
   




