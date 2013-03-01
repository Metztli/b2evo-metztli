<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _item_list_sidebar.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;

global $tab, $show_past, $show_future, $show_statuses, $s, $sentence, $exact, $author, $assgn, $status;

/**
 * @var ItemList
 */
global $ItemList;

load_funcs( 'skins/_skin.funcs.php' );

$Widget = new Widget();
$template = $AdminUI->get_template( 'side_item' );

$Widget->title = format_to_output( $Blog->get_maxlen_name( 22 ), 'htmlbody' );
echo $Widget->replace_vars( $template['block_start'] );

	// CALENDAR:
	// Call the Calendar plugin:
	$Plugins->call_by_code( 'evo_Calr', array(	// Params follow:
			'block_start'=>'',
			'block_end'=>'',
			'title'=>'',								// No title.
			'link_type'=>'context', 		// Preserve page context
		) );

echo $template['block_end'];

$Widget = new Widget();
$Widget->title = T_('Filters');
if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 4, 4 );
}
echo $Widget->replace_vars( $template['block_start'] );

	$Form = new Form( NULL, 'resetform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->hidden_ctrl();
		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $Blog->ID );

		echo '<fieldset>';
		echo '<legend>'.T_('Posts to show').'</legend>';
		?>
		<div>

		<input type="checkbox" name="show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" '?> />
		<label for="ts_min"><?php echo T_('Past') ?></label><br />

		<input type="checkbox" name="show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" '?> />
		<label for="ts_max"><?php echo T_('Future') ?></label>

		</div>

		<div>

		<input type="checkbox" name="show_statuses[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="protected" id="sh_protected" class="checkbox" <?php if( in_array( "protected", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_protected"><?php echo T_('Protected') ?> <span class="notes">(<?php echo T_('Members only') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="private" id="sh_private" class="checkbox" <?php if( in_array( "private", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_private"><?php echo T_('Private') ?> <span class="notes">(<?php echo T_('You only') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="redirected" id="sh_redirected" class="checkbox" <?php if( in_array( "redirected", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_redirected"><?php echo T_('Redirected') ?></label><br />

	 	</div>

		<?php
		echo '</fieldset>';


		echo '<fieldset>';
		echo '<legend>'.T_('Title / Text contains').'</legend>';

		echo $Form->inputstart;
		?>
		<div><input type="text" name="s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" /></div>
		<?php
		echo $Form->inputend;
		// echo T_('Words').' : ';
		?>
		<div class="tile">
			<input type="radio" name="sentence" value="AND" id="sentAND" class="radio" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
			<label for="sentAND"><?php echo T_('AND') ?></label>
		</div>
		<div class="tile">
			<input type="radio" name="sentence" value="OR" id="sentOR" class="radio" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
			<label for="sentOR"><?php echo T_('OR') ?></label>
		</div>
		<div class="tile">
			<input type="radio" name="sentence" value="sentence" id="sentence" class="radio" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
			<label for="sentence"><?php echo T_('Entire phrase') ?></label>
		</div>
		<div class="tile">
			<input type="checkbox" name="exact" value="1" id="exact" class="checkbox" <?php if( $exact ) echo 'checked="checked" '?> />
			<label for="exact"><?php echo T_('Exact match') ?></label>
		</div>

		<?php
		echo '</fieldset>';


		/*
		 * Assignees:
 		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Assignees').'</legend>';
		// Load current blog members into cache:
		$UserCache = & get_UserCache();
		$UserCache->load_blogmembers( $Blog->ID );
		if( count($UserCache->cache) )
		{
			echo '<ul>';

			echo '<li><input type="radio" name="assgn" value="-" class="radio"';
			if( '-' == $assgn ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn=-' ).'">'.T_('Not assigned').'</a></li>';

			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="assgn" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $assgn ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn='.$loop_Obj->ID ).'">';
				$loop_Obj->preferred_name();
				echo '</a></li>';
			}
			echo '</ul>';
		}
		echo '</fieldset>';


		/*
		 * Authors:
		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Authors').'</legend>';
		// Load current blog members into cache:
		$UserCache->load_blogmembers( $Blog->ID );
		if( count($UserCache->cache) )
		{
			echo '<ul>';
			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="author" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $author ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'author', 'author='.$loop_Obj->ID ).'">';
				$loop_Obj->preferred_name();
				echo '</a></li>';
			}
			echo '</ul>';
		}
		echo '</fieldset>';


		/*
		 * Statuses
 		 * TODO: allow multiple selection
		 */
		$ItemStatusCache = & get_ItemStatusCache();
		$ItemStatusCache->load_all(); // TODO: load for current blog only
		if( count($ItemStatusCache->cache) )
		{	// We have satuses:
			echo '<fieldset>';
			echo '<legend>'.T_('Statuses').'</legend>';
			echo '<ul>';

			echo '<li><input type="radio" name="status" value="-" class="radio"';
			if( '-' == $status ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( 'status', 'status=-' ).'">'.T_('Without status').'</a></li>';

			foreach( $ItemStatusCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="status" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $status ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'status', 'status='.$loop_Obj->ID ).'">';
				$loop_Obj->disp('name');
				echo '</a></li>';
			}
			echo '</ul>';
			echo '</fieldset>';
		}

		// --------------------------------- START OF CATEGORY LIST --------------------------------
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_category_list',
				// Optional display params
				'block_start' => '<fieldset>',
				'block_end' => '</fieldset>',
				'block_title_start' => '<legend>',
				'block_title_end' => '</legend>',
				'link_type' => 'context',
				'display_checkboxes' => 1,
			) );
		// ---------------------------------- END OF CATEGORY LIST ---------------------------------


		// ARCHIVES:
		// Call the Archives plugin:
		$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
				'block_start'=>'<fieldset>',
				'block_end'=>"</fieldset>\n",
				'title'=>'<legend>'.T_('Archives')."</legend>\n",
				'link_type'=>'context', 							// Preserve page context
				'form'=>true,                         // add form fields (radio buttons)
				'limit'=>'',                          // No limit
				'more_link'=>'',                      // No more link
			)	);


		$Form->submit( array( 'submit', T_('Search'), 'search' ) );

		if( $ItemList->is_filtered() )
		{
			// TODO: style this better:
			echo '&nbsp; <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset">'.T_('Reset all filters!').'</a>';
		}

	$Form->end_form();

echo $template['block_end'];

/*
 * $Log: _item_list_sidebar.view.php,v $
 */
?>