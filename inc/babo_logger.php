<?php

namespace BABO;

/**
 * Backup process logging class
 * 
 * @since 1.0.0
 */
class Babo_Logger
{

  private $handle;

  public function __construct()
  {
    $this->handle = fopen(BABO_PATH . 'logs/babo_progress.log', 'w');
  }

  public function log($type = 'INFO', $msg = '')
  {
    $message = '[' . esc_html($type) . '] ' . esc_html($msg) . "\n";
    fwrite($this->handle, $message);

    if ($type == 'ERROR' || $type == 'SUCCESS') {
      $this->close();
      exit();
    }
  }

  public function close()
  {
    fclose($this->handle);
  }

  public function __destruct()
  {
    fclose($this->handle);
  }
}
