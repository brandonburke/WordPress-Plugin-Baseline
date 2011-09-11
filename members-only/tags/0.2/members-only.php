<?php
/* 
Plugin Name: Members Only
Plugin URI:  http://labs.saruken.com/
Description: A simple plugin that allows you to make your WordPress blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected either to the WordPress login page or a page of your choice. Once logged in they can be redirected back to the page that they originally requested.
Version: 0.2
Author: Andrew Hamilton 
Author URI: http://andrewhamilton.net
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS
//----------------------------------------------------------------------------

$members_only_opt = get_option('members_only_options');

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
	global $userdata, $members_only_opt;
	
	$home = get_bloginfo('url'); //Get base URL or WordPress install
	$requested_page = $_SERVER["REQUEST_URI"]; //Get the page that was originally requested
	$currenturl = sprintf('http%s://%s%s',(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': ''), $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']); //Get the current URL
	
	//Check redirection settings
	if ($members_only_opt['redirect_to'] == 'login' || $members_only_opt['redirect_to'] == 'specifypage' && $members_only_opt['redirect_url'] == '') //If redirecting to login page or specified page is blank
	{
		$redirect = "/wp-login.php";
		if ($members_only_opt['redirect'] == TRUE) //If redirecting to original page after logging in
		{
			$redirect .= "?redirect_to=";
			$redirect .= $requested_page;
		}
	} 
	elseif ($members_only_opt['redirect_to'] == 'specifypage' && $members_only_opt['redirect_url'] != '') //If redirecting to specific page
	{
		$redirect = '/'.$members_only_opt['redirect_url'];
	}
	
	//Create Redirection URL
	$redirection = $home.$redirect;
		
	if ($userdata->ID == '') //Check if user is logged in
	{ 
	
		//Check we aren't already at the page we're redirecting to (with and without the trailing slash) or wanting to go to the login page
		if ($currenturl == $redirection || $currenturl == $redirection.'/' || preg_match("/wp-login.php/i", $_SERVER["REQUEST_URI"]))
		{
			//Do Nothing
		}
		else
		{
			//Redirect Page	
			wp_redirect($redirection);
		}
		
	} 
	else
	{
		//Do Nothing	
	}

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
			'redirect' => TRUE
		);
		add_option('members_only_options', $optionarray_def, 'Members Only Wordpress Plugin Options');

		if (isset($_POST['submit']) ) {
			
	// Options Array Update
		$optionarray_update = array (
			'members_only' => $_POST['members_only'],
			'redirect_to' => $_POST['redirect_to'],
			'redirect_url' => $_POST['redirect_url'],
			'redirect' => $_POST['redirect']
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
	<fieldset class="options">
	<p>
	Checking the <em>Members Only</em> option below will make your blog only viewable to users that are logged in. If a visitor is not logged in, 
	they will be redirected to the WordPress login page or a page that you can specify. Once logged in they can be redirected back to the page that they originally requested if you choose to.
	</p>
	<table width="100%" cellspacing="2" cellpadding="5" class="editform">
		<tr valign="center"> 
			<th width="200px" scope="row">Members Only? </th> 
			<td width="15px"><input name="members_only" type="checkbox" id="members_only_inp" value="1" <?php checked('1', $optionarray_def['members_only']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Toggle between making your blog only accessable to users that are logged in</span></td> 
		</tr>
		<tr valign="center"> 
			<th width="200px" scope="row">Redirect To </th> 
			<td width="15px"><select name="redirect_to" id="redirect_to_inp"><?php echo $redirectoptions ?></select></td>
			<td><span style="color: #555; font-size: .85em;">Choose where a user that isn't logged in is redirected to</span></td> 
		</tr>
		<tr valign="center"> 
			<th width="200px" scope="row">Return To Requested Page </th> 
			<td width="15px"><input name="redirect" type="checkbox" id="redirect_inp" value="1" <?php checked('1', $optionarray_def['redirect']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Once logged in, you can return the user to the originally requested page <br /><em>(Only applies if your redirecting to the login page)</em></span></td> 
		</tr>
	</table>
	<p>
	If you have choosen to redirect to a specific page other than the login page, please enter it below. 
	<br /><span style="color: #555; font-size: .85em;"><em>(If the field is left blank, users will be redirected to the login page instead).</em></span></p>
	<table width="100%" cellspacing="2" cellpadding="5" class="editform">
		<tr valign="center"> 
			<th width="200px" scope="row">Redirection Page</th> 
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

add_action('admin_menu', 'members_only_add_options_page');

if ($members_only_opt['members_only'] == TRUE)
{
	add_action('wp_head', 'members_only');
}

?>