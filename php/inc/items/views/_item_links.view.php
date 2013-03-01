<?php
/**
 * This file displays the links attached to an Item (called within the attachment_frame)
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _item_links.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;

/**
 * Needed by functions
 * @var Item
 */
global $edited_Item;

global $AdminUI;

// Override $debug in order to keep the display of the iframe neat
global $debug;
$debug = 0;

// Name of the iframe we want some actions to come back to:
param( 'iframe_name', 'string', '', true );

$SQL = new SQL();

$SQL->SELECT( 'link_ID, link_ltype_ID, link_position, file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
$SQL->FROM( 'T_links LEFT JOIN T_files ON link_file_ID = file_ID' );
$SQL->WHERE( 'link_itm_ID = '.$edited_Item->ID );
$SQL->ORDER_BY( 'link_order, link_ID' );

$Results = new Results( $SQL->get(), 'link_' );

$Results->title = T_('Attachments');

/*
 * Sub Type column
 */
function display_subtype( & $row )
{
	if( empty($row->file_ID) )
	{
		return '';
	}

	global $current_File;
	// Instantiate a File object for this line:
	$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY!
	// Flow meta data into File object:
	$current_File->load_meta( false, $row );

	return $current_File->get_preview_thumb( 'fulltype' );
}
$Results->cols[] = array(
						'th' => T_('Icon/Type'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_subtype( {row} )%',
					);


/*
 * LINK column
 */
function display_link( & $row )
{
	if( !empty($row->file_ID) )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $edited_Item;

		$r = '';

		// File relative path & name:
		// return $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;itm_ID='.$edited_Item->ID );
		if( $current_File->is_dir() )
		{ // Directory
			$r .= $current_File->dget( '_name' );
		}
		else
		{ // File
			if( $view_link = $current_File->get_view_link() )
			{
				$r .= $view_link;
			}
			else
			{ // File extension unrecognized
				$r .= $current_File->dget( '_name' );
			}
		}

		$title = $current_File->dget('title');
		if( $title !== '' )
		{
			$r .= '<span class="filemeta"> - '.$title.'</span>';
		}

		return $r;
	}

	return '?';
}
$Results->cols[] = array(
						'th' => T_('Destination'),
						'td' => '%display_link( {row} )%',
					);


if( $current_User->check_perm( 'files', 'view', false, $Blog->ID ) )
{
	function file_actions( $link_ID, $cur_idx, $total_rows )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $edited_Item, $current_User;
		global $iframe_name;

		$r = '';

		// Change order.
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:
			if( $cur_idx > 0 )
			{
				$r .= action_icon( T_('Move upwards'), 'move_up',
					regenerate_url( 'p,itm_ID,action', 'link_ID='.$link_ID.'&amp;action=link_move_up&amp;'.url_crumb('item') ) );
			}
			else
			{
				$r .= get_icon( 'nomove' ).' ';
			}

			if( $cur_idx < $total_rows-1 )
			{
				$r .= action_icon( T_('Move down'), 'move_down',
					regenerate_url( 'p,itm_ID,action', 'link_ID='.$link_ID.'&amp;action=link_move_down&amp;'.url_crumb('item') ) );
			}
			else
			{
				$r .= get_icon( 'nomove' ).' ';
			}
		}

		if( isset($current_File) && $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			if( $current_File->is_dir() )
				$title = T_('Locate this directory!');
			else
				$title = T_('Locate this file!');
			$url = $current_File->get_linkedit_url( $edited_Item->ID );
			$r .= '<a href="'.$url.'" onclick="return pop_up_window( \''
						.url_add_param( $url, 'mode=upload&amp;iframe_name='.$iframe_name.'' ).'\', \'fileman_upload\', 1000 )" target="_parent" title="'.$title.'">'
						.get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ).'</a> ';
		}

		// Delete link.
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	  {	// Check that we have permission to edit item:
			$r .= action_icon( T_('Delete this link!'), 'unlink',
			                  regenerate_url( 'p,itm_ID,action', 'link_ID='.$link_ID.'&amp;action=unlink&amp;'.url_crumb('item') ) );
		}

		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID#, {CUR_IDX}, {TOTAL_ROWS} )%',
						);
}


/*
 * POSITION column
 */
function display_position( & $row )
{
	// TODO: fp>dh: can you please implement cumbs in here? I don't clearly understand your code.
	// TODO: dh> centralize somewhere.. might get parsed out of ENUM info?!
	// Should be ordered like the ENUM.
	$positions = array(
		'teaser' => T_('Teaser'),
		'aftermore' => T_('After "more"'),
		);

	// TODO: dh> only handle images

	$id = 'display_position_'.$row->link_ID;

	// NOTE: dh> using method=get so that we can use regenerate_url (for non-JS).
	$r = '<form action="" method="post">
		<select id="'.$id.'" name="link_position">'
		.Form::get_select_options_string($positions, $row->link_position, true).'</select>'
		.'<script type="text/javascript">jQuery("#'.$id.'").change( evo_display_position_onchange );</script>';

	$r .= '<noscript>';
	// Add hidden fields for non-JS
	$url = regenerate_url( 'p,itm_ID,action', 'link_ID='.$row->link_ID.'&action=set_item_link_position&'.url_crumb('item'), '', '&' );
	$params = explode('&', substr($url, strpos($url, '?')+1));

	foreach($params as $param)
	{
		list($k, $v) = explode('=', $param);
		$r .= '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
	}
	$r .= '<input class="SaveButton" type="submit" value="&raquo;" />';
	$r .= '</noscript>';
	$r .= '</form>';

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_position( {row} )%',
					);

$Results->display( $AdminUI->get_template( 'compact_results' ) );


/*
 * $Log: _item_links.view.php,v $
 */
?>