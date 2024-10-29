<?php

if (!class_exists('PclZip')) {
  require_once BABO_ABSPATH . 'wp-admin/includes/class-pclzip.php';
}
/**
 * Main backup class run by server side without using WordPress environment
 * 
 * @since 1.0.0
 */

class Babo_Backup
{

  private $logger = false;
  private $secret;
  private $settings;

  public function __construct($post = array())
  {
    $this->settings = $post;

    if (file_exists(BABO_ABSPATH . '.babo_abort')) {
      $babokey = strip_tags($this->settings['key']);
      $destination = BABO_WP_CONTENT . '/backup-bolt-' . $babokey . '/backupbolt-' . $this->clean_path($this->settings['dest']) . '_' . $babokey . '.zip';

      unlink($destination);
      unlink(BABO_ABSPATH . '.babo_abort');

      $this->babo_terminate('BABO_ERROR: Backup aborted by user.');
    }

    if (file_exists(BABO_ABSPATH . '.babo_running') && !isset($this->settings['sub_batch'])) {
      $this->logit('Backup already started! Please wait while background process completes.');
      exit();
    }

    // $this->secret = strip_tags($this->settings['secret']);
    // if (!file_exists(BABO_ABSPATH . '.babo-' . $this->secret)) {
    //   @unlink(BABO_ABSPATH . '.babo_running');
    //   $this->logit('BABO_ERROR: Secret file missing!. Please try again.');
    //   exit();
    // }

    touch(BABO_ABSPATH . '.babo_running');

    /// $this->logit(json_encode($post));

    $this->zip_backup();
  }

  private function zip_backup()
  {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      if ($errno == E_ERROR || $errno == E_PARSE) {
        $this->logit('BABO_ERROR:');
        $this->logit($errno . ' - ' . $errstr);
        $this->logit($errfile . ' - ' . $errline);
      } else {
        $this->logit('Skipped logging a minor error/warning');
      }
    },);

    register_shutdown_function([$this, 'babo_shutdown']);

    //$mtime = microtime(true);

    $backup_path = $this->clean_path($this->settings['source']);
    $source = $backup_path . '/';

    $fullwp = $this->settings['fullwp'] == 'false' ? false : true;

    $babokey = strip_tags($this->settings['key']);

    if (!defined('PCLZIP_TEMPORARY_DIR')) define('PCLZIP_TEMPORARY_DIR',  BABO_WP_CONTENT . '/backup-bolt-' . $babokey . '/');

    $destination = BABO_WP_CONTENT . '/backup-bolt-' . $babokey . '/backupbolt-' . $this->clean_path($this->settings['dest']) . '_' . $babokey . '.zip';

    if (!file_exists($source)) {
      $this->babo_terminate('BABO_ERROR: Backup aborted - source path invalid' . ' ' . $source);
    }

    $zip = new \PclZip($destination);

    if (!$zip) {
      $this->babo_terminate('BABO_ERROR: PCLZip cannot be initiated');
    }

    $source = str_replace('\\', '/', realpath($source));

    $this->logit('-----------------------------------------------------');

    $this->logit('[STEP] Batch compressing files to zip archive - STARTING AT ' . (int) $this->settings['start_at']);

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

    $currentbatch = $this->settings['start_at']; //1,201,401

    $add_path = 'wordpress' . DIRECTORY_SEPARATOR;

    if (!$fullwp && $currentbatch == 1) {

      $chosen = (array) $this->settings['selections'];

      if (in_array('htaccess', $chosen) && is_readable(BABO_ABSPATH . '.htaccess')) {

        $addFiles = $zip->add(array(BABO_ABSPATH . '.htaccess'), PCLZIP_OPT_REMOVE_PATH, BABO_ABSPATH, PCLZIP_OPT_ADD_PATH, $add_path);

        if (!$addFiles) {
          $this->logit($zip->errorInfo(true));
          $this->logit('[ALERT] Htaccess backup fail due to error');
        }
      }

      if (in_array('wp-config', $chosen) && is_readable(BABO_ABSPATH . 'wp-config.php')) {
        $addFiles = $zip->add(array(BABO_ABSPATH . 'wp-config.php'), PCLZIP_OPT_REMOVE_PATH, BABO_ABSPATH, PCLZIP_OPT_ADD_PATH, $add_path);

        if (!$addFiles) {
          $this->logit($zip->errorInfo(true));
          $this->logit('[ALERT] wp-config backup fail due to error');
        }
      }
    }

    $totalFiles = (int) $this->settings['total_files'];
    $batches = (int) $this->settings['batches'];

    $fcount = 0;
    $file_list = []; //for current batch zipping

    //prepare overall files array 
    $fileArr = array(); //files of all batches together
    foreach ($files as $file) {
      if ($file->isDir() || stripos($file, 'backupbolt-') !== FALSE) continue;

      $fcount++;
      $fileArr[]  = $file->getRealPath();
    }

    //prepare files array for current batch
    $start_cursor = $currentbatch - 1;
    $haveNext = $theEnd = false;
    $zipUpto = $start_cursor + $batches - 1;
    for ($i = $start_cursor; $i <= $zipUpto; $i++) {
      //backup complete on end of overall file array
      if (!isset($fileArr[$i])) {
        $theEnd = $i;
        break;
      }

      $file = realpath($fileArr[$i]);

      $file_list[] = $file;
    }

    if (isset($fileArr[$zipUpto + 1])) {
      $haveNext = true;
    }

    $totalFiles = $fcount;

    //$this->logit('STAGE: Current Batch Starting at ' . $currentbatch . '/' . $totalFiles);
    //$this->logit('Have next - ' . $haveNext . '|total - ' . count($fileArr));
    // foreach ($file_list as $f) {
    //   $this->logit('FILE: ' . $f);
    // }

    $addFiles = $zip->add($file_list, PCLZIP_OPT_REMOVE_PATH, BABO_ABSPATH, PCLZIP_OPT_ADD_PATH, $add_path);

    if (!$addFiles) {
      $this->logit($zip->errorInfo(true));
      $this->babo_terminate('BABO_ERROR|PCLZIP: Process terminated due to above error');
    }

    if ($theEnd) {
      $this->logit('[FINISHED] Backup Completed! Files backed up - ' . (int) $theEnd . '/' . $fcount);
      $this->babo_backup_complete();
      exit();
    }

    if ($haveNext) {
      $this->logit('[COMPLETE] Batch complete - Files zipped ' . ($zipUpto + 1) . '/' . $totalFiles);

      //call for next batch
      usleep(100);
      $this->settings['start_at'] = $currentbatch + $batches;
      $this->settings['sub_batch'] = 1;
      $this->settings['selections'] = '';

      // $ch = curl_init();
      // curl_setopt($ch, CURLOPT_POST, 1);
      // curl_setopt($ch, CURLOPT_URL, $this->settings['url']);
      // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      // curl_setopt($ch, CURLOPT_POSTFIELDS, $this->settings);
      // curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      // curl_setopt($ch, CURLOPT_VERBOSE, false);
      // curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
      // curl_setopt($ch, CURLOPT_COOKIESESSION, true);
      // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      // curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      // curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

      // $res = curl_exec($ch);


      // $error_msg = '';
      // if (curl_errno($ch)) {
      //   $error_msg = curl_error($ch);
      //   $this->babo_terminate('BABO_CURL:' . $error_msg);
      // }

      // curl_close($ch);

      echo json_encode($this->settings);
      exit();
    }

    exit();
  }

  private function logit($msg = '')
  {
    if (!$this->logger) { //init
      $mode = 'a';
      $this->logger = fopen(BABO_WP_CONTENT . '/plugins/backup-bolt/logs/babo_progress.log', $mode);
    }
    fwrite($this->logger, $msg . "\n");
  }

  public function babo_shutdown()
  {
    $error = error_get_last();
    $fatals = array(1, 4, 16, 64, 4096);
    if ($error !== null && in_array($error['type'], $fatals)) {

      $this->babo_terminate('BABO_ERROR|FATAL: Backup process shutdown due to error caused from file [' . $error['file'] . ']:line ' . $error['line'] . ':type ' . $error['type'] . ' ---->  ' . $error['message'] . '. Please try temporarily deactivating the conflicting plugin');
    }
  }

  private function clean_path($path)
  {
    return preg_replace("/[^A-Za-z0-9-._\/]+/", "", $path);
  }

  private function babo_backup_complete()
  {

    if ($this->logger) fclose($this->logger);

    unlink(BABO_ABSPATH . '.babo_running');
    unlink(BABO_ABSPATH . '.babo-' . $this->secret);

    $this->settings['step'] = 'complete';
    echo json_encode($this->settings);

    exit();
  }

  private function babo_terminate($msg = '')
  {
    $this->logit($msg);

    if ($this->logger) fclose($this->logger);

    unlink(BABO_ABSPATH . '.babo_running');
    unlink(BABO_ABSPATH . '.babo-' . $this->secret);

    $this->settings['step'] = 'error';
    echo json_encode($this->settings);

    exit();
  }
}
