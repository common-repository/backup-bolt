<?php

namespace BABO\Admin;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Admin side enqueues, page register, ajax handlers
 * 
 * @since 1.0.0 
 */
class Babo_Admin_Init
{

  public function __construct()
  {
    new Babo_Enqueues();
    new Babo_Pages();
    new Babo_Ajax_Handlers();

    add_action('babo_clear_backups', [$this, 'babo_remove_all_backups']); //daily recurring cron

    $show_rev = get_option('babo_show_review');
    if ($show_rev != FALSE && $show_rev == 1 && FALSE === get_option('babo_show_review_disabled')) {
      add_action('admin_notices', [$this, 'babo_rateus']);
    }
  }

  public function babo_remove_all_backups()
  {

    $babokey = get_option('babo_backup_key');
    if (FALSE !== $babokey) {
      $bpath = WP_CONTENT_DIR . "/" . BABO_SLUG . "-" . esc_attr($babokey);

      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($bpath), RecursiveIteratorIterator::SELF_FIRST);

      foreach ($files as $file) {
        if (!$file->isDir() && FALSE !== stripos($file->getRealpath(), 'backupbolt-')) {
          @unlink($file->getRealpath());
        }
      }
    }
  }

  public function babo_rateus()
  {
    $reviewnonce = wp_create_nonce('baboreview');
    $html = '<div class="notice notice-info babo-admin-review">
        <div class="babo-review-box">
          <div><img src="' . BABO_URL . 'assets/symbol.png"/></div>
          <span><strong>' . esc_html__('Congratulations!', 'backup-bolt') . '</strong><p>Backup process was completed in record time!. Could you please do us a BIG favor & rate us with 5 star review to support further development of this plugin.</p></span>
        </div>
        <a class="babo-lets-review baborevbtn" href="https://wordpress.org/support/plugin/backup-bolt/reviews/#new-post" rel="nofollow noopener" target="_blank">' . esc_html__('Rate plugin', 'backup-bolt') . '</a>
        <a class="babo-did-review baborevbtn" href="#" data-nc="' . esc_attr($reviewnonce) . '" data-action="1">' . esc_html__("Don't show again", 'backup-bolt') . '</a>
        <a class="babo-later-review baborevbtn" href="#" data-nc="' . esc_attr($reviewnonce) . '" data-action="2">' . esc_html__('Remind me later', 'backup-bolt') . '&nbsp;<span class="dashicons dashicons-clock"></span></a>
      </div>';

    echo $html;
  }
}
