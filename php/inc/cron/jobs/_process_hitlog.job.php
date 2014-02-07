<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @version $Id: _process_hitlog.job.php 5555 2014-01-03 00:10:21Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

keyphrase_job();
return 1; /* ok */
?>