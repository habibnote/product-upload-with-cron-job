<?php 

//Function for calculate sale price
function woo_apis_sale_price($regular_price, $discountPercentage){
    $discount_price = ($discountPercentage * $regular_price) / 100;
    $sale_price = round($regular_price - $discount_price);
    return $sale_price;
}

//Function for add Image gallery
function woo_apis_add_product_gallery($post_id, $img_src){
    
    $product_gallery = [];
    if(is_array($img_src)){
        $total_img = count($img_src);
        for($i = 0; $i < $total_img; $i++){
            array_push($product_gallery, woo_apis_upload_file_by_url($img_src[$i]));
        }
    }

    // add Product gallery 
    update_post_meta($post_id, '_product_image_gallery', implode(',' ,$product_gallery));
}

//Function for checking existing products
function woo_apis_is_product_have($product_id){
    return wc_get_product($product_id);
}
