<?php
/**
 * This file implements various File handling functions.
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
 * @version $Id: _file.funcs.php 622 2011-12-21 08:44:02Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( ! function_exists('fnmatch') )
{
	/**
	 * A replacement for fnmatch() which needs PHP 4.3 and a POSIX compliant system (Windows is not).
	 *
	 * @author jk at ricochetsolutions dot com {@link http://php.net/manual/function.fnmatch.php#71725}
	 */
	function fnmatch($pattern, $string)
	{
	   return preg_match( '#^'.strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')).'$#i', $string);
	}
}


/**
 * Converts bytes to readable bytes/kb/mb/gb, like "12.45mb"
 *
 * @param integer bytes
 * @param boolean use HTML <abbr> tags
 * @return string bytes made readable
 */
function bytesreadable( $bytes, $htmlabbr = true )
{
	static $types = NULL;

	if( empty($bytes) )
	{
		return T_('Empty');
	}

	if( !isset($types) )
	{ // generate once:
		$types = array(
			0 => array( 'abbr' => /* TRANS: Abbr. for "Bytes" */ T_('B.'), 'text' => T_('Bytes') ),
			1 => array( 'abbr' => /* TRANS: Abbr. for "Kilobytes" */ T_('KB'), 'text' => T_('Kilobytes') ),
			2 => array( 'abbr' => /* TRANS: Abbr. for Megabytes */ T_('MB'), 'text' => T_('Megabytes') ),
			3 => array( 'abbr' => /* TRANS: Abbr. for Gigabytes */ T_('GB'), 'text' => T_('Gigabytes') ),
			4 => array( 'abbr' => /* TRANS: Abbr. for Terabytes */ T_('TB'), 'text' => T_('Terabytes') )
		);
	}

	for( $i = 0; $bytes > 1024; $i++ )
	{
		$bytes /= 1024;
	}

	// Format to maximum of 1 digit after .
	$precision = max( 0, ( 1 -floor(log($bytes)/log(10))) );
	$r = sprintf( '%.'.$precision.'f', $bytes );

	$r .= $htmlabbr ? ( '&nbsp;<abbr title="'.$types[$i]['text'].'">' ) : ' ';
	$r .= $types[$i]['abbr'];
	$r .= $htmlabbr ? '</abbr>' : ( ' ('.$types[$i]['text'].')' );

	// $r .= ' '.$precision;

	return $r;
}


/**
 * Get an array of all directories (and optionally files) of a given
 * directory, either flat (one-dimensional array) or multi-dimensional (then
 * dirs are the keys and hold subdirs/files).
 *
 * Note: there is no ending slash on dir names returned.
 *
 * @param string the path to start
 * @param boolean include files (not only directories)
 * @param boolean include directories (not the directory itself!)
 * @param boolean flat (return an one-dimension-array)
 * @param boolean Recurse into subdirectories?
 * @param boolean Get the basename only.
 * @return false|array false if the first directory could not be accessed,
 *                     array of entries otherwise
 */
function get_filenames( $path, $inc_files = true, $inc_dirs = true, $flat = true, $recurse = true, $basename = false, $trailing_slash = false )
{
	$r = array();

	$path = trailing_slash( $path );

	if( $dir = @opendir($path) )
	{
		while( ( $file = readdir($dir) ) !== false )
		{
			if( $file == '.' || $file == '..' )
			{
				continue;
			}
			if( is_dir($path.$file) )
			{
				if( $flat )
				{
					if( $inc_dirs )
					{
						$directory_name = $basename ? $file : $path.$file;
						if( $trailing_slash )
						{
							$directory_name = trailing_slash( $directory_name );
						}

						$r[] = $directory_name;
					}
					if( $recurse )
					{
						$rSub = get_filenames( $path.$file, $inc_files, $inc_dirs, $flat, $recurse, $basename, $trailing_slash );
						if( $rSub )
						{
							$r = array_merge( $r, $rSub );
						}
					}
				}
				else
				{
					$r[$file] = get_filenames( $path.$file, $inc_files, $inc_dirs, $flat, $recurse, $basename, $trailing_slash );
				}
			}
			elseif( $inc_files )
			{
				$r[] = $basename ? $file : $path.$file;
			}
		}
		closedir($dir);
	}
	else
	{
		return false;
	}

	return $r;
}


/**
 * Get a list of available admin skins.
 *
 * This checks if there's a _adminUI.class.php in there.
 *
 * @return array  List of directory names that hold admin skins or false, if the admin skins driectory does not exist.
 */
function get_admin_skins()
{
	global $adminskins_path, $admin_subdir, $adminskins_subdir;

	$dirs_in_adminskins_dir = get_filenames( $adminskins_path, false, true, true, false, true );

	if( $dirs_in_adminskins_dir === false )
	{
		return false;
	}

	$r = array();
	if( $dirs_in_adminskins_dir )
	{
		foreach( $dirs_in_adminskins_dir as $l_dir )
		{
			if( !file_exists($adminskins_path.$l_dir.'/_adminUI.class.php') )
			{
				continue;
			}
			$r[] = $l_dir;
		}
	}
	return $r;
}


/**
 * Get size of a directory, including anything (especially subdirs) in there.
 *
 * @param string the dir's full path
 */
function get_dirsize_recursive( $path )
{
	$files = get_filenames( $path );
	$total = 0;

	if( !empty( $files ) )
	{
		foreach( $files as $lFile )
		{
			$total += filesize($lFile);
		}
	}

	return $total;
}


/**
 * Deletes a dir recursively, wiping out all subdirectories!!
 *
 * @param string The dir
 * @return boolean False on failure
 */
function rmdir_r( $path )
{
	$path = trailing_slash( $path );

	$r = true;

	if( ! cleardir_r( $path ) )
	{
		$r = false;
	}

	if( ! @rmdir( $path ) )
	{
		$r = false;
	}

	return $r;
}


/**
 * Clear contents of dorectory, but do not delete directory itself
 * @return boolean False on failure (may be only partial), true on success.
 */
function cleardir_r( $path )
{
	$path = trailing_slash( $path );
	// echo "<br>rmdir_r($path)";

	$r = true; // assume success

	if( $dir = @opendir($path) )
	{
		while( ( $file = readdir($dir) ) !== false )
		{
			if( $file == '.' || $file == '..' )
				continue;

			$adfp_filepath = $path.$file;

			// echo "<br> - $os_filepath ";

			if( is_dir( $adfp_filepath ) && ! is_link($adfp_filepath) )
			{ // Note: we do NOT follow symlinks
				// echo 'D';
				if( ! rmdir_r( $adfp_filepath ) )
				{
					$r = false;
				}
			}
			else
			{ // File or symbolic link
				//echo 'F/S';
				if( ! @unlink( $adfp_filepath ) )
				{
					$r = false;
				}
			}
		}
		closedir($dir);
	}
	else
	{
		$r = false;
	}

	return $r;
}

/**
 * Get the size of an image file
 *
 * @param string absolute file path
 * @param string what property/format to get: 'width', 'height', 'widthxheight',
 *               'type', 'string' (as for img tags), 'widthheight_assoc' (array
 *               with keys "width" and "height", else 'widthheight' (numeric array)
 * @return mixed false if no image, otherwise what was requested through $param
 */
function imgsize( $path, $param = 'widthheight' )
{
	/**
	 * Cache image sizes
	 */
	global $cache_imgsize;

	if( isset($cache_imgsize[$path]) )
	{
		$size = $cache_imgsize[$path];
	}
	elseif( !file_exists($path) || !($size = @getimagesize( $path )) )
    {    // File not found, or not an image 
		return false;
	}
	else
	{
		$cache_imgsize[$path] = $size;
	}

	if( $param == 'width' )
	{
		return $size[0];
	}
	elseif( $param == 'height' )
	{
		return $size[1];
	}
	elseif( $param == 'widthxheight' )
	{
		return $size[0].'x'.$size[1];
	}
	elseif( $param == 'type' )
	{
		switch( $size[1] )
		{
			case 1: return 'gif';
			case 2: return 'jpg';
			case 3: return 'png';
			case 4: return 'swf';
			default: return 'unknown';
		}
	}
	elseif( $param == 'string' )
	{
		return $size[3];
	}
	elseif( $param == 'widthheight_assoc' )
	{
		return array( 'width' => $size[0], 'height' => $size[1] );
	}
	else
	{ // default: 'widthheight'
		return array( $size[0], $size[1] );
	}
}


/**
 * Add a trailing slash, if none present
 *
 * @param string the path/url
 * @return string the path/url with trailing slash
 */
function trailing_slash( $path )
{
	if( empty($path) || substr( $path, -1 ) == '/' )
	{
		return $path;
	}
	else
	{
		return $path.'/';
	}
}


/**
 * Remove trailing slash, if present
 *
 * @param string the path/url
 * @return string the path/url without trailing slash
 */
function no_trailing_slash( $path )
{
	if( substr( $path, -1 ) == '/' )
	{
		return substr( $path, 0, strlen( $path )-1 );
	}
	else
	{
		return $path;
	}
}


/**
 * Remove leading slash, if any.
 *
 * @param string
 * @return string
 */
function no_leading_slash( $path )
{
	if( isset($path[0]) && $path[0] == '/' )
	{
		return substr( $path, 1 );
	}
	else
	{
		return $path;
	}
}


/**
 * Returns canonicalized pathname of a directory + ending slash
 *
 * @param string absolute path to be reduced ending with slash
 * @return string absolute reduced path, slash terminated or NULL if the path could not get canonicalized.
 */
function get_canonical_path( $ads_path )
{
	// Remove windows backslashes:
	$ads_path = str_replace( '\\', '/', $ads_path );

	$is_absolute = is_absolute_pathname($ads_path);

	// Make sure there's a trailing slash
	$ads_path = trailing_slash($ads_path);

	while( strpos($ads_path, '//') !== false )
	{
		$ads_path = str_replace( '//', '/', $ads_path );
	}
	while( strpos($ads_path, '/./') !== false )
	{
		$ads_path = str_replace( '/./', '/', $ads_path );
	}
	$parts = explode('/', $ads_path);
	for( $i = 0; $i < count($parts); $i++ )
	{
		if( $parts[$i] != '..' )
		{
			continue;
		}
		if( $i <= 0 || $parts[$i-1] == '' || substr($parts[$i-1], -1) == ':' /* windows drive letter */ )
		{
			return NULL;
		}
		// Remove ".." and the part before it
		unset($parts[$i-1], $parts[$i]);
		// Respin array
		$parts = array_values($parts);
		$i = $i-2;
	}
	$ads_realpath = implode('/', $parts);

	// pre_dump( 'get_canonical_path()', $ads_path, $ads_realpath );

	if( strpos( $ads_realpath, '..' ) !== false )
	{	// Path malformed:
		return NULL;
	}

	if( $is_absolute && ! strlen($ads_realpath) )
	{
		return NULL;
	}

	return $ads_realpath;
}


/**
 * Check for valid filename and extension of the filename (no path allowed). (MB)
 *
 * @uses $FiletypeCache, $settings or $force_regexp_filename form _advanced.php
 *
 * @param string filename to test
 * @param mixed true/false to allow locked filetypes. NULL means that FileType will decide
 * @return nothing if the filename is valid according to the regular expression and the extension too, error message if not
 */
function validate_filename( $filename, $allow_locked_filetypes = NULL )
{
	global $Settings, $force_regexp_filename;

	// Check filename
	if( $force_regexp_filename )
	{ // Use the regexp from _advanced.php
		if( !preg_match( ':'.str_replace( ':', '\:', $force_regexp_filename ).':', $filename ) )
		{ // Invalid filename
			return sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $filename );
		}
	}
	else
	{	// Use the regexp from SETTINGS
		if( !preg_match( ':'.str_replace( ':', '\:', $Settings->get( 'regexp_filename' ) ).':', $filename ) )
		{ // Invalid filename
			return sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $filename );
		}
	}

	// Check extension filename
	if( preg_match( '#\.([a-zA-Z0-9\-_]+)$#', $filename, $match ) )
	{ // Filename has a valid extension
		$FiletypeCache = & get_FiletypeCache();
		if( $Filetype = & $FiletypeCache->get_by_extension( strtolower( $match[1] ) , false ) )
		{
			if( $Filetype->is_allowed( $allow_locked_filetypes ) )
			{ // Filename has an unlocked extension or we allow locked extensions
				return;
			}
			else
			{	// Filename hasn't an allowed extension
				return sprintf( T_('&laquo;%s&raquo; is a locked extension.'), htmlentities($match[1]) );
			}
		}
		else
		{ // Filename hasn't an allowed extension
			return sprintf( T_('&laquo;%s&raquo; has an unrecognized extension.'), $filename );
		}
	}
	else
	{ // Filename hasn't a valid extension
		return sprintf( T_('&laquo;%s&raquo; has not a valid extension.'), $filename );
	}
}


/**
 * Check for valid dirname (no path allowed). ( MB )
 *
 * @uses $Settings or $force_regexp_dirname form _advanced.php
 * @param string dirname to test
 * @return nothing if the dirname is valid according to the regular expression, error message if not
 */
function validate_dirname( $dirname )
{
	global $Settings, $force_regexp_dirname;

	if( $dirname != '..' )
	{
		if( !empty( $force_regexp_dirname ) )
		{ // Use the regexp from _advanced.php
			if( preg_match( ':'.str_replace( ':', '\:', $force_regexp_dirname ).':', $dirname ) )
			{ // Valid dirname
				return;
			}
		}
		else
		{ // Use the regexp from SETTINGS
			if( preg_match( ':'.str_replace( ':', '\:', $Settings->get( 'regexp_dirname' ) ).':', $dirname ) )
			{ // Valid dirname
				return;
			}
		}
	}

	return sprintf( T_('&laquo;%s&raquo; is not a valid directory name.'), $dirname );
}


/**
 * Check if file rename is acceptable
 *
 * used when renaming a file, File settings
 *
 * @param string the new name
 * @param boolean 0 if directory
 * @param boolean 0 if permission denied
 * @return nothing if the rename is acceptable, error message if not
 */
function check_rename ( & $newname, $is_dir, $allow_locked_filetypes )
{
	// Check if provided name is okay:
	$newname = trim( strip_tags($newname) );

	if( ! $is_dir )
	{
		if( $error_filename = validate_filename( $newname, $allow_locked_filetypes ) )
		{ // Not a file name or not an allowed extension
			return $error_filename;
		}
	}
	elseif( $error_dirname = validate_dirname( $newname ) )
	{ // directory name
		return $error_dirname;
	}
	return;
}


/**
 * Return a string with upload restrictions ( allowed extensions, max file size )
 */
function get_upload_restriction()
{
	global $DB, $Settings, $current_User;
	$restrictNotes = array();

	if( is_logged_in() )
	{
		$condition = ( $current_User->check_perm( 'files', 'all' ) ) ? '' : 'ftyp_allowed <> "admin"';
	}
	else
	{
		$condition = 'ftyp_allowed = "any"';
	}

	if( !empty( $condition ) )
	{
		$condition = ' WHERE '.$condition;
	}

	// Get list of recognized file types (others are not allowed to get uploaded)
	// dh> because FiletypeCache/DataObjectCache has no interface for getting a list, this dirty query seems less dirty to me.
	$allowed_extensions = $DB->get_col( 'SELECT ftyp_extensions FROM T_filetypes'.$condition );
	$allowed_extensions = implode( ' ', $allowed_extensions ); // implode with space, ftyp_extensions can hold many, separated by space
	// into array:
	$allowed_extensions = preg_split( '~\s+~', $allowed_extensions, -1, PREG_SPLIT_NO_EMPTY );
	// readable:
	$allowed_extensions = implode_with_and($allowed_extensions);

	$restrictNotes[] = '<strong>'.T_('Allowed file extensions').'</strong>: '.$allowed_extensions;

	if( $Settings->get( 'upload_maxkb' ) )
	{ // We want to restrict on file size:
		$restrictNotes[] = '<strong>'.T_('Maximum allowed file size').'</strong>: '.bytesreadable( $Settings->get( 'upload_maxkb' )*1024 );
	}

	return implode( '<br />', $restrictNotes ).'<br />';
}


/**
 * Return the path without the leading {@link $basepath}, or if not
 * below {@link $basepath}, just the basename of it.
 *
 * Do not use this for file handling.  JUST for displaying! (DEBUG MESSAGE added)
 *
 * @param string Path
 * @return string Relative path or even base name.
 *   NOTE: when $debug, the real path gets appended.
 */
function rel_path_to_base( $path )
{
	global $basepath, $debug;

	// Remove basepath prefix:
	if( preg_match( '~^('.preg_quote($basepath, '~').')(.+)$~', $path, $match ) )
	{
		$r = $match[2];
	}
	else
	{
		$r = basename($path).( is_dir($path) ? '/' : '' );
	}

	if( $debug )
	{
		$r .= ' [DEBUG: '.$path.']';
	}

	return $r;
}


/**
 * Get the directories of the supplied path as a radio button tree.
 *
 * @todo fp> Make a DirTree class (those static hacks suck)
 *
 * @param FileRoot A single root or NULL for all available.
 * @param string the root path to use
 * @param boolean add radio buttons ?
 * @param string used by recursion
 * @param string what kind of action do the user ( we need this to check permission )
 * 			fp>asimo : in what case can this be something else than "view" ?
 * 			asimo>fp : on the _file_upload.view, we must show only those roots where current user has permission to add files
 * @return string
 */
function get_directory_tree( $Root = NULL, $ads_full_path = NULL, $ads_selected_full_path = NULL, $radios = false, $rds_rel_path = NULL, $is_recursing = false, $action = 'view' )
{
	static $js_closeClickIDs; // clickopen IDs that should get closed
	static $instance_ID = 0;
	static $fm_highlight;
	global $current_User;

	// A folder might be highlighted (via "Locate this directory!")
	if( ! isset($fm_highlight) )
	{
		$fm_highlight = param('fm_highlight', 'string', '');
	}


	if( ! $is_recursing )
	{	// This is not a recursive call (yet):
		// Init:
		$instance_ID++;
		$js_closeClickIDs = array();
		$ret = '<ul class="clicktree">';
	}
	else
	{
		$ret = '';
	}

	// ________________________ Handle Roots ______________________
	if( $Root === NULL )
	{ // We want to list all roots:
		$FileRootCache = & get_FileRootCache();
		$_roots = $FileRootCache->get_available_FileRoots();

		foreach( $_roots as $l_Root )
		{
			if( ! $current_User->check_perm( 'files', $action, false, $l_Root ) )
			{	// current user does not have permission to "view" (or other $action) this root
				continue;
			}
			$subR = get_directory_tree( $l_Root, $l_Root->ads_path, $ads_selected_full_path, $radios, '', true );
			if( !empty( $subR['string'] ) )
			{
				$ret .= '<li>'.$subR['string'].'</li>';
			}
		}
	}
	else
	{
		// We'll go through files in current dir:
		$Nodelist = new Filelist( $Root, trailing_slash($ads_full_path) );
		check_showparams( $Nodelist );
		$Nodelist->load();
		$Nodelist->sort( 'name' );
		$has_sub_dirs = $Nodelist->count_dirs();

		$id_path = 'id_path_'.$instance_ID.md5( $ads_full_path );

		$r['string'] = '<span class="folder_in_tree"';

		if( $ads_full_path == $ads_selected_full_path )
		{	// This is the current open path
	 		$r['opened'] = true;

	 		if( $fm_highlight && $fm_highlight == substr($rds_rel_path, 0, -1) )
	 		{
	 			$r['string'] .= ' id="fm_highlighted"';
	 			unset($fm_highlight);
	 		}
		}
		else
		{
	 		$r['opened'] = NULL;
		}

		$r['string'] .= '>';

		if( $radios )
		{ // Optional radio input to select this path:
			$root_and_path = format_to_output( implode( '::', array($Root->ID, $rds_rel_path) ), 'formvalue' );

			$r['string'] .= '<input type="radio" name="root_and_path" value="'.$root_and_path.'" id="radio_'.$id_path.'"';

			if( $r['opened'] )
			{	// This is the current open path
				$r['string'] .= ' checked="checked"';
			}

			//.( ! $has_sub_dirs ? ' style="margin-right:'.get_icon( 'collapse', 'size', array( 'size' => 'width' ) ).'px"' : '' )
			$r['string'] .= ' /> &nbsp; &nbsp;';
		}

		// Folder Icon + Name:
		$url = regenerate_url( 'root,path', 'root='.$Root->ID.'&amp;path='.$rds_rel_path );
		$label = action_icon( T_('Open this directory in the file manager'), 'folder', $url )
			.'<a href="'.$url.'"
			title="'.T_('Open this directory in the file manager').'">'
			.( empty($rds_rel_path) ? $Root->name : basename( $ads_full_path ) )
			.'</a>';

		// Handle potential subdir:
		if( ! $has_sub_dirs )
		{	// No subirs
			$r['string'] .= get_icon( 'expand', 'noimg', array( 'class'=>'' ) ).'&nbsp;'.$label.'</span>';
		}
		else
		{ // Process subdirs
			$r['string'] .= get_icon( 'collapse', 'imgtag', array( 'onclick' => 'toggle_clickopen(\''.$id_path.'\');',
						'id' => 'clickimg_'.$id_path
					) )
				.'&nbsp;'.$label.'</span>'
				.'<ul class="clicktree" id="clickdiv_'.$id_path.'">'."\n";

			while( $l_File = & $Nodelist->get_next( 'dir' ) )
			{
				$rSub = get_directory_tree( $Root, $l_File->get_full_path(), $ads_selected_full_path, $radios, $l_File->get_rdfs_rel_path(), true );

				if( $rSub['opened'] )
				{ // pass opened status on, if given
					$r['opened'] = $rSub['opened'];
				}

				$r['string'] .= '<li>'.$rSub['string'].'</li>';
			}

			if( !$r['opened'] )
			{
				$js_closeClickIDs[] = $id_path;
			}
			$r['string'] .= '</ul>';
		}

   	if( $is_recursing )
		{
			return $r;
		}
		else
		{
			$ret .= '<li>'.$r['string'].'</li>';
		}
	}

	if( ! $is_recursing )
	{
 		$ret .= '</ul>';

		if( ! empty($js_closeClickIDs) )
		{ // there are IDs of checkboxes that we want to close
			$ret .= "\n".'<script type="text/javascript">toggle_clickopen( \''
						.implode( "' );\ntoggle_clickopen( '", $js_closeClickIDs )
						."' );\n</script>";
		}
	}

	return $ret;
}


/**
 * Create a directory recursively.
 *
 * NOTE: this can be done with the "recursive" param in PHP5
 *
 * @todo dh> simpletests for this (especially for open_basedir)
 *
 * @param string directory name
 * @param integer permissions
 * @return boolean
 */
function mkdir_r( $dirName, $chmod = NULL )
{
	if( is_dir($dirName) )
	{ // already exists:
		return true;
	}

	if( $chmod === NULL )
	{
		global $Settings;
		$chmod = $Settings->get('fm_default_chmod_dir');
	}

	/*
	if( version_compare(PHP_VERSION, 5, '>=') )
	{
		return mkdir( $dirName, $chmod, true );
	}
	*/

	$dirName = trailing_slash($dirName);

	$parts = array_reverse( explode('/', $dirName) );
	$loop_dir = $dirName;
	$create_dirs = array();
	foreach($parts as $part)
	{
		if( ! strlen($part) )
		{
			continue;
		}
		// We want to create this dir:
		array_unshift($create_dirs, $loop_dir);
		$loop_dir = substr($loop_dir, 0, 0 - strlen($part)-1);

		if( is_dir($loop_dir) )
		{ // found existing dir:
			foreach($create_dirs as $loop_dir )
			{
				// Tblue> Note: The chmod value for mkdir() is affected by the user's umask.
				if( ! @mkdir($loop_dir, octdec($chmod)) )
				{
					return false;
				}
			}
			return true;
		}
	}
	return true;
}


/**
 * Is the given path absolute (non-relative)?
 *
 * @return boolean
 */
function is_absolute_pathname($path)
{
	$pathlen = strlen($path);
	if( ! $pathlen )
	{
		return false;
	}

	if( is_windows() )
	{ // windows e-g: (note: "XY:" can actually happen as a drive ID in windows; I have seen it once in 2009 on MY XP sp3 after plugin in & plugin out an USB stick like 26 times over 26 days! (with sleep/hibernate in between)
		return ( $pathlen > 1 && $path[1] == ':' );
	}
	else
	{ // unix
		return ( $path[0] == '/' );
	}
}


/**
 * Define sys_get_temp_dir, if not available (PHP 5 >= 5.2.1)
 * @link http://us2.php.net/manual/en/function.sys-get-temp-dir.php#93390
 * @return string NULL on failure
 */
if ( !function_exists('sys_get_temp_dir'))
{
  function sys_get_temp_dir()
	{
    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
    $tempfile=tempnam(__FILE__,'');
    if (file_exists($tempfile))
		{
      unlink($tempfile);
      return realpath(dirname($tempfile));
    }
    return null;
  }
}

/**
* Controller helper
*/
function file_controller_build_tabs()
{
	global $AdminUI, $current_User, $blog;

	$AdminUI->add_menu_entries(
			'files',
			array(
					'browse' => array(
						'text' => T_('Browse'),
						'href' => regenerate_url( 'ctrl', 'ctrl=files' ) ),
					)
				);

	if( $current_User->check_perm( 'files', 'add', false, $blog ? $blog : NULL ) )
	{ // Permission to upload: (no subtabs needed otherwise)
		$AdminUI->add_menu_entries(
				'files',
				array(
						'upload' => array(
							'text' => /* TRANS: verb */ T_('Upload '),
							'href' => regenerate_url( 'ctrl', 'ctrl=upload' ) ),
					)
			);

		$AdminUI->add_menu_entries(
			array('files', 'upload'),
			array(
					'quick' => array(
						'text' => T_('Quick'),
						'href' => '?ctrl=upload&amp;tab3=quick' ),
					'standard' => array(
						'text' => T_('Standard'),
						'href' => '?ctrl=upload&amp;tab3=standard' ),
					'advanced' => array(
						'text' => T_('Advanced'),
						'href' => '?ctrl=upload&amp;tab3=advanced' ),
				)
			);
	}

	if( $current_User->check_perm( 'options', 'view' ) )
	{	// Permission to view settings:
		$AdminUI->add_menu_entries(
			'files',
			array(
				'settings' => array(
					'text' => T_('Settings'),
					'href' => '?ctrl=fileset',
					)
				)
			);

		$AdminUI->add_menu_entries(
			array('files', 'settings'),
			array(
					'settings' => array(
						'text' => T_('Settings'),
						'href' => '?ctrl=fileset' ),
					'filetypes' => array(
						'text' => T_('File types'),
						'href' => '?ctrl=filetypes' ),
				)
			);
	}

}

/**
 * Rename evocache folders after File settings update, whe evocahe folder name was chaned
 *
 * @param string old evocache folder name
 * @param string new evocache folder name
 * @return bool true on success
 */
function rename_cachefolders( $oldname, $newname )
{
	$FileRootCache = & get_FileRootCache();

	$available_Roots = $FileRootCache->get_available_FileRoots();

	$slash_oldname = '/'.$oldname;

	$result = true;
	foreach( $available_Roots as $fileRoot )
	{
		$dirpaths = get_filenames( $fileRoot->ads_path, false );
		foreach( $dirpaths as $dirpath )
		{ // search ?evocache folders
			$dirpath_length = strlen( $dirpath );
			if( $dirpath_length < 10 )
			{ // The path is to short, can not contains ?evocache folder name
				continue;
			}
			// searching at the end of the path -> '/' character + ?evocache, length = 1 + 9
			$path_end = substr( $dirpath, $dirpath_length - 10 );
			if( $path_end == $slash_oldname )
			{ // this is a ?evocache folder
				$new_dirpath = substr_replace( $dirpath, $newname, $dirpath_length - 9 );
				// result is true only if all rename call return true (success)
				$result = $result && @rename( $dirpath, $new_dirpath );
			}
		}
	}
	return $result;
}


/**
 * Delete any ?evocache folders.
 *
 * @param Log Pass a Log object here to have error messages added to it.
 * @return integer Number of deleted dirs.
 */
function delete_cachefolders( $Log = NULL )
{
	global $media_path, $Settings;

	// Get this, just in case someone comes up with a different naming:
	$evocache_foldername = $Settings->get( 'evocache_foldername' );

	$dirs = get_filenames( $media_path, false );
	$deleted_dirs = 0;
	foreach( $dirs as $dir )
	{
		$basename = basename($dir);
		if( $basename == '.evocache' || $basename == '_evocache' || $basename == $evocache_foldername )
		{	// Delete .evocache directory recursively
			if( rmdir_r( $dir ) )
			{
				$deleted_dirs++;
			}
			elseif( $Log )
			{
				$Log->add( sprintf( T_('Could not delete directory: %s'), $dir ), 'error' );
			}
		}
	}
	return $deleted_dirs;
}


/**
 * Check and set the given FileList object fm_showhidden and fm_showevocache params 
 */
function check_showparams( & $Filelist )
{
	global $UserSettings;

	if( $UserSettings->param_Request( 'fm_showhidden', 'fm_showhidden', 'integer', 0 ) )
	{
		$Filelist->_show_hidden_files = true;
	}

	if( $UserSettings->param_Request( 'fm_showevocache', 'fm_showevocache', 'integer', 0 ) )
	{
		$Filelist->_show_evocache = true;
	}
}


/**
 * Process file uploads (this can process multiple file uploads at once)
 * 
 * @param string FileRoot id string
 * @param string the upload dir relative path in the FileRoot
 * @param boolean Shall we create path dirs if they do not exist?
 * @param boolean Shall we check files add permission for current_User?
 * @param boolean upload quick mode
 * @param boolean show warnings if filename is not valid
 * @return mixed NULL if upload was impossible to complete for some reason (wrong fileroot ID, insufficient user permission, etc.)
 * 				 array, which contains uploadedFiles, failedFiles, renamedFiles and renamedMessages
 */
function process_upload( $root_ID, $path, $create_path_dirs = false, $check_perms = true, $upload_quickmode = true, $warn_invalid_filenames = true )
{
	global $Settings, $Plugins, $Messages, $current_User, $force_upload_forbiddenext;

	if( empty($_FILES) )
	{	// We have NO uploaded files to process...
		return NULL;
	}

	/**
	 * Remember failed files (and the error messages)
	 * @var array
	 */
	$failedFiles = array();
	/**
	 * Remember uploaded files
	 * @var array
	 */
	$uploadedFiles = array();
	/**
	 * Remember renamed files
	 * @var array
	 */
	$renamedFiles = array();
	/**
	 * Remember renamed Messages
	 * @var array
	 */
	$renamedMessages = array();

	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = & $FileRootCache->get_by_ID($root_ID, true);
	if( !$fm_FileRoot )
	{ // fileRoot not found:
		return NULL;
	}

	if( $check_perms && ( !isset( $current_User ) || $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
	{ // Permission check required but current User has no permission to upload:
		return NULL;
	}

	// Let's get into requested list dir...
	$non_canonical_list_path = $fm_FileRoot->ads_path.$path;
	// Dereference any /../ just to make sure, and CHECK if directory exists:
	$ads_list_path = get_canonical_path( $non_canonical_list_path );

	// check if the upload dir exists
	if( !is_dir( $ads_list_path ) )
	{
		if( $create_path_dirs )
		{ // Create path
			mkdir_r( $ads_list_path );
		}
		else
		{ // This case should not happen! If it happens then there is a bug in the code where this function was called!
			return NULL;
		}
	}

	// Get param arrays for all uploaded files:
	$uploadfile_title = param( 'uploadfile_title', 'array', array() );
	$uploadfile_alt = param( 'uploadfile_alt', 'array', array() );
	$uploadfile_desc = param( 'uploadfile_desc', 'array', array() );
	$uploadfile_name = param( 'uploadfile_name', 'array', array() );

	// LOOP THROUGH ALL UPLOADED FILES AND PROCCESS EACH ONE:
	foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
	{
		if( empty( $lName ) )
		{ // No file name:
			if( $upload_quickmode
				 || !empty( $uploadfile_title[$lKey] )
				 || !empty( $uploadfile_alt[$lKey] )
				 || !empty( $uploadfile_desc[$lKey] )
				 || !empty( $uploadfile_name[$lKey] ) )
			{ // User specified params but NO file! Warn the user:
				$failedFiles[$lKey] = T_( 'Please select a local file to upload.' );
			}
			// Abort upload for this file:
			continue;
		}

		if( $Settings->get( 'upload_maxkb' )
				&& $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 )
		{ // File is larger than allowed in settings:
			$failedFiles[$lKey] = sprintf(
					T_('The file is too large: %s but the maximum allowed is %s.'),
					bytesreadable( $_FILES['uploadfile']['size'][$lKey] ),
					bytesreadable($Settings->get( 'upload_maxkb' )*1024) );
			// Abort upload for this file:
			continue;
		}

		if( $_FILES['uploadfile']['error'][$lKey] )
		{ // PHP itself has detected an error!:
			switch( $_FILES['uploadfile']['error'][$lKey] )
			{
				case UPLOAD_ERR_FORM_SIZE:
					// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
					// This can easily be edited by the user/hacker, so we do not use it.. file size gets checked for real just above.
					break;

				case UPLOAD_ERR_INI_SIZE:
					// File is larger than allowed in php.ini:
					$failedFiles[$lKey] = 'The file exceeds the upload_max_filesize directive in php.ini.'; // Configuration error, no translation
					// Abort upload for this file:
					continue;

				case UPLOAD_ERR_PARTIAL:
					$failedFiles[$lKey] = T_('The file was only partially uploaded.');
					// Abort upload for this file:
					continue;

				case UPLOAD_ERR_NO_FILE:
					// Is probably the same as empty($lName) before.
					$failedFiles[$lKey] = T_('No file was uploaded.');
					// Abort upload for this file:
					continue;

				case 6: // numerical value of UPLOAD_ERR_NO_TMP_DIR
					# (min_php: 4.3.10, 5.0.3) case UPLOAD_ERR_NO_TMP_DIR:
					// Missing a temporary folder.
					$failedFiles[$lKey] = 'Temporary upload dir is missing! (upload_tmp_dir in php.ini)'; // Configuration error, no translation
					// Abort upload for this file:
					continue;

				default:
					$failedFiles[$lKey] = T_('An unknown error has occured.').' Error code #'.$_FILES['uploadfile']['error'][$lKey];
					// Abort upload for this file:
					continue;
			}
		}

		if( ! isset($_FILES['uploadfile']['_evo_fetched_url'][$lKey]) // skip check for fetched URLs
			&& ! is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
		{ // Ensure that a malicious user hasn't tried to trick the script into working on files upon which it should not be working.
			$failedFiles[$lKey] = T_('The file does not seem to be a valid upload! It may exceed the upload_max_filesize directive in php.ini.');
			// Abort upload for this file:
			continue;
		}

		// Use new name on server if specified:
		$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

		if( !$warn_invalid_filenames )
		{ // Sanitize down any add characters:
			$newName = preg_replace( '/[^a-z0-9\-_.]+/i', '_', $newName );
		}

		if( $error_filename = validate_filename( $newName ) )
		{ // Not a valid file name or not an allowed extension:
			$failedFiles[$lKey] = $error_filename;
			// Abort upload for this file:
			continue;
		}

		// Check if the uploaded file type is an image, and if is an image then try to fix the file extension based on mime type
		// If the mime type is a known mime type and user has right to upload files with this kind of file type, 
		// this part of code will check if the file extension is the same as admin defined for this file type, and will fix it if it isn't the same
		// Note: it will also change the jpeg extensions to jpg.
		$uploadfile_path = $_FILES['uploadfile']['tmp_name'][$lKey];
		// this image_info variable will be used again to get file thumb
		$image_info = getimagesize($uploadfile_path);
		if( $image_info )
		{ // This is an image, validate mimetype vs. extension:
			$image_mimetype = $image_info['mime'];
			$FiletypeCache = get_Cache('FiletypeCache');
			// get correct file type based on mime type
			$correct_Filetype = $FiletypeCache->get_by_mimetype( $image_mimetype, false, false );

			// Check if file type is known by us, and if it is allowed for upload.
			// If we don't know this file type or if it isn't allowed we don't change the extension! The current extension is allowed for sure.
			if( $correct_Filetype && $correct_Filetype->is_allowed() )
			{ // A FileType with the given mime type exists in database and it is an allowed file type for current User
				// The "correct" extension is a plausible one, proceed...
				$correct_extension = array_shift($correct_Filetype->get_extensions());
				$path_info = pathinfo($newName);
				$current_extension = $path_info['extension'];

				// change file extension to the correct extension, but only if the correct extension is not restricted, this is an extra security check!
				if( strtolower($current_extension) != strtolower($correct_extension) && ( !in_array( $correct_extension, $force_upload_forbiddenext ) ) )
				{ // change the file extension to the correct extension
					$old_name = $newName;
					$newName = $path_info['filename'].'.'.$correct_extension;
					$Messages->add( sprintf(T_('The extension of the file &laquo;%s&raquo; has been corrected. The new filename is &laquo;%s&raquo;.'), $old_name, $newName), 'warning' );
				}
			}
		}

		// Get File object for requested target location:
		$oldName = $newName;
		list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName, $image_info );
		$newName = $newFile->get( 'name' );

		// Trigger plugin event
		if( $Plugins->trigger_event_first_false( 'AfterFileUpload', array(
				  'File' => & $newFile,
				  'name' => & $_FILES['uploadfile']['name'][$lKey],
				  'type' => & $_FILES['uploadfile']['type'][$lKey],
				  'tmp_name' => & $_FILES['uploadfile']['tmp_name'][$lKey],
				  'size' => & $_FILES['uploadfile']['size'][$lKey],
			  ) ) )
		{
			// Plugin returned 'false'.
			// Abort upload for this file:
			continue;
		}

		// Attempt to move the uploaded file to the requested target location:
		if( isset($_FILES['uploadfile']['_evo_fetched_url'][$lKey]) )
		{ // fetched remotely
			if( ! rename( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
			{
				$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
				// Abort upload for this file:
				continue;
			}
		}
		elseif( ! move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
		{
			$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
			// Abort upload for this file:
			continue;
		}

		// change to default chmod settings
		if( $newFile->chmod( NULL ) === false )
		{ // add a note, this is no error!
			$Messages->add( sprintf( T_('Could not change permissions of &laquo;%s&raquo; to default chmod setting.'), $newFile->dget('name') ), 'note' );
		}

		// Refreshes file properties (type, size, perms...)
		$newFile->load_properties();

		if( ! empty( $oldFile_thumb ) )
		{ // The file name was changed!
			if( $image_info )
			{
				$newFile_thumb = $newFile->get_preview_thumb( 'fulltype' );
			}
			else
			{
				$newFile_thumb = $newFile->get_size_formatted();
			}
			//$newFile_size = bytesreadable ($_FILES['uploadfile']['size'][$lKey]);
			$renamedMessages[$lKey]['message'] = sprintf( T_('"%s was renamed to %s. Would you like to replace %s with the new version instead?'),
													   '&laquo;'.$oldName.'&raquo;', '&laquo;'.$newName.'&raquo;', '&laquo;'.$oldName.'&raquo;' );
			$renamedMessages[$lKey]['oldThumb'] = $oldFile_thumb;
			$renamedMessages[$lKey]['newThumb'] = $newFile_thumb;
			$renamedFiles[$lKey]['oldName'] = $oldName;
			$renamedFiles[$lKey]['newName'] = $newName;
		}

		// Store extra info about the file into File Object:
		if( isset( $uploadfile_title[$lKey] ) )
		{ // If a title text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'title', trim( strip_tags($uploadfile_title[$lKey])) );
		}
		if( isset( $uploadfile_alt[$lKey] ) )
		{ // If an alt text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'alt', trim( strip_tags($uploadfile_alt[$lKey])) );
		}
		if( isset( $uploadfile_desc[$lKey] ) )
		{ // If a desc text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'desc', trim( strip_tags($uploadfile_desc[$lKey])) );
		}

		// Store File object into DB:
		$newFile->dbsave();
		$uploadedFiles[] = $newFile;
	}

	return array( 'uploadedFiles' => $uploadedFiles, 'failedFiles' => $failedFiles, 'renamedFiles' => $renamedFiles, 'renamedMessages' => $renamedMessages );
}


/**
 * Check if file exists in the target location with the given name. Used during file upload.
 * 
 * @param FileRoot target file Root
 * @param string target path
 * @param string file name
 * @param array the new file image_info
 * @return array contains two elements
 * 			first elements is a new File object
 * 			second element is the existing file thumb, or empty string, if the file doesn't exists
 */
function check_file_exists( $fm_FileRoot, $path, $newName, $image_info = NULL )
{
	// Get File object for requested target location:
	$FileCache = & get_FileCache();
	$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$newName, true );

	$num_ext = 0;
	$oldName = $newName;

	$oldFile_thumb = "";
	while( $newFile->exists() )
	{ // The file already exists in the target location!
		$num_ext++;
		$ext_pos = strrpos( $newName, '.');
		if( $num_ext == 1 )
		{
			if( $image_info == NULL )
			{
				$image_info = getimagesize( $newFile->get_full_path() );
			}
			$newName = substr_replace( $newName, '-'.$num_ext.'.', $ext_pos, 1 );
			if( $image_info )
			{
				$oldFile_thumb = $newFile->get_preview_thumb( 'fulltype' );
			}
			else
			{
				$oldFile_thumb = $newFile->get_size_formatted();
			}
		}
		else
		{
			$replace_length = strlen( '-'.($num_ext-1) );
			$newName = substr_replace( $newName, '-'.$num_ext, $ext_pos-$replace_length, $replace_length );
		}
		$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$newName, true );
	}

	return array( $newFile, $oldFile_thumb );
}


/**
 * Remove files with the given ids
 * 
 * @param array file ids to remove, default to remove all orphan file IDs
 * @param integer remove files older then the given hour, default NULL will remove all
 * @return integer the number of removed files
 */
function remove_orphan_files( $file_ids = NULL, $older_then = NULL )
{
	global $DB, $localtimenow;
	// asimo> This SQL query should use file class delete_restrictions array (currently T_links and T_users is explicitly used)
	// select orphan comment attachment file ids
	$sql = 'SELECT file_ID FROM T_files
				WHERE ( file_path LIKE "comments/p%" OR file_path LIKE "anonymous_comments/p%" ) AND file_ID NOT IN (
					SELECT * FROM (
						( SELECT DISTINCT link_file_ID FROM T_links 
							WHERE link_file_ID IS NOT NULL ) UNION
						( SELECT DISTINCT user_avatar_file_ID FROM T_users 
							WHERE user_avatar_file_ID IS NOT NULL ) ) AS linked_files )';

	if( $file_ids != NULL )
	{ // remove only from the given files
		$sql .= ' AND file_ID IN ( '.implode( ',', $file_ids ).' )';
	}

	$result = $DB->get_col( $sql );
	$FileCache = & get_FileCache();
	$count = 0;
	foreach( $result as $file_ID )
	{
		$File = $FileCache->get_by_ID( $file_ID, false, false );
		if( $older_then != NULL )
		{ // we have to check if the File is older then the given value
			$datediff = $localtimenow - filemtime( $File->_adfp_full_path );
			if( $datediff > $older_then * 3600 ) // convert hours to seconds
			{ // not older
				continue;
			}
		}
		// delete the file
		if( $File->unlink() )
		{
			$count++;
		}
	}

	return $count;
}


/*
 * $Log: _file.funcs.php,v $
 */
?>