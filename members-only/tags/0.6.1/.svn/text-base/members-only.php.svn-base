<?php
/* 
Plugin Name: Members Only
Plugin URI:  http://code.andrewhamilton.net/wordpress/plugins/members-only/
Description: A plugin that allows you to make your WordPress blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected either to the WordPress login page or a page of your choice. Once logged in they can be redirected back to the page that they originally requested. You can also protect your Feeds whilst allowing registered user access to them by using <em>Feed Keys</em>.
Version: 0.6.1
Author: Andrew Hamilton
Author URI: http://andrewhamilton.net
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS & GLOBAL VARIABLES
//----------------------------------------------------------------------------

register_activation_hook(__FILE__,'members_only_setup_options');

//Members Only Options
$members_only_opt = get_option('members_only_options');

//Get the page that was originally requested by the user
$members_only_reqpage = $_SERVER["REQUEST_URI"];

//Setup Feedkey Variables
$feedkey_valid = FALSE;
$feed_redirected = FALSE;

//Get the current URL
$currenturl = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

//----------------------------------------------------------------------------
//	Error Messages
//----------------------------------------------------------------------------

$errormsg = array(
	'feedkey_invalid' => 'The Feed Key you used is invalid. It is either incorrect or has been revoked. Please <a href="'.get_bloginfo('url').'/wp-login.php">login</a> to obtain a valid Feed Key',
	'feedkey_missing' => 'You need to use a Feed Key to access feeds on this site. Please <a href="'.get_bloginfo('url').'/wp-login.php">login</a> to obtain yours.',
	'feedkey_notgen' => 'Feed Key has not been generated yet',
	'feedurl_notgen' => 'URL is available once Feed Key has been generated'
	
	);

//----------------------------------------------------------------------------
//	Setup Default Settings
//----------------------------------------------------------------------------

function members_only_setup_options()
{
	global $members_only_opt;
	
	$members_only_version = get_option('members_only_version'); //Members Only Version Number
	$members_only_this_version = '0.6.1';
	
	// Check the version of Members Only
	if (empty($members_only_version))
	{
		add_option('members_only_version', $members_only_this_version);
	} 
	elseif ($members_only_version != $members_only_this_version)
	{
		update_option('members_only_version', $members_only_this_version);
	}
	
	// Setup Default Options Array
	$optionarray_def = array(
		'members_only' => FALSE,
		'redirect_to' => 'login',
		'redirect_url' => '',
		'redirect' => TRUE,
		'feed_access' => 'feedkey',
		'feedkey_reset' => TRUE,
		'require_feedkeys' => FALSE
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
	global $profileuser, $current_user, $members_only_opt, $errormsg;
	
	$yourprofile = $profileuser->ID == $current_user->ID;
	$feedkey = get_usermeta($profileuser->ID,'feed_key');
	$permalink_structure = get_option(permalink_structure);
	
	//Check if Permalinks are being used
	empty($permalink_structure) ? $feedjoin = '?feed=rss2&feedkey=' : $feedjoin = '/feed/?feedkey=';
	
	$feedurl = get_bloginfo('url').$feedjoin.$feedkey;
	$feedurl = '<a href="'.$feedurl.'">'.$feedurl.'</a>';

	if ($members_only_opt ['feed_access'] == 'feedkey') //Check if Feed Keys are being used
	{
		?>
		<table class="form-table">
			<h3><?php echo $yourprofile ? _e("Your Feed Key", 'feed-key') : _e("User's Feed Key", 'feed-key') ?></h3>
			<tr>
				<th><label for="feedkey">Feed Key</label></th>
				<td width="250px"><?php echo empty($feedkey) ? _e($errormsg['feedkey_notgen']) : _e($feedkey); ?></td>
				<td>
				<?php if ($current_user->has_cap('level_9') || $members_only_opt ['feedkey_reset'] == TRUE) { ?>
				<input name="feedkey-reset" type="checkbox" id="feedkey-reset_inp" value="0" /><?php echo empty($feedkey) ? _e(" Generate Key") : _e(" Reset Key"); ?>
				<?php } ?>
				</td>
			</tr>
			<tr>
				<th><label for="feedkey">Your Feed URL</label></th>
				<td colspan="2"><?php echo empty($feedkey) ? _e($errormsg['feedurl_notgen']) : _e($feedurl); ?></td>
			</tr>
		</table>
		<?php
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
	global $currenturl, $members_only_opt, $feedkey_valid, $errormsg, $userdata;
	
	//Get Blog Information
	$blogurl = get_bloginfo('url');
	$blogtitle = get_bloginfo('title');
	
	//Get Redirect
	$redirection = members_only_createredirect();
		
	if (empty($userdata->ID)) //Check if user is logged in and blog is Members Only
	{		
		
		if (is_feed()) //Check if URL is a Feed
		{
			if (empty($_GET['feedkey']))
			{
				$feed = members_only_create_feed($blogtitle, 'No Feed Key Found', $blogurl, $errormsg['feedkey_missing']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == FALSE) 
			{
				$feed = members_only_create_feed($blogtitle, 'Feed Key is Invalid', $blogurl, $errormsg['feedkey_invalid']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == TRUE || $members_only_opt['feed_access'] == 'feednone')
			{
				// Do Nothing
			}	
		}
		
		// Check if whether we are...
		if ($currenturl == $redirection || //...at the redirection page without a trailing slash 
			$currenturl == $redirection.'/' //...at the redirection page with a trailing slash
			) 		
		{
			// Do Nothing
		}
		else 
		{
			//Redirect Page
			members_only_redirect($redirection);
		}		
	}
	else //User is logged in
	{
		if (is_feed() && $members_only_opt['require_feedkeys'] == TRUE) //If site requires Feed Keys for logged in users
		{
			if (empty($_GET['feedkey']))
			{
				$feed = members_only_create_feed($blogtitle, 'No Feed Key Found', $blogurl, $errormsg['feedkey_missing']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == FALSE) 
			{
				$feed = members_only_create_feed($blogtitle, 'Feed Key is Invalid', $blogurl, $errormsg['feedkey_invalid']);
				header("Content-Type: application/xml; charset=ISO-8859-1");
				echo $feed;
				exit;
			}
			elseif ($feedkey_valid == TRUE || $members_only_opt['feed_access'] == 'feednone')
			{
				// Do Nothing
			} 
		}
	}
}

//----------------------------------------------------------------------------
//	Init Function
//----------------------------------------------------------------------------

function members_only_init()
{
	global $userdata, $currenturl, $feedkey_valid, $feed_redirected, $errormsg, $members_only_opt, $wpdb;
	
	//Get Redirect
	$redirection = members_only_createredirect();
	
	//Parse URL
	$parsed_url = parse_url($currenturl);
	
	//Check if user is logged in and if feeds are accessable
	if (empty($userdata->ID) && $members_only_opt['feed_access'] != 'feednone')
	{
		$feedkey = $_GET['feedkey'];
		
		if (!empty($feedkey))
		{
			$feedkey_found = $wpdb->get_results("SELECT umeta_id FROM wp_usermeta WHERE meta_value = '$feedkey'");
		}
		
		
		if (empty($feedkey) || empty($feedkey_found))
		{
			//WordPress Feed Files
			switch (basename($_SERVER['PHP_SELF'])) 
			{
				case 'wp-rss.php':
				case 'wp-rss2.php':
				case 'wp-atom.php':
				case 'wp-rdf.php':
				case 'wp-commentsrss2.php':
				case 'wp-feed.php':
					if (empty($feedkey) && $feed_redirected == FALSE)
					{
						members_only_redirect($redirection);
						$feed_redirected = TRUE;
					}
					else
					{
						//Bring up WordPress Error Page
						wp_die( __($errormsg['feedkey_invalid']));
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
					if (empty($feedkey) && $feed_redirected == FALSE)
					{
						members_only_redirect($redirection);
						$feed_redirected = TRUE;
					}
					else
					{
						//Bring up WordPress Error Page
						wp_die( __($errormsg['feedkey_invalid']));	
					}
					break;
			}
		}
		else
		{
			$feedkey_valid = TRUE;
		}
	}
	else //If User is logged in
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
}

//----------------------------------------------------------------------------
//	Create Redirect Function
//----------------------------------------------------------------------------

function members_only_createredirect()
{
	global $members_only_opt, $members_only_reqpage;
	
	$home = get_bloginfo('url'); //Get base URL or WordPress install
	
	//Check redirection settings
	//If redirecting to login page or specified page is blank
	if ($members_only_opt['redirect_to'] == 'login' || $members_only_opt['redirect_to'] == 'specifypage' && $members_only_opt['redirect_url'] == '')	
	{
		$output = "/wp-login.php";
		if ($members_only_opt['redirect'] == TRUE) //If redirecting to original page after logging in
		{
			$output .= "?redirect_to=";
			$output .= $members_only_reqpage;
		}
	}
	elseif ($members_only_opt['redirect_to'] == 'specifypage' && $members_only_opt['redirect_url'] != '') //If redirecting to specific page
	{
		$output = '/'.$members_only_opt['redirect_url'];
	}
	
	//Create Redirection URL
	$output = $home.$output;
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

	if ($_POST['feedkey-reset'] == 0) //If the reset check box is checked
	{
		$feedkey = members_only_gen_feedkey();
		update_usermeta($id, 'feed_key', $feedkey);
	}
}

//----------------------------------------------------------------------------
//	Create RSS Feed Function
//----------------------------------------------------------------------------

function members_only_create_feed($blog_title, $item_title, $item_link, $item_description)
{	
	$today = date('F j, Y G:i:s T');
	
	$feed_content = '<?xml version="1.0" encoding="ISO-8859-1" ?> 
					<rss version="2.0"> 
						<channel> 
							<title>'.$blog_title.'</title>
							<link>'.$item_link.'</link>
							<item>
								<title>'.$item_title.'</title>
								<link>'.$item_link.'</link>
								<description>'.$item_description.'</description>
								<pubDate>'.$today.'</pubDate>
							</item>
						</channel>
					</rss>';
					
	return $feed_content;
}
	

//----------------------------------------------------------------------------
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function members_only_options_page()
{
	global $wpdb;

	if (isset($_POST['submit']) ) {
		
	// Options Array Update
	$optionarray_update = array (
		'members_only' => $_POST['members_only'],
		'redirect_to' => $_POST['redirect_to'],
		'redirect_url' => $_POST['redirect_url'],
		'redirect' => $_POST['redirect'],
		'feed_access' => $_POST['feed_access'],
		'feedkey_reset' => $_POST['feedkey_reset'],
		'require_feedkeys' => $_POST['require_feedkeys']
	);
	
	update_option('members_only_options', $optionarray_update);
	}
	
	// Get Options
	$optionarray_def = get_option('members_only_options');
	
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
	
	// Setup Feed Access Options
	$feedaccesstypes = array(
	'Use Feed Keys' => 'feedkey',
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
	<p>
	Checking the <em>Members Only</em> option below will make your blog only viewable to users that are logged in. If a visitor is not logged in, 
	they will be redirected to the WordPress login page or a page that you can specify. Once logged in they can be redirected back to the page that they originally requested if you choose to.
	<table width="100%" class="form-table">
		<tr valign="top">
			<th width="350px" scope="row">Members Only?</th>
			<td width="100px"><input name="members_only" type="checkbox" id="members_only_inp" value="1" <?php checked('1', $optionarray_def['members_only']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Choose between making your blog only accessable to users that are logged in</span></td>
		</tr>
	</table>
	</p>
	<h3>Blog Access Options</h3>
	<table width="100%" class="form-table">
		<tr valign="top">
			<th width="350px" scope="row">Redirect To</th>
			<td width="100px"><select name="redirect_to" id="redirect_to_inp"><?php echo $redirectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose where a user that isn't logged in is redirected to</span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">Return User</th>
			<td width="100px"><input name="redirect" type="checkbox" id="redirect_inp" value="1" <?php checked('1', $optionarray_def['redirect']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether once logged in, the user returns to the originally requested page <em>(Only applies if your redirecting to the login page)</em></span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">Redirection Page</th> 
			<td colspan="2"><?php bloginfo('url');?>/<input type="text" name="redirect_url" id="redirect_url_inp" value="<?php echo $optionarray_def['redirect_url']; ?>" size="35" /><br />
			<span style="color: #555; font-size: .85em;">If the field is left blank, users will be redirected to the login page instead. 
			<em>(Only applies if your redirecting to the specific page)</em></span></span>
			</td>
		</tr>
	</table>
	<h3>Feed Access Options</h3>
	<em>Members Only</em> can also protect your blog's feeds either by requiring a user to be logged in, or using <em>Feed Keys</em>. <em>Feed Keys</em> are unique 32bit keys that are created for every user on your site. This allows each user on your site to access your feeds using their own unique URL, so you can protect your feeds whilst still allowing your users to use other methods, such as feed readers, to access your feeds. Your users can also find their <em>Feed Key</em> in their profile page, and you can allow them to reset their <em>Feed Keys</em> if you choose.
	<table width="100%" class="form-table">
		<tr valign="top">
			<th width="350px" scope="row">Feed Access</th>
			<td width="100px"><select name="feed_access" id="feed_access_inp"><?php echo $feedprotectionoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose if Feeds are accessable, by using Feed Keys, User Login or Open Feeds to anyone.<br /></span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">Require Feed Keys</th>
			<td width="100px"><input name="require_feedkeys" type="checkbox" id="require_feedkeys_inp" value="1" <?php checked('1', $optionarray_def['require_feedkeys']); ?>"  /></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether to always use Feed Keys even if user is logged in. <em>(Only applies if your using Feed Keys)</em></span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">User Reset</th>
			<td width="100px"><input name="feedkey_reset" type="checkbox" id="feedkey_reset_inp" value="1" <?php checked('1', $optionarray_def['feedkey_reset']); ?>"  /></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose whether users can reset their own Feed Keys. <em>(Only applies if your using Feed Keys)</em></span></td>
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

if ($members_only_opt['members_only'] == TRUE) //Check if Members Only is Active
{
	add_action('template_redirect', 'members_only');
	add_action('init', 'members_only_init');
	add_action('show_user_profile', 'members_only_display_feedkey');
	add_action('edit_user_profile', 'members_only_display_feedkey');
	add_action('profile_update', 'members_only_reset_feedkey');
}

?>