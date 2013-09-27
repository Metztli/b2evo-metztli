<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @version $Id: _process_hitlog.job.php 4858 2013-09-24 23:58:17Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	// Extract keyphrases from the hitlog:
	keyphrase_job();
	return 1; /* ok */

?>