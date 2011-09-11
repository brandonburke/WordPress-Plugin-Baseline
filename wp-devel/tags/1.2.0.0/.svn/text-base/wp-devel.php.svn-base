<?php
/*
######################################################################################

Plugin Name: WP-Devel
Plugin URI: http://webdevstudios.com/support/wordpress-plugins
Description: A collection of useful development tools for your WordPress site.
Version: 1.2
Author: WebDevStudios
Author URI: http://webdevstudios.com

######################################################################################

Copyright 2009  WebDevStudios  (email : contact@webdevstudios.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

######################################################################################

VERSION HISTORY
v1.2 - fixed WP_DEBUG to work with the plugin now using error_reporting(E_ALL);
	   fixed "Notice: Undefined index" warnings when checking setting values

v1.1 - added functionality to show debug info in header or footer
	 - added menu option to easily change where debug info is displayed
	 - added WP_DEBUG option in settings and menu

v1.0 - released 5/24/2009

ATTRIBUTION
WP-Devel was built with the help of these great plugins:
http://wordpress.org/extend/plugins/wp-debug/
http://wordpress.org/extend/plugins/debug-queries/
http://wordpress.org/extend/plugins/show-template/
http://wordpress.org/extend/plugins/wordpress-admin-bar/
*/

//set current version
$wds_dts_version = "1.2";

//PLUGIN MUST INCLUDE THIS LINE FOR ALL FEATURES TO WORK:
define('SAVEQUERIES', true);

//include Krumo: http://krumo.sourceforge.net/
include ('krumo/class.krumo.php');
add_action('wp_head', 'krumo_styles');

//hook for adding admin settings menu
add_action('admin_menu', 'wds_dts_menu');

//hook for adding the debug menu and styling
add_action('wp_footer', 'wds_dts_bar');
add_action('wp_head','wds_dts_bar_style');

function wds_dts_menu() {
  add_options_page('WP-Devel Options', 'WP-Devel', 8, __FILE__, 'wds_dts_options');
}

if ( (isset($_GET['wds_dts_option']) && $_GET['wds_dts_option']) || (isset($_POST['wds_dts_display_where']) && $_POST['wds_dts_display_where']) )
{
	$options_arr = get_option('post_wds_dts_params');
	
	//coming from the menu form post
	If (isset($_POST['wds_dts_display_where']) ) {
	
		If ($_POST['wds_dts_display_where'] == "HEADER") {
			//update display option to header
			$options_arr['dts_display_where'] = "HEADER";
		}Else{
			//update display option to footer
			$options_arr['dts_display_where'] = "FOOTER";
		}
		
		update_option('post_wds_dts_params', $options_arr);
		
	//else if If coming from a menu selection
	}Else{
			
		$option_name = $_GET['wds_dts_option'];
	
		If ($options_arr[$option_name] == "on") {
			//option is currently enabled so disable it
			$options_arr[$option_name] = "";
		}Else{
			//option is currently disabled so enable it
			$options_arr[$option_name] = "on";
		}
	
		update_option('post_wds_dts_params', $options_arr);	
	}

}

//SAVE PLUGIN SETTINGS: function to save the plugin settings
function update_options()
{

	check_admin_referer('wds_dts_check');
	$wds_dts_show_template = $_POST['wds_dts_show_template'];
	$wds_dts_make_comment = $_POST['wds_dts_make_comment'];
	$wds_dts_show_queries = $_POST['wds_dts_show_queries'];
	$wds_dts_load_time = $_POST['wds_dts_load_time'];
	$wds_dts_phpinfo = $_POST['wds_dts_phpinfo'];
	$wds_dts_wp_query = $_POST['wds_dts_wp_query'];
	$wds_dts_includes = $_POST['wds_dts_includes'];
	$wds_dts_functions = $_POST['wds_dts_functions'];
	$wds_dts_classes = $_POST['wds_dts_classes'];
	$wds_dts_http_headers = $_POST['wds_dts_http_headers'];
	$wds_dts_constants = $_POST['wds_dts_constants'];
	$wds_dts_cookies = $_POST['wds_dts_cookies'];
	$wds_dts_server = $_POST['wds_dts_server'];
	$wds_dts_env = $_POST['wds_dts_env'];
	$wds_dts_session = $_POST['wds_dts_session'];
	$wds_dts_post = $_POST['wds_dts_post'];
	$wds_dts_get = $_POST['wds_dts_get'];
	$wds_dts_request = $_POST['wds_dts_request'];
	$wds_dts_k_phpinfo = $_POST['wds_dts_k_phpinfo'];
	$wds_dts_display_where = $_POST['wds_dts_display_where'];
	$wds_dts_wp_debug = $_POST['wds_dts_wp_debug'];
	
	$wds_dts_arr=array(
		"dts_show_template"=>$wds_dts_show_template,
		"dts_make_comment"=>$wds_dts_make_comment,
		"dts_show_queries"=>$wds_dts_show_queries,
		"dts_load_time"=>$wds_dts_load_time,
		"dts_phpinfo"=>$wds_dts_phpinfo,
		"dts_wp_query"=>$wds_dts_wp_query,
		"dts_includes"=>$wds_dts_includes,
		"dts_functions"=>$wds_dts_functions,
		"dts_classes"=>$wds_dts_classes,
		"dts_http_headers"=>$wds_dts_http_headers,
		"dts_constants"=>$wds_dts_constants,
		"dts_cookies"=>$wds_dts_cookies,
		"dts_server"=>$wds_dts_server,
		"dts_env"=>$wds_dts_env,
		"dts_session"=>$wds_dts_session,
		"dts_post"=>$wds_dts_post,
		"dts_get"=>$wds_dts_get,
		"dts_request"=>$wds_dts_request,
		"dts_k_phpinfo"=>$wds_dts_k_phpinfo,
		"dts_display_where"=>$wds_dts_display_where,
		"dts_wp_debug"=>$wds_dts_wp_debug,
		);
	
	update_option('post_wds_dts_params', $wds_dts_arr);
	
} # update_options()

//SHOW SETTINGS PAGE
function wds_dts_options() {
	global $wds_dts_version;
		# Acknowledge update

		if ( isset($_POST['wds_dts_update_options']) && $_POST['wds_dts_update_options'] )
		{
			update_options();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Settings saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

	$options_arr = get_option('post_wds_dts_params');
	
	echo '<div class="wrap">';
	echo '<h2>' . __('WP-Devel Settings') . '</h2>';
	//echo 'Fill in instructions here later. <br /><br />';
	echo '<form method="post" action="">';
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('wds_dts_check');
	echo '<input type="hidden" name="wds_dts_update_options" value="1">';

	//add comment tag option
	echo 'Show/hide debug information<br />';
	echo '<input type="checkbox" name="wds_dts_make_comment" ';
		If ($options_arr["dts_make_comment"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Wrap debug info in comment tags? (only viewable in source)<br />';

	//show debug display option in header or footer
	echo 'Show debug info in: <select name="wds_dts_display_where">';
	echo '<option value="FOOTER"';
	If ($options_arr["dts_display_where"] == "FOOTER") { echo "SELECTED"; }
	echo '>Footer';
	echo '<option value="HEADER"';
	If ($options_arr["dts_display_where"] == "HEADER") { echo "SELECTED"; } 
	echo '>Header';
	echo '</select>';

	echo '<br /><br />';
	echo 'Debug information<br />';

	//show template file option
	echo '<input type="checkbox" name="wds_dts_show_template" ';
		If ($options_arr["dts_show_template"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show template file currently viewing?<br />';

	//show page load time option
	echo '<input type="checkbox" name="wds_dts_load_time" ';
		If ($options_arr["dts_load_time"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Display page load times?<br />';

	//show queries option
	echo '<input type="checkbox" name="wds_dts_show_queries" ';
		If ($options_arr["dts_show_queries"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Display all queries executed with load times?<br />';

	//show WP_Debug option
	echo '<input type="checkbox" name="wds_dts_wp_debug" ';
		If ($options_arr["dts_wp_debug"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Enable WP_Debug (show error messages)?<br />';
		
	//show phpinfo
	echo '<input type="checkbox" name="wds_dts_phpinfo" ';
		If ($options_arr["dts_phpinfo"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show phpinfo?';

	echo '<br /><br />';
	echo 'Krumo debug info (<a href="http://krumo.sourceforge.net/" target="_blank">http://krumo.sourceforge.net/</a>)<br />';
	
	//show WP_Query and WP array values
	echo '<input type="checkbox" name="wds_dts_wp_query" ';
		If ($options_arr["dts_wp_query"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show WP_Query and WP detailed array values<br />';
	
	//show all included files
	echo '<input type="checkbox" name="wds_dts_includes" ';
		If ($options_arr["dts_includes"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all included files<br />';
	
	//show all included functions
	echo '<input type="checkbox" name="wds_dts_functions" ';
		If ($options_arr["dts_functions"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all included functions<br />';
	
	//show all declared classes
	echo '<input type="checkbox" name="wds_dts_classes" ';
		If ($options_arr["dts_classes"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all declared classes<br />';
	
	//show all HTTP headers
	echo '<input type="checkbox" name="wds_dts_http_headers" ';
		If ($options_arr["dts_http_headers"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all HTTP headers<br />';
	
	//show all defined constants
	echo '<input type="checkbox" name="wds_dts_constants" ';
		If ($options_arr["dts_constants"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all defined constants<br />';
	
	//show all current cookies
	echo '<input type="checkbox" name="wds_dts_cookies" ';
		If ($options_arr["dts_cookies"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all current cookies<br />';
	
	//show all values in $_SERVER array
	echo '<input type="checkbox" name="wds_dts_server" ';
		If ($options_arr["dts_server"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_SERVER array<br />';
	
	//show all values in $_ENV array
	echo '<input type="checkbox" name="wds_dts_env" ';
		If ($options_arr["dts_env"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_ENV array<br />';
	
	//show all values in $_SESSION array
	echo '<input type="checkbox" name="wds_dts_session" ';
		If ($options_arr["dts_session"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_SESSION array<br />';
	
	//show all values in $_POST array
	echo '<input type="checkbox" name="wds_dts_post" ';
		If ($options_arr["dts_post"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_POST array<br />';
	
	//show all values in $_GET array
	echo '<input type="checkbox" name="wds_dts_get" ';
		If ($options_arr["dts_get"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_GET array<br />';
	
	//show all values in $_REQUEST array
	echo '<input type="checkbox" name="wds_dts_request" ';
		If ($options_arr["dts_request"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in the $_REQUEST array<br />';
	
	//show all values in phpinfo
	echo '<input type="checkbox" name="wds_dts_k_phpinfo" ';
		If ($options_arr["dts_k_phpinfo"] == "on") { echo "CHECKED"; } 
	echo ' >&nbsp;&nbsp;Show all values in phpinfo<br />';
		
	//echo '<p>More instructions if we need it</p>';
	echo '<p class="submit">'
	. '<input type="submit"'
		. ' value="' . attribute_escape(__('Save Changes')) . '"'
		. ' />'
	. '</p></form>';
	echo '<p>For support please visit our <a href="http://webdevstudios.com/support/wordpress-plugins/" target="_blank">WordPress Plugins Support page</a> | Version ' .$wds_dts_version .' by <a href="http://webdevstudios.com/" title="WordPress Development and Design" target="_blank">WebDevStudios.com</a></p>';
	echo '</div>';
}

//WP-Devel Toolbar
function wds_dts_bar() {
	global $wpdb, $user_level;
	if ( current_user_can('edit_plugins') ) {
	?>

	<div id="wpabar">
	<div id="wpabar-leftside">
	<ul>
	<li class="wpabar-menu-first">
		<a href="<?php echo admin_url(); ?>"><?php _e('WP-Devel'); ?></a>
	</li>
    <li>
    	<div id="holder">
		Debug Info<br />
		<hr />
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_show_template"); ?>"><?php display_option_value_sh('dts_show_template');?> Template File</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_load_time"); ?>"><?php display_option_value_sh('dts_load_time');?> Load Time</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_show_queries"); ?>"><?php display_option_value_sh('dts_show_queries');?> Queries</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_wp_debug"); ?>"><?php display_option_value_ed('dts_wp_debug');?> WP_Debug</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_phpinfo"); ?>"><?php display_option_value_sh('dts_phpinfo');?> PHPinfo</a>
		</div>
	</li>
        <li>
    	<div id="holder">
		Krumo Debug<br />
		<hr />
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_wp_query"); ?>"><?php display_option_value_sh('dts_wp_query');?> WP_Query and WP</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_includes"); ?>"><?php display_option_value_sh('dts_includes');?> Includes</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_functions"); ?>"><?php display_option_value_sh('dts_functions');?> Functions</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_classes"); ?>"><?php display_option_value_sh('dts_classes');?> Classes</a>
		<a href="<?php echo add_query_arg ("wds_dts_option", "dts_http_headers"); ?>"><?php display_option_value_sh('dts_http_headers');?> HTTP Headers</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_constants"); ?>"><?php display_option_value_sh('dts_constants');?> Constants</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_cookies"); ?>"><?php display_option_value_sh('dts_cookies');?> Cookies</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_server"); ?>"><?php display_option_value_sh('dts_server');?> $_SERVER</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_env"); ?>"><?php display_option_value_sh('dts_env');?> $_ENV</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_session"); ?>"><?php display_option_value_sh('dts_session');?> $_SESSION</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_post"); ?>"><?php display_option_value_sh('dts_post');?> $_POST</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_get"); ?>"><?php display_option_value_sh('dts_get');?> $_GET</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_request"); ?>"><?php display_option_value_sh('dts_request');?> $_REQUEST</a>
        <a href="<?php echo add_query_arg ("wds_dts_option", "dts_k_phpinfo"); ?>"><?php display_option_value_sh('dts_k_phpinfo');?> PHPinfo</a>
		</div>
	</li>
	</ul>
	</div>
    <?php 
	$options_arr = get_option('post_wds_dts_params');
	?>
    <form method="post" action="">
	<div id="wpabar-rightside">
	<ul>
    	<li style="line-height:28px !important;padding-right:10px;">Display in <select name="wds_dts_display_where" onChange="this.form.submit();"><option value="FOOTER" <?php If ($options_arr["dts_display_where"] == "FOOTER") { echo "SELECTED"; } ?>>Footer</option><option value="HEADER" <?php If ($options_arr["dts_display_where"] == "HEADER") { echo "SELECTED"; } ?>>Header</option></select></li>
    	<li style="line-height:28px !important;padding-right:10px;">Queries: <?php echo $wpdb->num_queries; ?></li>
    	<li style="line-height:28px !important;padding-right:10px;">Load Time: <?php timer_stop(1); echo _e(' seconds')?></li>
        <li><a href="<?php echo wp_logout_url(); ?>"><?php _e('Log Out'); ?></a></li>
	</ul>
	</div>
    </form>
	</div>
	<?php
	}
}

//WP-Devel Toolbar Styling
function wds_dts_bar_style () {
global $user_level;
if ( current_user_can('edit_plugins') ) {
?>

<style type="text/css">

#info {position:relative; height:24em;}
#info h2 {margin-bottom:7em;}
#holder {margin: 2px 2px 2px 2px; top:2px; left:320px; width:175px; line-height:18px; height:18px; border:1px solid #dddddd; overflow:hidden; text-align:left; z-index:1000; background:#333333;padding: 2px;}
#holder:hover {height:100%; cursor:pointer; background:#333333;}
#holder a:visited, #holder a {display:block; width:100%; line-height:18px; color:#000; text-decoration:none;}
#holder a:hover {color:#c00;background:#ddd;}


</style>

<link rel="stylesheet" href="<?php echo plugins_url( 'wp-devel/css/style.css' );?>" type="text/css" />
	<?php
	}
}


class ShowDevSuite {

	var $template;

	function __construct() {
		if ( is_admin() )
			return;
			add_action('template_redirect', array(&$this, 'check_template'));
	}

	// Using the same logic used by WordPress determine the template to be used
	function check_template() {
		if ( is_trackback() ) {
			$this->template = ABSPATH . 'wp-trackback.php';
		} else if ( is_404() && $template = get_404_template() ) {
			$this->template = $template;
		} else if ( is_search() && $template = get_search_template() ) {
			$this->template = $template;
		} else if ( is_tax() && $template = get_taxonomy_template()) {
			$this->template = $template;
		} else if ( is_home() && $template = get_home_template() ) {
			$this->template = $template;
		} else if ( is_attachment() && $template = get_attachment_template() ) {
			$this->template = $template;
		} else if ( is_single() && $template = get_single_template() ) {
			$this->template = $template;
		} else if ( is_page() && $template = get_page_template() ) {
			$this->template = $template;
		} else if ( is_category() && $template = get_category_template()) {
			$this->template = $template;
		} else if ( is_tag() && $template = get_tag_template()) {
			$this->template = $template;
		} else if ( is_author() && $template = get_author_template() ) {
			$this->template = $template;
		} else if ( is_date() && $template = get_date_template() ) {
			$this->template = $template;
		} else if ( is_archive() && $template = get_archive_template() ) {
			$this->template = $template;
		} else if ( is_comments_popup() && $template = get_comments_popup_template() ) {
			$this->template = $template;
		} else if ( is_paged() && $template = get_paged_template() ) {
			$this->template = $template;
		} else if ( file_exists(TEMPLATEPATH . "/index.php") ) {
			$this->template = TEMPLATEPATH . "/index.php";
		}
		
		$options_arr = get_option('post_wds_dts_params');
		If ($options_arr["dts_display_where"] == "HEADER") {
			// Hook into the header so we can echo the active template
			add_action('wp_head', array(&$this, 'show_template'));
		}Else{
			// Hook into the footer so we can echo the active template
			add_action('wp_footer', array(&$this, 'show_template'));
		}
	}

	// Echo the active template to the footer
	function show_template() {
		global $user_ID, $wp_query, $wp, $wpdb;
		?>
        <div style="align:left;">
        <?php
		//check if user is admin
		if ( current_user_can('edit_plugins') ) {
			
			//load options
			$options_arr = get_option('post_wds_dts_params');
			
			//check whether to add comment tags around output
			If(isset($options_arr["dts_make_comment"]) && $options_arr["dts_make_comment"] == "on") {
					//hide debug info with comment tags
					echo "<!--";
			}
			echo "<p>WP-Devel Info:</p>";
			
			//check whether to enable WP_Debug
			If ($options_arr["dts_wp_debug"] == "on") {
				error_reporting(E_ALL);
				echo "WP_Debug is <strong>enabled</strong><br>";
			}else{
				echo "WP_Debug is <strong>disabled</strong><br>";
			}
			
			//check whether to show page load times
			If (isset($options_arr["dts_load_time"]) && $options_arr["dts_load_time"] == "on") {
				//display load seconds
				echo "Queries Executed:";
				echo $wpdb->num_queries."<br>";
				echo "Page Load Time: \n";
		
				//display load time		
				timer_stop(1); 
				echo _e(' seconds')."<br>";
			}
			
			//check whether to show template file
			If (isset($options_arr["dts_show_template"]) && $options_arr["dts_show_template"] == "on" ) {
				echo " Viewing Template File: {$this->template} <br />";
			}
			
			//KRUMO DEBUG INFO
			//check whether to show WP_Query and WP array values
			If (isset($options_arr["dts_wp_query"]) && $options_arr["dts_wp_query"] == "on" ) {
				krumo($wp_query, $wp);
			}		
	
			//check whether to show included files
			If (isset($options_arr["dts_includes"]) && $options_arr["dts_includes"] == "on" ) {
				krumo::includes();
			}	
		
			//check whether to show declared functions
			If (isset($options_arr["dts_functions"]) && $options_arr["dts_functions"] == "on" ) {
				krumo::functions();
			}	
		
			//check whether to show declared classes
			If (isset($options_arr["dts_classes"]) && $options_arr["dts_classes"] == "on" ) {
				krumo::classes();
			}	
		
			//check whether to show HTTP headers
			If (isset($options_arr["dts_http_headers"]) && $options_arr["dts_http_headers"] == "on" ) {
				krumo::headers();
			}	
		
			//check whether to show defined constants
			If (isset($options_arr["dts_constants"]) && $options_arr["dts_constants"] == "on" ) {
				krumo::defines();
			}	
		
			//check whether to show current cookies
			If (isset($options_arr["dts_cookies"]) && $options_arr["dts_cookies"] == "on" ) {
				krumo::cookie();
			}	
		
			//check whether to show values in $_SERVER array
			If (isset($options_arr["dts_server"]) && $options_arr["dts_server"] == "on" ) {
				krumo::server();
			}	
		
			//check whether to show values in $_ENV array
			If (isset($options_arr["dts_env"]) && $options_arr["dts_env"] == "on" ) {
				krumo::env();
			}	
		
			//check whether to show values in $_SESSION array
			If (isset($options_arr["dts_session"]) && $options_arr["dts_session"] == "on" ) {
				krumo::session();
			}		
		
			//check whether to show values in $_POST array
			If (isset($options_arr["dts_post"]) && $options_arr["dts_post"] == "on" ) {
				krumo::post();
			}	
		
			//check whether to show values in $_GET array
			If (isset($options_arr["dts_get"]) && $options_arr["dts_get"] == "on" ) {
				krumo::get();
			}	
		
			//check whether to show values in $_REQUEST array
			If (isset($options_arr["dts_request"]) && $options_arr["dts_request"] == "on" ) {
				krumo::request();
			}	
		
			//check whether to show phpinfo
			If (isset($options_arr["dts_k_phpinfo"]) && $options_arr["dts_k_phpinfo"] == "on" ) {
				krumo::phpini();
			}				
			
			
			
			//check whether to show all queries executed
			If (isset($options_arr["dts_show_queries"]) && $options_arr["dts_show_queries"] == "on" ) {
				echo "<br />All Queries Executed: \n";
				
				// display all queries ran
				// taken from Debug Queries plugin by Frank Bultge
				// http://wordpress.org/extend/plugins/debug-queries/
				$debugQueries  = '';
				if ($wpdb) {
					$x = 0;
					$total_time = timer_stop( false, 22 );
					$total_query_time = 0;
					$class = ''; 
					$debugQueries .= '<ol>' . "\n";
					
					foreach ($wpdb->queries as $q) {
						if ( $x % 2 != 0 )
							$class = '';
						else
							$class = ' class="alt"';
						$q[0] = trim( ereg_replace('[[:space:]]+', ' ', $q[0]) );
						$total_query_time += $q[1];
						$debugQueries .= '<li' . $class . '><strong>' . __('Time:') . '</strong> ' . $q[1];
						if ( isset($q[1]) )
							$debugQueries .= '<br /><strong>' . __('Query:') . '</strong> ' . $q[0];
						if ( isset($q[2]) )
							$debugQueries .= '<br /><strong>' . __('Call from:') . '</strong> ' . $q[2];
						$debugQueries .= '</li>' . "\n";
						$x++;
					}
					
					$debugQueries .= '</ol>' . "\n\n";
					
					$php_time = $total_time - $total_query_time;
					// Create the percentages
					$mysqlper = number_format_i18n( $total_query_time / $total_time * 100, 2 );
					$phpper   = number_format_i18n( $php_time / $total_time * 100, 2 );
					
					$debugQueries .= '<ul>' . "\n";
					$debugQueries .= '<li><strong>' . __('Total query time:') . ' ' . number_format_i18n( $total_query_time, 5 ) . __('s for') . ' ' . count($wpdb->queries) . ' ' . __('queries.') . '</strong></li>';
					if ( count($wpdb->queries) != get_num_queries() ) {
						$debugQueries .= '<li><strong>' . __('Total num_query time:') . ' ' . timer_stop() . ' ' . __('for') . ' ' . get_num_queries() . ' ' . __('num_queries.') . '</strong></li>' . "\n";
						$debugQueries .= '<li class="none_list">' . __('&raquo; Different values in num_query and query? - please set the constant') . ' <code>define(\'SAVEQUERIES\', true);</code>' . __('in your') . ' <code>wp-config.php</code></li>' . "\n";
					}
					if ( $total_query_time == 0 )
						$debugQueries .= '<li class="none_list">' . __('&raquo; Query time is null (0)? - please set the constant') . ' <code>SAVEQUERIES</code>' . ' ' . __('at') . ' <code>TRUE</code> ' . __('in your') . ' <code>wp-config.php</code></li>' . "\n";
					$debugQueries .= '<li>' . __('Page generated in'). ' ' . number_format_i18n( $total_time, 5 ) . __('s, ') . $phpper . __('% PHP') . ', ' . $mysqlper . __('% MySQL') . '</li>' . "\n";
					$debugQueries .= '</ul>' . "\n";
					
					echo $debugQueries;
				}
			}
			
			If (isset($options_arr["dts_phpinfo"]) && $options_arr["dts_phpinfo"] == "on") {
				//hide debug info with comment tags
				phpinfo();
			}
			
			If (isset($options_arr["dts_make_comment"]) && $options_arr["dts_make_comment"] == "on") {
				//hide debug info with comment tags
				echo "-->";
			}
		}
		?>
        </div>
        <?php
	}
}

function krumo_styles() {
	global $user_ID;
	if ( $user_ID ) {
	 	echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-devel/css/krumo.css"></link>' . "\n";
	}
	else {
	}
}

//Retrieves menu item status: SHOW or HIDE
function display_option_value_sh($option_name) {
	
	$options_arr = get_option('post_wds_dts_params');
	
	if (isset($options_arr[$option_name]) && $options_arr[$option_name] == "on") {
		echo "Hide";
	}else{
		echo "Show";
	}
}

//Retrieves menu item status: ENABLED or DISABLED
function display_option_value_ed($option_name) {
	
	$options_arr = get_option('post_wds_dts_params');
	
	if (isset($options_arr[$option_name]) && $options_arr[$option_name] == "on") {
		echo "Disable";
	}else{
		echo "Enable";
	}
}

//EXECUTE DEV TOOL STUFF ON PUBLIC SITE
if ( is_admin() )
	return;
	
	//execute WP-Devel code for public site
	$ShowDevSuite = new ShowDevSuite();	
	
?>