<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 * @author vegarg: Vegar BERG GULDAL
 *
 * @version $Id: stats.ctrl.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('sessions/model/_hitlist.class.php', 'Hitlist' );
load_funcs('sessions/model/_hitlog.funcs.php');

/**
 * @var User
 */
global $current_User;

global $dispatcher;

global $collections_Module;

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

// We set the default to -1 so that blog=0 will make its way into regenerate_url()s whenever watching global stats.
memorize_param( 'blog', 'integer', -1 );

$tab = param( 'tab', 'string', 'summary', true );
if( $tab == 'sessions' && (!$perm_view_all || $blog != 0) )
{	// Sessions tab is not narrowed down to blog level:
	$tab = 'summary';
}
$tab3 = param( 'tab3', 'string', '', true );

param( 'action', 'string' );

if( $blog == 0 )
{
	if( (!$perm_view_all) && isset($collections_Module) )
	{	// Find a blog we can view stats for:
		if( ! $selected = autoselect_blog( 'stats', 'view' ) )
		{ // No blog could be selected
			$Messages->add( T_('Sorry, there is no blog you have permission to view stats for.'), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{	// Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
	}
}

switch( $action )
{
	case 'changetype': // Change the type of a hit
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true );      // Required!
		param( 'new_hit_type', 'string', true ); // Required!

		Hitlist::change_type( $hit_ID, $new_hit_type );
		$Messages->add( sprintf( T_('Changed hit #%d type to: %s.'), $hit_ID, $new_hit_type), 'success' );
		break;


	case 'delete': // DELETE A HIT
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true ); // Required!

		if( Hitlist::delete( $hit_ID ) )
		{
			$Messages->add( sprintf( T_('Deleted hit #%d.'), $hit_ID ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('Could not delete hit #%d.'), $hit_ID ), 'note' );
		}
		break;


	case 'prune': // PRUNE hits for a certain date
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'stats' );
		
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'date', 'integer', true ); // Required!
		if( $r = Hitlist::prune( $date ) )
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('Deleted %d hits for %s.'), $r, date( locale_datefmt(), $date) ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('No hits deleted for %s.'), date( locale_datefmt(), $date) ), 'note' );
		}
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=stats', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}

if( $tab != 'sessions' )
{ // no need to show blogs list while displaying sessions

	if( isset($collections_Module) )
	{ // Display list of blogs:
		if( $perm_view_all )
		{
			$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ), T_('All'),
							$dispatcher.'?ctrl=stats&amp;tab='.$tab.'&amp;tab3='.$tab3.'&amp;blog=0' );
		}
		else
		{	// No permission to view aggregated stats:
			$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ) );
		}
	}
}

$AdminUI->breadcrumbpath_init();
switch( $tab )
{
	case 'summary':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Summary'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'global';
		}
		switch( $tab3 )
		{
			case 'global':
				$AdminUI->breadcrumbpath_add( T_('All'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'browser':
				$AdminUI->breadcrumbpath_add( T_('Browsers'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'robot':
				$AdminUI->breadcrumbpath_add( T_('Robots'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'feed':
				$AdminUI->breadcrumbpath_add( T_('RSS/Atom'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'other':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Direct hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'referers':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referred by other sites'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'refsearches':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Incoming searches'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'hits';
		}
		switch( $tab3 )
		{
			case 'hits':
				// $AdminUI->breadcrumbpath_add( T_('Latest'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'keywords':
				$AdminUI->breadcrumbpath_add( T_('Searched keywords'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'topengines':
				$AdminUI->breadcrumbpath_add( T_('Top search engines'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'domains':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referring domains'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'sessions':
		$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
		$AdminUI->breadcrumbpath_add( T_('Sessions'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'login';
		}
		switch( $tab3 )
		{
			case 'login':
				$AdminUI->breadcrumbpath_add( T_('Session by user'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
			case 'sessid':
				// fp> TODO: include username in path if we have one
				$AdminUI->breadcrumbpath_add( T_('Recent sessions'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
			case 'hits':
				$AdminUI->breadcrumbpath_add( T_('Recent hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'goals':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Goal tracking'), '?ctrl=goals' );
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->breadcrumbpath_add( T_('Goal hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
				break;
		}
		break;

}

if( $tab == 'sessions' )
{ // Show this sub-tab in Users tab
	$AdminUI->set_path( 'users', $tab, $tab3 );
	$AdminUI->title = T_('Stats');
}
else
{
	$AdminUI->set_path( 'stats', $tab, $tab3 );
	$AdminUI->title = T_('Stats');
}

if( ( $tab3 == 'keywords' ) || ( $tab == 'goals' && $tab3 == 'hits' ) )
{ // Load the data picker style for _stats_search_keywords.view.php and _stats_goalhits.view.php
	require_css( 'ui.datepicker.css' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

flush();

switch( $AdminUI->get_path(1) )
{
	case 'summary':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'browser':
				$AdminUI->disp_view( 'sessions/views/_stats_browserhits.view.php' );
				break;

			case 'robot':
				$AdminUI->disp_view( 'sessions/views/_stats_robots.view.php' );
				break;

			case 'feed':
				$AdminUI->disp_view( 'sessions/views/_stats_syndication.view.php' );
				break;

			case 'global':
			default:
				$AdminUI->disp_view( 'sessions/views/_stats_summary.view.php' );
		}
		break;

	case 'other':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_direct.view.php' );
		break;

	case 'referers':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_referers.view.php' );
		break;

	case 'refsearches':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_refsearches.view.php' );
				break;

			case 'keywords':
				$AdminUI->disp_view( 'sessions/views/_stats_search_keywords.view.php' );
				break;

			case 'topengines':
				$AdminUI->disp_view( 'sessions/views/_stats_search_engines.view.php' );
				break;
		}
		break;

	case 'domains':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_refdomains.view.php' );
		break;

	case 'sessions':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'sessid':
				$AdminUI->disp_view( 'sessions/views/_stats_sessions_list.view.php' );
				break;

			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_hit_list.view.php' );
				break;

			case 'login':
				$AdminUI->disp_view( 'sessions/views/_stats_sessions.view.php' );
		}
		break;

	case 'goals':
		// Display VIEW for Goal HITS:
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_goalhits.view.php' );
				break;
		}
		break;

}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: stats.ctrl.php,v $
 */
?>