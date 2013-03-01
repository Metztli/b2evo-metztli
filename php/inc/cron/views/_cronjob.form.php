<?php
/**
 * This file implements the UI view for the cron job form.
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
 * @version $Id: _cronjob.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $localtimenow, $cron_job_names;

$Form = new Form( NULL, 'cronjob' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('New scheduled job') );

	$Form->add_crumb( 'crontask' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );
	$Form->hidden( 'action', 'create' );

	$Form->begin_fieldset( T_('Job details').get_manual_link('scheduler_job_form') );

		$Form->select_input_array( 'cjob_type', NULL, $cron_job_names, T_('Job type') );

		$Form->date_input( 'cjob_date', date2mysql( $localtimenow ), T_('Schedule date'), array(
							 'required' => true ) );

		$Form->time_input( 'cjob_time', date2mysql( $localtimenow ), T_('Schedule time'), array(
							 'required' => true ) );

		$Form->duration_input( 'cjob_repeat_after', 0, T_('Repeat every'), 'days', 'minutes', array( 'minutes_step' => 1 ) );

	$Form->end_fieldset();

$Form->end_form( array(
			array( 'submit', 'submit', T_('Create'), 'SaveButton' ),
			array( 'reset', '', T_('Reset'), 'ResetButton' ),
		) );


/*
 * $Log: _cronjob.form.php,v $
 */
?>