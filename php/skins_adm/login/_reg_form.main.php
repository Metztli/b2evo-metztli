<?php
/**
 * This is the registration form
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * Include page header:
 */
$page_title = T_('New account creation');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_html_header.inc.php';


$Form = new Form( $htsrv_url_sensitive.'register.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'regform' );
$Form->hidden( 'action', 'register' );
$source = param( 'source', 'string', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

$Form->begin_fieldset();

	$Form->text_input( $dummy_fields[ 'login' ], $login, 22, T_('Login'), T_('Choose a username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true ) );

	$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'), array( 'note'=>T_('Choose a password.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '', array( 'note'=>T_('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );

	$Form->text_input( $dummy_fields[ 'email' ], $email, 55, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{
		$CountryCache = & get_CountryCache();
		$Form->select_input_object( 'country', $country, $CountryCache, T_('Country'), array('allow_none'=>true, 'required'=>true) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender == 'required' )
	{
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
				), T_('I am'), array( 'required' => true ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{
		$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
	}

	$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form ) );

	$Form->buttons_input( array( array('name'=>'submit', 'value'=>T_('Register my account now!'), 'class'=>'ActionInput', 'style'=>'font-size: 120%' ) ) );

	// $Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

$Form->end_fieldset();
$Form->end_form(); // display hidden fields etc
?>

<div style="margin-top: 1em">
	<a href="<?php echo $htsrv_url_sensitive.'login.php?redirect_to='.rawurlencode(url_rel_to_same_host($redirect_to, $htsrv_url_sensitive)) ?>">&laquo; <?php echo T_('Already have an account... ?') ?></a>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log: _reg_form.main.php,v $
 */
?>