<?php
/**
 * This is the template that displays the user profile page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _user.disp.php 57 2011-10-26 08:18:58Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
* @var Blog
*/
global $Blog;
/**
 * @var GeneralSettings
 */
global $Settings;

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{	// Grab the blog owner
	$user_ID = $Blog->owner_user_ID;
}

$UserCache = & get_UserCache();
/**
 * @var User
 */
$User = & $UserCache->get_by_ID( $user_ID );

/**
 * form to update the profile
 * @var Form
 */
$ProfileForm = new Form( '', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );

echo $User->get_avatar_imgtag( 'fit-160x160', 'rightmargin' );

$ProfileForm->begin_fieldset( T_('Identity') );

	$ProfileForm->info( T_('Name'), $User->get( 'preferredname' ) );
  $ProfileForm->info( T_('Login'), $User->get('login') );

	if( ! empty( $User->gender ) )
	{
		$ProfileForm->info( T_( 'I am' ), $User->get_gender() );
	}

	if( ! empty( $User->ctry_ID ) )
	{
		$CountryCache = & get_CountryCache();
		$user_Country = $CountryCache->get_by_ID( $User->ctry_ID );
		$ProfileForm->info( T_( 'Country' ), $user_Country->get_name() );
	}

	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$User->ID, '&' );
	$msgform_url = $User->get_msgform_url( $Blog->get('msgformurl'), $redirect_to );
	if( !empty($msgform_url) )
	{
		$ProfileForm->info( T_('Contact'), '<a href="'.$msgform_url.'">'.T_('Send a message').'</a>' );
	}
	else
	{
		if( is_logged_in() && $User->accepts_pm() )
		{
			global $current_User;
			if( $current_User->accepts_pm() )
			{
				$ProfileForm->info( T_('Contact'), T_('You cannot send a private message to yourself.') );
			}
			else
			{
				$ProfileForm->info( T_('Contact'), T_('This user can only be contacted through private messages but you are not allowed to send any private messages.') );
			}
		}
		else
		{
			$ProfileForm->info( T_('Contact'), T_('This user does not wish to be contacted directly.') );
		}
	}

	if( !empty($User->url) )
	{
		$ProfileForm->info( T_('Website'), '<a href="'.$User->url.'" rel="nofollow" target="_blank">'.$User->url.'</a>' );
	}

$ProfileForm->end_fieldset();


$ProfileForm->begin_fieldset( T_('Additional info') );

	// Load the user fields:
	$User->userfields_load();

	// fp> TODO: have some clean iteration support
	foreach( $User->userfields as $uf_ID=>$uf_array )
	{
		$ProfileForm->info( $User->userfield_defs[$uf_array[0]][1], $uf_array[1] );
	}

$ProfileForm->end_fieldset();


$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $ProfileForm, 'User' => & $User, 'edit_layout' => 'public' ) );

// Make sure we're below the floating user avatar on the right
echo '<div class="clear"></div>';

$ProfileForm->end_form();


/*
 * $Log: _user.disp.php,v $
 */
?>