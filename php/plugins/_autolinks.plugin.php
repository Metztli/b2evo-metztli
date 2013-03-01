<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Automatic links plugin.
 *
 * @todo dh> Provide a setting for: fp> This should be a DIFFERENT plugin that kicks in last in the rendering and actually prcesses ALL links, auto links as well as explicit/manual links
 *   - marking external and internal (relative URL or on the blog's URL) links with a HTML/CSS class
 *   - add e.g. 'target="_blank"' to external links
 * @todo Add "max. displayed length setting" and add full title + dots in the middle to shorten it.
 *       (e.g. plain long URLs with a lot of params and such). This should not cause the layout to
 *       behave ugly. This should only shorten non-whitespace strings in the link's innerHTML of course.
 *
 * @package plugins
 */
class autolinks_plugin extends Plugin
{
	var $code = 'b2evALnk';
	var $name = 'Auto Links';
	var $priority = 60;
	var $version = '3.3.2';
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $number_of_installs = null;	// Let admins install several instances with potentially different word lists

	/**
	 * Lazy loaded from txt files
	 *
	 * @var array of array for each blog. Index 0 is for shared content
	 */
	var $link_array = array();

	var $already_linked_array;

	var $previous_word = null;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs and specific terms/defintions clickable');
		$this->long_desc = T_('This renderer automatically creates links for you. URLs can be made clickable automatically. Specific and frequently used terms can be configured to be automatically linked to a definition URL.');
	}


	/**
	 * @return array
	 */
	function GetDefaultSettings()
	{
		global $rsc_subdir;
		return array(
				'autolink_urls' => array(
						'label' => T_( 'Autolink URLs' ),
						'defaultvalue' => 1,
						'type' => 'checkbox',
						'note' => T_('Autolink URLs starting with http: https: mailto: aim: icq: as well as adresses of the form www.*.* or *@*.*'),
					),
				'autolink_defs_default' => array(
						'label' => T_( 'Autolink definitions' ),
						'defaultvalue' => 0,
						'type' => 'checkbox',
						'note' => T_('As defined in definitions.default.txt'),
					),
				'autolink_defs_local' => array(
						'label' => '',
						'defaultvalue' => 0,
						'type' => 'checkbox',
						'note' => T_('As defined in definitions.local.txt'),
					),
				'autolink_defs_db' => array(
						'label' => '',
						'type' => 'html_textarea',
						'rows' => 15,
						'note' => $this->T_( 'Enter custom definitions above.' ),
						'defaultvalue' => '',
					),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array(
				'autolink_defs_coll_db' => array(
						'label' => T_( 'Custom autolink definitions' ),
						'type' => 'html_textarea',
						'rows' => 15,
						'note' => $this->T_( 'Enter custom definitions above.' ),
						'defaultvalue' => '',
					),
			);
	}


	/**
	 * Lazy load global definitions array
	 *
	 * @param Blog
	 */
	function load_link_array( $Blog )
	{
		global $plugins_path;

		if( !isset($this->link_array[0]) )
		{	// global defs NOT already loaded
			$this->link_array[0] = array();

			if( $this->Settings->get( 'autolink_defs_default' ) )
			{	// Load defaults:
				$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.default.txt', 0 );
			}
			if( $this->Settings->get( 'autolink_defs_local' ) )
			{	// Load local user defintions:
				$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.local.txt', 0 );
			}
			$text = $this->Settings->get( 'autolink_defs_db', 0 );
			if( !empty($text) )
			{	// Load local user defintions:
				$this->read_textfield( $text, 0 );
			}
		}

		// load defs for current blog:
		$coll_ID = $Blog->ID;
		if( !isset($this->link_array[$coll_ID]) )
		{	// This blog is not loaded yet:
			$this->link_array[$coll_ID] = array();
			$text = $this->get_coll_setting( 'autolink_defs_coll_db', $Blog );
			if( !empty($text) )
			{	// Load local user defintions:
				$this->read_textfield( $text, $coll_ID );
			}
		}

		// Prepare working link array:
		$this->replacement_link_array = array_merge( $this->link_array[0], $this->link_array[$coll_ID] );

		// pre_dump( $this->replacement_link_array );
	}


	/**
 	 * Load contents of one specific CSV file
	 *
	 * @param string $filename
	 */
	function read_csv_file( $filename, $coll_ID )
	{
		if( ! $handle = @fopen( $filename, 'r') )
		{	// File could not be opened:
			return;
		}

		while( ($data = fgetcsv($handle, 1000, ';', '"')) !== false )
		{
			$this->read_line( $data, $coll_ID );
		}

		fclose($handle);
	}


	/**
 	 * Load contents of one large textfield to be treated as CSV
 	 *
 	 * Note: This method is probably not well suited for very large lists.
	 *
	 * @param string $filename
	 */
	function read_textfield( $text, $coll_ID )
	{
		// split into lines:
		$lines = preg_split( '#\r|\n#', $text );

		foreach( $lines as $line )
		{
			// CSV style decoding in memory:
			// $keywords = preg_split( "/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|[\s,]+/", "textline with, commas and \"quoted text\" inserted", 0, PREG_SPLIT_DELIM_CAPTURE );
			$data = explode( ';', $line );
			$this->read_line( $data, $coll_ID );
		}
	}


	/**
	 * read line
	 *
	 * @param exploded $data array
	 */
	function read_line( $data, $coll_ID )
	{
		if( empty($data[0]) || empty($data[3]) )
		{	// Skip empty and comment lines
			return;
		}

		$word = $data[0];
		$url = $data[3];
		if( $url =='-' )
		{	// Remove URL (useful to remove some defs on a specific site):
			unset( $this->link_array[$coll_ID][$word] );
		}
		else
		{
			$this->link_array[$coll_ID][$word] = array( $data[1], $url );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];
		$Item = & $params['Item'];
    /**
		 * @var Blog
		 */
		$item_Blog = $params['Item']->get_Blog();

		// load global defs
		$this->load_link_array( $item_Blog );

		// reset already linked:
		$this->already_linked_array = array();
		if( preg_match_all( '|[\'"](http://[^\'"]+)|i', $content, $matches ) )
		{	// There are existing links:
			$this->already_linked_array = $matches[1];
		}

		if( $this->Settings->get( 'autolink_urls' ) )
		{ // First, make the URLs clickable:
			$content = make_clickable( $content );
		}

		if( ! empty($this->replacement_link_array) )
		{ // Make the desired remaining terms/definitions clickable:
			$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback' ) );
		}

		return true;
	}


	/**
	 * Callback function for {@link make_clickable()}.
	 *
	 * @return string The clickable text.
	 */
	function make_clickable_callback( $text, $moredelim = '&amp;' )
	{
		$this->previous_lword = null;

		// Find word with 3 characters at least:
		$text = preg_replace_callback( '/(^|\s|[(),;])([@a-z0-9_\-]{3,})/i', array( & $this, 'replace_callback' ), $text );

		// pre_dump($text);

		// Cleanup words to be deleted:
		$text = preg_replace( '/[@a-z0-9_\-]+\s*==!#DEL#!==/i', '', $text );

		return $text;
	}


	/**
	 * This is the 2nd level of callback!!
	 */
	function replace_callback( $matches )
	{
		$sign = $matches[1];
		$word = $matches[2];
		$lword = strtolower($word);
		$r = $sign.$word;

		if( isset( $this->replacement_link_array[$lword] ) )
		{
			$previous = $this->replacement_link_array[$lword][0];
			$url = 'http://'.$this->replacement_link_array[$lword][1];

			// pre_dump( $this->already_linked_array );

			if( in_array( $url, $this->already_linked_array ) )
			{	// Do not repeat link to same destination:
				// pre_dump( 'already linked:'. $url );
				// save previous word
				$this->previous_word = $word;
				$this->previous_lword = $lword;
				return $r;
			}

			if( !empty($previous) )
			{
				if( $this->previous_lword != $previous )
				{	// We do not have the required previous word
					// pre_dump( 'previous word does not match', $this->previous_lword, $previous );
					// save previous word
					$this->previous_word = $word;
					$this->previous_lword = $lword;
					return $r;
				}
				$r = '==!#DEL#!==<a href="'.$url.'">'.$this->previous_word.' '.$word.'</a>';
			}
			else
			{
				$r = $sign.'<a href="'.$url.'">'.$word.'</a>';
			}

			// Make sure we don't link to same destination twice in the same text/post:
			$this->already_linked_array[] = $url;
			// pre_dump( $this->already_linked_array );
		}

		$this->previous_word = $word;
		$this->previous_lword = $lword;

		return $r;
	}
}


/*
 * $Log: _autolinks.plugin.php,v $
 */
?>