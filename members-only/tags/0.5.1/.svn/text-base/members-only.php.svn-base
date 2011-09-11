<?php
/* 
Plugin Name: Members Only
Plugin URI:  http://code.andrewhamilton.net/wordpress/plugins/members-only/
Description: A simple plugin that allows you to make your WordPress blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected either to the WordPress login page or a page of your choice. Once logged in they can be redirected back to the page that they originally requested.
Version: 0.5.1
Author: Andrew Hamilton
Author URI: http://andrewhamilton.net
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

//----------------------------------------------------------------------------
//		GLOBAL VARIABLES
//----------------------------------------------------------------------------

$members_only_opt = get_option('members_only_options'); //Members Only Options
$members_only_reqpage = $_SERVER["REQUEST_URI"]; //The page that was originally requested

//Get the current URL
$currenturl = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS
//----------------------------------------------------------------------------


function members_only_add_options_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page('Members Only', 'Members Only', 8, basename(__FILE__), 'members_only_options_page');
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
	global $currenturl, $members_only_opt, $userdata;
	
	//Get Redirect
	$redirection = members_only_createredirect();
		
	if ($userdata->ID == '' && $members_only_opt['members_only'] == TRUE) //Check if user is logged in and blog is Members Only
	{		
		// Check if whether we are...
		if (is_feed() && $members_only_opt['feed_access'] == TRUE || //...trying to get to a feed and if they are accessable
			$currenturl == $redirection || //...at the redirection page without a trailing slash 
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
}

//----------------------------------------------------------------------------
//	Init Function
//----------------------------------------------------------------------------

function members_only_init()
{
	global $userdata, $currenturl, $userdata, $members_only_opt;
	
	//Get Redirect
	$redirection = members_only_createredirect();
	
	//Parse URL
	$parsed_url = parse_url($currenturl);
	
	//Check if user is logged in and feeds are accessable
	if ($userdata->ID == '' && $members_only_opt['feed_access'] == FALSE)
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
				members_only_redirect($redirection);
				break;
		}
		
		//WordPress Feed Queries
		switch ($parsed_url['query'])
		{
			case 'feed=rss':
			case 'feed=rss2':
			case 'feed=atom':
			case 'feed=rdf':
				members_only_redirect($redirection);
				break;
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
//		ADMIN OPTION PAGE FUNCTIONS
//----------------------------------------------------------------------------

function members_only_options_page()
{
	global $wpdb;

	// Setup Default Options Array
		$optionarray_def = array(
			'members_only' => FALSE,
			'redirect_to' => 'login',
			'redirect_url' => '',
			'redirect' => TRUE,
			'feed_access' => FALSE
		);
		add_option('members_only_options', $optionarray_def, 'Members Only Wordpress Plugin Options');

		if (isset($_POST['submit']) ) {
			
	// Options Array Update
		$optionarray_update = array (
			'members_only' => $_POST['members_only'],
			'redirect_to' => $_POST['redirect_to'],
			'redirect_url' => $_POST['redirect_url'],
			'redirect' => $_POST['redirect'],
			'feed_access' => $_POST['feed_access']
		);
		
		update_option('members_only_options', $optionarray_update);
		}
		
	// Get Options
		$optionarray_def = get_option('members_only_options');
		
	// Setup Redirection Options
		$redirecttypes = array(
		'Login Page' => 'login',
		'Specify Page' => 'specifypage'
		);
		
		foreach ($redirecttypes as $option => $value) {
			if ($value == $optionarray_def['redirect_to']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$redirectoptions .= "\n\t<option value='$value' $selected>$option</option>";
		}

?>
	<div class="wrap">
	<h2>Members Only Options</h2>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
	<fieldset class="options" style="border: none">
	<p>
	Checking the <em>Members Only</em> option below will make your blog only viewable to users that are logged in. If a visitor is not logged in, 
	they will be redirected to the WordPress login page or a page that you can specify. Once logged in they can be redirected back to the page that they originally requested if you choose to.
	</p>
	<table width="100%" class="form-table">
		<tr valign="top">
			<th width="350px" scope="row">Members Only? </th>
			<td width="100px"><input name="members_only" type="checkbox" id="members_only_inp" value="1" <?php checked('1', $optionarray_def['members_only']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Toggle between making your blog only accessable to users that are logged in</span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">Redirect To </th>
			<td width="100px"><select name="redirect_to" id="redirect_to_inp"><?php echo $redirectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose where a user that isn't logged in is redirected to</span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">Return To Requested Page </th>
			<td width="100px"><input name="redirect" type="checkbox" id="redirect_inp" value="1" <?php checked('1', $optionarray_def['redirect']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Once logged in, you can return the user to the originally requested page <br /><em>(Only applies if your redirecting to the login page)</em></span></td>
		</tr>
		<tr valign="top">
			<th width="350px" scope="row">RSS Feeds Accessable</th>
			<td width="100px"><input name="feed_access" type="checkbox" id="feed_access_inp" value="1" <?php checked('1', $optionarray_def['feed_access']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Allow access to feeds, even if user is not logged in<br /><em>(This allows your users to access feeds from a feed reader)</em></span></td>
		</tr>
	</table>
	<p>
	If you have choosen to redirect to a specific page other than the login page, please enter it below. 
	<br /><span style="color: #555; font-size: .85em;"><em>(If the field is left blank, users will be redirected to the login page instead).</em></span></p>
	<table width="100%" class="form-table">
		<tr valign="top">
			<th width="350px" scope="row">Redirection Page</th> 
			<td><?php bloginfo('url');?>/<input type="text" name="redirect_url" id="redirect_url_inp" value="<?php echo $optionarray_def['redirect_url']; ?>" size="35" /></td>
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

add_action('template_redirect', 'members_only');
add_action('init', 'members_only_init');
add_action('admin_menu', 'members_only_add_options_page');

?>