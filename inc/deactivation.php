<?php

namespace BABO;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Plugin De-activation class
 * 
 * @since 1.0.0
 */
class Babo_Deactivator
{

  public function __construct()
  {
    //remove log
    $logfile = BABO_PATH . 'logs/babo_progress.log';
    if (file_exists($logfile)) {
      @unlink($logfile);
    }

    //remove backups folder
    $babokey = esc_attr(get_option('babo_backup_key'));
    if (FALSE !== $babokey) {
      $bpath = WP_CONTENT_DIR . '/' . BABO_SLUG . '-' . $babokey;
      if (file_exists($bpath)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($bpath), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
          if (!$file->isDir()) {
            @unlink($file->getRealpath());
          }
        }

        @rmdir($bpath);
      }
    }

    //remove options
    $opts = array('babo_backup_key', 'babo_backup_last');
    foreach ($opts as $opt) {
      delete_option($opt);
    }

    //remove cron
    wp_clear_scheduled_hook('babo_clear_backups');
  }
}
