<?php
/**
 * This file implements the UI view for the Direct accesses stats.
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
 * @version $Id: _stats_direct.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url;

?>
<h2><?php echo T_('Direct browser hits') ?></h2>
<p class="notes"><?php echo T_('These are browser hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)') ?></p>
<?php
// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE hit_ID, hit_datetime, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname' );
$SQL->FROM( 'T_hitlog LEFT JOIN T_blogs ON hit_blog_ID = blog_ID' );
$SQL->WHERE( 'hit_referer_type = "direct" AND hit_agent_type = "browser"' );
if( ! empty( $blog ) )
	$SQL->WHERE_and( 'hit_blog_ID = ' . $blog );

$Results = new Results( $SQL->get(), 'lstref_', 'D' );

$Results->title = T_('Direct browser hits');

// datetime:
$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_ID', // This field is index, much faster than actually sorting on the datetime!
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( \'$hit_datetime$\' )%',
	);

// Referer:
if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[] = array(
			'th' => /* TRANS: Abbrev. for Delete (stats) */ T_('Del'),
			'td_class' => 'center',
			'td' => ' <a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
						 .T_('Delete this hit!').'">'.get_icon('delete').'</a>',
		);
}

// Target Blog:
if( empty($blog) )
{
	$Results->cols[] = array(
			'th' => T_('Target Blog'),
			'order' => 'hit_blog_ID',
			'td' => '$blog_shortname$',
		);
}

// Requested URI (linked to blog's baseurlroot+URI):
$Results->cols[] = array(
		'th' => T_('Requested URI'),
		'order' => 'hit_uri',
		'td' => '%stats_format_req_URI( #hit_blog_ID#, #hit_uri# )%',
	);

// Remote address (IP):
$Results->cols[] = array(
		'th' => T_('Remote IP'),
		'order' => 'hit_remote_addr',
		'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'FilterIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
	);

// Display results:
$Results->display();

/*
 * $Log: _stats_direct.view.php,v $
 */
?>