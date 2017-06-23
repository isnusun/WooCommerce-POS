<?php

/**
 * POS Product Class
 * duck punches the WC REST API
 *
 * @class    WC_POS_API_Products
 * @package  WooCommerce POS
 * @author   Paul Kilmurray <paul@kilbot.com.au>
 * @link     http://www.wcpos.com
 */

namespace WC_POS\API;

class Products {

  /* @var string Barcode postmeta */
  public $barcode_meta_key;

  /**
   * Product fields used by the POS
   * @var array
   */
  private $whitelist = array(

  );


  /**
   *
   */
  public function __construct() {
    add_filter( 'rest_dispatch_request', array( $this, 'rest_dispatch_request' ), 10, 4 );
    add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'product_response' ), 10, 3 );

  }


  /**
   * @param $response
   * @param $request
   * @param $route
   * @param $handler
   * @return array
   */
  public function rest_dispatch_request( $response, $request, $route, $handler ) {

    // only hi-jack response for fields=id queries
    if( isset( $request['fields'] ) && $request['fields'] == 'id' ) {
      $modified_after = isset( $request['modified_after'] ) ? $request['modified_after'] : null ;
      $response = $this->get_ids( $modified_after );
    }

    return $response;

  }


  /**
   * Returns array of all product ids
   * @param $modified_after
   * @return array
   */
  private function get_ids( $modified_after ) {
    $args = array(
      'post_type'       => array( 'product' ),
      'post_status'     => array( 'publish' ),
      'posts_per_page'  => -1,
      'fields'          => 'ids'
    );

    if ( $modified_after ) {
      $args[ 'date_query' ][] = array(
        'column'    => 'post_modified',
        'after'     => $modified_after,
        'inclusive' => false
      );
    }

    $query = new \WP_Query( $args );
    return array_map( 'intval', $query->posts );
  }


  /**
   * Filter each product response from WC REST API for easier handling by the POS
   * - use the thumbnails rather than fullsize
   * - add barcode field
   * - unset unnecessary data
   *
   * @param $response
   * @param $product
   * @param $request
   * @return array modified data array $product_data
   */
  public function product_response( $response, $product, $request ) {
    $data = $response->get_data();
    $id = isset( $data['id'] ) ? $data['id'] : '';

    // add thumbnail
    $data['featured_src'] = $this->get_thumbnail( $id );

    $response->set_data($data);
    return $response;
  }


  /**
   * Returns thumbnail if it exists, if not, returns the WC placeholder image
   * @param int $id
   * @return string
   */
  private function get_thumbnail($id){
    $image = false;
    $thumb_id = get_post_thumbnail_id( $id );

    if( $thumb_id )
      $image = wp_get_attachment_image_src( $thumb_id, 'shop_thumbnail' );

    if( is_array($image) )
      return $image[0];

    return wc_placeholder_img_src();
  }

}