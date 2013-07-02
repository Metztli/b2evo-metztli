<?php
/**
 * This file implements the Error Test Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _error_test.job.php 3328 2013-03-26 11:44:11Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$result_message = T_('The Error TEST cron controller simulates an error, thus this "error" is normal!');

return 100; /* Simulated error */

?>