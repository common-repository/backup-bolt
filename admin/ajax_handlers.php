<?php

namespace BABO\Admin;

use Babo_Backup;

/**
 * Ajax handlers admin side
 * 
 * @since 1.0.0
 * 
 */
class Babo_Ajax_Handlers
{

  public function __construct()
  {
    add_action('wp_ajax_babo_calculate_backup', [$this, 'calculate_backup']);
    add_action('wp_ajax_babo_initiate_backup', [$this, 'initiate_backup']);
    add_action('wp_ajax_babo_refresh_log', [$this, 'log_fetcher']);
    add_action('wp_ajax_babo_stop_backup', [$this, 'stop_the_backup']);
    add_action('wp_ajax_babo_backup_success', [$this, 'backup_successful']);

    add_action('wp_ajax_babo_process_backup', [$this, 'process_backup_batch']);

    add_action('wp_ajax_babo_review_notice', [$this, 'babo_review_disable']);
    add_action('babo_show_reviewrequest', array($this, 'babo_set_review_flag'));
  }

  public function calculate_backup()
  {
    if (!wp_verify_nonce($_POST['nc'], 'babo-backup') || !current_user_can('manage_options')) {
      exit('Unauthorized');
    }

    $items = explode('&', sanitize_text_field($_POST['items'])); //selected checkboxes 
    $selections = [];

    if (!empty($items)) {
      foreach ($items as $item) {
        $itemarr = explode('=', $item);
        $selections[] = sanitize_text_field($itemarr[0]);
      }
    }

    $backup_path = WP_CONTENT_DIR;
    if (in_array('full-wp', $selections)) {
      $backup_path = ABSPATH;
    }

    $totalsize = \BABO\Babo_Utility::calculate_dirsize($backup_path);

    $free = intval(disk_free_space(ABSPATH));

    echo 'Total Backup Size: ' . number_format($totalsize, 2) . ' MB <br>Available Free Space On Your Server: ' . number_format($free / 1024 / 1024 / 1024, 2) . ' GB';

    exit();
  }

  public function initiate_backup()
  {
    if (!wp_verify_nonce($_POST['nc'], 'babo-initiate-backup') || !current_user_can('manage_options')) {
      exit('Unauthorized'); //todo catch
    }

    $items = explode('&', sanitize_text_field($_POST['items'])); //selected checkboxes
    $selections = [];

    if (!empty($items)) {
      foreach ($items as $item) {
        $itemarr = explode('=', $item);
        $selections[] = sanitize_text_field($itemarr[0]);
      }
    }

    update_option('babo_backup_items', $selections);

    new \BABO\Babo_Job_Starter();

    exit();
  }

  public function log_fetcher()
  {
    if (!wp_verify_nonce($_GET['nc'], 'babo-initiate-backup') || !current_user_can('manage_options')) {
      exit('ERROR - Unauthorized'); //todo catch
    }

    if (!file_exists(BABO_PATH . 'logs/babo_progress.log')) {
      exit('ERROR - Log file not found');
    }

    $get = @file_get_contents(BABO_PATH . 'logs/babo_progress.log');

    echo nl2br(esc_html($get));
    exit();
  }

  public function process_backup_batch()
  {
    ignore_user_abort(true);

    $params = (array) $_POST['res']; //individual array elements are sanitized below

    if (!wp_verify_nonce($_POST['nc'], 'babo-initiate-backup') || !current_user_can('manage_options')) {
      exit('Unauthorized'); //todo catch
    }

    $abspath = preg_replace("/[^A-Za-z0-9-._\/]+/", "", $params['abs']);
    $contentdir = preg_replace("/[^A-Za-z0-9-._\/]+/", "", $params['cdir']);

    //define('BABO_BACKGROUND_REQUEST', true);
    define('BABO_MEMORYPEAK', intval($params['memory_peak']));
    define('BABO_ABSPATH', $abspath);
    define('BABO_WP_CONTENT', $contentdir);

    @set_time_limit(259200);
    @ini_set('max_execution_time', '259200');
    @ini_set('max_input_time', '259200');
    @ini_set('session.gc_maxlifetime', '1200');
    @ini_set('memory_limit', (BABO_MEMORYPEAK * 4 + 16) . 'M');


    if ($params['start_at'] == 1) {

      // $items = explode('&', preg_replace("/[^A-Za-z-._=&\/]+/", "", $params['items'])); //selected checkboxes
      // $selections = [];

      // if (!empty($items)) {
      //   foreach ($items as $item) {
      //     $itemarr = explode('=', $item);
      //     $selections[] = sanitize_text_field($itemarr[0]);
      //   }
      // }
      $selections = $params['selections'];

      update_option('babo_backup_items', $selections);
    }

    require_once BABO_WP_CONTENT . '/plugins/backup-bolt/inc/babo_backup.php';
    new Babo_Backup($params);

    exit();
  }

  public function stop_the_backup()
  {

    if (!wp_verify_nonce($_GET['nc'], 'babo-terminate-backup') || !current_user_can('manage_options')) {
      exit('false');
    }

    if (file_exists(ABSPATH . 'babo_running')) {
      touch(ABSPATH . '.babo_abort');
    }

    exit();
  }

  public function backup_successful()
  {

    if (!current_user_can('manage_options')) {
      exit('Unauthorized access');
    }

    update_option('babo_show_review', 1);

    update_option('babo_backup_last', sanitize_text_field($_POST['fname']));
    exit();
  }

  public function babo_review_disable()
  {

    if (!wp_verify_nonce($_POST['nc'], 'baboreview') || !current_user_can('manage_options')) {
      exit('Unauthorized');
    }

    $ch = (int) $_POST['choice'];

    if ($ch == 2) { //remind later
      delete_option('babo_show_review');
      wp_schedule_single_event(strtotime('+3 day', time()), 'babo_show_reviewrequest');
    } else { //already reviewed //dont show again
      update_option('babo_show_review_disabled', true);
      delete_option('babo_show_review');
    }

    exit();
  }

  public function babo_set_review_flag()
  {
    update_option('babo_show_review', 1);
  }
}
