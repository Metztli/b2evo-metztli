<?php
/**
 * This file implements the Link class, which manages extra links on items.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _link.class.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Item Link
 *
 * @package evocore
 */
class Link extends DataObject
{
	var $ltype_ID = 0;
	var $Item;
	var $Comment;
	/**
	 * @access protected Use {@link get_File()}
	 */
	var $File;


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Link( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_links', 'link_', 'link_ID',
													'datecreated', 'datemodified', 'creator_user_ID', 'lastedit_user_ID' );

		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			if( $db_row->link_itm_ID != NULL )
			{ // Item
				$ItemCache = & get_ItemCache();
				$this->Item = & $ItemCache->get_by_ID( $db_row->link_itm_ID );
				$this->Comment = NULL;
			}
			else
			{ // Comment
				$CommentCache = & get_CommentCache();
				$this->Comment = $CommentCache->get_by_ID( $db_row->link_cmt_ID );
				$this->Item = NULL;
			}

			$this->file_ID = $db_row->link_file_ID;

			// TODO: dh> deprecated, check where it's used, and fix it.
			$this->File = & $this->get_File();

			$this->position = $db_row->link_position;
			$this->order = $db_row->link_order;
		}
		else
		{	// New object:

		}
	}


	/**
	 * Get {@link File} of the link.
	 *
	 * @return File
	 */
	function & get_File()
	{
		if( ! isset($this->File) )
		{
			if( isset($GLOBALS['files_Module']) )
			{
				$FileCache = & get_FileCache();
				// fp> do not halt on error. For some reason (ahem bug) a file can disappear and if we fail here then we won't be
				// able to delete the link
				$this->File = & $FileCache->get_by_ID( $this->file_ID, false, false );
			}
			else
			{
				$this->File = NULL;
			}
		}
		return $this->File;
	}


	/**
	 * Return type of target for this Link:
	 *
	 * @todo incomplete
	 */
	function target_type()
	{
 		if( !is_null($this->File) )
		{
			return 'file';
		}


		return 'unkown';
	}

}

/*
 * $Log: _link.class.php,v $
 */
?>