<?php
/**
 * This file implements the UI view for the browser hits summary.
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
 * @version $Id: _stats_view.funcs.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Helper function for "Requested URI" column
 * @param integer Blog ID
 * @param string Requested URI
 * @return string
 */
function stats_format_req_URI( $hit_blog_ID, $hit_uri, $max_len = 40 )
{
	if( !empty( $hit_blog_ID ) )
	{
		$BlogCache = & get_BlogCache();
		$tmp_Blog = & $BlogCache->get_by_ID( $hit_blog_ID );
		$full_url = $tmp_Blog->get('baseurlroot').$hit_uri;
	}
	else
	{
		$full_url = $hit_uri;
	}

	if( evo_strlen($hit_uri) > $max_len )
	{
		$hit_uri = '...'.evo_substr( $hit_uri, -$max_len );
	}

	return '<a href="'.$full_url.'">'.$hit_uri.'</a>';
}


/**
 * display avatar and login linking to sessions list for user
 *
 * @param mixed $login
 */
function stat_session_login( $login )
{
	if( empty($login) )
	{
		return '<span class="note">'.T_('Anon.').'</span>';
	}

	return get_user_identity_link( $login, NULL, 'admin' );
}


/**
 * Display user sessions
 * 
 * @param string user login
 * @param string link text
 */
function stat_user_sessions( $login,  $link_text )
{
	return '<strong><a href="?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;user='.$login.'">'.$link_text.'</a></strong>';
}


/**
 * Display session hits
 * 
 * @param string session ID
 * @param string link text
 */
function stat_session_hits( $sess_ID,  $link_text )
{
	return '<strong><a href="?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID='.$sess_ID.'">'.$link_text.'</a></strong>';
}


/*
 * $Log: _stats_view.funcs.php,v $
 */
?>