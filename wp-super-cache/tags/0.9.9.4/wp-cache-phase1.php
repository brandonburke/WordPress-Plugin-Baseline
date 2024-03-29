<?php
//error_reporting(E_ERROR | E_PARSE); // uncomment to debug this file!
// Pre-2.6 compatibility
if( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

function wp_cache_load_config() {
	global $wpsc_settings;
	if( !include( WP_CONTENT_DIR . '/wp-cache-config.php' ) )
		return false;
	$fields = array( "cache_compression", "cache_enabled", "super_cache_enabled", "cache_max_time", "cache_path", "file_prefix", "blogcacheid", "cache_acceptable_files", "cache_rejected_uri", "cache_rejected_user_agent", "cache_rebuild_files", "wp_cache_mutex_disabled", "wp_cache_mobile", "wp_cache_mobile_whitelist", "wp_cache_mobile_browsers", "wp_cache_plugins_dir", "wp_cache_shutdown_gc", "wp_super_cache_late_init", "wp_super_cache_advanced_debug", "wp_super_cache_front_page_text", "wp_super_cache_front_page_clear", "wp_super_cache_front_page_check", "wp_super_cache_front_page_notification", "wp_cache_object_cache", "wp_cache_anon_only", "wp_supercache_cache_list", "wp_cache_debug_to_file", "wp_super_cache_debug", "wp_cache_debug_level", "wp_cache_debug_ip", "wp_cache_debug_log", "wp_cache_debug_email", "wp_cache_pages", "wp_cache_hide_donation", "wp_cache_not_logged_in", "wp_cache_clear_on_post_edit", "wp_cache_hello_world", "wp_cache_mobile_enabled", "wp_cache_cron_check", "use_flock", "sem_id" );
	foreach( $fields as $field ) {
		$GLOBALS[ $field ] = $$field;
		$wpsc_settings[ $field ] = $$field;
	}
	return true;
}

if( false == wp_cache_load_config() )
	return true;

if( !defined( 'WPCACHEHOME' ) )
	define('WPCACHEHOME', dirname(__FILE__).'/');

include( WPCACHEHOME . 'wp-cache-base.php');

if( $wpsc_settings[ 'blogcacheid' ] != '' ) {
	$wpsc_settings[ 'blog_cache_dir' ] = str_replace( '//', '/', $wpsc_settings[ 'cache_path' ] . "blogs/" . $wpsc_settings[ 'blogcacheid' ] . '/' );
} else {
	$wpsc_settings[ 'blog_cache_dir' ] = $wpsc_settings[ 'cache_path' ];
}
$blog_cache_dir = $wpsc_settings[ 'blog_cache_dir' ];

$wpsc_settings[ 'mutex_filename' ] = 'wp_cache_mutex.lock';
$new_cache = false;

if( !isset( $wp_cache_plugins_dir ) )
	$wp_cache_plugins_dir = WPCACHEHOME . 'plugins';

require_once( WPCACHEHOME . 'wp-cache-phase2.php');

$plugins = glob( $wp_cache_plugins_dir . '/*.php' );
if( is_array( $plugins ) ) {
	foreach ( $plugins as $plugin ) {
	if( is_file( $plugin ) )
		require_once( $plugin );
	}
}

if (!$cache_enabled || $_SERVER["REQUEST_METHOD"] == 'POST') 
	return true;

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

add_cacheaction( 'wp_cache_get_cookies_values', 'wp_cache_check_mobile' );

$wp_cache_request_uri = $_SERVER[ 'REQUEST_URI' ]; // Cache this in case any plugin modifies it.

if ( $wp_cache_object_cache ) {
	if ( ! include_once( WP_CONTENT_DIR . '/object-cache.php' ) )
		return;
	
	wp_cache_init(); // Note: wp-settings.php calls wp_cache_init() which clobbers the object made here.
	
	if ( ! is_object( $wp_object_cache ) )
		return;
}

if( false == @is_dir( $blog_cache_dir ) ) {
	@mkdir( $cache_path . "blogs" );
	@mkdir( $blog_cache_dir );
}

if( false == @is_dir( $blog_cache_dir . 'meta' ) )
	@mkdir( $blog_cache_dir . 'meta' );


$wp_start_time = microtime();

function get_wp_cache_key( $url = false ) {
	global $wp_cache_request_uri, $wp_cache_gzip_encoding;
	if ( !$url )
		$url = $wp_cache_request_uri;
	return do_cacheaction( 'wp_cache_key', $_SERVER['HTTP_HOST'] . intval( $_SERVER[ 'SERVER_PORT' ] ) . preg_replace('/#.*$/', '', str_replace( '/index.php', '/', $url ) ) . $wp_cache_gzip_encoding . wp_cache_get_cookies_values() );
}

function wp_super_cache_init() {
	global $wp_cache_key, $key, $blogcacheid, $wp_cache_request_uri, $file_prefix, $blog_cache_dir, $meta_file, $cache_file, $cache_filename, $wp_super_cache_debug, $meta_pathname, $wp_cache_gzip_encoding, $meta;

	$wp_cache_key = get_wp_cache_key();
	$key = $blogcacheid . md5( $wp_cache_key );
	$wp_cache_key = $blogcacheid . $wp_cache_key;

	$cache_filename = $file_prefix . $key . '.html';
	$meta_file = $file_prefix . $key . '.meta';
	$cache_file = realpath( $blog_cache_dir . $cache_filename );
	$meta_pathname = realpath( $blog_cache_dir . 'meta/' . $meta_file );
	return compact( 'key', 'cache_filename', 'meta_file', 'cache_file', 'meta_pathname' );
}

function wp_cache_serve_cache_file() {
	global $key, $blogcacheid, $wp_cache_request_uri, $file_prefix, $blog_cache_dir, $meta_file, $cache_file, $cache_filename, $wp_super_cache_debug, $meta_pathname, $wp_cache_gzip_encoding, $meta;
	global $wp_cache_object_cache, $cache_compression, $wp_cache_slash_check;

	extract( wp_super_cache_init() );

	if ( wp_cache_user_agent_is_rejected() ) {
		if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "No wp-cache file served as user agent rejected.", 5 );
		return false;
	}

	if ( $wp_cache_object_cache && wp_cache_get_cookies_values() == '' ) { 
		if ( !empty( $_GET ) ) {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Non empty GET request. Not serving request from object cache", 1 );
			return false;
		}

		$oc_key = get_oc_key();
		$meta_filename = $oc_key . ".meta";
		if ( gzip_accepted() ) {
			$oc_key .= ".gz";
			$meta_filename .= ".gz";
		}
		$cache = wp_cache_get( $oc_key, 'supercache' );
		$meta = unserialize( wp_cache_get( $meta_filename, 'supercache' ) );
		if ( is_array( $meta ) == false ) {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Meta array from object cache corrupt. Ignoring cache.", 1 );
			return true;
		}
	} elseif ( file_exists( $cache_file ) ) {
		if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "wp-cache file exists: $cache_file", 5 );
		if ( !( $meta = unserialize( @file_get_contents( $meta_pathname) ) ) )  {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "couldn't load wp-cache meta file", 5 );
			return true;
		}
		if ( is_array( $meta ) == false ) {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "meta array corrupt, deleting $meta_pathname and $cache_file", 1 );
			@unlink( $meta_pathname );
			@unlink( $cache_file );
			return true;
		}
	} else {
		// last chance, check if a supercache file exists. Just in case .htaccess rules don't work on this host
		$file = get_current_url_supercache_dir() . "index.html";
		if ( ( $wp_cache_request_uri == '/' || ( $wp_cache_slash_check && substr( $wp_cache_request_uri, -1 ) == '/' ) || ( $wp_cache_slash_check == 0 && substr( $wp_cache_request_uri, -1 ) != '/' ) ) && ( wp_cache_get_cookies_values() == '' && empty( $_GET ) && file_exists( $file ) ) ) {
			header( "Content-type: text/html; charset=UTF-8" ); // UTF-8 hard coded is bad but we don't know what it is this early in the process
			header( "Vary: Accept-Encoding, Cookie" );
			header( "Cache-Control: max-age=300, must-revalidate" );
			header( "WP-Super-Cache: Served supercache file from PHP" );
			if ( file_exists( $file . ".gz" ) && $wp_cache_gzip_encoding ) {
				header( 'Content-Encoding: ' . $wp_cache_gzip_encoding );
				header( 'Content-Length: ' . filesize( $file . ".gz" ) );
				readfile( $file . ".gz" );
			} else {
				readfile( $file );
			}
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Served page from supercache file. Mod rewrite rules may be broken or missing.", 5 );
			die();
		} else {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "No wp-cache file exists. Must generate a new one.", 5 );
			return false;
		}
	}
	$cache_file = do_cacheaction( 'wp_cache_served_cache_file', $cache_file );
	// Sometimes the gzip headers are lost. Make sure html returned isn't compressed!
	if ( $cache_compression && $wp_cache_gzip_encoding && !in_array( 'Content-Encoding: ' . $wp_cache_gzip_encoding, $meta[ 'headers' ] ) ) {
		$ungzip = true;
		if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "GZIP headers not found. Force uncompressed output.", 1 );
	} else {
		$ungzip = false;
	}
	foreach ($meta[ 'headers' ] as $t => $header) {
		// godaddy fix, via http://blog.gneu.org/2008/05/wp-supercache-on-godaddy/ and http://www.littleredrails.com/blog/2007/09/08/using-wp-cache-on-godaddy-500-error/
		if( strpos( $header, 'Last-Modified:' ) === false ) 
			header($header);
	}
	header( 'WP-Super-Cache: Served legacy cache file' );
	if ( $wp_cache_object_cache ) { 
		if ( $cache ) {
			if ( $ungzip ) {
				$uncompressed = gzuncompress( $cache );
				if ( $uncompressed ) {
					if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Uncompressed gzipped cache file from object cache", 1 );
					$cache = $uncompressed;
					unset( $uncompressed );
				}
			}
			if ( $meta[ 'dynamic' ] ) {
				if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Serving wp-cache dynamic file from object cache", 5 );
				eval( '?>' . $cache . '<?php ' );
			} else {
				if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Serving wp-cache static file from object cache", 5 );
				echo $cache;
			}
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "exit request", 5 );
			die();
		}
	} else {
		if ( $meta[ 'dynamic' ] ) {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Serving wp-cache dynamic file", 5 );
			if ( $ungzip ) {
				$cache = file_get_contents( $cache_file );
				$uncompressed = gzuncompress( $cache );
				if ( $uncompressed ) {
					if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Uncompressed gzipped cache file from wp-cache", 1 );
					unset( $cache );
					eval( '?>' . $uncompressed . '<?php ' );
				} else {
					eval( '?>' . $cache . '<?php ' );
				}
			} else {
				include( $cache_file );
			}
		} else {
			if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Serving wp-cache static file", 5 );
			if ( $ungzip ) {
				$cache = file_get_contents( $cache_file );
				$uncompressed = gzuncompress( $cache );
				if ( $uncompressed ) {
					if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Uncompressed gzipped cache file from wp-cache", 1 );
					echo $uncompressed;
				} else {
					echo $cache;
				}
			} else {
				readfile( $cache_file );
			}
		}
		if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "exit request", 5 );
		die();
	}
}

if(defined('DOING_CRON')) {
	extract( wp_super_cache_init() );
	return true;
}

if ( !isset( $wp_super_cache_late_init ) || ( isset( $wp_super_cache_late_init ) && false == $wp_super_cache_late_init ) ) {
	wp_cache_serve_cache_file();
}

function wp_cache_postload() {
	global $cache_enabled, $wp_super_cache_late_init, $wp_super_cache_debug;

	if ( !$cache_enabled || isset( $_GET[ 'preview' ] ) )
		return true;

	if ( isset( $wp_super_cache_late_init ) && true == $wp_super_cache_late_init ) {
		if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Supercache Late Init: add wp_cache_serve_cache_file to init", 3 );
		add_action( 'init', 'wp_cache_late_loader', 9999 );
	} else {
		wp_super_cache_init();
		wp_cache_phase2();
	}
}

function wp_cache_late_loader() {
	global $wp_super_cache_debug;
	if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug ) wp_cache_debug( "Supercache Late Loader running on init", 3 );
	wp_cache_serve_cache_file();
	wp_cache_phase2();
}

function wp_cache_get_cookies_values() {
	$string = '';
	while ($key = key($_COOKIE)) {
		if ( preg_match( "/^wp-postpass|^wordpress_logged_in|^comment_author_/", $key ) ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "Cookie detected: $key", 5 );
			$string .= $_COOKIE[ $key ] . ",";
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
	if( array_key_exists($action, $wp_supercache_actions) && is_array( $wp_supercache_actions[ $action ] ) ) {
		$actions = $wp_supercache_actions[ $action ];
		foreach( $actions as $func ) {
			$value = $func( $value );
		}
	}

	return $value;
}

function wp_cache_mobile_group( $user_agent ) {
	global $wp_cache_mobile_groups;
	foreach( (array)$wp_cache_mobile_groups as $name => $group ) {
		foreach( (array)$group as $browser ) {
			$browser = trim( strtolower( $browser ) );
			if ( $browser != '' && strstr( $user_agent, $browser ) ) {
				return $browser;
			}
		}
	}
	return "mobile";
}

// From http://wordpress.org/extend/plugins/wordpress-mobile-edition/ by Alex King
function wp_cache_check_mobile( $cache_key ) {
	global $wp_cache_mobile_enabled, $wp_cache_mobile_browsers, $wp_cache_mobile_prefixes;
	if ( !isset( $wp_cache_mobile_enabled ) || false == $wp_cache_mobile_enabled )
		return $cache_key;

	if ( !isset( $_SERVER[ "HTTP_USER_AGENT" ] ) ) {
		return $cache_key;
	}

	$browsers = explode( ',', $wp_cache_mobile_browsers );
	$user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );
	foreach ($browsers as $browser) {
		if ( strstr( $user_agent, trim( strtolower( $browser ) ) ) ) {
			if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "mobile browser detected: " . $_SERVER[ "HTTP_USER_AGENT" ], 5 );
			return $cache_key . '-' . wp_cache_mobile_group( $user_agent );
		}
	}
	if (isset($_SERVER['HTTP_X_WAP_PROFILE']) ) 
		return $cache_key . '-' . $_SERVER['HTTP_X_WAP_PROFILE'];
	if (isset($_SERVER['HTTP_PROFILE']) )
		return $cache_key . '-' . $_SERVER['HTTP_PROFILE'];

	if ( isset( $wp_cache_mobile_prefixes ) ) {
		$browsers = explode( ',', $wp_cache_mobile_prefixes );
		foreach ($browsers as $browser_prefix) {
			if ( substr($user_agent, 0, 4) == $browser_prefix ) {
				if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "mobile browser (prefix) detected: " . $_SERVER[ "HTTP_USER_AGENT" ], 5 );
				return $cache_key . '-' . $browser_prefix;
			}
		}
	}
	$accept = strtolower($_SERVER['HTTP_ACCEPT']);
	if (strpos($accept, 'wap') !== false) {
		return $cache_key . '-' . 'wap';
	}

	if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false) {
		return $cache_key . '-' . 'operamini';
	}
	return $cache_key;
}

function wp_cache_debug( $message, $level = 1 ) {
	global $wp_cache_debug_level, $wp_cache_debug_log, $wp_cache_debug_email, $cache_path, $wp_cache_debug_ip, $wp_super_cache_debug;

	if ( isset( $wp_super_cache_debug ) && $wp_super_cache_debug == false )
		return false;

	if ( isset( $wp_cache_debug_email ) == false && isset( $wp_cache_debug_log ) == false )
		return false;

	if ( isset( $wp_cache_debug_level ) == false )
		$wp_cache_debug_level = 1;
	if ( $wp_cache_debug_level < $level )
		return false;

	if ( isset( $wp_cache_debug_ip ) && $wp_cache_debug_ip != '' && $wp_cache_debug_ip != $_SERVER[ 'REMOTE_ADDR' ] )
		return false;

	if ( isset( $wp_cache_debug_log ) && $wp_cache_debug_log != '' ) {
		error_log( date( 'H:i:s' ) . " " . $_SERVER[ 'REQUEST_URI' ] . " " . $message . "\n", 3, $cache_path . str_replace( '/', '', str_replace( '..', '', $wp_cache_debug_log ) ) );
	}

	if ( isset( $wp_cache_debug_email ) && $wp_cache_debug_email != ''  ) {
		$message .= "\n\nDisable these emails by commenting out or deleting the line containing\n\$wp_cache_debug_email in wp-content/wp-cache-config.php on your server.\n";
		mail( $wp_cache_debug_email, '[' . addslashes( $_SERVER[ 'HTTP_HOST' ] ) . "] WP Super Cache Debug", $message );
	}
}

function wp_cache_user_agent_is_rejected() {
	global $cache_rejected_user_agent;

	if (!function_exists('apache_request_headers')) return false;
	$headers = apache_request_headers();
	if (!isset($headers["User-Agent"])) return false;
	foreach ($cache_rejected_user_agent as $expr) {
		if (strlen($expr) > 0 && stristr($headers["User-Agent"], $expr))
			return true;
	}
	return false;
}

function get_current_url_supercache_dir( $post_id = 0 ) {
	global $cached_direct_pages, $cache_path, $wp_cache_request_uri;

	if ( $post_id != 0 ) {
		$uri = str_replace( site_url(), '', get_permalink( $post_id ) );
	} else {
		$uri = $wp_cache_request_uri;
	}
	$uri = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', str_replace( '/index.php', '/', str_replace( '..', '', preg_replace("/(\?.*)?$/", '', $uri ) ) ) );
	$uri = str_replace( '\\', '', $uri );
	$dir = strtolower(preg_replace('/:.*$/', '',  $_SERVER["HTTP_HOST"])) . $uri; // To avoid XSS attacks
	if ( function_exists( "apply_filters" ) )
		$dir = apply_filters( 'supercache_dir', $dir );
	$dir = $cache_path . 'supercache/' . $dir . '/';
	if( is_array( $cached_direct_pages ) && in_array( $_SERVER[ 'REQUEST_URI' ], $cached_direct_pages ) ) {
		$dir = ABSPATH . $uri . '/';
	}
	$dir = str_replace( '//', '/', $dir );
	if ( isset( $GLOBALS[ 'wp_super_cache_debug' ] ) && $GLOBALS[ 'wp_super_cache_debug' ] ) wp_cache_debug( "supercache dir: $dir", 5 );
	return $dir;
}

function get_oc_version() {
	$wp_cache_oc_key = wp_cache_get( "wp_cache_oc_key" );
	if ( ! $wp_cache_oc_key ) {
		$wp_cache_oc_key[ 'key' ] = reset_oc_version();
	} elseif ( $wp_cache_oc_key[ 'ts' ] < time() - 600 )
		wp_cache_set( "wp_cache_oc_key", array( 'ts' => time(), 'key' => $wp_cache_oc_key[ 'key' ] ) );
	return $wp_cache_oc_key[ 'key' ];
}

function reset_oc_version( $version = 1 ) {
	if ( $version == 1 )
		$version = mt_rand();
	wp_cache_set( "wp_cache_oc_key", array( 'ts' => time(), 'key' => $version ) );

	return $version;
}

function get_oc_key( $url = false ) {
	global $wp_cache_gzip_encoding;

	if ( $url ) {
		$key = intval( $_SERVER[ 'SERVER_PORT' ] ) . strtolower( preg_replace( '/:.*$/', '',  $_SERVER[ "HTTP_HOST" ] ) ) . $url;
	} else {
		$key = get_current_url_supercache_dir();
	}
	return $key . $wp_cache_gzip_encoding . get_oc_version();
}

?>
