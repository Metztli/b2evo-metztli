<?php
/**
 * This is the handler for ANONYMOUS (non logged in) asynchronous 'AJAX' calls.
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
 * @package evocore
 * 
 * @version $Id: anon_async.php 419 2011-11-25 16:22:08Z sam2kb $
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

load_funcs( '../inc/skins/_skin.funcs.php' );

global $skins_path;
param( 'action', 'string', '' );
$item_ID = param( 'p', 'integer' );
$blog_ID = param( 'blog', 'integer' );

// Make sure the async responses are never cached:
header_nocache();
header_content_type( 'text/html', $io_charset );

// Do not append Debuglog to response!
$debug = false;

$params = param( 'params', 'array', array() );
switch( $action )
{
	case 'get_comment_form':
		// display comment form
		$ItemCache = & get_ItemCache();
		$Item = $ItemCache->get_by_ID( $item_ID );
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );

		locale_activate( $Blog->get('locale') );

		// Re-Init charset handling, in case current_charset has changed:
		if( init_charsets( $current_charset ) )
		{
			// Reload Blog(s) (for encoding of name, tagline etc):
			$BlogCache->clear();
			$Blog = & $BlogCache->get_by_ID( $blog_ID );
		}

		$disp = param( 'disp', 'string', '' );
		$skin = '';
		if( !empty( $Blog->skin_ID ) )
		{ // check if Blog skin has specific comment form
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
			$skin = $Skin->folder.'/';
			if( ! file_exists( $skins_path.$skin.'_item_comment_form.inc.php' ) )
			{
				$skin = '';
			}
		}

		require $skins_path.$skin.'_item_comment_form.inc.php';
		break;

	case 'get_msg_form':
		// display send message form
		$recipient_id = param( 'recipient_id', 'integer', 0 );
		$recipient_name = param( 'recipient_name', 'string', '' );
		$subject = param( 'subject', 'string', '' );
		$email_author = param( 'email_author', 'string', '' );
		$email_author_address = param( 'email_author_address', 'string', '' );
		$allow_msgform = param( 'allow_msgform', 'string', '' );
		$redirect_to = param( 'redirect_to', 'string', '' );
		$post_id = NULL;
		$comment_id = NULL;
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );

		locale_activate( $Blog->get('locale') );

		// Re-Init charset handling, in case current_charset has changed:
		if( init_charsets( $current_charset ) )
		{
			// Reload Blog(s) (for encoding of name, tagline etc):
			$BlogCache->clear();
			$Blog = & $BlogCache->get_by_ID( $blog_ID );
		}

		require $skins_path.'_contact_msg.form.php';
		break;
}

exit();

/*
 * $Log: anon_async.php,v $
 */
?>