<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @todo dh> Do not replace in tags, it matches e.g. the following for italic:
 *           """<img src="//url" /> [...] http://"""!
 *
 * @package plugins
 */
class gmcode_plugin extends Plugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 45;
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '3.0';
	var $number_of_installs = 1;


	/**
	 * GreyMatter formatting search array
	 *
	 * @access private
	 */
	var $search = array(
			'# \*\* (.+?) \*\* #x',                // **bold**
			'# \x5c\x5c (.+?) \x5c\x5c #x',        // \\italics\\
			'# (?<!:) \x2f\x2f (.+?) \x2f\x2f #x', // //italics// (not preceded by : as in http://)
			'# __ (.+?) __ #x',                    // __underline__
			'/ \#\# (.+?) \#\# /x',                // ##tt##
			'/ %%
				( \s*? \n )?      # Eat optional blank line after %%%
				(.+?)
				( \n \s*? )?      # Eat optional blank line before %%%
				%%
			/sx'                                   // %%codeblock%%
		);

	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
			'<strong>$1</strong>',
			'<em>$1</em>',
			'<em>$1</em>',
			'<span style="text-decoration:underline">$1</span>',
			'<tt>$1</tt>',
			'<div class="codeblock"><pre><code>$2</code></pre></div>'
		);


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\italics\\ //italics// __underline__ ##tt## %%codeblock%%');
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = callback_on_non_matching_blocks( $content, '~<[^>]*>~s', array($this, 'replace_callback') );

		return true;
	}


	/**
	 * Replace callback
	 * @param string
	 * @return string
	 */
	function replace_callback($text)
	{
		return preg_replace($this->search, $this->replace, $text);
	}

}


/*
 * $Log: _gmcode.plugin.php,v $
 */
?>