<?php
/* 
Plugin Name: Members Only
Plugin URI:  http://code.andrewhamilton.net/wordpress/plugins/members-only/
Description: A plugin that allows you to make your WordPress blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected either to the WordPress login page or a page of your choice. Once logged in they can be redirected back to the page that they originally requested. You can also protect your Feeds whilst allowing registered user access to them by using <em>Feed Keys</em>.
Version: 0.7 alpha4
Author: Andrew Hamilton
Author URI: http://andrewhamilton.net
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS & GLOBAL VARIABLES
//----------------------------------------------------------------------------

register_activation_hook(__FILE__,'members_only_setup_options');

//Class to add column to Manage pages to show if post or page is Members Only
if(!class_exists('members_only_manage_columns')) 
{

	class members_only_manage_columns
	{
		function members_only_add_manage_column($defaults) 
		{
			$new = array();
			$new['cb'] = $defaults['cb'];
			$new['mo'] = __('<div class="vers"><img alt="Members Only" src="'.content_url().'/plugins/members-only/img/members-only-padlock.png" /></div>');
			unset($defaults['cb']);
			//print_r(array_merge($new, $defaults));
			return array_merge($new, $defaults);
		}
		
		function members_only_fill_manage_column($column_name, $id) 
		{
			if('mo' == $column_name) 
			{
				$post_access = get_post_meta($id, '_post_access', true);
				
				if ($post_access == 'members-only')
				{
					echo '<div class="vers"><img alt="Members Only" src="../wp-content/plugins/members-only/img/members-only-padlock-grey.gif" /></div>';
				}
				
			}
		}
	}
	
}

//Members Only Options
$members_only_opt = get_option('members_only_options');

$members_only_version = get_option('members_only_version'); //Members Only Version Number
$members_only_this_version = '0.7';

// Check the version of Members Only
if (empty($members_only_version))
{
	add_option('members_only_version', $members_only_this_version);
}
elseif ($members_only_version <= '0.6')
{
	if ($members_only_opt['protect'] == '') { update_option('protect', 'all'); }
}
elseif ($members_only_version != $members_only_this_version)
{
	update_option('members_only_version', $members_only_this_version);
}

//Get the page that was originally requested by the user
$members_only_reqpage = $_SERVER["REQUEST_URI"];

//Setup Feedkey Variables
$feedkey_valid = FALSE;
$feed_redirected = FALSE;

//Get WordPress URLs and Title
$blogurl = get_bloginfo('url');
$wpurl = get_bloginfo('wpurl');
$blogtitle = get_bloginfo('title');

//Get the current URL
$currenturl = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

//----------------------------------------------------------------------------
//	Error Messages
//----------------------------------------------------------------------------

$errormsg = array(
	'feedkey_invalid' => 'The Feed Key you used is invalid. It is either incorrect or has been revoked. Please login to obtain a valid Feed Key.',
	'feedkey_missing' => 'You need to use a Feed Key to access feeds on this site. Please login to obtain yours.',
	'feedkey_notgen' => 'Feed Key not found.',
	'feedurl_notgen' => 'URL is available once Feed Key has been generated.'
	);

//----------------------------------------------------------------------------
//	Setup Default Settings
//----------------------------------------------------------------------------

function members_only_setup_options()
{
	global $members_only_opt;
	
	// Setup Default Options Array
	$optionarray_def = array(
		'members_only' => FALSE,
		'protect' => 'all',
		'default_post_protect' => 'anyone',
		'default_page_protect' => 'members-only',
		'redirect_to' => 'login',
		'login_redirect_to' => 'dashboard',
		'redirect_url' => '',
		'redirect' => TRUE,
		'feed_access' => 'feedkeys',
		'feedkey_reset' => TRUE,
		'require_feedkeys' => FALSE,
		'one_time_view_ip' => NULL
	);
	
	$optionarray_exclusionlist = array(
	
	);
		
	if (empty($members_only_opt)){ //If there aren't already options for Members Only
		add_option('members_only_options', $optionarray_def, 'Members Only Wordpress Plugin Options');
	}	
}

//Detect WordPress version to add compatibility with 2.3 or higher
$wpversion_full = get_bloginfo('version');
$wpversion = preg_replace('/([0-9].[0-9])(.*)/', '$1', $wpversion_full); //Boil down version number to X.X

//--------------------------------------------------------------------------
//	Add Admin Page
//--------------------------------------------------------------------------

function members_only_add_options_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page('Members Only', 'Members Only', 8, basename(__FILE__), 'members_only_options_page');
	}
}

//---------------------------------------------------------------------------
//	Add Feed Key to Profile Page
//---------------------------------------------------------------------------

function members_only_display_feedkey()
{	
	global $profileuser, $current_user, $blogurl, $members_only_opt, $errormsg;
	
	// Setup Feed Key Reset Options
	$feedkey_reset_types = array(
	'Feed Key Options...' => NULL,
	'Reset Feed Key' => 'feedkey-reset',
	'Remove Feed Key' => 'feedkey-remove'
	);
	
	foreach ($feedkey_reset_types as $option => $value) {
		if ($value == $optionarray_def['login_redirect_to']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$feedkey_reset_options .= "\n\t<option value='$value' $selected>$option</option>";
	}

	if ($members_only_opt ['feed_access'] == 'feedkeys') //Check if Feed Keys are being used
	{
		$yourprofile = $profileuser->ID == $current_user->ID;
		$feedkey = get_usermeta($profileuser->ID,'feed_key');
		$permalink_structure = get_option(permalink_structure);
		
		//Check if Permalinks are being used
		empty($permalink_structure) ? $feedjoin = '?feed=rss2&feedkey=' : $feedjoin = '/feed/?feedkey=';
		
		$feedurl = $blogurl.$feedjoin.$feedkey;
		$feedurl = '<a href="'.$feedurl.'">'.$feedurl.'</a>';
		
		?>
		<table class="form-table">
			<h3><?php echo $yourprofile ? _e("Your Feed Key", 'feed-key') : _e("User's Feed Key", 'feed-key') ?></h3>
			<tr>
				<th><label for="feedkey">Feed Key</label></th>
				<td width="250px"><?php echo empty($feedkey) ? _e('<em>'.$errormsg['feedkey_notgen'].'</em>') : _e($feedkey); ?></td>
				<td>
				
				<?php if ($members_only_opt ['feedkey_reset'] == TRUE && !$current_user->has_cap('level_9')) : ?>
					<input name="feedkey-reset" type="checkbox" id="feedkey-reset_inp" value="0" /> Reset Key
				<?php elseif ($current_user->has_cap('level_9')) : ?>
					<?php if (empty($feedkey)) : ?>
						<input name="feedkey-generate" type="checkbox" id="feedkey-generate_inp" value="0" /> Generate Key
					<?php else : ?>
						<select name="feedkey-reset-admin" id="feedkey-reset-admin"><?php echo $feedkey_reset_options ?></select>
					<?php endif; ?>
				<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="feedkey">Your Feed URL</label></th>
				<td colspan="2"><?php echo empty($feedkey) ? _e('<em>'.$errormsg['feedurl_notgen'].'</em>') : _e($feedurl); ?></td>
			</tr>
		</table>
		<?php
	}
}

//----------------------------------------------------------------------------
//	Add Meta Boxes
//----------------------------------------------------------------------------

function members_only_meta_boxes()
{	
	if (function_exists('add_meta_box'))
	{
		add_meta_box('members_only_page_settings','Members Only', 'members_only_page_meta_box','page','normal');
		add_meta_box('members_only_post_settings','Members Only', 'members_only_post_meta_box','post','normal');
	}
}

//----------------------------------------------------------------------------
//	Page Meta Box
//----------------------------------------------------------------------------

function members_only_page_meta_box()
{	
	global $post, $members_only_opt;
	
	$cur_post_access = get_post_meta($post->ID, '_post_access', true);
	
	if (empty($cur_post_access))
	{
		$cur_post_access = $members_only_opt['default_page_protect'];
	}
	
	members_only_create_meta_box('page', $cur_post_access);
	
}

//----------------------------------------------------------------------------
//	Post Meta Box
//----------------------------------------------------------------------------

function members_only_post_meta_box()
{	
	global $post, $members_only_opt;
	
	$cur_post_access = get_post_meta($post->ID, '_post_access', true);
	
	if (empty($cur_post_access))
	{
		$cur_post_access = $members_only_opt['default_post_protect'];
	}
	
	members_only_create_meta_box('post', $cur_post_access);

}

//----------------------------------------------------------------------------
//	Create Meta Box
//----------------------------------------------------------------------------

function members_only_create_meta_box($posttype, $cur_post_access)
{	
	global $post, $members_only_opt;

	// Setup Redirection Options
	$postaccess_types = array(
	'Anyone' => 'anyone',
	'Members Only' => 'members-only'
	);

	foreach ($postaccess_types as $option => $value) {
		if ($value == $cur_post_access)
		{
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$postaccess_options .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	//Create nonce for Members Only
	$members_only_nonce = wp_create_nonce( plugin_basename(__FILE__) );
	
	?>
	<input type="hidden" name="members_only_nonce" id="members_only_nonce" value="<?php echo $members_only_nonce; ?>" />
	<select name="_post_access" id="_post_access_inp"><?php echo $postaccess_options ?></select>
	<p>
	Choose who can access this <?php echo $posttype ?>. <strong>Anyone</strong> (any visitor to your site) or <strong>Members Only</strong> (registered users that are logged in)<br />
	Once you have choosen your option, please save this <?php echo $posttype ?>.	
	</p>
	<?php
	
	return;
}

//----------------------------------------------------------------------------
//	Save Post/Page Meta Data
//----------------------------------------------------------------------------

function members_only_save_meta()
{
	global $post;  	
  	
  	//Verify Members Only nonce
  	if ( !wp_verify_nonce( $_POST['members_only_nonce'], plugin_basename(__FILE__) )) {
  		return $post_id;
  	}
  	
  	//Check user can edit post or page
  	if ( $_POST['post_type'] == 'page') {
    	if (!current_user_can('edit_page', $_POST['ID'])) return $post_id;
  	} else {
    	if (!current_user_can('edit_post', $_POST['ID'])) return $post_id;
  	}
  	
  	//Get Current Post Members Only Setting
	$cur_post_access = get_post_meta($_POST['ID'], '_post_access', true);
	
	//Save Post Members Only Setting in Post Meta
	if (empty($cur_post_access)){
		add_post_meta($_POST['ID'], '_post_access', $_POST['_post_access'], true);
	} else {
		update_post_meta($_POST['ID'], '_post_access', $_POST['_post_access'], $cur_post_access);
	}
  	
}

//----------------------------------------------------------------------------
//		PLUGIN FUNCTIONS
//----------------------------------------------------------------------------

//----------------------------------------------------------------------------
//	Main Function
//----------------------------------------------------------------------------

function members_only()
{
	global $currenturl, $members_only_opt, $feedkey_valid, $errormsg, $userdata, $post, $current_user, $wpurl;
	
	//Get Redirect
	$redirection = members_only_create_redirect();
	
	//Ask if user is logged in
	$userloggedin = is_user_logged_in();
	
	if (md5($_SERVER['REMOTE_ADDR']) == $members_only_opt['one_time_view_ip'] && $members_only_opt['protect'] == 'all' && XMLRPC_REQUEST)	//Check for one-time allowed IP address
	{
		//Remove IP and Update Settings
		$members_only_opt['one_time_view_ip'] = NULL;
		update_option('members_only_options', $members_only_opt);
		
		//Do Nothing
		return;
	}
	
	if (empty($userloggedin)) //Check if user is logged in
	{
		if ($currenturl == $redirection || //...at the redirection page without a trailing slash 
			$currenturl == $redirection.'/' //...at the redirection page with a trailing slash
			)
		{
			// Do Nothing
		}
		elseif ($members_only_opt['protect'] == 'all') //Protecting Entire Site
		{
			//Redirect Page
			members_only_redirect($redirection);
		}
		elseif ($members_only_opt['protect'] == 'pagepost') //Protecting Specific Posts or Pages
		{
			$cur_post_access = get_post_meta($post->ID, '_post_access', true);
			
			if (is_page() || is_single()) //Check whether the User is trying to view a Post or Page
			{
				if ($cur_post_access == 'members-only')
				{
					//Redirect Page
					members_only_redirect($redirection);
				}
			}
		}
	}
	
	//Check whether the User is try to view a Feed
	if (is_feed())
	{	
		if (empty($userloggedin)) //Check if user is logged in
		{
			//Protect Feed
			members_only_feedprotect();
		}
		elseif (is_feed() && $members_only_opt['feed_access'] == 'feedkeys' && $members_only_opt['require_feedkeys'] == TRUE) //If site requires Feed Keys for logged in users
		{
			if (empty($_GET['feedkey']))
			{
				$feed = members_only_create_feed('No Feed Key Found', $errormsg['feedkey_missing']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == FALSE) 
			{
				$feed = members_only_create_feed('Feed Key is Invalid', $errormsg['feedkey_invalid']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == TRUE)
			{
				// Do Nothing
			} 
		}
	}
}

//----------------------------------------------------------------------------
//	Protect Feed Function
//----------------------------------------------------------------------------

function members_only_feedprotect()
{
	global $members_only_opt, $feedkey_valid, $errormsg;
	
	if (empty($_GET['feedkey']) && $members_only_opt['feed_access'] == 'feedkeys')
	{
		$feed = members_only_create_feed('No Feed Key Found', $errormsg['feedkey_missing']);
		header("Content-Type: application/xml; charset=ISO-8859-1");
		echo $feed;
		exit;
	}
	elseif ($feedkey_valid == FALSE && $members_only_opt['feed_access'] == 'feedkeys') 
	{
		$feed = members_only_create_feed('Feed Key is Invalid', $errormsg['feedkey_invalid']);
		header("Content-Type: application/xml; charset=ISO-8859-1");
		echo $feed;
		exit;
	}
	elseif ($feedkey_valid == TRUE || $members_only_opt['feed_access'] == 'feednone')
	{
		// Do Nothing
	}
	else //Not using Feed Keys
	{
		members_only_redirect($redirection);
	}
}

//----------------------------------------------------------------------------
//	Init Function
//----------------------------------------------------------------------------

function members_only_init()
{
	global $userdata, $currenturl, $feedkey_valid, $feed_redirected, $errormsg, $members_only_opt, $wpdb;
	
	//Get Redirect
	$redirection = members_only_create_redirect();
	
	//Parse URL
	$parsed_url = parse_url($currenturl);
	
	//Ask if user is logged in
	$userloggedin = is_user_logged_in();
	
	if (!empty($userloggedin)) // If user is logged in
	{
		//Get User's Feed key
		$feedkey = get_usermeta($userdata->ID,'feed_key');
		
		//If there isn't one then generate one
		if (empty($feedkey))
		{
			$feedkey = members_only_gen_feedkey();
			update_usermeta($userdata->ID, 'feed_key', $feedkey);
		}
	}
	
	if (empty($userloggedin) && $members_only_opt['feed_access'] != 'feednone')  //Check if user is logged in or Feed Keys is required
	{
		$feedkey = $_GET['feedkey'];
		
		if (!empty($feedkey))
		{
			// Check if Feed Key is in the Database
			$find_feedkey = $wpdb->get_results("SELECT umeta_id FROM $wpdb->usermeta WHERE meta_value = '$feedkey'");
			
			if (!empty($find_feedkey) && $members_only_opt['feed_access'] == 'feedkeys') //If Feed Key is found and using Feed Keys
			{
				$feedkey_valid = TRUE;
			}
		}
		
		//WordPress Feed Files
		switch (basename($_SERVER['PHP_SELF'])) 
		{
			case 'wp-rss.php':
			case 'wp-rss2.php':
			case 'wp-atom.php':
			case 'wp-rdf.php':
			case 'wp-commentsrss2.php':
			case 'wp-feed.php':
				if (empty($feedkey) && $members_only_opt['feed_access'] == 'feedkeys')
				{
					$feed = members_only_create_feed('No Feed Key Found', $errormsg['feedkey_missing']);
					header("Content-Type: application/xml; charset=ISO-8859-1");
					echo $feed;
					exit;
				}
				elseif ($feedkey_valid == FALSE && $members_only_opt['feed_access'] == 'feedkeys')
				{
					$feed = members_only_create_feed('Feed Key is Invalid', $errormsg['feedkey_invalid']);
					header("Content-Type: application/xml; charset=ISO-8859-1");
					echo $feed;
					exit;
				}
				elseif ($feed_redirected == FALSE && $members_only_opt['feed_access'] != 'feedkeys') //Not Using Feed Keys
				{
					members_only_redirect($redirection);
					$feed_redirected = TRUE;
				}
				break;
		}
	
		//WordPress Feed Queries
		switch ($_GET['feed'])
		{
			case 'rss':
			case 'rss2':
			case 'atom':
			case 'rdf':
				if (empty($feedkey) && $members_only_opt['feed_access'] == 'feedkeys')
				{
					$feed = members_only_create_feed('No Feed Key Found', $errormsg['feedkey_missing']);
					header("Content-Type: application/xml; charset=ISO-8859-1");
					echo $feed;
					exit;
				}
				elseif ($feedkey_valid == FALSE && $members_only_opt['feed_access'] == 'feedkeys')
				{
					$feed = members_only_create_feed('Feed Key is Invalid', $errormsg['feedkey_invalid']);
					header("Content-Type: application/xml; charset=ISO-8859-1");
					echo $feed;
					exit;
				}
				elseif ($feed_redirected == FALSE && $members_only_opt['feed_access'] != 'feedkeys') //Not Using Feed Keys
				{
					members_only_redirect($redirection);
					$feed_redirected = TRUE;
				}
				break;
		}
	}
}

//----------------------------------------------------------------------------
//	Create Redirect Function
//----------------------------------------------------------------------------

function members_only_create_redirect()
{
	global $members_only_opt, $members_only_reqpage, $blogurl, $wpurl;
	
	//Check redirection settings
	//If redirecting to login page or specified page is blank
	if ($members_only_opt['redirect_to'] == 'login')	
	{
		$output = "/wp-login.php";
		
		if ($members_only_opt['redirect'] == TRUE) //If redirecting to original page after logging in
		{
			$output .= "?redirect_to=";
			$output .= $members_only_reqpage;
		}
		
		$output = $wpurl.$output;
	}
	elseif ($members_only_opt['redirect_to'] == 'specifypage') //If redirecting to specific page
	{
		$output = '/'.$members_only_opt['redirect_url'];
		$output = $blogurl.$output;
	}

	return $output;
}

//----------------------------------------------------------------------------
//	Redirect Function
//----------------------------------------------------------------------------

function members_only_redirect($redirection)
{
	//Redirect Page
	if (function_exists('status_header')) status_header( 302 );
	header("HTTP/1.1 302 Temporary Redirect");
	header("Location:".$redirection);
	exit();
}

//----------------------------------------------------------------------------
//	Generate Feed Key Function
//----------------------------------------------------------------------------

function members_only_gen_feedkey()
{
	global $userdata;
	
	$charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"; //Key Character Set
	$keylength = 32; //Key Length

	for ($i=0; $i<$keylength; $i++) 
	{
		$key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
	}
	
	//Hash key against user login to make sure no two users can ever have the same key
	$hashedkey = md5($userdata->user_login.$key);
	
	return $hashedkey;
}

//----------------------------------------------------------------------------
//	Reset Feed Key Function
//----------------------------------------------------------------------------

function members_only_reset_feedkey()
{	
	$id = $_POST['user_id'];
	
	if ($_POST['feedkey-reset'] != NULL || $_POST['feedkey-generate'] != NULL || $_POST['feedkey-reset-admin'] == 'feedkey-reset') //If the reset or generate check box is checked
	{
		$feedkey = members_only_gen_feedkey();
		update_usermeta($id, 'feed_key', $feedkey);
	}
	
	if ($_POST['feedkey-reset-admin'] == 'feedkey-remove')
	{
		$feedkey = NULL;
		update_usermeta($id, 'feed_key', $feedkey);
	}
}

//----------------------------------------------------------------------------
//	Create RSS Feed Function
//----------------------------------------------------------------------------

function members_only_create_feed($item_title, $item_description)
{	
	global $blogtitle, $blogurl;
	
	$today = date('F j, Y G:i:s T');
	
	$feed_content = '<?xml version="1.0" encoding="ISO-8859-1" ?> 
					<rss version="2.0"> 
						<channel> 
							<title>'.$blogtitle.'</title>
							<link>'.$blogurl.'</link>
							<item>
								<title>'.$item_title.'</title>
								<link>'.$blogurl.'</link>
								<description>'.$item_description.'</description>
								<pubDate>'.$today.'</pubDate>
							</item>
						</channel>
					</rss>';
					
	return $feed_content;
}

//----------------------------------------------------------------------------
//	Login Redirect Function
//----------------------------------------------------------------------------

function members_only_login_redirect() {
	global $redirect_to, $members_only_opt;
	
	if (!isset($_GET['redirect_to']) && $members_only_opt['login_redirect_to'] == 'frontpage') 
	{
		$redirect_to = get_option('siteurl');
	}
}



//----------------------------------------------------------------------------
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function members_only_options_page()
{
	global $wpdb, $wpversion;

	if (isset($_POST['submit']) ) {
	
		if ($_POST['one_time_view_ip'] == 1)
		{
			
			$one_time_view_ip = md5($_SERVER['REMOTE_ADDR']);
		}
		else
		{
			$one_time_view_ip = NULL;
		}
		
	// Options Array Update
	$optionarray_update = array (
		'members_only' => $_POST['members_only'],
		'protect' => $_POST['protect'],
		'default_post_protect' => $_POST['default_post_protect'],
		'default_page_protect' => $_POST['default_page_protect'],
		'redirect_to' => $_POST['redirect_to'],
		'login_redirect_to' => $_POST['login_redirect_to'],
		'redirect_url' => $_POST['redirect_url'],
		'redirect' => $_POST['redirect'],
		'feed_access' => $_POST['feed_access'],
		'feedkey_reset' => $_POST['feedkey_reset'],
		'require_feedkeys' => $_POST['require_feedkeys'],
		'one_time_view_ip' => $one_time_view_ip
	);
	
	update_option('members_only_options', $optionarray_update);
	}
	
	// Get Options
	$optionarray_def = get_option('members_only_options');
	
	// Setup Protection Options
	$protecttypes = array(
	'Whole Site' => 'all',
	'Individual Posts & Pages' => 'pagepost'
	);
	
	foreach ($protecttypes as $option => $value) {
		if ($value == $optionarray_def['protect']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$protectoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	// Setup Default Protection Options
	$defaultprotecttypes = array(
	'Anyone' => 'anyone',
	'Members Only' => 'members-only'
	);
	
	foreach ($defaultprotecttypes as $option => $value) {
		if ($value == $optionarray_def['default_post_protect']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$default_postprotectoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	foreach ($defaultprotecttypes as $option => $value) {
		if ($value == $optionarray_def['default_page_protect']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$default_pageprotectoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	// Setup Redirection Options
	$redirecttypes = array(
	'Login Page' => 'login',
	'Specific Page' => 'specifypage'
	);
	
	foreach ($redirecttypes as $option => $value) {
		if ($value == $optionarray_def['redirect_to']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$redirectoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	// Setup Login Redirection Options
	$loginredirecttypes = array(
	'Dashboard' => 'dashboard',
	'Front Page' => 'frontpage'
	);
	
	foreach ($loginredirecttypes as $option => $value) {
		if ($value == $optionarray_def['login_redirect_to']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$login_redirectoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}
	
	// Setup Feed Access Options
	$feedaccesstypes = array(
	'Use Feed Keys' => 'feedkeys',
	'Require User Login' => 'feedlogin',
	'Open Feeds' => 'feednone'
	);
	
	foreach ($feedaccesstypes as $option => $value) {
		if ($value == $optionarray_def['feed_access']) {
				$selected = 'selected="selected"';
		} else {
				$selected = '';
		}
		
		$feedprotectionoptions .= "\n\t<option value='$value' $selected>$option</option>";
	}

?>
	<div class="wrap">
	<h2>Members Only Options</h2>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
	<fieldset class="options" style="border: none">
	<h3>Blog Access Options</h3>
	<p>
	Checking the <em>Members Only</em> option below will make your blog only viewable to users that are logged in. If a visitor is not logged in, 
	they will be redirected to the WordPress login page or a page that you can specify. Once logged in they can be redirected back to the page that they originally requested if you choose to.
	</p>
	<table width="100%" <?php $wpversion >= 2.5 ? _e('class="form-table"') : _e('cellspacing="2" cellpadding="5" class="editform"'); ?> >
		<tr valign="top">
			<th width="200px" scope="row">Members Only?</th>
			<td width="100px"><input name="members_only" type="checkbox" id="members_only_inp" value="1" <?php checked('1', $optionarray_def['members_only']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Choose between making your blog only accessable to users that are logged in</span></td>
		</tr>
		<tr valign="top">
			<th scope="row">Protect</th>
			<td><select name="protect" id="protect_inp"><?php echo $protectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose how the site is protected by Members Only</span></td>
		</tr>
		<?php if ($optionarray_def['protect'] == 'pagepost') :?>
		<tr valign="top">
			<th scope="row">Default Post Protection</th>
			<td><select name="default_post_protect" id="default_post_protect_inp"><?php echo $default_postprotectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Default Protection Option for Posts</span></td>
		</tr>
		<tr valign="top">
			<th scope="row">Default Page Protection</th>
			<td><select name="default_page_protect" id="default_page_protect_inp"><?php echo $default_pageprotectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Default Protection Option for Pages</span></td>
		</tr>
		<?php endif; ?>
		<?php if ($optionarray_def['protect'] == 'all') :?>
		<tr valign="top">
			<th scope="row">XML RPC Access</th>
			<td width="100px"><input name="one_time_view_ip" type="checkbox" id="one_time_view_ip_inp" value="1" <?php checked('1', $optionarray_def['one_time_view_ip']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Allow a one-time view from <strong><span style="font-size: 1.2em;"><?php echo $_SERVER['REMOTE_ADDR'];?></span></strong>, to add your blog to an XML RPC application <em>(such a WordPress for iPhone)</em></span></span>
			</td>
		</tr>
		<?php endif; ?>
	</table>
	<h3>Feed Access Options</h3>
	<p>
	<em>Members Only</em> can also protect your blog's feeds either by requiring a user to be logged in, or using <em>Feed Keys</em>. <em>Feed Keys</em> are unique 32bit keys that are created for every user on your site. This allows each user on your site to access your feeds using their own unique URL, so you can protect your feeds whilst still allowing your users to use other methods, such as feed readers, to access your feeds. Your users can also find their <em>Feed Key</em> in their profile page, and you can allow them to reset their <em>Feed Keys</em> if you choose.
	</p>
	<table width="100%" <?php $wpversion >= 2.5 ? _e('class="form-table"') : _e('cellspacing="2" cellpadding="5" class="editform"'); ?> >
		<tr valign="top">
			<th width="200"px" scope="row">Feed Access</th>
			<td width="100px"><select name="feed_access" id="feed_access_inp"><?php echo $feedprotectionoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose if Feeds are accessable, by using Feed Keys, User Login or Open Feeds to anyone.<br /></span></td>
		</tr>
		<?php if ($optionarray_def['feed_access'] == 'feedkeys') :?>
		<tr valign="top">
			<th scope="row">Require Feed Keys</th>
			<td><input name="require_feedkeys" type="checkbox" id="require_feedkeys_inp" value="1" <?php checked('1', $optionarray_def['require_feedkeys']); ?>  /></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether to always use Feed Keys even if user is logged in.</span></td>
		</tr>
		<tr valign="top">
			<th scope="row">User Reset</th>
			<td><input name="feedkey_reset" type="checkbox" id="feedkey_reset_inp" value="1" <?php checked('1', $optionarray_def['feedkey_reset']); ?> /></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether users can reset their own Feed Keys.</span></td>
		</tr>
		<?php endif; ?>
	</table>
	<h3>Redirection Options</h3>
	<table width="100%" <?php $wpversion >= 2.5 ? _e('class="form-table"') : _e('cellspacing="2" cellpadding="5" class="editform"'); ?> >
		<tr valign="top">
			<th scope="row">Redirect To</th>
			<td><select name="redirect_to" id="redirect_to_inp"><?php echo $redirectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose where a user that isn't logged in is redirected to</span></td>
		</tr>
		<?php if ($optionarray_def['redirect_to'] == 'login') :?>
		<tr valign="top">
			<th width="200px" scope="row">Return User</th>
			<td width="100px"><input name="redirect" type="checkbox" id="redirect_inp" value="1" <?php checked('1', $optionarray_def['redirect']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether once logged in, the user returns to the originally requested page.</span></td>
		</tr>
		<?php elseif($optionarray_def['redirect_to'] == 'specifypage'): ?>
		<tr valign="top">
			<th scope="row">Redirection Page</th> 
			<td colspan="2"><?php bloginfo('url');?>/<input type="text" name="redirect_url" id="redirect_url_inp" value="<?php echo $optionarray_def['redirect_url']; ?>" size="35" /><br />
			<span style="color: #555; font-size: .85em;">If the field is left blank, users will able to access your front page without being logged in.</span>
			</td>
		</tr>
		<?php endif; ?>
		<tr valign="top">
			<th width="200px" scope="row">Login Redirect</th>
			<td width="100px"><select name="login_redirect_to" id="login_redirect_to_inp"><?php echo $login_redirectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose where the User is redirected to if they login directly from the login page.</span></td>
		</tr>
	</table>
	</fieldset>
	<p />
	<div class="submit">
		<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
	</div>
	</form>
<?php
}

//----------------------------------------------------------------------------
//		WORDPRESS FILTERS AND ACTIONS
//----------------------------------------------------------------------------

add_action('admin_menu', 'members_only_add_options_page');
add_action('login_form', 'members_only_login_redirect');
		
if ($members_only_opt['members_only'] == TRUE) //Check if Members Only is Active
{
	add_action('template_redirect', 'members_only');
	add_action('init', 'members_only_init');
	add_action('show_user_profile', 'members_only_display_feedkey');
	add_action('edit_user_profile', 'members_only_display_feedkey');
	add_action('profile_update', 'members_only_reset_feedkey');
	
	if ($members_only_opt['protect'] == 'pagepost')
	{
		add_action('admin_menu', 'members_only_meta_boxes');
		add_action('save_post', 'members_only_save_meta');
		add_filter('manage_posts_columns', array('members_only_manage_columns', 'members_only_add_manage_column'));
		add_action('manage_posts_custom_column', array('members_only_manage_columns', 'members_only_fill_manage_column'), 10, 2);
		add_filter('manage_pages_columns', array('members_only_manage_columns', 'members_only_add_manage_column'));
		add_action('manage_pages_custom_column', array('members_only_manage_columns', 'members_only_fill_manage_column'), 10, 2);
	}
}

?>