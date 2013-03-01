<?php
/**
 * This file implements misc functions that handle output of the HTML page.
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
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _template.funcs.php 918 2012-02-27 18:53:39Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template tag. Output content-type header
 *
 * @param string content-type; override for RSS feeds
 */
function header_content_type( $type = 'text/html', $charset = '#' )
{
	global $io_charset;
	global $content_type_header;

	$content_type_header = 'Content-type: '.$type;

	if( !empty($charset) )
	{
		if( $charset == '#' )
		{
			$charset = $io_charset;
		}

		$content_type_header .= '; charset='.$charset;
	}

	header( $content_type_header );
}


/**
 * This is a placeholder for future development.
 *
 * @param string content-type; override for RSS feeds
 * @param integer seconds
 */
function headers_content_mightcache( $type = 'text/html', $max_age = '#', $charset = '#' )
{
	global $Messages, $is_admin_page;

	header_content_type( $type, $charset );

	if( empty($max_age) || $is_admin_page || is_logged_in() || $Messages->count() )
	{	// Don't cache if no max_age given
		// + NEVER EVER allow admin pages to cache
		// + NEVER EVER allow logged in data to be cached
		// + NEVER EVER allow transactional Messages to be cached!:
		header_nocache();
		return;
	}

	// If we are on a "normal" page, we may, under some circumstances, tell the browser it can cache the data.
	// This MAY be extremely confusing though, every time a user logs in and gets back to a screen with no evobar!
	// This cannot be enabled by default and requires admin switches.

	// For feeds, it is a little bit less confusing. We might want to have the param enabled by default in that case.

	// WARNING: extra special care needs to be taken before ever caching a blog page that might contain a form or a comment preview
	// having user details cached would be extremely bad.

	// in the meantime...
	header_nocache();
}


/**
 * Sends HTTP header to redirect to the previous location (which
 * can be given as function parameter, GET parameter (redirect_to),
 * is taken from {@link Hit::$referer} or {@link $baseurl}).
 *
 * {@link $Debuglog} and {@link $Messages} get stored in {@link $Session}, so they
 * are available after the redirect.
 *
 * NOTE: This function {@link exit() exits} the php script execution.
 *
 * @todo fp> do NOT allow $redirect_to = NULL. This leads to spaghetti code and unpredictable behavior.
 *
 * @param string Destination URL to redirect to
 * @param boolean|integer is this a permanent redirect? if true, send a 301; otherwise a 303 OR response code 301,302,303
 */
function header_redirect( $redirect_to = NULL, $status = false )
{
	global $Hit, $baseurl, $Blog, $htsrv_url_sensitive;
	global $Session, $Debuglog, $Messages;
	global $http_response_code;

	// TODO: fp> get this out to the caller, make a helper func like get_returnto_url()
	if( empty($redirect_to) )
	{ // see if there's a redirect_to request param given:
		$redirect_to = param( 'redirect_to', 'string', '' );

		if( empty($redirect_to) )
		{
			if( ! empty($Hit->referer) )
			{
				$redirect_to = $Hit->referer;
			}
			elseif( isset($Blog) && is_object($Blog) )
			{
				$redirect_to = $Blog->get('url');
			}
			else
			{
				$redirect_to = $baseurl;
			}
		}
		elseif( $redirect_to[0] == '/' )
		{ // relative URL, prepend current host:
			global $ReqHost;
			$redirect_to = $ReqHost.$redirect_to;
		}
	}
	// <fp

	if( $redirect_to[0] == '/' )
	{
		// TODO: until all calls to header_redirect are cleaned up:
		global $ReqHost;
		$redirect_to = $ReqHost.$redirect_to;
		// debug_die( '$redirect_to must be an absolute URL' );
	}

	if( strpos($redirect_to, $htsrv_url_sensitive) === 0 /* we're going somewhere on $htsrv_url_sensitive */
	 || strpos($redirect_to, $baseurl) === 0   /* we're going somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		// blueyed> Removed the removing of "action" here, as it is used to trigger certain views. Instead, "confirm(ed)?" gets removed now
		// fp> which views please (important to list in order to remove asap)
		// dh> sorry, don't remember
		// TODO: fp> action should actually not be used to trigger views. This should be changed at some point.
		// TODO: fp> confirm should be normalized to confirmed
		$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd|confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}

	if( is_integer($status) )
	{
		$http_response_code = $status;
	}
	else
	{
		$http_response_code = $status ? 301 : 303;
	}
 	$Debuglog->add('***** REDIRECT TO '.$redirect_to.' (status '.$http_response_code.') *****', 'request' );

	// Transfer of Debuglog to next page:
	if( $Debuglog->count('all') )
	{ // Save Debuglog into Session, so that it's available after redirect (gets loaded by Session constructor):
		$sess_Debuglogs = $Session->get('Debuglogs');
		if( empty($sess_Debuglogs) )
		{
			$sess_Debuglogs = array();
		}

		$sess_Debuglogs[] = $Debuglog;
		$Session->set( 'Debuglogs', $sess_Debuglogs, 60 /* expire in 60 seconds */ );
	 	// echo 'Passing Debuglog(s) to next page';
	 	// pre_dump( $sess_Debuglogs );
	}

	// Transfer of Messages to next page:
	if( $Messages->count() )
	{ // Set Messages into user's session, so they get restored on the next page (after redirect):
		$Session->set( 'Messages', $Messages );
	 // echo 'Passing Messages to next page';
	}

	if( ! empty($Session) )
	{
		$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.
	}

	// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	switch( $http_response_code )
	{
		case 301:
			// This should be a permanent move redirect!
			header( 'HTTP/1.1 301 Moved Permanently' );
			break;

		case 303:
			// This should be a "follow up" redirect
			// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
			header( 'HTTP/1.1 303 See Other' );
			break;

		case 302:
		default:
			header( 'HTTP/1.1 302 Found' );
	}

	if( headers_sent() )
	{
		debug_die('Headers have already been sent. Cannot <a href="'.htmlspecialchars($redirect_to).'">redirect</a>.');
	}
	header( 'Location: '.$redirect_to, true, $http_response_code ); // explictly setting the status is required for (fast)cgi
	exit(0);
}



/**
 * Sends HTTP headers to avoid caching of the page at the browser level
 * (at least without revalidating with the server to make sure whether the content has changed or not).
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
function header_nocache( $timestamp = NULL )
{
	global $servertimenow;
	if( empty($timestamp) )
	{
		$timestamp = $servertimenow;
	}

	header('Expires: '.gmdate('r',$timestamp));
	header('Last-Modified: '.gmdate('r',$timestamp));
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
}


/**
 * This is to "force" (strongly suggest) caching.
 *
 * WARNING: use this only for STATIC content that does NOT depend on the current user.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
function header_noexpire()
{
	global $servertimenow;
	header('Expires: '.gmdate('r', $servertimenow + 31536000)); // 86400*365 (1 year)
}


/**
 * Generate an etag to identify the version of the current page.
 * We use this primarily to make a difference between the same page that has been generated for anonymous users
 * and a version that has been generated for a specific user.
 *
 * A common problem without this would be that when users log out, the page cache would tell them "304 Not Modified"
 * based on the date of the cache and then the browser would show a locally cached version of the page that includes
 * the evobar.
 *
 * When a specific user logs out, the browser will send back the Etag of the logged in version it got and we will
 * be able to detect that this is not a "304 Not Modified" case -> we will send back the anonymou version of the page.
 */
function gen_current_page_etag()
{
	global $current_User, $Messages;

	if( isset($current_User) )
	{
		$etag = 'user:'.$current_User->ID;
	}
	else
	{
		$etag = 'user:anon';
	}

	if( $Messages->count() )
	{	// This case has never been observed yet, but let's forward protect us against client side cached messages
		$etag .= '-msg:'.md5($Messages->get_string('',''));
	}

	return '"'.$etag.'"';
}


/**
 * This adds teh etag header
 *
 * @param string etag MUST be "quoted"
 */
function header_etag( $etag )
{
	header( 'ETag: '.$etag );
}


/**
 * Display a global title matching filter params
 *
 * Outputs the title of the category when you load the page with <code>?cat=</code>
 * Display "Archive Directory" title if it has been requested
 * Display "Latest comments" title if these have been requested
 * Display "Statistics" title if these have been requested
 * Display "User profile" title if it has been requested
 *
 * @todo single month: Respect locales datefmt
 * @todo single post: posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 * @todo make it complete with all possible params!
 *
 * @param array params
 *        - "auto_pilot": "seo_title": Use the SEO title autopilot. (Default: "none")
 */
function request_title( $params = array() )
{
	global $MainList, $preview, $disp, $action;

	$r = array();

	$params = array_merge( array(
			'auto_pilot'          => 'none',
			'title_before'        => '',
			'title_after'         => '',
			'title_none'          => '',
			'title_single_disp'   => true,
			'title_single_before' => '#',
			'title_single_after'  => '#',
			'title_page_disp'     => true,
			'title_page_before'   => '#',
			'title_page_after'    => '#',
			'glue'                => ' - ',
			'format'              => 'htmlbody',
			'arcdir_text'         => T_('Archive Directory'),
			'catdir_text'         => T_('Category Directory'),
			'mediaidx_text'       => T_('Photo Index'),
			'postidx_text'        => T_('Post Index'),
			'search_text'         => T_('Search'),
			'sitemap_text'        => T_('Site Map'),
			'msgform_text'        => T_('Sending a message'),
			'login_text'          => /* TRANS: trailing space = verb */ T_('Login '),
			'req_validatemail'    => T_('Email validation'),
			'register_text'       => T_('Register'),
			'register_complete'   => T_('Registration complete'),
			'register_validation' => T_('Account email validation'),
			'profile_text'        => T_('User Profile'),
			'avatar_text'         => T_('Profile picture'),
			'pwdchange_text'      => T_('Password change'),
			'userprefs_text'      => T_('User preferences'),
			'user_text'           => T_('User'),
			'subs_text'           => T_('Subscriptions'),
			'comments_text'       => T_('Latest Comments'),
			'feedback-popup_text' => T_('Feedback'),
		), $params );

	if( $params['auto_pilot'] == 'seo_title' )
	{	// We want to use the SEO title autopilot. Do overrides:
		global $Blog;
		$params['format'] = 'htmlhead';
		$params['title_after'] = $params['glue'].$Blog->get('name');
		$params['title_single_after'] = '';
		$params['title_page_after'] = '';
		$params['title_none'] = $Blog->dget('name','htmlhead');
	}


	$before = $params['title_before'];
	$after = $params['title_after'];

	switch( $disp )
	{
		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = $params['arcdir_text'];
			break;

		case 'catdir':
			// We are requesting the archive directory:
			$r[] = $params['catdir_text'];
			break;

		case 'mediaidx':
			$r[] = $params['mediaidx_text'];
			break;

		case 'postidx':
			$r[] = $params['postidx_text'];
			break;

		case 'sitemap':
			$r[] = $params['sitemap_text'];
			break;

		case 'search':
			$r[] = $params['search_text'];
			break;

		case 'comments':
			// We are requesting the latest comments:
			global $Item;
			if( isset( $Item ) )
			{
				$r[] = sprintf( $params['comments_text'] . T_(' on %s'), $Item->get('title') );
			}
			else
			{
				$r[] = $params['comments_text'];
			}
			break;

		case 'feedback-popup':
			// We are requesting the comments on a specific post:
			// Should be in first position
			$Item = & $MainList->get_by_idx( 0 );
			$r[] = sprintf( $params['feedback-popup_text'] . T_(' on %s'), $Item->get('title') );
			break;

		case 'profile':
			// We are requesting the user profile:
			$r[] = $params['profile_text'];
			break;

		case 'avatar':
			// We are requesting the user avatar:
			$r[] = $params['avatar_text'];
			break;

		case 'pwdchange':
			// We are requesting the user change password:
			$r[] = $params['pwdchange_text'];
			break;

		case 'userprefs':
			// We are requesting the user preferences:
			$r[] = $params['userprefs_text'];
			break;

		case 'subs':
			// We are requesting the subscriptions screen:
			$r[] = $params['subs_text'];
			break;

		case 'msgform':
			// We are requesting the message form:
			$r[] = $params['msgform_text'];
			break;

		case 'login':
			// We are requesting the login form:
			if( $action == 'req_validatemail' )
			{
				$r[] = $params['req_validatemail'];
			}
			else
			{
				$r[] = $params['login_text'];
			}
			break;

		case 'register':
			// We are requesting the registration form:
			if( $action == 'reg_complete' )
			{ // registration complete
				$r[] = $params['register_complete'];
			}
			elseif( $action == 'reg_validation' )
			{ // registration complete, but needs email validation
				$r[] = $params['register_validation'];
			}
			else
			{ // register form
				$r[] = $params['register_text'];
			}
			break;

		case 'single':
		case 'page':
			// We are displaying a single message:
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = T_('PREVIEW');
			}
			elseif( $params['title_'.$disp.'_disp'] && isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			if( $params['title_'.$disp.'_before'] != '#' )
			{
				$before = $params['title_'.$disp.'_before'];
			}
			if( $params['title_'.$disp.'_after'] != '#' )
			{
				$after = $params['title_'.$disp.'_after'];
			}
			break;

		case 'user':
			// We are requesting the message form:
			$r[] = $params['user_text'];
			break;

		default:
			if( isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			break;
	}


	if( ! empty( $r ) )
	{	// We have at leats one title match:
		$r = implode( $params['glue'], $r );
		if( ! empty( $r ) )
		{	// This is in case we asked for an empty title (e-g for search)
			$r = $before.format_to_output( $r, $params['format'] ).$after;
		}
	}
	elseif( !empty( $params['title_none'] ) )
	{
		$r = $params['title_none'];
	}

	if( !empty( $r ) )
	{ // We have something to display:
		echo $r;
	}

}


/**
 * Returns a "<base />" tag and remembers that we've used it ({@link regenerate_url()} needs this).
 *
 * @param string URL to use (this gets used as base URL for all relative links on the HTML page)
 * @return string
 */
function base_tag( $url, $target = NULL )
{
	global $base_tag_set;
	$base_tag_set = $url;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo " />\n";
}


/**
 * Robots tag
 *
 * Outputs the robots meta tag if necessary
 */
function robots_tag()
{
	global $robots_index, $robots_follow;

	if( is_null($robots_index) && is_null($robots_follow) )
	{
		return;
	}

	$r = '<meta name="robots" content="';

	if( $robots_index === false )
		$r .= 'NOINDEX';
	else
		$r .= 'INDEX';

	$r .= ',';

	if( $robots_follow === false )
		$r .= 'NOFOLLOW';
	else
		$r .= 'FOLLOW';

	$r .= '" />'."\n";

	echo $r;
}


/**
 * Output a link to current blog.
 *
 * We need this function because if no Blog is currently active (some admin pages or site pages)
 * then we'll go to the general home.
 */
function blog_home_link( $before = '', $after = '', $blog_text = 'Blog', $home_text = 'Home' )
{
	global $Blog, $baseurl;

	if( !empty( $Blog ) )
	{
		echo $before.'<a href="'.$Blog->get( 'url' ).'">'.$blog_text.'</a>'.$after;
	}
	elseif( !empty($home_text) )
	{
		echo $before.'<a href="'.$baseurl.'">'.$home_text.'</a>'.$after;
	}
}


/**
 * Memorize that a specific javascript file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/js directory and certain aliases, like 'jquery' and 'jquery_debug'
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug' is automatically swapped in.
 * Any javascript added to the page is also added to the $required_js array, which is then checked to prevent adding the same code twice
 *
 * @todo dh>merge with require_css()
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean Is the file's path relative to the base path/url? -- Use false if file is in $rsc_url/js/
 */
function require_js( $js_file, $relative_to_base = false )
{
	global $rsc_url, $debug, $app_version;
	static $required_js;

	$js_aliases = array(
		'#jquery#' => 'jquery.min.js',
		'#jquery_debug#' => 'jquery.js',
		'#jqueryUI#' => 'jquery/jquery.ui.all.min.js',
		'#jqueryUI_debug#' => 'jquery/jquery.ui.all.js',
	);

	if( in_array( $js_file, array( '#jqueryUI#', '#jqueryUI_debug#' ) ) )
	{	// Dependency : ensure jQuery is loaded
		require_js( '#jquery#' );
	}
	elseif( $js_file == 'communication.js' )
	{ // jQuery dependency
		require_js( '#jquery#' );
	}

	// First get the real filename or url
	$absolute = FALSE;
	if( preg_match('~^https?://~', $js_file ) )
	{ // It's an absolute url
		$js_url = $js_file;
		$absolute = TRUE;
	}
	elseif( !empty( $js_aliases[$js_file]) )
	{ // It's an alias
		if ( $js_file == '#jquery#' && $debug ) $js_file = '#jquery_debug#';
		$js_file = $js_aliases[$js_file];
	}

	if( $relative_to_base || $absolute )
	{
		$js_url = $js_file;
	}
	else
	{
		$js_url = $rsc_url.'js/'.$js_file;
	}

	// Be sure to get a fresh copy of this JS file after application upgrades:
	$js_url = url_add_param( $js_url, 'v='.$app_version );

	// Add to headlines, if not done already:
	if( empty( $required_js ) || ! in_array( strtolower($js_url), $required_js ) )
	{
		$required_js[] = strtolower($js_url);
		add_headline( '<script type="text/javascript" src="'.$js_url.'"></script>' );
	}
}


/**
 * Memorize that a specific css that file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/css directory.
 * Set $relative_to_base to TRUE to prevent this function from adding on the rsc_path
 *
 * @todo dh>merge with require_js()
 * @param string alias, url or filename (relative to rsc/css) for CSS file
 * @param boolean|string Is the file's path relative to the base path/url? -- Use true to not add any prefix ("$rsc_url/css/").
 * @param string title.  The title for the link tag
 * @param string media.  ie, 'print'
 */
function require_css( $css_file, $relative_to_base = false, $title = NULL, $media = NULL, $version = '#' )
{
	global $rsc_url, $debug, $app_version;
	static $required_css;

	// First get the real filename or url
	$absolute = FALSE;
	if( preg_match('~^https?://~', $css_file ) )
	{ // It's an absolute url
		$css_url = $css_file;
		$absolute = TRUE;
	}

	if( $relative_to_base || $absolute )
	{
		$css_url = $css_file;
	}
	else
	{
		$css_url = $rsc_url . 'css/' . $css_file;
	}

	if( !empty($version) )
	{	// Be sure to get a fresh copy of this CSS file after application upgrades:
		if( $version == '#' )
		{
			$version = $app_version;
		}
		$css_url = url_add_param( $css_url, 'v='.$version );
	}

	// Add to headlines, if not done already:
	// fp> TODO: check for url without version to avoid duplicate load due to lack of verison in @import statements
	if( empty( $required_css ) || ! in_array( strtolower($css_url), $required_css ) )
	{
		$required_css[] = strtolower($css_url);

		$start_link_tag = '<link rel="stylesheet"';
		if ( !empty( $title ) ) $start_link_tag .= ' title="' . $title . '"';
		if ( !empty( $media ) ) $start_link_tag .= ' media="' . $media . '"';
		$start_link_tag .= ' type="text/css" href="';
		$end_link_tag = '" />';
		add_headline( $start_link_tag . $css_url . $end_link_tag );
	}

}


/**
 * Memorize that a specific js helper will be required by the current page.
 * This allows to require JS + SS + do init.
 *
 * All requested helpers will be included in the page head only once (when headlines is called)
 * Requested helpers should add their required translation strings and any other settings
 *
 * @param string helper, name of the required helper
 */
function require_js_helper( $helper = '' )
{
	static $helpers;

	if( empty( $helpers ) || !in_array( $helper, $helpers ) )
	{ // Helper not already added, add the helper:

		switch( $helper )
		{
			case 'helper' :
				// main helper object required
				global $debug;
				require_js( '#jquery#' ); // dependency
				require_js( 'helper.js' );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoHelper.Init({
						debug:'.( $debug ? 'true' : 'false' ).'
					});
				});');
				break;

			case 'communications' :
				// communications object required
				require_js_helper('helper'); // dependency

				global $dispatcher;
				require_js( 'communication.js' );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoCommunications.Init({
						dispatcher:"'.$dispatcher.'"
					});
				});' );
				// add translation strings
				T_('Update cancelled', NULL, array( 'for_helper' => true ) );
				T_('Update paused', NULL, array( 'for_helper' => true ) );
				T_('Changes pending', NULL, array( 'for_helper' => true ) );
				T_('Saving changes', NULL, array( 'for_helper' => true ) );
				break;

			case 'colorbox':
				// Colorbox: a lightweight Lightbox alternative -- allows zooming on images and slideshows in groups of images
				// Added by fplanque - (MIT License) - http://colorpowered.com/colorbox/
				require_js( '#jqueryUI#' );
				require_js( 'colorbox/jquery.colorbox-min.js' );
				require_css( 'colorbox/colorbox.css' );
				add_js_headline('jQuery(document).ready(function()
						{
							$("a[rel^=\'lightbox\']").colorbox({maxWidth:"95%", maxHeight:"90%", slideshow:true, slideshowAuto:false });
						});' );
				// TODO: translation strings
				break;
		}
		// add to list of loaded helpers
		$helpers[] = $helper;
	}
}

/**
 * Memorize that a specific translation will be required by the current page.
 * All requested translations will be included in the page body only once (when footerlines is called)
 *
 * @param string string, untranslated string
 * @param string translation, translated string
 */
function add_js_translation( $string, $translation )
{
	global $js_translations;
	if( $string != $translation )
	{ // it's translated
		$js_translations[ $string ] = $translation;
	}
}


/**
 * Add a headline, which then gets output in the HTML HEAD section.
 * If you want to include CSS or JavaScript files, please use
 * {@link require_css()} and {@link require_js()} instead.
 * This avoids duplicates and allows caching/concatenating those files
 * later (not implemented yet)
 * @param string
 */
function add_headline($headline)
{
	global $headlines;
	$headlines[] = $headline;
}


/**
 * Add a Javascript headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string Javascript
 */
function add_js_headline($headline)
{
	add_headline("<script type=\"text/javascript\">\n\t/* <![CDATA[ */\n\t\t"
		.$headline."\n\t/* ]]> */\n\t</script>");
}


/**
 * Add a CSS headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string CSS
 */
function add_css_headline($headline)
{
	add_headline("<style type=\"text/css\">\n\t".$headline."\n\t</style>");
}


/**
 * Registers all the javascripts needed by the toolbar menu
 *
 * @todo fp> include basic.css ? -- rename to add_headlines_for* -- potential problem with inclusion order of CSS files!!
 *       dh> would be nice to have the batch of CSS in a separate file. basic.css would get included first always, then e.g. this toolbar.css.
 */
function add_js_for_toolbar()
{
	if( ! is_logged_in() )
	{ // the toolbar (blogs/skins/_toolbar.inc.php) gets only used when logged in.
		return false;
	}

	require_js( '#jquery#' );
	require_js( 'functions.js' );	// for rollovers AddEvent - TODO: change to jQuery
	require_js( 'rollovers.js' );	// TODO: change to jQuery
	// Superfish menus:
	require_js( 'hoverintent.js' );
	require_js( 'superfish.js' );
	add_js_headline( '
		jQuery( function() {
			jQuery("ul.sf-menu").superfish({
	            delay: 500, // mouseout
	            animation: {opacity:"show",height:"show"},
	            speed: "fast"
	        });
		} )');

	return true;
}


/**
 * Registers headlines required by AJAX forms, but only if javascript forms are enabled in blog settings.
 */
function init_ajax_forms( $relative_to = 'blog' )
{
	global $Blog;

	if( !empty($Blog) && $Blog->get_setting('ajax_form_enabled') )
	{
		require_js( 'communication.js' ); # No relative_to param in 4.1.x
	}
}


/**
 * Outputs the collected HTML HEAD lines.
 * @see add_headline()
 * @return string
 */
function include_headlines()
{
	global $headlines;

	if( $headlines )
	{
		echo "\n\t<!-- headlines: -->\n\t".implode( "\n\t", $headlines );
		echo "\n\n";
	}
}


/**
 * Outputs the collected translation lines before </body>
 *
 * yabs > Should this be expanded to similar functionality to headlines?
 *
 * @see add_js_translation()
 */
function include_footerlines()
{
	global $js_translations;
	if( empty( $js_translations ) )
	{ // nothing to do
		return;
	}
	$r = '';

	foreach( $js_translations as $string => $translation )
	{ // output each translation
		if( $string != $translation )
		{ // this is translated
			$r .= '<div><span class="b2evo_t_string">'.$string.'</span><span class="b2evo_translation">'.$translation.'</span></div>'."\n";
		}
	}
	if( $r )
	{ // we have some translations
		echo '<div id="b2evo_translations" style="display:none;">'."\n";
		echo $r;
		echo '</div>'."\n";
	}
}


/**
 * Template tag.
 */
function app_version()
{
	global $app_version;
	echo $app_version;
}


/**
 * Displays an empty or a full bullet based on boolean
 *
 * @param boolean true for full bullet, false for empty bullet
 */
function bullet( $bool )
{
	if( $bool )
		return get_icon( 'bullet_full', 'imgtag' );

	return get_icon( 'bullet_empty', 'imgtag' );
}




/**
 * Stub: Links to previous and next post in single post mode
 */
function item_prevnext_links( $params = array() )
{
	global $MainList;

	$params = array_merge( array( 'target_blog' => 'auto' ), $params );

	if( isset($MainList) )
	{
		$MainList->prevnext_item_links( $params );
	}
}


/**
 * Stub
 */
function messages( $params = array() )
{
	global $Messages;

	$Messages->disp( $params['block_start'], $params['block_end'] );
}


/**
 * Stub: Links to list pages:
 */
function mainlist_page_links( $params = array() )
{
	global $MainList;

	if( isset($MainList) )
	{
		$MainList->page_links( $params );
	}
}


/**
 * Stub
 *
 * Sets $Item ion global scope
 *
 * @return Item
 */
function & mainlist_get_item()
{
	global $MainList, $featured_displayed_item_ID;

	if( isset($MainList) )
	{
		$Item = & $MainList->get_item();

		if( $Item && $Item->ID == $featured_displayed_item_ID )
		{	// This post was already displayed as a Featured post, let's skip it and get the next one:
			$Item = & $MainList->get_item();
		}
	}
	else
	{
		$Item = NULL;
	}

	$GLOBALS['Item'] = & $Item;

	return $Item;
}


/**
 * Stub
 *
 * @return boolean true if empty MainList
 */
function display_if_empty( $params = array() )
{
	global $MainList, $featured_displayed_item_ID;

	if( isset($MainList) && empty($featured_displayed_item_ID) )
	{
		return $MainList->display_if_empty( $params );
	}

	return NULL;
}


/**
 * Template tag for credits
 *
 * Note: You can limit (and even disable) the number of links being displayed here though the Admin interface:
 * Blog Settings > Advanced > Software credits
 *
 * @param array
 */
function credits( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;
	global $Blog;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'list_start'  => ' ',
			'list_end'    => ' ',
			'item_start'  => ' ',
			'item_end'    => ' ',
			'separator'   => ',',
			'after_item'  => '#',
		), $params );


	$cred_links = $global_Cache->get( 'creds' );
	if( empty( $cred_links ) )
	{	// Use basic default:
		$cred_links = unserialize('a:2:{i:0;a:2:{i:0;s:24:"http://b2evolution.net/r";i:1;s:18:"free blog software";}i:1;a:2:{i:0;s:36:"http://b2evolution.net/web-hosting/r";i:1;s:19:"quality web hosting";}}');
	}

	$max_credits = (empty($Blog) ? NULL : $Blog->get_setting( 'max_footer_credits' ));

	display_list( $cred_links, $params['list_start'], $params['list_end'], $params['separator'], $params['item_start'], $params['item_end'], NULL, $max_credits );
}


/**
 * Display rating as 5 stars
 */
function star_rating( $stars, $class = 'middle' )
{
	if( is_null($stars) )
	{
		return;
	}

	for( $i=1; $i<=5; $i++ )
	{
		if( $i <= $stars )
		{
			echo get_icon( 'star_on', 'imgtag', array( 'class'=>$class ) );
		}
		elseif( $i-.5 <= $stars )
		{
			echo get_icon( 'star_half', 'imgtag', array( 'class'=>$class ) );
		}
		else
		{
			echo get_icon( 'star_off', 'imgtag', array( 'class'=>$class ) );
		}
	}
}


/**
 * Display "powered by b2evolution" logo
 */
function powered_by( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;

	global $rsc_url;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'block_start' => '<div class="powered_by">',
			'block_end'   => '</div>',
			'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
			'img_width'   => '',
			'img_height'  => '',
		), $params );

	echo $params['block_start'];

	$img_url = str_replace( '$rsc$', $rsc_url, $params['img_url'] );

	$evo_links = $global_Cache->get( 'evo_links' );
	if( empty( $evo_links ) )
	{	// Use basic default:
		$evo_links = unserialize('a:1:{s:0:"";a:1:{i:0;a:3:{i:0;i:100;i:1;s:23:"http://b2evolution.net/";i:2;a:2:{i:0;a:2:{i:0;i:55;i:1;s:36:"powered by b2evolution blog software";}i:1;a:2:{i:0;i:100;i:1;s:29:"powered by free blog software";}}}}}');
	}

	echo resolve_link_params( $evo_links, NULL, array(
			'type'        => 'img',
			'img_url'     => $img_url,
			'img_width'   => $params['img_width'],
			'img_height'  => $params['img_height'],
			'title'       => 'b2evolution: next generation blog software',
		) );

	echo $params['block_end'];
}



/**
 * DEPRECATED
 */
function bloginfo( $what )
{
	global $Blog;
	$Blog->disp( $what );
}

/**
 * Display allowed tags for comments
 * (Mainly provided for WP compatibility. Not recommended for use)
 *
 * @param string format
 */
function comment_allowed_tags( $format = 'htmlbody' )
{
	global $comment_allowed_tags;

	echo format_to_output( $comment_allowed_tags, $format );
}

/**
 * DEPRECATED
 */
function link_pages()
{
	echo '<!-- link_pages() is DEPRECATED -->';
}


/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	return number_format( $hit_count * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}

function addup_percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	static $addup = 0;

	$addup += $hit_count;
	return number_format( $addup * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}


/**
 * Display a form (like comment or contact form) through an ajax call
 *
 * @param array params
 */
function display_ajax_form( $params )
{
	global $rsc_url, $htsrv_url;

	echo '<div class="section_requires_javascript">';

	// Needs json_encode function to create json type params
	$json_params = json_encode( $params );
	$ajax_loader = "<p class='ajax-loader'><img src='".$rsc_url."img/ajax-loader2.gif' /><br />".T_( 'Form is loading...' )."</p>";
	?>
	<script type="text/javascript">
		// display loader gif until the ajax call returns
		document.write( <?php echo '"'.$ajax_loader.'"'; ?> );

		function get_form()
		{
			$.ajax({
				url: '<?php echo $htsrv_url; ?>anon_async.php',
				type: 'POST',
				data: <?php echo $json_params; ?>,
				success: function(result)
					{
						$('.section_requires_javascript').html(result);
					}
			});
		}

		// get the form
		get_form();
	</script>
	<noscript>
		<?php echo '<p>'.T_( 'This section can only be displayed by javascript enabled browsers.' ).'</p>'; ?>
	</noscript>
	<?php
	echo '</div>';
}


/*
 * $Log: _template.funcs.php,v $
 */
?>