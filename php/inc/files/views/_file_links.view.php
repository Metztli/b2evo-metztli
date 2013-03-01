<?php
/**
 * This file implements the UI for item links in the filemanager.
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
 * @version $Id: _file_links.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Item
 */
global $edited_Item;

global $mode;

if( $mode != 'upload' )
{	// If not opearting in a popup opened from post edit screen:

	$Form = new Form( NULL, 'fm_links', 'post', 'fieldset' );


	$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();

	$SQL = new SQL();
	$SQL->SELECT( 'link_ID, link_ltype_ID, T_files.*' );
	$SQL->FROM( 'T_links INNER JOIN T_files ON link_file_ID = file_ID' );
	$SQL->WHERE( 'link_itm_ID = ' . $edited_Item->ID );

	$Results = new Results( $SQL->get(), 'link_' );

	$Results->title = sprintf( T_('Files linked to &laquo;%s&raquo;'),
					'<a href="?ctrl=items&amp;blog='.$edited_Item->get_blog_ID().'&amp;p='.$edited_Item->ID.'" title="'
					.T_('View this post...').'">'.$edited_Item->dget('title').'</a>' );

	if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{ // User has permission to edit this post
		$Results->global_icon( T_('Edit this post...'), 'edit', '?ctrl=items&amp;action=edit&amp;p='.$edited_Item->ID, T_('Edit') );
	}

	// Close link mode and continue in File Manager (remember the Item_ID though):
	$Results->global_icon( T_('Quit link mode!'), 'close', regenerate_url( 'fm_mode' ) );


	// TYPE COLUMN:
	function file_type( & $row )
	{
		global $current_File;

		// Instantiate a File object for this line:
		$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY (FUNC) needed for following columns
		// Flow meta data into File object:
		$current_File->load_meta( false, $row );

		return $current_File->get_preview_thumb( 'fulltype' );
	}
	$Results->cols[] = array(
							'th' => T_('File'),
							'order' => 'link_ID',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%file_type( {row} )%',
						);


	// PATH COLUMN:
	function file_path()
	{
		/**
		 * @global File
		 */
		global $current_File, $current_User;
		global $edited_Item;

		$r = T_( 'You don\'t have permission to access this file root' );
		if( $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			// File relative path & name:
			$r = $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID );
		}
		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Path'),
							'order' => 'file_path',
							'td_class' => 'left',
							'td' => '%file_path()%',
						);


	// TITLE COLUMN:
	$Results->cols[] = array(
							'th' => T_('Title'),
							'order' => 'file_title',
							'td_class' => 'left',
							'td' => '$file_title$',
						);


	// ACTIONS COLUMN:
	function file_actions( $link_ID )
	{
		global $current_File, $edited_Item, $current_User;

		$r = '';
		if( $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			if( $current_File->is_dir() )
				$title = T_('Locate this directory!');
			else
				$title = T_('Locate this file!');
	
	
			$r = $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID,
							get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title );
		}
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:
			$r .= action_icon( T_('Delete this link!'), 'unlink',
	                      regenerate_url( 'action', 'link_ID='.$link_ID.'&amp;action=unlink&amp;'.url_crumb('link') ) );
		}

		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID# )%',
						);

	$Results->display();

	$Form->end_form( );
}

if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
{	// Check that we have permission to edit item:
	echo '<div>', sprintf( T_('Click on link %s icons below to link additional files to the post %s.'),
							get_icon( 'link', 'imgtag', array('class'=>'top') ),
							'&laquo;<strong>'.$edited_Item->dget( 'title' ).'</strong>&raquo;' ), '</div>';
}



/*
 * $Log: _file_links.view.php,v $
 */
?>