<?php
/**
 * This file implements the Skin properties form.
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_skin_settings.form.php 1201 2012-04-07 04:03:31Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */
global $edited_Skin;

global $Blog, $current_User;


$Form = new Form( NULL, 'skin_settings_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'collection' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', 'skin' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'blog', $Blog->ID );

	$fieldset_title_links = '<span class="floatright">&nbsp;'.action_icon( T_('Select another skin...'), 'edit', regenerate_url( 'action', 'ctrl=coll_settings&amp;skinpage=selection' ), T_('Use a different skin').' &raquo;', 3, 4 ).'</span>';
	if( $current_User->check_perm( 'options', 'view' ) )
	{
		$fieldset_title_links .= ' <span class="floatright">'.action_icon( T_('Reset params'), 'reload', regenerate_url( 'action', 'ctrl=skins&amp;skin_ID='.$edited_Skin->ID.'&amp;blog='.$Blog->ID.'&amp;action=reset&amp;'.url_crumb('skin') ), ' '.T_('Reset params'), 3, 4 ).'&nbsp;</span>';
	}

	$Form->begin_fieldset( T_('Current skin').get_manual_link('blog_skin_settings').' '.$fieldset_title_links );

		Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name );

		$Form->info( T_('Skin name'), $edited_Skin->name );

		if( isset($edited_Skin->version) )
		{
				$Form->info( T_('Skin version'), $edited_Skin->version );
		}

		$Form->info( T_('Skin type'), $edited_Skin->type );

		if( $skin_containers = $edited_Skin->get_containers() )
		{
			$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers ).'</li></ul>';
		}
		else
		{
			$container_ul = '-';
		}
		$Form->info( T_('Containers'), $container_ul );

	$Form->end_fieldset();

	$skin_params = $edited_Skin->get_param_definitions( $tmp_params = array('for_editing'=>true) );

	$Form->begin_fieldset( T_('Params') );

		if( empty($skin_params) )
		{	// Advertise this feature!!
			echo '<p>'.T_('This skin does not provide any configurable settings.').'</p>';
		}
		else
		{
			load_funcs( 'plugins/_plugin.funcs.php' );

			// Loop through all widget params:
			foreach( $skin_params as $l_name => $l_meta )
			{
				// Display field:
				autoform_display_field( $l_name, $l_meta, $Form, 'Skin', $edited_Skin );
			}
		}

	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log: _coll_skin_settings.form.php,v $
 */
?>