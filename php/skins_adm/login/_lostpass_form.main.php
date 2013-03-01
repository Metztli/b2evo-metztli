<?php
/**
 * This is the lost password form, from where the user can request
 * a set-password-link to be sent to his/her email address.
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
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_html_header.inc.php';

$Form = new Form( $htsrv_url_sensitive.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'lostpassform' );
	$Form->hidden( 'action', 'retrievepassword' );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	$Form->begin_fieldset();

	echo '<ol>';
	echo '<li>'.T_('Please enter your login below. Do <strong>NOT</strong> enter your e-mail address!').'</li>';
	echo '<li>'.T_('An email will be sent to your registered email address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to change your password.').'</li>';
	echo '</ol>';

	$Form->text( $dummy_fields[ 'login' ], $login, 16, T_('Login'), T_('Note: your login is NOT your email address.'), 20, 'input_text' );

	$Form->buttons_input( array(array( /* TRANS: Text for submit button to request an activation link by email */ 'value' => T_('Send me an email now!'), 'class' => 'ActionButton' )) );

	// $Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	$Form->end_fieldset();;

$Form->end_form();

require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log: _lostpass_form.main.php,v $
 */
?>