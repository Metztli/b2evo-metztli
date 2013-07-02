<?php
/**
 * This file implements the Page Cache pruning Cron controller (delete old files from the cache)
 *
 * @author asimo: Attila Simo
 *
 * @version $Id: _prune_page_cache.job.php 3328 2013-03-26 11:44:11Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/_pagecache.class.php', 'PageCache' );

$result_message = PageCache::prune_page_cache();
if( empty( $result_message ) )
{
	return 1; /* OK */
}

return 100;

?>