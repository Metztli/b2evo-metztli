<?php
/**
 * This file implements login/logout handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER - {@link http://www.jeffbearer.com/}.
 * @author jupiterx: Jordan RUNNING.
 *
 * @version $Id: _user.funcs.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_group.class.php', 'Group' );
load_class( 'users/model/_user.class.php', 'User' );


/**
 * Log the user out
 */
function logout()
{
	global $current_User, $Session, $Plugins;

	$Plugins->trigger_event( 'Logout', array( 'User' => $current_User ) );

	// Reset all global variables
	// Note: unset is bugguy on globals
	$current_User = NULL; // NULL, as we do isset() on it in several places!

	$Session->logout();
}


/**
 * is_logged_in(-)
 */
function is_logged_in()
{
	global $generating_static, $current_User;

	if( isset($generating_static) )
	{ // When generating static page, we should always consider we are not logged in.
		return false;
	}

	return is_object( $current_User ) && !empty( $current_User->ID );
}


/**
 * Check if a password is ok for a login.
 *
 * @param string login
 * @param string password
 * @param boolean Is the password parameter already MD5()'ed?
 * @return boolean
 */
function user_pass_ok( $login, $pass, $pass_is_md5 = false )
{
	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_login( $login );
	if( !$User )
	{
		return false;
	}
	// echo 'got data for: ', $User->login;

	return $User->check_password( $pass, $pass_is_md5 );
}


/**
 * Template tag: Output link to login
 */
function user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	if( is_logged_in() ) return false;

	if( $link_text == '' ) $link_text = T_('Log in');
	if( $link_title == '#' ) $link_title = T_('Log in if you have an account...');

	$r = $before;
	$r .= '<a href="'.get_login_url().'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;

	echo $r;
}


/**
 * Get url to login
 *
 * @return string
 */
function get_login_url( $redirect_to = NULL )
{
	global $htsrv_url_sensitive, $edited_Blog, $generating_static;

	if( !empty( $redirect_to ) )
	{
		$redirect = $redirect_to;
	}
	elseif( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = regenerate_url( '', '', '', '&' );
	}
	elseif( isset($edited_Blog) ) // fp> this is a shady test!! :/
	{ // We are generating a static page
		$redirect = $edited_Blog->get('url'); // was dynurl
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	if( use_in_skin_login() )
	{ // use in-skin login
		global $blog;

		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog );

		if( ! empty($redirect) )
		{
			$redirect = 'redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $Blog->get( 'loginurl' ) ) );
		}
		return url_add_param( $Blog->get( 'loginurl' ), $redirect );
	}

	// Normal login
	if( ! empty($redirect) )
	{
		$redirect = '?redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $htsrv_url_sensitive ) );
	}
	return $htsrv_url_sensitive.'login.php'.$redirect;
}


/**
 * Use in-skin login
 */
function use_in_skin_login()
{
	global $blog;

	if( is_admin_page() )
	{
		return false;
	}

	if( !isset( $blog ) )
	{
		return false;
	}

	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( empty( $Blog ) )
	{
		return false;
	}

	return $Blog->get_setting( 'in_skin_login' );
}


/**
 * Template tag: Output a link to new user registration
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 * @param string used for source tracking if $source is not already set
 */
function user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#', $disp_when_logged_in = false, $default_source_string = '' )
{
	echo get_user_register_link( $before, $after, $link_text, $link_title, $disp_when_logged_in, NULL, $default_source_string );
}


/**
 * Template tag: Get a link to new user registration
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 * @param string Where to redirect
 * @return string used for source tracking
 */
function get_user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#',
		$disp_when_logged_in = false, $redirect = null, $default_source_string = '' )
{
	$register_url = get_user_register_url( $redirect, $default_source_string, $disp_when_logged_in );

	if( !$register_url )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Register').' &raquo;';
	if( $link_title == '#' ) $link_title = T_('Register for a new account...');

	$r = $before;
	$r .= '<a href="'.$register_url.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}

function get_user_register_url( $redirect = NULL, $default_source_string = '', $disp_when_logged_in = false )
{
	global $htsrv_url_sensitive, $Settings, $edited_Blog, $generating_static;

	if( is_logged_in() && ! $disp_when_logged_in )
	{ // Do not display, when already logged in:
		return false;
	}

	if( ! $Settings->get('newusers_canregister'))
	{ // We won't let him register
		return false;
	}

	if( use_in_skin_login() )
	{
		global $blog;

		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog );

		$register_url = $Blog->get( 'url' ).'?disp=register';
	}
	else
	{
		$register_url = $htsrv_url_sensitive.'register.php';
	}

	// Source=
	$source = param( 'source', 'string', '' );
	if( empty($source) )
	{
		$source = $default_source_string;
	}
	if( ! empty($source) )
	{
		$register_url = url_add_param( $register_url, 'source='.rawurlencode($source), '&' );
	}

	// Redirect_to=
	if( ! isset($redirect) )
	{
		if( !isset($generating_static) )
		{ // We are not generating a static page here:
			$redirect = regenerate_url( '', '', '', '&' );
		}
		elseif( isset($edited_Blog) )
		{ // We are generating a static page
			$redirect = $edited_Blog->get('url'); // was dynurl
		}
		else
		{ // We are in a weird situation
			$redirect = '';
		}
	}

	if( ! empty($redirect) )
	{
		$register_url = url_add_param( $register_url, 'redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $htsrv_url_sensitive ) ), '&' );
	}

	return $register_url;
}


/**
 * Template tag: Output a link to logout
 */
function user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#', $params = array() )
{
	echo get_user_logout_link( $before, $after, $link_text, $link_title, $params );
}


/**
 * Template tag: Get a link to logout
 *
 * @param string
 * @param string
 * @param string link text can include %s for current user login
 * @return string
 */
function get_user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#', $params = array() )
{
	global $current_User;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Logout');
	if( $link_title == '#' ) $link_title = T_('Logout from your account');

	$r = $before;
	$r .= '<a href="'.get_user_logout_url().'"';
	$r .= get_field_attribs_as_string( $params, false );
	$r .= ' title="'.$link_title.'">';
	$r .= sprintf( $link_text, $current_User->login );
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Get the URL for the logout button
 *
 * @return string
 */
function get_user_logout_url()
{
	global $admin_url, $baseurl, $htsrv_url_sensitive, $is_admin_page, $Blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $is_admin_page )
	{
		if( isset( $Blog ) )
		{	// Go to the home page of the blog that was being edited:
  		$redirect_to = $Blog->get( 'url' );
		}
		else
		{	// We were not editing a blog...
			// return to global home:
  		$redirect_to = url_rel_to_same_host( $baseurl, $htsrv_url_sensitive);
  		// Alternative: return to the login page (fp> a basic user would be pretty lost on that login page)
  		// $redirect_to = url_rel_to_same_host($admin_url, $htsrv_url_sensitive);
		}
	}
	else
	{	// Return to current blog page:
		$redirect_to = url_rel_to_same_host(regenerate_url('','','','&'), $htsrv_url_sensitive);
	}

	return $htsrv_url_sensitive.'login.php?action=logout&amp;redirect_to='.rawurlencode($redirect_to);
}


/**
 * Template tag: Output a link to the backoffice.
 *
 * Usually provided in skins in order for newbies to find the admin interface more easily...
 *
 * @param string To be displayed before the link.
 * @param string To be displayed after the link.
 * @param string The page/controller to link to inside of {@link $admin_url}
 * @param string Text for the link.
 * @param string Title for the link.
 */
function user_admin_link( $before = '', $after = '', $link_text = '', $link_title = '#', $not_visible = '' )
{
	echo get_user_admin_link( $before, $after, $link_text, $link_title, $not_visible );
}


/**
 * Template tag: Get a link to the backoffice.
 *
 * Usually provided in skins in order for newbies to find the admin interface more easily...
 *
 * @param string To be displayed before the link.
 * @param string To be displayed after the link.
 * @param string The page/controller to link to inside of {@link $admin_url}
 * @param string Text for the link.
 * @param string Title for the link.
 * @return string
 */
function get_user_admin_link( $before = '', $after = '', $link_text = '', $link_title = '#', $not_visible = '' )
{
	global $admin_url, $blog, $current_User;

	if( is_logged_in() && ! $current_User->check_perm( 'admin', 'normal' ) )
	{ // If user should NOT see admin link:
		return $not_visible;
	}

	if( $link_text == '' ) $link_text = T_('Admin');
	if( $link_title == '#' ) $link_title = T_('Go to the back-office...');
	// add the blog param to $page if it is not already in there

	if( !empty( $blog ) )
	{
		$url = url_add_param( $admin_url, 'blog='.$blog );
	}
	else
	{
		$url = $admin_url;
	}

	$r = $before;
	$r .= '<a href="'.$url.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Template tag: Display a link to user profile
 */
function user_profile_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_profile_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to user profile
 *
 * @return string|false
 */
function get_user_profile_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' )
	{
		$link_text = T_('Profile');
	}
	else
	{
		$link_text = str_replace( '%s', $current_User->login, $link_text );
	}
	if( $link_title == '#' ) $link_title = T_('Edit your profile');

	$r = $before
		.'<a href="'.get_user_profile_url().'" title="'.$link_title.'">'
		.sprintf( $link_text, $current_User->login )
		.'</a>'
		.$after;

	return $r;
}


/**
 * Get URL to edit user profile
 */
function get_user_profile_url()
{
	return get_user_settings_url( 'profile' );
}


/**
 * Get URL to edit user avatar
 */
function get_user_avatar_url()
{
	return get_user_settings_url( 'avatar' );
}


/**
 * Get URL to change user password
 */
function get_user_pwdchange_url()
{
	return get_user_settings_url( 'pwdchange' );
}


/**
 * Get URL to edit user preferences
 */
function get_user_preferences_url()
{
	return get_user_settings_url( 'userprefs' );
}


/**
 * Get User identity link. User is given with his login or ID. User login or ID must be set.
 *
 * @param string User login ( can be NULL if ID is set )
 * @param integer User ID ( can be NULL if login is set )
 * @param string On which user profile tab should this link point to
 * @return NULL|string NULL if this user or the profile tab doesn't exists, the identity link otherwise.
 */
function get_user_identity_link( $user_login, $user_ID = NULL, $profile_tab = 'profile' )
{
	$UserCache = & get_UserCache();

	if( empty( $user_login ) )
	{
		if( $user_ID == NULL )
		{
			return NULL;
		}
		$User = & $UserCache->get_by_ID( $user_ID );
	}
	else
	{
		$User = & $UserCache->get_by_login( $user_login );
	}

	if( $User == false )
	{
		return NULL;
	}

	return $User->get_identity_link( $profile_tab );
}


/**
 * Get the available user display url
 *
 * @param integer user ID
 */
function get_user_identity_url ( $user_ID = NULL, $profile_tab = 'profile', $redirect_to = NULL )
{
	global $current_User, $Blog;

	if( $user_ID == NULL )
	{
		if( isset( $current_User ) )
		{
			$user_ID = $current_User->ID;
		}
		else
		{
			return NULL;
		}
	}

	if( $redirect_to == NULL )
	{
		$redirect_to = rawurlencode( regenerate_url( '','','','&' ) );
	}

	if( ( !isset( $current_User ) ) || 
		( ( $user_ID != NULL ) && ( $current_User->ID != $user_ID ) && ( ! $current_User->check_perm( 'users', 'view' ) ) ) )
	{ // user is not logged in or current User is not the same as requested user, and current User doesn't have users view permission
		if( empty( $Blog ) )
		{ // Incorrect state
			return NULL;
		}
		else
		{ // can't display the profile form, display the front office User form
			return url_add_param( $Blog->gen_blogurl(), 'disp=user&amp;user_ID='.$user_ID.'&amp;redirect_to='.$redirect_to );
		}
	}

	// return the corresponding user profile tab url
	return url_add_param( get_user_settings_url( $profile_tab, $user_ID ), 'redirect_to='.$redirect_to );
}


/**
 * Get URL to a specific user settings tab (profile, avatar, pwdchange, userprefs)
 *
 * @param string user tab
 * @param integer user ID for the requested user. If isn't set then return $current_User settings url.
 */
function get_user_settings_url( $user_tab, $user_ID = NULL )
{
	global $current_User, $Blog, $is_admin_page, $admin_url, $ReqURI;

	if( !is_logged_in() )
	{
		debug_die( 'Active user not found.' );
	}

	if( in_array( $user_tab, array( 'advanced', 'admin', 'blogs' ) ) )
	{
		$is_admin_tab = true;
	}
	else
	{
		$is_admin_tab = false;
	}

	if( ( !$is_admin_tab ) && ( ! in_array( $user_tab, array( 'profile', 'avatar', 'pwdchange', 'userprefs' ) ) ) )
	{
		debug_die( 'Not supported user tab!' );
	}

	if( $user_ID == NULL )
	{
		$user_ID = $current_User->ID;
	}

	if( $is_admin_page || $is_admin_tab || empty( $Blog ) || $current_User->ID != $user_ID )
	{
		if( ( $current_User->ID != $user_ID ) && ( ! $current_User->check_perm( 'users', 'view' ) ) )
		{
			return NULL;
		}
		$current_User->get_Group();
		if( ( $user_tab == 'admin' ) && ( $current_User->Group->ID != 1 ) )
		{
			$user_tab = 'profile';
		}
		return $admin_url.'?ctrl=user&amp;user_tab='.$user_tab.'&amp;user_ID='.$user_ID;
	}

	return url_add_param( $Blog->gen_blogurl(), 'disp='.$user_tab );
}


/**
 * Template tag: Provide a link to subscription screen
 */
function user_subs_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User, $Blog;

	if( ! $url = get_user_subs_url() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Subscribe');
	if( $link_title == '#' ) $link_title = T_('Subscribe to email notifications');

	echo $before;
	echo '<a href="'.$url.'" title="', $link_title, '">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}

/**
 * Template tag: Provide url to subscription screen
 */
function get_user_subs_url()
{
	global $Blog, $is_admin_page;

	if( ! is_logged_in() || $is_admin_page )
	{
		return false;
	}

	if( empty( $Blog ) || ! $Blog->get_setting( 'allow_subscriptions' ) )
	{
		return false;
	}

	return url_add_param( $Blog->gen_blogurl(), 'disp=subs&amp;redirect_to='.rawurlencode( url_rel_to_same_host(regenerate_url('','','','&'), $Blog->gen_blogurl())));
}


/**
 * Template tag: Display the user's preferred name
 *
 * Used in result lists.
 *
 * @param integer user ID
 */
function user_preferredname( $user_ID )
{
	$UserCache = & get_UserCache();
	if( !empty( $user_ID )
		&& ($User = & $UserCache->get_by_ID( $user_ID )) )
	{
		$User->disp('preferredname');
	}
}


/**
 * Check profile parameters and add errors through {@link param_error()}.
 *
 * @param array associative array.
 *     Either array( $value, $input_name ) or just $value;
 *     ($input_name gets used for associating it to a form fieldname)
 *     - 'login': check for non-empty
 *     - 'nickname': check for non-empty
 *     - 'icq': must be a number
 *     - 'email': mandatory, must be well formed
 *     - 'country': check for non-empty
 *     - 'url': must be well formed, in allowed scheme, not blacklisted
 *     - 'pass1' / 'pass2': passwords (twice), must be the same and not == login (if given)
 *     - 'pass_required': false/true (default is true)
 * @param User|NULL A user to use for additional checks (password != login/nick).
 */
function profile_check_params( $params, $User = NULL )
{
	global $Messages, $Settings;

	foreach( $params as $k => $v )
	{
		// normalize params:
		if( $k != 'pass_required' && ! is_array($v) )
		{
			$params[$k] = array($v, $k);
		}
	}

	// checking login has been typed:
	param_check_valid_login( 'login' );

	// checking the nickname has been typed
	if( isset($params['nickname']) && empty($params['nickname'][0]) )
	{
		param_error($params['nickname'][1], T_('Please enter a nickname (can be the same as your login).') );
	}

	// if the ICQ UIN has been entered, check to see if it has only numbers
	if( !empty($params['icq'][0]) )
	{
		if( !preg_match( '#^[0-9]+$#', $params['icq'][0]) )
		{
			param_error( $params['icq'][1], T_('The ICQ UIN can only be a number, no letters allowed.') );
		}
	}

	// checking e-mail address
	if( isset($params['email'][0]) )
	{
		if( empty($params['email'][0]) )
		{
			param_error( $params['email'][1], T_('Please enter your e-mail address.') );
		}
		elseif( !is_email($params['email'][0]) )
		{
			param_error( $params['email'][1], T_('The email address is invalid.') );
		}
	}

	// Checking country
	if( isset($params['country']) && empty($params['country'][0]) )
	{
		param_error( 'country', T_('Please select country.') );
	}

	// Checking gender
	if( isset($params['gender']) )
	{
		if( empty($params['gender'][0]) )
		{
			param_error( 'gender', T_('Please select gender.') );
		}
		elseif( ( $params['gender'][0] != 'M' ) && ( $params['gender'][0] != 'F' ) )
		{
			param_error( 'gender', 'Gender value is invalid' );
		}
	}

	// Checking URL:
	if( isset($params['url']) )
	{
		if( $error = validate_url( $params['url'][0], 'commenting' ) )
		{
			param_error( $params['url'][1], T_('Supplied URL is invalid: ').$error );
		}
	}

	// Check passwords:

	$pass_required = isset( $params['pass_required'] ) ? $params['pass_required'] : true;

	if( isset($params['pass1'][0]) && isset($params['pass2'][0]) )
	{
		if( $pass_required || !empty($params['pass1'][0]) || !empty($params['pass2'][0]) )
		{ // Password is required or was given
			// checking the password has been typed twice
			if( empty($params['pass1'][0]) || empty($params['pass2'][0]) )
			{
				param_error( $params['pass2'][1], T_('Please enter your password twice.') );
			}

			// checking the password has been typed twice the same:
			if( $params['pass1'][0] !== $params['pass2'][0] )
			{
				param_error( $params['pass1'][1], T_('You typed two different passwords.') );
			}
			elseif( evo_strlen($params['pass1'][0]) < $Settings->get('user_minpwdlen') )
			{
				param_error( $params['pass1'][1], sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen')) );
			}
			elseif( isset($User) && $params['pass1'][0] == $User->get('login') )
			{
				param_error( $params['pass1'][1], T_('The password must be different from your login.') );
			}
			elseif( isset($User) && $params['pass1'][0] == $User->get('nickname') )
			{
				param_error( $params['pass1'][1], T_('The password must be different from your nickname.') );
			}
		}
	}
}


/**
 * Get avatar <img> tag by user login
 *
 * @param user login
 * @param if true show user login after avatar
 * @param if true link to user profile
 * @param avatar size
 * @param style class
 * @param image align
 * @return login <img> tag
 */
function get_avatar_imgtag( $user_login, $show_login = true, $link = true, $size = 'crop-15x15', $class = 'avatar_before_login', $align = '' )
{
	global $current_User;

	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_login( $user_login );

	$img_tag = '';
	if( $User !== false )
	{
		$img_tag = $User->get_avatar_imgtag( $size, $class, $align );

		if( $show_login )
		{
			$img_tag = '<span class="nowrap">'.$img_tag.$user_login.'</span>';
		}

		if( $link && $current_User->check_perm( 'users', 'view', false ) )
		{	// Permission to view user details
			global $admin_url;
	// fp>dh why did you add $admin_url here? If this is gonna be used outside of admin, it should not point to the profile in the admin but rather to the profile disp in the public blog skin
			$img_tag = '<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$User->ID.'">'.$img_tag.'</a>';
		}

	}

	return $img_tag;
}


/**
 * Get avatar <img> tags for list of user logins
 *
 * @param list of user logins
 * @param if true show user login after each avatar
 * @param avatar size
 * @param style class
 * @param image align
 * @return coma separated login <img> tag
 */
function get_avatar_imgtags( $user_logins_list, $show_login = true, $link = true, $size = 'crop-15x15', $class = 'avatar_before_login', $align = '' )
{
	if( !is_array( $user_logins_list ) )
	{
		$user_logins_list = explode( ', ', $user_logins_list );
	}

	$user_imgtags_list = array();
	foreach( $user_logins_list as $user_login )
	{
		$user_imgtags_list[] = get_avatar_imgtag( $user_login, $show_login, $link, $size, $class, $align );
	}
	return implode( ', ', $user_imgtags_list );
}


/**
 * Convert seconds duration
 *
 * @param integer seconds
 * @return string
 */
function seconds_to_fields( $duration )
{
	$fields = '';

	$month_seconds = 2592000; // 1 month
	$months = floor( $duration / $month_seconds );
	$duration = $duration - $months * $month_seconds;
	if( $months > 0 )
	{
		$fields .= $months.' months ';
	}

	$day_seconds = 86400; // 1 day
	$days = floor( $duration / $day_seconds );
	$duration = $duration - $days * $day_seconds;
	if( $days > 0 )
	{
		$fields .= $days.' days ';
	}

	$hour_seconds = 3600; // 1 hour
	$hours = floor( $duration / $hour_seconds );
	$duration = $duration - $hours * $hour_seconds;
	if( $hours > 0 )
	{
		$fields .= $hours.' hours ';
	}

	$minute_seconds = 60; // 1 minute
	$minutes = floor( $duration / $minute_seconds );
	$duration = $duration - $minutes * $minute_seconds;
	if( $minutes > 0 )
	{
		$fields .= $minutes.' minutes ';
	}

	$seconds = $duration;
	if( $seconds > 0 )
	{
		$fields .= $seconds.' seconds ';
	}

	$fields = trim( $fields );
	if( empty( $fields ) )
	{
		$fields = '0';
	}

	return $fields;
}


/**
 * Display user edit forms action icons
 *
 * @param Form where to display
 * @param User edited user
 * @param String the action string, 'view' or 'edit'
 */
function echo_user_actions( $Form, $edited_User, $action )
{
	global $current_User;

	if( ( $current_User->check_perm( 'users', 'edit', false ) ) && ( $current_User->ID != $edited_User->ID )
		&& ( $edited_User->ID != 1 ) )
	{
		$Form->global_icon( T_('Delete this user!'), 'delete', '?ctrl=users&amp;action=delete&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete'), 3, 4  );
	}
	if( $edited_User->get_msgform_possibility( $current_User ) )
	{
		$Form->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );
	}

	$redirect_to = get_param( 'redirect_to' );
	if( $redirect_to == NULL )
	{
		$redirect_to = regenerate_url( 'user_ID,action,ctrl', 'ctrl=users' );
	}
	$Form->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', $redirect_to );
}


/**
 * Get user menu sub entries
 *
 * @param boolean true to get admin interface user sub menu entries, false to get front office user sub menu entries
 * @param integer edited user ID
 * @return array user sub entries
 */
function get_user_sub_entries( $is_admin, $user_ID )
{
	global $current_User, $Settings, $Blog;
	$users_sub_entries = array();
	if( empty( $user_ID ) )
	{
		$user_ID = $current_User->ID;
	}

	if( $is_admin )
	{
		$ctrl_param = '?ctrl=user&amp;user_tab=';
		$user_param = '&amp;user_ID='.$user_ID;
		$base_url = '';
	}
	else
	{
		$ctrl_param = '?disp=';
		$user_param = '';
		$base_url = $Blog->gen_blogurl();
	}
	$edit_perm = ( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'edit' ) );
	$view_perm = ( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'view' ) );

	if( $view_perm )
	{
		$users_sub_entries['profile'] = array(
							'text' => T_('Profile'),
							'href' => $base_url.$ctrl_param.'profile'.$user_param	);

		if( $Settings->get('allow_avatars') )
		{
			$users_sub_entries['avatar'] = array(
							'text' => T_('Profile picture'),
							'href' => $base_url.$ctrl_param.'avatar'.$user_param );
		}

		if( $edit_perm )
		{
			$users_sub_entries['pwdchange'] = array(
								'text' => T_('Password'),
								'href' => $base_url.$ctrl_param.'pwdchange'.$user_param );
		}

		$users_sub_entries['userprefs'] = array(
							'text' => T_('Preferences'),
	 						'href' => $base_url.$ctrl_param.'userprefs'.$user_param );

		if( $is_admin )
		{ // show this only in backoffice
			$users_sub_entries['advanced'] = array(
								'text' => T_('Advanced'),
								'href' => '?ctrl=user&amp;user_tab=advanced'.$user_param );

			if( $current_User->group_ID == 1 )
			{ // Only admin users can see the 'Admin' tab
				$users_sub_entries['admin'] = array(
									'text' => T_('Admin'),
									'href' => '?ctrl=user&amp;user_tab=admin'.$user_param );
			}

			$users_sub_entries['blogs'] = array(
								'text' => T_('Personal blogs'),
		 						'href' => '?ctrl=user&amp;user_tab=blogs'.$user_param );
		}
	}

	return $users_sub_entries;
}


/**
 * Get if user is subscribed to get emails, when a new comment is published on this item.
 *
 * @param integer user ID
 * @param integer item ID
 * @return boolean true if user is subscribed and false otherwise
 */
function get_user_isubscription( $user_ID, $item_ID )
{
	global $DB;
	$result = $DB->get_var( 'SELECT count( isub_user_ID )
								FROM T_items__subscriptions
								WHERE isub_user_ID = '.$user_ID.' AND isub_item_ID = '.$item_ID.' AND isub_comments <> 0' );
	return $result > 0;
}


/**
 * Set user item subscription
 *
 * @param integer user ID
 * @param integer item ID
 * @param integer value 0 for unsubscribe and 1 for subscribe
 * @param string item subscription type ( isub_comments, isub_attend )
 * @return boolean true is new value was successfully set, false otherwise
 */
function set_user_isubscription( $user_ID, $item_ID, $value, $type )
{
	global $DB;
	if( ( $value < 0 ) || ( $value > 1 ) )
	{ // Invalid value. It should be 0 for unsubscribe and 1 for subscribe.
		return false;
	}

	if( $type != 'isub_comments' )
	{ // Invalid type. It should be 'isub_comments'.
		return false;
	}

	return $DB->query( 'REPLACE INTO T_items__subscriptions( isub_item_ID, isub_user_ID, '.$type.' )
								VALUES ( '.$item_ID.', '.$user_ID.', '.$value.' )' );
}


/**
 * Get user prefered name for notification emails salutation
 *
 * @param string nickname
 * @param string firstname
 * @param string login
 * @return prefered salutation name
 */
function get_prefered_name( $nickname, $firstname, $login )
{
	if( empty( $nickname ) )
	{
		if( empty( $firstname ) )
		{
			return $login;
		}
		else
		{
			return $firstname;
		}
	}
	return $nickname;
}


/**
 * Get usertab header. Contains the user avatar image, the user tab title, and the user menu.
 *
 * @param object edited User
 * @param string user tab name
 * @param string user tab title
 * @return string tab header
 */
function get_usertab_header( $edited_User, $user_tab, $user_tab_title )
{
	global $AdminUI;

	// set title
	$form_title = '<span class="user_title">'.sprintf( '%s &ndash; %s', $edited_User->dget('fullname').' &laquo;'.$edited_User->dget('login').'&raquo;', $user_tab_title ).'</span>';

	// set avatar tag
	if( $edited_User->has_avatar() )
	{
		$avatar_tag = $edited_User->get_avatar_imgtag( 'crop-48x48', 'avatar', '', true );
	}
	else
	{
		$avatar_tag = '';
	}

	// build menu3
	$AdminUI->add_menu_entries( array( 'users', 'users' ), get_user_sub_entries( true, $edited_User->ID ) );
	$AdminUI->set_path( 'users', 'users', $user_tab );
	$user_menu3 = $AdminUI->get_html_menu( array( 'users', 'users' ), 'menu3' );

	$result = $avatar_tag.'<div class="user_header_content">'.$form_title.$user_menu3.'</div>';
	return '<div class="user_header">'.$result.'</div>'.'<div class="clear"></div>';
}


/*
 * $Log: _user.funcs.php,v $
 */
?>