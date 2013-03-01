<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _user_list.view.php 1107 2012-03-28 19:05:24Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var DB
 */
global $DB;

global $collections_Module;

// query which groups have users (in order to prevent deletion of groups which have users)
global $usedgroups;	// We need this in a callback below
$usedgroups = $DB->get_col( 'SELECT grp_ID
                             FROM T_groups INNER JOIN T_users ON user_grp_ID = grp_ID
							 GROUP BY grp_ID');

/*
 * Query user list:
 */
$keywords = param( 'keywords', 'string', '', true );
// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
// $usr_unconfirmed = param( 'usr_unconfirmed', 'boolean', false, true );

$where_clause = '';

if( !empty( $keywords ) )
{
	$kw_array = preg_split( '~ ~', $keywords );
	foreach( $kw_array as $kw )
	{
		// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
		$where_clause .= 'CONCAT_WS( " ", user_login, user_firstname, user_lastname, user_nickname, user_email) LIKE "%'.$DB->escape($kw).'%" AND ';
	}
}
// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
/*
if( $usr_unconfirmed )
{
	$where_clause .= 'user_validated = 0 AND ';
}
*/
$SQL = new SQL();
$SQL->SELECT( 'T_users.*, grp_ID, grp_name, ctry_name' );
$SQL->FROM( 'T_users RIGHT JOIN T_groups ON user_grp_ID = grp_ID' );
$SQL->FROM_add( 'LEFT JOIN T_country ON user_ctry_ID = ctry_ID' );
$SQL->WHERE( $where_clause.' 1' );
$SQL->GROUP_BY( 'user_ID, grp_ID' );
$SQL->ORDER_BY( 'grp_name, *' );

if( isset($collections_Module) )
{	// We are handling blogs:
	$SQL->SELECT_add( ', COUNT(DISTINCT blog_ID) AS nb_blogs' );
	$SQL->FROM_add( ' LEFT JOIN T_blogs on user_ID = blog_owner_user_ID' );
}
else
{
	$SQL->SELECT_add( ', 0 AS nb_blogs' );
}

$count_sql = 'SELECT COUNT(*)
							 	FROM T_users
							 WHERE '.$where_clause.' 1';

if( $Settings->get('allow_avatars') )
{
	$default_sort = '--A';
}
else
{
	$default_sort = '-A';
}

$Results = new Results( $SQL->get(), 'user_', $default_sort, NULL, $count_sql );

$Results->title = T_('Groups & Users').get_manual_link('users_and_groups');

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{ // create new user link
	$Results->global_icon( T_('Create a new user...'), 'new', '?ctrl=user&amp;action=new&amp;user_tab=profile', T_('Add user').' &raquo;', 3, 4  );
	$Results->global_icon( T_('Create a new group...'), 'new', '?ctrl=groups&amp;action=new', T_('Add group').' &raquo;', 3, 4  );
}


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_userlist( & $Form )
{
	$Form->text( 'keywords', get_param('keywords'), 20, T_('Keywords'), T_('Separate with space'), 50 );
}
$Results->filter_area = array(
	'callback' => 'filter_userlist',
	'url_ignore' => 'results_user_page,keywords',
	'presets' => array(
		'all' => array( T_('All users'), '?ctrl=users' ),
// fp> TODO: implement this like other filtersets in the app. You need a checkbox, or better yet: a select that allows: Confirmed/Unconfirmed/All
//		'unconfirmed' => array( T_('Unconfirmed email'), '?ctrl=users&amp;usr_unconfirmed=1' ),
		)
	);


/*
 * Grouping params:
 */
$Results->group_by = 'grp_ID';
$Results->ID_col = 'user_ID';


/*
 * Group columns:
 */
$Results->grp_cols[] = array(
						'td_class' => 'firstcol'.($current_User->check_perm( 'users', 'edit', false ) ? '' : ' lastcol' ),
						'td_colspan' => -1,  // nb_colds - 1
						'td' => '<a href="?ctrl=groups&amp;grp_ID=$grp_ID$">$grp_name$</a>'
										.'¤conditional( (#grp_ID# == '.$Settings->get('newusers_grp_ID').'), \' <span class="notes">('.T_('default group for new users').')</span>\' )¤',
					);

function grp_actions( & $row )
{
	global $usedgroups, $Settings, $current_User;

	$r = '';
	if( $current_User->check_perm( 'users', 'edit', false ) )
	{
		$r = action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;grp_ID='.$row->grp_ID ) );

		$r .= action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;action=new&amp;grp_ID='.$row->grp_ID ) );

		if( ($row->grp_ID != 1) && ($row->grp_ID != $Settings->get('newusers_grp_ID')) && !in_array( $row->grp_ID, $usedgroups ) )
		{ // delete
			$r .= action_icon( T_('Delete this group!'), 'delete', regenerate_url( 'ctrl,action', 'ctrl=groups&amp;action=delete&amp;grp_ID='.$row->grp_ID.'&amp;'.url_crumb('group') ) );
		}
		else
		{
			$r .= get_icon( 'delete', 'noimg' );
		}
	}
	return $r;
}
$Results->grp_cols[] = array(
						'td_class' => 'shrinkwrap',
						'td' => '%grp_actions( {row} )%',
					);

/*
 * Data columns:
 */
$Results->cols[] = array(
						'th' => T_('ID'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'user_ID',
						'td' => '$user_ID$',
					);

if( $Settings->get('allow_avatars') )
{
	function user_avatar( $user_ID, $user_avatar_file_ID )
	{
		$FileCache = & get_FileCache();

		// Do not halt on error. A file can disappear without the profile being updated.
		/**
		 * @var File
		 */
		if( ! $File = & $FileCache->get_by_ID( $user_avatar_file_ID, false, false ) )
		{
			return '';
		}
		return '<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$user_ID.'">'.$File->get_thumb_imgtag( 'crop-48x48' ).'</a>';
	}
	$Results->cols[] = array(
							'th' => T_('Picture'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap center',
							'td' => '%user_avatar( #user_ID#, #user_avatar_file_ID# )%',
						);
}

$Results->cols[] = array(
						'th' => T_('Login'),
						'th_class' => 'shrinkwrap',
						'order' => 'user_login',
						'td' => '<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID=$user_ID$"><strong>$user_login$</strong></a>',
					);

$Results->cols[] = array(
						'th' => T_('Nickname'),
						'th_class' => 'shrinkwrap',
						'order' => 'user_nickname',
						'td' => '$user_nickname$',
					);

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'user_lastname, user_firstname',
						'td' => '$user_firstname$ $user_lastname$',
					);

$Results->cols[] = array(
						'th' => T_('Gender'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'user_gender',
						'td' => '$user_gender$',
					);

$Results->cols[] = array(
						'th' => T_('Country'),
						'th_class' => 'shrinkwrap',
						'order' => 'ctry_name',
						'td' => '$ctry_name$',
					);

function user_mailto( $email )
{
	if( empty( $email ) )
	{
		return '&nbsp;';
	}
	return action_icon( T_('Email').': '.$email, 'email', 'mailto:'.$email, T_('Email') );
}
$Results->cols[] = array(
						'th' => T_('Email'),
						'td_class' => 'shrinkwrap',
						'td' => '%user_mailto( #user_email# )%',
					);

function user_pm ( $user_ID, $user_login )
{
	global $current_User;

	if( $user_ID == $current_User->ID )
	{
		return '&nbsp;';
	}
	return action_icon( T_('Private Message').': '.$user_login, 'comments', '?ctrl=threads&action=new&user_login='.$user_login );
}
$Results->cols[] = array(
						'th' => T_('PM'),
						'td_class' => 'shrinkwrap',
						'td' => '%user_pm( #user_ID#, #user_login# )%',
					);


$Results->cols[] = array(
						'th' => T_('URL'),
						'td_class' => 'shrinkwrap',
						'td' => '¤conditional( (#user_url# != \'http://\') && (#user_url# != \'\'), \'<a href="$user_url$" title="Website: $user_url$">'
								.get_icon( 'www', 'imgtag', array( 'class' => 'middle', 'title' => 'Website: $user_url$' ) ).'</a>\', \'&nbsp;\' )¤',
					);

if( isset($collections_Module) )
{	// We are handling blogs:
	$Results->cols[] = array(
							'th' => T_('Blogs'),
							'order' => 'nb_blogs',
							'th_class' => 'shrinkwrap',
							'td_class' => 'center',
							'td' => '¤conditional( (#nb_blogs# > 0), #nb_blogs#, \'&nbsp;\' )¤',
						);
}

if( $current_User->check_perm( 'users', 'edit', false ) )
{
	$Results->cols[] = array(
						'th' => T_('Source'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'center',
						'order' => 'user_source',
						'default_dir' => 'D',
						'td' => '$user_source$',
					);
}

if( ! $current_User->check_perm( 'users', 'edit', false ) )
{
	$Results->cols[] = array(
						'th' => T_('Level'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'right',
						'order' => 'user_level',
						'default_dir' => 'D',
						'td' => '$user_level$',
					);
}
else
{
	$Results->cols[] = array(
						'th' => /* TRANS: email Confirmed */ T_('C'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'user_validated',
						'default_dir' => 'D',
						'td' => '%get_icon( (#user_validated# ? "allowback" : "ban"), "imgtag" ,(#user_validated# ? array("title" => "'.T_( 'Email address has been confirmed' ).'"):array( ) ) )%',
					);

	function display_level( $user_level, $user_ID )
	{
		$r = '';
		if( $user_level > 0)
		{
			$r .= action_icon( TS_('Decrease user level'), 'decrease',
							regenerate_url( 'action', 'action=promote&amp;prom=down&amp;user_ID='.$user_ID.'&amp;'.url_crumb('user') ) );
		}
		else
		{
			$r .= get_icon( 'decrease', 'noimg' );
		}
		$r .= sprintf( '<code>% 2d </code>', $user_level );
		if( $user_level < 10 )
		{
			$r.= action_icon( TS_('Increase user level'), 'increase',
							regenerate_url( 'action', 'action=promote&amp;prom=up&amp;user_ID='.$user_ID.'&amp;'.url_crumb('user') ) );
		}
		else
		{
	  	$r .= get_icon( 'increase', 'noimg' );
		}
		return $r;
	}
	$Results->cols[] = array(
						'th' => T_('Level'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'user_level',
						'default_dir' => 'D',
						'td' => '%display_level( #user_level#, #user_ID# )%',
					);

	$Results->cols[] = array(
						'th' => T_('Actions'),
						'td_class' => 'shrinkwrap',
						'td' => action_icon( T_('Edit this user...'), 'edit', '%regenerate_url( \'ctrl,action\', \'ctrl=user&amp;user_ID=$user_ID$&amp;user_tab=profile\' )%' )
										.action_icon( T_('Duplicate this user...'), 'copy', '%regenerate_url( \'ctrl,action\', \'ctrl=user&amp;action=new&amp;user_ID=$user_ID$&amp;user_tab=profile\' )%' )
										.'¤conditional( (#user_ID# != 1) && (#nb_blogs# < 1) && (#user_ID# != '.$current_User->ID.'), \''
											.action_icon( T_('Delete this user!'), 'delete',
												'%regenerate_url( \'action\', \'action=delete&amp;user_ID=$user_ID$&amp;'.url_crumb('user').'\' )%' ).'\', \''
	                    .get_icon( 'delete', 'noimg' ).'\' )¤'
					);
}


// Display result :
$Results->display();


/*
 * $Log: _user_list.view.php,v $
 */
?>