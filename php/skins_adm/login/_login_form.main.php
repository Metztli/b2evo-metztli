<?php
/**
 * This is the login form
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


// TODO: dh> the message below should also get displayed in _reg_form.
// E.g., the user might have clicked accidently on an old password change link.
if( $Session->has_User() )
{ // The user is already logged in...
	$tmp_User = & $Session->get_User();
	if( $tmp_User->validated )
	{	// User is validated
		if( empty($redirect_to) || $redirect_to == '/login.php' )
		{	// Prevent endless loops
			$redirect_to = $baseurl;
		}
		$Messages->add( sprintf( T_('Note: You are already logged in as %s!'), $tmp_User->get('login') )
			.' <a href="'.htmlspecialchars($redirect_to).'">'.T_('Continue').' &raquo;</a>', 'note' );
	}
	unset($tmp_User);
}


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Log in to your account');
$page_icon = 'icon_login.gif';

/*
  fp> The login page is small. Let's use it as a preloader for the backoffice (which is awfully slow to initialize)
  fp> TODO: find a javascript way to preload more stuff (like icons) WITHOUT delaying the browser autocomplete of the login & password fields
	dh>
	// include jquery JS:
	require_js( '#jquery#' );

	jQuery(function(){
	 alert("Document is ready");
	});
	See also http://www.texotela.co.uk/code/jquery/preload/ - might be a good opportunity to take a look at jQuery for you.. :)
 */


require_js( 'functions.js' );

$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
if( $transmit_hashed_password )
{ // Include JS for client-side password hashing:
	require_js( 'md5.js' );
	require_js( 'sha1.js' );
}

/**
 * Login header
 */
require dirname(__FILE__).'/_html_header.inc.php';

$links = array();

if( empty($login_required)
	&& $action != 'req_validatemail'
	&& strpos($redirect_to, $admin_url) !== 0
	&& strpos($ReqHost.$redirect_to, $admin_url ) !== 0 )
{ // No login required, allow to pass through
	// TODO: dh> validate redirect_to param?!
	$links[] = '<a href="'.htmlspecialchars(url_rel_to_same_host($redirect_to, $ReqHost)).'">'
	./* Gets displayed as link to the location on the login form if no login is required */ T_('Abort login!').'</a>';
}

if( is_logged_in() )
{ // if we arrive here, but are logged in, provide an option to logout (e.g. during the email
	// validation procedure)
	$links[] = get_user_logout_link();
}

if( count($links) )
{
	echo '<div style="float:right; margin: 0 0 1em">'.implode( $links, ' &middot; ' ).'</div>
	<div class="clear"></div>';
}


// The login form has to point back to itself, in case $htsrv_url_sensitive is a "https" link and $redirect_to is not!
$Form = new Form( $htsrv_url_sensitive.'login.php', 'evo_login_form', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'loginform' );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	if( isset( $action, $reqID, $sessID ) && $action == 'validatemail' )
	{ // the user clicked the link from the "validate your account" email, but has not been logged in; pass on the relevant data:
		$Form->hidden( 'action', 'validatemail' );
		$Form->hidden( 'reqID', $reqID );
		$Form->hidden( 'sessID', $sessID );
	}

	if( $transmit_hashed_password )
	{ // used by JS-password encryption/hashing:
		$pwd_salt = $Session->get('core.pwd_salt');
		if( empty($pwd_salt) )
		{ // Do not regenerate if already set because we want to reuse the previous salt on login screen reloads
			// fp> Question: the comment implies that the salt is reset even on failed login attemps. Why that? I would only have reset it on successful login. Do experts recommend it this way?
			// but if you kill the session you get a new salt anyway, so it's no big deal.
			// At that point, why not reset the salt at every reload? (it may be good to keep it, but I think the reason should be documented here)
			$pwd_salt = generate_random_key(64);
			$Session->set( 'core.pwd_salt', $pwd_salt, 86400 /* expire in 1 day */ );
			$Session->dbsave(); // save now, in case there's an error later, and not saving it would prevent the user from logging in.
		}
		$Form->hidden( 'pwd_salt', $pwd_salt );
		$Form->hidden( 'pwd_hashed', '' ); // gets filled by JS
	}

	$Form->begin_fieldset();

	$Form->text_input( $dummy_fields[ 'login' ], $login, 16, T_('Login'), T_('Type your username, <b>not</b> your email address.'),
			array( 'maxlength' => 20, 'class' => 'input_text', 'required'=>true ) );

	$pwd_note = '<a href="'.$htsrv_url_sensitive.'login.php?action=lostpassword&amp;redirect_to='
		.rawurlencode( url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	if( !empty($login) )
	{
		$pwd_note .= '&amp;'.$dummy_fields[ 'login' ].'='.rawurlencode($login);
	}
	$pwd_note .= '">'.T_('Lost password ?').'</a>';

	$Form->password_input( $dummy_fields[ 'pwd' ], '', 16, T_('Password'), array( 'note'=>$pwd_note, 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );



	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	// Submit button(s):
	$submit_buttons = array( array( 'name'=>'login_action[login]', 'value'=>T_('Log in!'), 'class'=>'search', 'style'=>'font-size: 120%' ) );
	if( strpos( $redirect_to, $admin_url ) !== 0
		&& strpos( $ReqHost.$redirect_to, $admin_url ) !== 0 // if $redirect_to is relative
		&& ! is_admin_page() )
	{ // provide button to log straight into backoffice, if we would not go there anyway
		$submit_buttons[] = array( 'name'=>'login_action[redirect_to_backoffice]', 'value'=>T_('Log into backoffice!'), 'class'=>'search' );
	}

	$Form->buttons_input($submit_buttons);

	echo '<div class="center notes" style="margin: 1em 0">'.T_('You will have to accept cookies in order to log in.').'</div>';

	// $Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	$Form->end_fieldset();

	// Passthrough REQUEST data (when login is required after having POSTed something)
	// (Exclusion of 'login_action', 'login', and 'action' has been removed. This should get handled via detection in Form (included_input_field_names),
	//  and "action" is protected via crumbs)
	$Form->hiddens_by_key( remove_magic_quotes($_REQUEST) );
$Form->end_form();

?>

<script type="text/javascript">
	// Autoselect login text input or pwd input, if there's a login already:
	var login = document.getElementById('<?php echo $dummy_fields[ 'login' ] ?>');
	if( login.value.length > 0 )
	{	// Focus on the password field:
		document.getElementById('<?php echo $dummy_fields[ 'pwd' ] ?>').focus();
	}
	else
	{	// Focus on the login field:
		login.focus();
	}


	<?php
	if( $transmit_hashed_password )
	{
		?>
		// Hash the password onsubmit and clear the original pwd field
		// TODO: dh> it would be nice to disable the clicked/used submit button. That's how it has been when the submit was attached to the submit button(s)
		addEvent( document.getElementById("evo_login_form"), "submit", function(){
			// this.value = '<?php echo TS_('Please wait...') ?>';
				var form = document.getElementById('evo_login_form');

				// Calculate hashed password and set it in the form:
				if( form.pwd_hashed && form.<?php echo $dummy_fields[ 'pwd' ] ?> && form.pwd_salt && typeof hex_sha1 != "undefined" && typeof hex_md5 != "undefined" )
				{
					// We first hash to md5, because that's how the passwords are stored in the database
					// We then hash with the salt using SHA1 (fp> can't we do that with md5 again, in order to load 1 less Javascript library?)
					// NOTE: MD5 is kind of "weak" and therefor we also use SHA1
					form.pwd_hashed.value = hex_sha1( hex_md5(form.<?php echo $dummy_fields[ 'pwd' ] ?>.value) + form.pwd_salt.value );
					form.<?php echo $dummy_fields[ 'pwd' ] ?>.value = "padding_padding_padding_padding_padding_padding_hashed_<?php echo $Session->ID /* to detect cookie problems */ ?>";
					// (paddings to make it look like encryption on screen. When the string changes to just one more or one less *, it looks like the browser is changing the password on the fly)
				}
				return true;
			}, false );
		<?php
	}
	?>
</script>


<div class="login_actions" style="text-align:right">
	<?php
	echo get_user_register_link( '', '', T_('No account yet? Register here').' &raquo;', '#', true /*disp_when_logged_in*/, $redirect_to, 'login form' );
	?>
</div>


<?php
require dirname(__FILE__).'/_html_footer.inc.php';


/*
 * $Log: _login_form.main.php,v $
 */
?>