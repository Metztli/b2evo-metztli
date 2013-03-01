<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id: _coll_skin.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $admin_url, $dispatcher;

$block_item_Widget = new Widget( 'block_item' );

$block_item_Widget->title = T_('Choose a skin');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
  $block_item_Widget->global_icon( T_('Manage installed skins...'), 'properties', $dispatcher.'?ctrl=skins', T_('Manage skins'), 3, 4 );
  $block_item_Widget->global_icon( T_('Install new skin...'), 'new', $dispatcher.'?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new'), 3, 4 );
  $block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();

	// TODO: this is like touching private parts :>
	foreach( $SkinCache->cache as $Skin )
	{
		if( $Skin->type != 'normal' )
		{	// This skin cannot be used here...
			continue;
		}

		$selected = ($edited_Blog->skin_ID == $Skin->ID);
		$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;blog_skin_ID='.$Skin->ID.'&amp;'.url_crumb('collection');
		$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($Skin->folder) );

		// Display skinshot:
		Skin::disp_skinshot( $Skin->folder, $Skin->name, 'select', $selected, $select_url, $preview_url );
	}

	echo '<div class="clear"></div>';

$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log: _coll_skin.view.php,v $
 */
?>