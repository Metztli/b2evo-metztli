<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
    'comment_start'  => '<div class="bComment">',
    'comment_end'    => '</div>',
		'link_to'		     => 'userurl>userpage',		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
    'Comment'        => NULL, // This object MUST be passed as a param!
	), $params );

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php
	$Comment->anchor();
	echo $params['comment_start'];
?>
	<div class="bCommentTitle">
	<?php
		switch( $Comment->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				if( empty($Comment->ID) )
				{	// PREVIEW comment
					echo T_('PREVIEW Comment from:').' ';
				}
				else
				{	// Normal comment
					$Comment->permanent_link( array(
							'before'    => '',
							'after'     => ' '.T_('from:').' ',
							'text' 			=> T_('Comment'),
							'nofollow'	=> true,
						) );
				}
				$Comment->author2( array(
						'before'       => ' ',
						'after'        => '#',
						'before_user'  => '',
						'after_user'   => '#',
						'format'       => 'htmlbody',
						'link_to'		   => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
						'link_text'    => 'preferredname',
					) );

				$Comment->msgform_link( $Blog->get('msgformurl') );
				// $Comment->author_url( '', ' &middot; ', '' );
				break;

			case 'trackback': // Display a trackback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 			=> T_('Trackback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;

			case 'pingback': // Display a pingback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 			=> T_('Pingback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;
		}
	?>
	</div>
	<?php $Comment->rating(); ?>
	<div class="bCommentText">
		<?php
			$Comment->avatar();
			$Comment->content();
		?>
	</div>
	<div class="bCommentSmallPrint">
		<?php
			$Comment->edit_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for editing */
			$Comment->delete_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for deleting */
		?>

		<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
	</div>
<?php
  echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->
<?php

/*
 * $Log: _item_comment.inc.php,v $
 */
?>