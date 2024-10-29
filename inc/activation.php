<?php

namespace BABO;

/**
 * Plugin activation class
 * 
 * @since 1.0.0
 */
class Babo_Activator
{

  public function __construct()
  {
    $babokey = random_bytes(5);
    $babokey = bin2hex($babokey);

    update_option('babo_backup_key', $babokey);

    $bpath = WP_CONTENT_DIR . '/' . BABO_SLUG . '-' . $babokey;

    mkdir($bpath, 0700);
    touch($bpath . '/index.html');
    touch($bpath . '/index.php');

    $this->babo_backup_htaccess($bpath);
  }

  function babo_backup_htaccess($backup_path)
  {
    $rule = "# BEGIN BACKUP_BOLT\n";
    $rule .= '<Files "*">' . "\n";
    $rule .= "Require all denied" . "\n";
    $rule .= "</Files>" . "\n";
    $rule .= "<IfModule mod_rewrite.c>" . "\n";
    $rule .= "RewriteEngine On" . "\n";
    $rule .= "RewriteRule (.*) / [R]" . "\n";
    $rule .= "</IfModule>" . "\n";
    $rule .= "# END BACKUP_BOLT\n";

    insert_with_markers($backup_path . '/.htaccess', '', $rule);
  }
}
