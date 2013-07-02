<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @version $Id: _process_hitlog.job.php 360 2011-11-21 09:49:12Z vitaliy $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	keyphrase_job();
	return 1; /* ok */

?>