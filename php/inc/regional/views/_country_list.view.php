<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _country_list.view.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_currency.class.php', 'Currency' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'ctry_ID, ctry_code, ctry_name, curr_shortcut, curr_code, ctry_enabled' );
$SQL->FROM( 'T_country	LEFT JOIN T_currency ON ctry_curr_ID=curr_ID' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE( 'CONCAT_WS( " ", ctry_code, ctry_name, curr_code ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'ctry_', '-A' );

$Results->title = T_('Countries list').get_manual_link('countries_list');

/*
 * STATUS TD:
 */
function ctry_td_enabled( $ctry_enabled, $ctry_ID )
{

	if( $ctry_enabled == true )
	{
		return get_icon('enabled', 'imgtag', array('title'=>T_('The country is enabled.')) );
	}
	else
	{
		return get_icon('disabled', 'imgtag', array('title'=>T_('The country is disabled.')) );
	}
}
$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'order' => 'ctry_enabled',
		'td' => '%ctry_td_enabled( #ctry_enabled#, #ctry_ID# )%',
		'td_class' => 'center'
	);

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_countries( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_countries',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=countries' ),
		)
	);

$Results->cols[] = array(
						'th' => T_('Code'),
						'td_class' => 'center',
						'order' => 'ctry_code',
						'td' => '<strong>$ctry_code$</strong>',
					);

/**
 * Template function: Display country flag
 *
 * @todo factor with locale_flag()
 *
 * @param string country code to use
 * @param string country name to use
 * @param string collection name (subdir of img/flags)
 * @param string name of class for IMG tag
 * @param string deprecated HTML align attribute
 * @param boolean to echo or not
 * @param mixed use absolute url (===true) or path to flags directory
 */
function country_flag( $country_code, $country_name, $collection = 'w16px', $class = 'flag', $align = '', $disp = true, $absoluteurl = true )
{
	global $rsc_path, $rsc_url;

	if( ! is_file( $rsc_path.'flags/'.$collection.'/'.$country_code.'.gif') )
	{ // File does not exist
		$country_code = 'default';
	}

	if( $absoluteurl !== true )
	{
		$iurl = $absoluteurl;
	}
	else
	{
		$iurl = $rsc_url.'flags';
	}

	$r = '<img src="'.$iurl.'/'.$collection.'/'.$country_code.'.gif" alt="' .
				$country_name .
				'"';
	if( !empty( $class ) ) $r .= ' class="'.$class.'"';
	if( !empty( $align ) ) $r .= ' align="'.$align.'"';
	$r .= ' /> ';

	if( $disp )
		echo $r;   // echo it
	else
		return $r; // return it

}


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
										'td' => '<a href="?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.T_('Edit this country...')
											.'">%country_flag( #ctry_code#, #ctry_name# )%
								<strong>$ctry_name$</strong></a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
							'td' => '%country_flag( #ctry_code#, #ctry_name# )%  $ctry_name$',
						);

}
$Results->cols[] = array(
						'th' => T_('Default Currency'),
						'td_class' => 'center',
						'order' => 'curr_code',
						'td' => '$curr_shortcut$ $curr_code$',
					);

/*
 * ACTIONS TD:
 */
function ctry_td_actions($ctry_enabled, $ctry_ID )
{
	global $dispatcher;

	$r = '';

	if( $ctry_enabled == true )
	{
		$r .= action_icon( T_('Disable the country!'), 'deactivate', 
										regenerate_url( 'action', 'action=disable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the country!'), 'activate',
										regenerate_url( 'action', 'action=enable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	$r .= action_icon( T_('Edit this country...'), 'edit',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this country...'), 'copy',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this country!'), 'delete',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=delete&amp;'.url_crumb('country') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '%ctry_td_actions( #ctry_enabled#, #ctry_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new country ...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New country').' &raquo;', 3, 4  );
}

$Results->display();

/*
 * $Log: _country_list.view.php,v $
 */
?>