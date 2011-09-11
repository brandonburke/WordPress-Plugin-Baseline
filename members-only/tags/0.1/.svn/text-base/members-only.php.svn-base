<?php
/* 
Plugin Name: Members Only
Plugin URI:  http://labs.saruken.com/
Description: A simple plugin that allows you to make your WordPress blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected to the WordPress login page. Once logged in they will be redirected back to the page that they originally requested.
Version: 0.1
Author: Andrew Hamilton 
Author URI: http://andrewhamilton.net
Licensed under the The GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/ 

//----------------------------------------------------------------------------
//		SETUP FUNCTIONS
//----------------------------------------------------------------------------

$members_only_opt = get_option('members_only_options');

function members_only_add_options_page() {
    if (function_exists('add_options_page')) {
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
	global $userdata;
	
	$home = get_bloginfo('url');
	$requested_page = $_SERVER["REQUEST_URI"]; //Get the page that was originally requested
	$login_redirect = "/wp-login.php?redirect_to=";
	
	if ($userdata->ID == '') //Check if user is logged in
	{ 
	
		//Check we aren't already at the login page
		if (preg_match("/wp-login.php/i", $_SERVER["REQUEST_URI"]))
		{
			//Do Nothing
		}
		else
		{
			//Create Redirection URL
			$redirection = $home.$login_redirect.$requested_page;
			
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
			'members_only' => FALSE
		);
		add_option('members_only_options', $optionarray_def, 'Members Only Wordpress Plugin Options');

		if (isset($_POST['submit']) ) {
			
	// Options Array Update
		$optionarray_update = array (
			'members_only' => $_POST['members_only']
		);
		
		update_option('members_only_options', $optionarray_update);
		}
		
	// Get Options
		$optionarray_def = get_option('members_only_options');

?>
	<div class="wrap">
	<h2>Members Only Options</h2>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
	<fieldset class="options">
	<p>
	Checking the option below will make your blog only viewable to users that are logged in. If a visitor is not logged in, they will be redirected to the WordPress login page.
	Once logged in they will be redirected back to the page that they originally requested.
	</p>
	<table width="100%" cellspacing="2" cellpadding="5" class="editform">
		<tr valign="center"> 
			<th width="150px" scope="row">Members Only? </th> 
			<td width="15px"><input name="members_only" type="checkbox" id="members_only_inp" value="1" <?php checked('1', $optionarray_def['members_only']); ?>"  /></td>
			<td><span style="color: #555; font-size: .85em;">Toggle between making your blog only accessable to users that are logged in</span></td> 
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