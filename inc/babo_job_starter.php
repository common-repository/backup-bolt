<?php

namespace BABO;

use BABO\Babo_Logger;

class Babo_Job_Starter
{

  private $logger;
  private $peak;
  public $fcount = 0;

  public function __construct()
  {
    $key = esc_attr(get_option('babo_backup_key'));

    if (!is_writable(WP_CONTENT_DIR . '/backup-bolt-' . $key)) {
      echo json_encode(array('not_writable'));
      exit();
    }

    if (file_exists(ABSPATH . '.babo_running')) {
      echo json_encode(array('already_running'));
      exit();
    }

    $this->logger = new Babo_Logger();

    @ini_set('memory_limit', '512M');
    $getMemory = @ini_get('memory_limit');

    $this->logger->log('INFO', 'Site url - ' . get_site_url());

    $this->logger->log('INFO', 'Initial memory limit - ' . $getMemory);

    $this->prepare_limits($getMemory);

    $this->initiate_backup_bot();
  }

  private function prepare_limits($memory)
  {

    $memory = intval($memory);
    $memory_peak  = intval($memory / 4);
    if ($memory_peak > 64) $memory_peak = 64;
    if ($memory === 384) $memory_peak = 96;
    if ($memory >= 512) $memory_peak = 128;
    if ($memory >= 1024) $memory_peak = 256;

    $memory_peak = intval($memory_peak * 0.9);

    $this->peak = $memory_peak;

    $this->logger->log('INFO', 'Setting memory peak limit to ' . $this->peak);
  }

  private function initiate_backup_bot()
  {

    $selections = (array) get_option('babo_backup_items');
    $backup_path = WP_CONTENT_DIR;

    $fullwp = false;
    if (in_array('full-wp', $selections)) {
      $backup_path = ABSPATH;
      $fullwp = true;
    }

    $this->fcount = \BABO\Babo_Utility::count_directories($backup_path);

    $this->logger->log('INFO', 'Sending the job to background processor');

    if ($this->fcount > 0) {
      $batches = 100;
      if ($this->fcount <= 200) $batches = 100;
      if ($this->fcount > 200) $batches = 200;
      if ($this->fcount > 1600) $batches = 400;
      if ($this->fcount > 3200) $batches = 800;
      if ($this->fcount > 6400) $batches = 1600;
      if ($this->fcount > 12800) $batches = 3200;
      if ($this->fcount > 25600) $batches = 5000;
      if ($this->fcount > 30500) $batches = 10000;
      if ($this->fcount > 60500) $batches = 20000;
      if ($this->fcount > 90500) $batches = 40000;
    }

    $this->logger->log('INFO', (int) $batches . ' Files will be zipped in each batch');


    $key = esc_attr(get_option('babo_backup_key'));
    //$nc = wp_create_nonce('babo_backup_bot'); //TODO
    $fname = current_time('d-m-Y_H-i');

    $res = array(
      'step' => 'background_backup',
      'abs' => ABSPATH,
      'cdir' => WP_CONTENT_DIR,
      'memory_peak' => $this->peak,
      'start_at' => 1,
      'total_files' => $this->fcount,
      'batches' => $batches,
      'source' => $backup_path,
      'dest' => $fname,
      'fullwp' => $fullwp,
      'key' => $key,
      //'secret' => $nc,
      'selections' => $selections,
      'url' => BABO_URL . 'inc/process-initiator.php'
    );

    ///update_option('babo_backup_last', sanitize_text_field($fname));
    //touch(ABSPATH . '.babo-' . $nc);

    if (!wp_next_scheduled('babo_clear_backups')) {
      wp_schedule_event(strtotime('+1 day', time()), 'daily', 'babo_clear_backups');
    }

    echo json_encode($res);

    exit();
  }
}
