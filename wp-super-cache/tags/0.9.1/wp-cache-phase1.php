<?php
// Pre-2.6 compatibility
if( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if( !include( WP_CONTENT_DIR . '/wp-cache-config.php' ) )
	return;
if( !defined( 'WPCACHEHOME' ) )
	define('WPCACHEHOME', dirname(__FILE__).'/');

include( WPCACHEHOME . 'wp-cache-base.php');
$wp_cache_meta_object = new CacheMeta;

if(defined('DOING_CRON')) {
	require_once( WPCACHEHOME . 'wp-cache-phase2.php');
	return;
}

$mutex_filename = 'wp_cache_mutex.lock';
$new_cache = false;

// Don't change variables behind this point

if( !isset( $wp_cache_plugins_dir ) )
	$wp_cache_plugins_dir = WPCACHEHOME . 'plugins';
$plugins = glob( $wp_cache_plugins_dir . '/*.php' );
if( is_array( $plugins ) ) {
	foreach ( $plugins as $plugin ) {
	if( is_file( $plugin ) )
		require_once( $plugin );
	}
}

if (!$cache_enabled || $_SERVER["REQUEST_METHOD"] == 'POST') 
	return;

$file_expired = false;
$cache_filename = '';
$meta_file = '';
$wp_cache_gzip_encoding = '';

$gzipped = 0;
$gzsize = 0;

function gzip_accepted(){
	if( ini_get( 'zlib.output_compression' ) ) // don't compress WP-Cache data files when PHP is already doing it
		return false;

	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) return false;
	return 'gzip';
}

if ($cache_compression) {
	$wp_cache_gzip_encoding = gzip_accepted();
}

add_cacheaction( 'wp_cache_key', 'wp_cache_check_mobile' );

$key = $blogcacheid . md5( do_cacheaction( 'wp_cache_key', $_SERVER['HTTP_HOST'].preg_replace('/#.*$/', '', str_replace( '/index.php', '/', $_SERVER['REQUEST_URI'] ) ).$wp_cache_gzip_encoding.wp_cache_get_cookies_values() ) );

$cache_filename = $file_prefix . $key . '.html';
$meta_file = $file_prefix . $key . '.meta';
$cache_file = realpath( $cache_path . $cache_filename );
$meta_pathname = realpath( $cache_path . 'meta/' . $meta_file );

$wp_start_time = microtime();
if( file_exists( $cache_file ) && ($mtime = @filemtime($meta_pathname)) ) {
	if ($mtime + $cache_max_time > time() ) {
		$meta = new CacheMeta;
		if (! ($meta = unserialize(@file_get_contents($meta_pathname))) ) 
			return;
		$file = do_cacheaction( 'wp_cache_served_cache_file', $cache_file );
		// Sometimes the gzip headers are lost. If this is a gzip capable client, send those headers.
		if( $wp_cache_gzip_encoding && !in_array( 'Content-Encoding: ' . $wp_cache_gzip_encoding, $meta->headers ) ) {
			array_push($meta->headers, 'Content-Encoding: ' . $wp_cache_gzip_encoding);
			array_push($meta->headers, 'Vary: Accept-Encoding, Cookie');
			array_push($meta->headers, 'Content-Length: ' . filesize( $cache_file ) );
			wp_cache_debug( "Had to add gzip headers to the page {$_SERVER[ 'REQUEST_URI' ]}." );
		}
		foreach ($meta->headers as $header) {
			// godaddy fix, via http://blog.gneu.org/2008/05/wp-supercache-on-godaddy/ and http://www.littleredrails.com/blog/2007/09/08/using-wp-cache-on-godaddy-500-error/
			if( strpos( $header, 'Last-Modified:' ) === false ) 
				header($header);
		}
		header( 'WP-Super-Cache: WP-Cache' );
		if ( !($content_size = @filesize($cache_file)) > 0 || $mtime < @filemtime($cache_file))
			return;
		if ($meta->dynamic) {
			include($cache_file);
		} else {
			echo do_cacheaction( 'wp_cache_file_contents', file_get_contents( $cache_file ) );
		}
		die();
	}
	$file_expired = true; // To signal this file was expired
}

/*register_shutdown_function( 'wp_cache_do_output' );

function wp_cache_do_output() {
	global $wp_cache_do_output;
	if( !$wp_cache_do_output ) {
		return false;
	}
	$buffer = ob_get_contents();
	ob_end_clean();
	$buffer = wp_cache_get_ob( $buffer );
	wp_cache_shutdown_callback();
	echo $buffer;
}*/

function wp_cache_postload() {
	global $cache_enabled;

	if (!$cache_enabled) 
		return;
	require_once( WPCACHEHOME . 'wp-cache-phase2.php');
	wp_cache_phase2();
}

function wp_cache_get_cookies_values() {
	$string = '';
	while ($key = key($_COOKIE)) {
		if (preg_match("/^wp-postpass|^wordpress|^comment_author_/", $key)) {
			$string .= $_COOKIE[$key] . ",";
		}
		next($_COOKIE);
	}
	reset($_COOKIE);

	// If you use this hook, make sure you update your .htaccess rules with the same conditions
	$string = do_cacheaction( 'wp_cache_get_cookies_values', $string );
	return $string;
}

function add_cacheaction( $action, $func ) {
	global $wp_supercache_actions;
	$wp_supercache_actions[ $action ][] = $func;
}

function do_cacheaction( $action, $value = '' ) {
	global $wp_supercache_actions;
	if( is_array( $wp_supercache_actions[ $action ] ) ) {
		$actions = $wp_supercache_actions[ $action ];
		foreach( $actions as $func ) {
			$value = $func( $value );
		}
	}

	return $value;
}

// From http://wordpress.org/extend/plugins/wordpress-mobile-edition/ by Alex King
function wp_cache_check_mobile( $cache_key ) {
	global $wp_cache_mobile_enabled, $wp_cache_mobile_browser, $wp_cache_mobile_browsers;
	if( !isset( $wp_cache_mobile_enabled ) || false == $wp_cache_mobile_enabled )
		return $cache_key;

	if (!isset($_SERVER["HTTP_USER_AGENT"])) {
		return $cache_key;
	}
	$whitelist = explode( ',', $wp_cache_mobile_whitelist );
	foreach ($whitelist as $browser) {
		if (strstr($_SERVER["HTTP_USER_AGENT"], trim($browser))) {
			return $cache_key;
		}
	}

	$browsers = explode( ',', $wp_cache_mobile_browsers );
	foreach ($browsers as $browser) {
		if (strstr($_SERVER["HTTP_USER_AGENT"], trim( $browser ))) {
			return $cache_key . $browser;
		}
	}
	return $cache_key;
}

function wp_cache_debug( $message ) {
	global $wp_cache_debug;
	if( !isset( $wp_cache_debug ) )
		return;
	$message .= "\n\nDisable these emails by commenting out or deleting the line containing\n\$wp_cache_debug in wp-content/wp-cache-config.php on your server.\n";
	mail( $wp_cache_debug, '[' . addslashes( $_SERVER[ 'HTTP_HOST' ] ) . "] WP Super Cache Debug", $message );
}

?>
