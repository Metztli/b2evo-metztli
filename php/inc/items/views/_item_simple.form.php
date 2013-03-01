<?php
/**
 * This file implements the SIMPLE Post form.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _item_simple.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var Item
 */
global $edited_Item;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $Session;

global $mode;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'item_checkchanges', 'post' );
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";

// ================================ START OF EDIT FORM ================================

$iframe_name = NULL;
$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}
$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $mode ) )   $Form->hidden( 'mode', $mode ); // used by bookmarklet
	if( isset( $edited_Item ) )
	{
		$Form->hidden( 'post_ID', $edited_Item->ID );

		// Here we add js code for attaching file popup window: (Yury)
		if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
		{ // item also created => we have $edited_Item->ID for popup window:
			echo_attaching_files_button_js( $iframe_name );
			// clear session variable:
			$Session->delete('create_edit_attachment');
		}
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );


	// Fields used in "advanced" form, but not here:
	$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
	$Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );
	$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
	$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
	$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
	$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
	$Form->hidden( 'metadesc', $edited_Item->get( 'metadesc' ) );
	$Form->hidden( 'metakeywords', $edited_Item->get( 'metakeywords' ) );


	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->hidden( 'item_priority', $edited_Item->priority );
		$Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
		$Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
		$Form->hidden( 'item_deadline', $edited_Item->datedeadline );
	}
	$Form->hidden( 'trackback_url', $trackback_url );
	$Form->hidden( 'renderers_displayed', 1 );
	$Form->hidden( 'renderers', $edited_Item->get_renderers_validated() );
	$Form->hidden( 'item_featured', $edited_Item->featured );
	$Form->hidden( 'item_order', $edited_Item->order );

	$creator_User = $edited_Item->get_creator_User();
	$Form->hidden( 'item_owner_login', $creator_User->login );
	$Form->hidden( 'item_owner_login_displayed', 1 );

	// CUSTOM FIELDS double
	for( $i = 1 ; $i <= 5; $i++ )
	{	// For each custom double field:
		$Form->hidden( 'item_double'.$i, $edited_Item->{'double'.$i} );
	}
	// CUSTOM FIELDS varchar
	for( $i = 1 ; $i <= 3; $i++ )
	{	// For each custom varchar field:
		$Form->hidden( 'item_varchar'.$i, $edited_Item->{'varchar'.$i} );
	}

	// TODO: Form::hidden() do not add, if NULL?!

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents').get_manual_link('post_contents_fieldset') );

	// Title input:
	$require_title = $Blog->get_setting('require_title');
	if( $require_title != 'none' )
	{
		$Form->switch_layout( 'none' );

		echo '<table cellspacing="0" class="compose_layout"><tr>';
		echo '<td class"label"><strong>'.T_('Title').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required'=>($require_title=='required')) );
		echo '</td><td width="1"><!-- for IE7 --></td></tr></table>';

		$Form->switch_layout( NULL );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		//-->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );

	echo_publish_buttons( $Form, $creating, $edited_Item );

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset($GLOBALS['files_Module']) )
	{
		attachment_iframe( $Form, $creating, $edited_Item, $Blog, $iframe_name );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Meta info').get_manual_link('post_simple_meta_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		$Form->switch_layout( 'linespan' );
		issue_date_control( $Form, false );
		$Form->switch_layout( NULL );
		echo '</div>';
	}

	echo '<table cellspacing="0" class="compose_layout">';
	echo '<tr><td class="label"><label for="item_tags">'.T_('Tags').':</strong> <span class="notes">'.T_('sep by ,').'</span></label></label></td>';
	echo '<td class="input">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';
	echo '</table>';

	$Form->end_fieldset();


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'simple' ) );
	?>

</div>

<div class="right_col">

	<?php
	// ################### CATEGORIES ###################

	cat_select( $Form );


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status );
	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( ( $Blog->get_setting( 'allow_comments' ) != 'never' ) && ( $Blog->get_setting( 'disable_comments_bypost' ) ) )
	{
		$Form->begin_fieldset( T_('Comments'), array( 'id' => 'itemform_comments' ) );

		?>
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="open" class="checkbox" <?php if( $post_comment_status == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />

			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="closed" class="checkbox" <?php if( $post_comment_status == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />

			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="disabled" class="checkbox" <?php if( $post_comment_status == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php

		$Form->end_fieldset();
	}

	?>

</div>

<div class="clear"></div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
echo_set_is_attachments();
echo_link_files_js();
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags();

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

/*
 * $Log: _item_simple.form.php,v $
 */
?>