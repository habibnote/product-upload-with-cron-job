<?php 

//ADD METABOX FOR PRUDCT BRAND
function wooapi_add_metabox(){
    add_meta_box(
        'wooapi_location',
        __('Brand', 'wooapi'),
        'wooapi_display_brand',
        'product',
        'normal',
        'default'
    );
}
add_action('admin_menu', 'wooapi_add_metabox');

//CALL BACK FUNCTION FOR DISPLAY METABOX
function wooapi_display_brand($post){
    $brand = get_post_meta( $post->ID, 'wooapi_brand', true );
    $label = __('Brand', 'our-metabox');
    wp_nonce_field( 'wooapi_brand', 'wooapi_brand_field' );
    $metabox_html = <<<EOD
<p>
    <label for="wooapi_brand">{$label}</label>
    <input type="text" name="wooapi_brand" id="wooapi_brand" value="{$brand}">
</p>
EOD;

    echo $metabox_html;
}

//FOR SECURE INPUT 
function is_secured( $nonce_field, $action, $post_id ) {
    $nonce = isset( $_POST[ $nonce_field ] ) ? $_POST[ $nonce_field ] : '';

    if ( $nonce == '' ) {
        return false;
    }
    if ( ! wp_verify_nonce( $nonce, $action ) ) {
        return false;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return false;
    }

    if ( wp_is_post_autosave( $post_id ) ) {
        return false;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return false;
    }

    return true;

}

//SAVE METABOX 
function wooapi_save_brand($post_id){
    if ( !is_secured( 'wooapi_brand_field', 'wooapi_brand', $post_id ) ) {
        return $post_id;
    }
    $brand = isset( $_POST['wooapi_brand'] ) ? $_POST['wooapi_brand'] : '';
    if ( $brand == '') {
        return $post_id;
    }
    $brand = sanitize_text_field($brand);
    update_post_meta( $post_id, 'wooapi_brand', $brand );
}
add_action( 'save_post', 'wooapi_save_brand');