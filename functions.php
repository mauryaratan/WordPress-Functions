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



/* Shortcode filter */
function shortcode_content( $content ) {

    /* Parse nested shortcodes and add formatting. */
    $content = trim( wpautop( do_shortcode( $content ) ) );

    /* Remove '</p>' from the start of the string. */
    if ( substr( $content, 0, 4 ) == '</p>' )
        $content = substr( $content, 4 );

    /* Remove '<p>' from the end of the string. */
    if ( substr( $content, -3, 3 ) == '<p>' )
        $content = substr( $content, 0, -3 );

    /* Remove any instances of '<p></p>'. */
    $content = str_replace( array( '<p></p>' ), '', $content );

    $content = str_replace( '<br />', '', $content );
    $content = str_replace( '&lt;br /&gt;', '', $content );

    return $content;
}

/* Check Remote URL content */
function get_remote_part( $url, $minutes_to_save = 60 ) {
	$transient_name = 'get_remote_part_' . substr( md5( $url ), 16 );
	if ( false === ( $value = get_transient( $transient_name ) ) ) {
		$value = wp_remote_retrieve_body( wp_remote_get( $url ) );
		if( $value ) {
			set_transient( $transient_name, $value, ( MINUTE_IN_SECONDS * $minutes_to_save ) );
		}
	}
	return $value;
}


/**
 * Fragment Caching
 *
 * @description 
 * @param string $key Fragment Identifier
 * @param integer $ttl Time in seconds
 * @param function $function Callback function
 * @return Cached data
 * @package StagFramework
 */
function stag_fragment_cache( $key, $ttl, $function ) {
	if ( is_user_logged_in() ) {
		call_user_func($function);
		return;
	}
	$key = apply_filters( 'stag_fragment_cache_prefix', 'stag_fragment_cache_' ) . $key;
	$output = get_transient($key);
	if ( empty($output) ) {
		ob_start();
		call_user_func($function);
		$output = ob_get_clean();
		set_transient( $key, $output, $ttl );
	}
	echo $output;
}
