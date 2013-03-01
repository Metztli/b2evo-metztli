<?php
/**
 * This is the template that displays the comment form for a post
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

global $htsrv_url, $dummy_fields;

$Form = new Form( $htsrv_url.'message_send.php' );
	$Form->begin_form( 'bComment' );

	$Form->add_crumb( 'newmessage' );
	if( !empty( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}
	$Form->hidden( 'recipient_id', $recipient_id );
	$Form->hidden( 'post_id', $post_id );
	$Form->hidden( 'comment_id', $comment_id );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url) );

	?>

	<fieldset>
		<div class="label"><label><?php echo T_('To')?>:</label></div>
		<div class="info"><strong><?php echo $recipient_name;?></strong></div>
	</fieldset>

	<?php
	// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
	$Form->text_input( $dummy_fields[ 'name' ], $email_author, 40, T_('From'), T_('Your name.'), array( 'maxlength'=>50, 'class'=>'wide_input', 'required'=>true ) );

	if( $allow_msgform == 'email' )
	{
		$Form->text_input( $dummy_fields[ 'email' ], $email_author_address, 40, T_('Email'), T_('Your email address. (Will <strong>not</strong> be displayed on this site.)'),
			 array( 'maxlength'=>150, 'class'=>'wide_input', 'required'=>true ) );
	}

	$Form->text_input( $dummy_fields[ 'subject' ], $subject, 40, T_('Subject'), T_('Subject of your message.'), array( 'maxlength'=>255, 'class'=>'wide_input', 'required'=>true ) );

	$Form->textarea( $dummy_fields[ 'content' ], '', 15, T_('Message'), T_('Plain text only.'), 35, 'wide_textarea' );

	$Plugins->trigger_event( 'DisplayMessageFormFieldset', array( 'Form' => & $Form,
		'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );

	$Form->begin_fieldset();
	?>
		<div class="input">
			<?php
			$Form->button_input( array( 'name' => 'submit_message_'.$recipient_id, 'class' => 'submit', 'value' => T_('Send message') ) );

			$Plugins->trigger_event( 'DisplayMessageFormButton', array( 'Form' => & $Form,
				'recipient_ID' => & $recipient_id, 'item_ID' => $post_id, 'comment_ID' => $comment_id ) );
			?>
		</div>
		<?php
	$Form->end_fieldset();
	?>

	<div class="clear"></div>

<?php
$Form->end_form();

/*
 * $Log: _contact_msg.form.php,v $
 */
?>