<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap
 *
 * @version $Id: _item_block.inc.php 6134 2014-03-08 07:48:07Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $Skin;

// Default params:
$params = array_merge( array(
		'feature_block'    => false,
		'content_mode'     => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'       => 'bPost',
		'image_size'       => 'fit-400x320',
		'author_link_text' => 'preferredname',
	), $params );

echo '<div id="styled_content_block">'; // Beginning of post display
?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

		if( $disp != 'single' && $disp != 'page' )
		{ // Don't display this on disp=single because there is already title header in h2
			$Item->title( array(
					'before'    => '<h3 class="bTitle linked">',
					'after'     => '</h3>',
					'link_type' => 'permalink'
				) );
		}
	?>

	<div class="bSmallHead">
	<?php
		if( $Item->status != 'published' )
		{
			$Item->status( array( 'format' => 'styled' ) );
		}
		// Permalink:
		$Item->permanent_link( array(
				'text' => '#icon#',
			) );

		// We want to display the post time:
		$Item->issue_time( array(
				'before'      => ' '.T_('posted on '),
				'after'       => ' ',
				'time_format' => 'M j, Y',
			) );

		// Author
		$Item->author( array(
			'before'    => ' '.T_('by').' ',
			'after'     => ' ',
			'link_text' => $params['author_link_text'],
		) );

		// Categories
		$Item->categories( array(
			'before'          => T_('in').' ',
			'after'           => ' ',
			'include_main'    => true,
			'include_other'   => true,
			'include_external'=> true,
			'link_categories' => true,
		) );

		// Link for editing
		$Item->edit_link( array(
			'before'    => ' &bull; ',
			'after'     => '',
		) );
	?>
	</div>

	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------

		// List all tags attached to this post:
		$Item->tags( array(
				'before'    => '<div class="bSmallPrint">'.T_('Tags').': ',
				'after'     => '</div>',
				'separator' => ', ',
			) );
	?>

	<div class="bSmallPrint">
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'comments',
							'link_before' => '',
							'link_after' => '',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
							'use_popup' => false,
						) );

			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'trackbacks',
							'link_before' => ' &bull; ',
							'link_after' => '',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
							'use_popup' => false,
						) );
		?>
	</div>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h4>',
				'after_section_title'  => '</h4>',
				'author_link_text' => $params['author_link_text'],
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>
<?php echo '</div>'; // End of post display ?>