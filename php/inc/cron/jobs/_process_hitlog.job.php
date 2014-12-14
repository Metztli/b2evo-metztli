<?php
/**
 * This file implements the cron job to extract keyphrase from the hit logs
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: _process_hitlog.job.php 7762 2014-12-06 06:23:15Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( '../inc/sessions/model/_hitlog.funcs.php' );

$extract_keyphrase_result = extract_keyphrase_from_hitlogs();
if( $extract_keyphrase_result === true )
{
	return 1; /* ok */
}

$result_message = $extract_keyphrase_result;
return 2;
?>