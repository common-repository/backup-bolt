<?php

namespace BABO\Admin;

/**
 * Register Backup Bolt admin side page & handle download action
 * 
 * @since 1.0.0
 */
class Babo_Pages
{
  public function __construct()
  {
    add_action('admin_menu', [$this, 'babo_admin_menu_page']);
    add_action('admin_init', [$this, 'babo_download_last_backup']);
  }

  public function babo_admin_menu_page()
  {
    add_menu_page(BABO_NAME, BABO_NAME, 'manage_options', BABO_SLUG, array($this, 'babo_menu_page'), BABO_URL . 'assets/icon.png', 100);
  }

  private function babo_backup_form()
  {

    if (file_exists(ABSPATH . '.started')) {
      unlink(ABSPATH . '.started');
    }

    $form = '<div id="babo-logo-wrapper">
    <img src="' . BABO_URL . 'assets/backup-bolt.png"/>
    </div>';

    $form .= '<div id="babo-form-wrapper">';

    $form .= '<div class="babo-checkboxes">
    <h3>CUSTOM</h3>
    <div><!--inner-div-->';
    $checkboxes = array(
      array('wp-content', 'WP-CONTENT (plugins, themes, uploads, etc.,)', true, true, __('wp-content directory includes most of your site including plugins, themes, uploads, etc.,', 'backup-bolt')),
      array('htaccess', '.htaccess', true, false, __('.htaccess file includes your permalink structure, redirects, cache configurations.', 'backup-bolt')),
      array('wp-config', 'wp-config.php', true, false, __('wp-config.php file holds all your WordPress credentials including database login, security keys.', 'backup-bolt')),
      array('full-wp', 'Full WordPress (wp-admin, wp-content, wp-includes)', false, false, __('FULL WordPress installation including wp-admin, wp-content, wp-includes & root directory files', 'backup-bolt')),
    );
    foreach ($checkboxes as $checkbox) {
      $form .= \BABO\Babo_Utility::checkbox(...$checkbox);
    }
    $form .= '
    </div><!--inner-div-->
    </div>
    </div>

    <div id="babo-submit-wrapper">
    <div id="babo-log"></div>
    <div style="margin-top:10px" id="babo_sendlog"><label for="babo_sendlog"><input type="checkbox" name="babo_sendlog" value="1" checked>Anonymously send backup log to get better support</label></div>
    <button id="babo-backup-now" type="button" data-token="' . wp_create_nonce('babo-backup') . '"><span class="dashicons dashicons-cloud"></span>' . __('BACKUP NOW', 'backup-bolt') . '</button>';

    $form .= '<div id="babo-confirm-backup-start">
    <button id="babo-abort-backup" type="button" data-token="' . wp_create_nonce('babo-terminate-backup') . '"><span class="dashicons dashicons-no-alt"></span>' . __('ABORT', 'backup-bolt') . '</button>&nbsp;&nbsp;
    <button id="babo-start-backup" type="button" data-token="' . wp_create_nonce('babo-initiate-backup') . '"><span class="dashicons dashicons-saved"></span><span class="text">' . __('PROCEED', 'backup-bolt') . '</span></button>
    </div>';

    $lastbackup = get_option('babo_backup_last');
    if (FALSE !== $lastbackup) {
      $lastbackup = explode('_', esc_html($lastbackup));

      $date = $lastbackup[0];
      $time = str_ireplace('-', ':', $lastbackup[1]);

      $form .= '<p class="babo-last-backup">' . __('LAST BACKUP TIME', 'backup-bolt') . ': <strong>' . esc_html($date) . ' ' . esc_html($time) . '</strong> | <a href="admin.php?page=backup-bolt&download">' . __('DOWNLOAD BACKUP', 'backup-bolt') . '</a></p>';
      $form .= '<p class="babo-last-backup"><strong>' . __('NOTE', 'backup-bolt') . ':-</strong> ' . __('Backups will be auto deleted every 24Hrs', 'backup-bolt') . '</p>';
    }

    $form .= "</div>";

    return $form;
  }

  public function babo_menu_page()
  {
    $output = '<div id="babo-wrapper">';

    $backupform = $this->babo_backup_form();

    $output .= '<div class="babo-sub-wrapper">
    ' . apply_filters('babo_backup_form', $backupform) . '
    </div>';

    $output .= '</div>';

    $allowedhtml = wp_kses_allowed_html('post');
    $allowedhtml['input'] = array(
      'type' => [],
      'name' => [],
      'class' => [],
      'value' => [],
      'checked' => [],
      'disabled' => []
    );

    echo wp_kses($output, $allowedhtml);
  }

  /**
   * Last Backup download handler
   *
   * @since 1.0.0
   */
  public function babo_download_last_backup()
  {
    if (isset($_GET['download']) && isset($_GET['page']) && $_GET['page'] == 'backup-bolt') {
      if (!current_user_can('manage_options')) {
        wp_die('Unauthorized Access');
      }

      $babofilename = get_option('babo_backup_last');
      $babokey = get_option('babo_backup_key');
      if (FALSE == $babofilename || FALSE == $babokey) {
        wp_die(__('No recent backup to download. Please run backup again.', 'backup-bolt'));
      }

      $filename = 'backupbolt-' . esc_attr($babofilename) . '.zip';
      $filepath = WP_CONTENT_DIR . '/' . BABO_SLUG . '-' . esc_attr($babokey) . '/backupbolt-' . esc_attr($babofilename) . '_' . esc_attr($babokey) . '.zip';

      if (!file_exists($filepath)) {
        wp_die('Latest backup file seems deleted and no longer available.');
      }

      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-type: application/octet-stream");
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: " . filesize($filepath));
      ob_end_flush();
      @readfile($filepath);
    }
  }
}
