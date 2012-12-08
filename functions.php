<?php
define('BITLY_USERNAME', 'username');
define('BITLY_APIKEY', 'your api key');
define('BITLY_DOMAIN', 'j.mp');

/**
* Generates short URLs for
* Call by get_post_meta() or wp_get_shortlink();
* Support custom domain, define edit BITLY_DOMAIN with your custom domain
*/
function bitly_shortlink($url, $id, $context, $allow_slugs){
  if ( ( is_singular() && !is_preview() ) || $context == 'post' ) {
    $short = get_post_meta($id, 'bitlylink', true);
    if ( !$short || $short == '' ) {
      if ( !defined('BITLY_USERNAME') || !defined('BITLY_APIKEY') ) {
        $short = get_bloginfo('url').'?p='.$post->ID;
      } else {
        $url = get_permalink( $id );
        $req = 'http://api.bit.ly/v3/shorten?format=txt&longUrl='.$url.'&login='.BITLY_USERNAME.'&apiKey='.BITLY_APIKEY;
        if(defined('BITLY_DOMAIN')){
            $req .= '&domain='.BITLY_DOMAIN;
        }
        $resp = wp_remote_get( $req );
        if ( !is_wp_error( $resp ) && is_array( $resp['response'] ) && 200 == $resp['response']['code'] ) {
          $short = trim( $resp['body'] );
          update_post_meta( $id, 'bitlylink', $short);
        }
      }
    }
    return $short;
  }
  return false;
}
add_filter( 'pre_get_shortlink', 'bitly_shortlink', 99, 4 );