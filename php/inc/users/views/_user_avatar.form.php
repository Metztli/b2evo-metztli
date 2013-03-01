<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var User
 */
global $edited_User;
/**
 * @var User
 */
global $current_User;
/**
 * @var current action
 */
global $action;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;

$Form = new Form( $form_action, 'user_checkchanges', 'post', NULL, 'multipart/form-data' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_title = get_usertab_header( $edited_User, 'avatar', T_( 'Edit profile picture' ) );
	$form_class = 'fform';
	$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;
}
else
{
	global $Blog;
	$form_title = '';
	$form_class = 'bComment';
	$ctrl_param = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}

$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	if( $is_admin )
	{
		$Form->hidden_ctrl();
	}
	else
	{
		$Form->hidden( 'disp', $disp );
	}
	$Form->hidden( 'user_tab', 'avatar' );
	$Form->hidden( 'avatar_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );

	/***************  Avatar  **************/

$Form->begin_fieldset( $is_admin ? T_('Profile picture') : '', array( 'class'=>'fieldset clear' ) );

global $admin_url;
$avatar_tag = $edited_User->get_avatar_imgtag( 'fit-160x160', 'avatar', '', true );
if( empty( $avatar_tag ) )
{
	if( ( $current_User->ID == $edited_User->ID ) )
	{
		$avatar_tag = T_( 'You currently have no profile picture.' );
	}
	else
	{
		$avatar_tag = T_( 'This user currently has no profile picture.' );
	}
}

$Form->info( T_( 'Current profile picture' ), $avatar_tag );

// fp> TODO: a javascript REFRAME feature would ne neat here: selecting a square area of the img and saving it as a new avatar image

if( ( $current_User->check_perm( 'users', 'all' ) ) || ( $current_User->ID == $edited_User->ID ) )
{
	// Upload or select:
	global $Settings;
	if( $Settings->get('upload_enabled') && ( $Settings->get( 'fm_enable_roots_user' ) ) )
	{	// Upload is enabled and we have permission to use it...
		load_class( 'files/model/_filelist.class.php', 'Filelist' );
		load_class( 'files/model/_fileroot.class.php', 'FileRoot' );
		$path = 'profile_pictures';

		$Form->hidden( 'action', 'upload_avatar' );
		// The following is mainly a hint to the browser.
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );

		$FileRootCache = & get_FileRootCache();
		$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
		$ads_list_path = get_canonical_path( $user_FileRoot->ads_path.$path );

		// Upload
		$info_content = '<input name="uploadfile[]" type="file" size="10" />';
		$info_content .= '<input class="ActionButton" type="submit" value="&gt; './* TRANS: action */ T_('Upload!').'" />';
		$Form->info( T_('Upload a new profile picture'), $info_content );

		// Previously uploaded avatars
		if( is_dir( $ads_list_path ) )
		{ // profile_picture folder exists in the user root dir
			$user_avatar_Filelist = new Filelist( $user_FileRoot, $ads_list_path );
			$user_avatar_Filelist->load();

			if( $user_avatar_Filelist->count() > 0 )
			{ // profile_pictures folder is not empty
				$info_content = '';
				while( $lFile = & $user_avatar_Filelist->get_next() )
				{ // Loop through all Files:
					$lFile->load_meta( true );
					if( $lFile->is_image() )
					{
						$url = regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$lFile->ID.'&'.url_crumb('user'), '', '&');
						$info_content .= '<div class="avatartag">';
						$info_content .= '<a href="'.$url.'">'.'<img '.$lFile->get_thumb_imgtag( 'crop-80x80' ).'</a>';
						$info_content .= '</div>';
					}
				}
				$Form->info( T_('Select a previously uploaded profile picture'), $info_content );
			}
		}
	}

	$more_content = '';
	if( $edited_User->has_avatar() )
	{
		$more_content = '<div><a href="'.$ctrl_param.'&amp;action=remove_avatar&amp;'.url_crumb('user').'">';
		if( $edited_User->ID == $current_User->ID )
		{
			$more_content .= T_( 'Remove your current profile picture' );
		}
		else
		{
			$more_content .= T_( 'Remove this user\'s current profile picture' );
		}
		$more_content .= '</a></div>';
	}

	if( $current_User->check_perm( 'files', 'view' ) )
	{
		$more_content .= '<div><a href="'.$admin_url.'?ctrl=files&amp;user_ID='.$edited_User->ID.'">';
		$more_content .= T_( 'Use the file manager to assign a new profile picture' ).'</a></div>';
	}

	if( ! empty( $more_content ) )
	{
		$Form->info( T_('More functions'), $more_content );
	}
}

$Form->end_fieldset();

$Form->end_form();

/*
 * $Log: _user_avatar.form.php,v $
 */
?>