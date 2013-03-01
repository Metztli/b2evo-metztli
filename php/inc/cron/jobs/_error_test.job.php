<?php
/**
 * This file implements the Error Test Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _error_test.job.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$result_message = T_('The Error TEST cron controller simulates an error, thus this "error" is normal!');

return 100; /* Simulated error */

/*
 * $Log: _error_test.job.php,v $
 */
?>