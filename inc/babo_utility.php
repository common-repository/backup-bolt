<?php

namespace BABO;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

/**
 * Helper functions
 * 
 * @since 1.0.0
 */
class Babo_Utility
{

  public static function checkbox($name, $label, $checked = false, $disabled = false, $tooltip = '')
  {
    $name = esc_attr($name);
    $ischeckd = $checked ? 'checked'  : '';
    $isdisabled = $disabled ? ' disabled' : '';

    $html = '';

    if ($name == 'full-wp') $html .= '</div><!--inner-div--></div><div class="babo-checkboxes babo-full-wp"><h3>' . __('FULL BACKUP', 'backup-bolt') . '</h3><div><!--inner-div-->';

    $html .= '<span class="babolecheck">
    <label class="babo-checkbox-label">
    <input type="checkbox" name="' . $name . '" class="' . $isdisabled . '" value="1" ' . $ischeckd . $isdisabled . '>
      <span class="babo-checkbox-custom rectangular"></span>
    </label>
    ' . esc_html($label) . '&nbsp; <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr($tooltip) . '"></span></label>
    </span>';

    return $html;
  }

  public static function calculate_dirsize($path)
  {

    $bytestotal = 0;
    $path = realpath($path);
    if ($path !== false && $path != '' && file_exists($path)) {
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
        try {
          if (FALSE !== stripos($object->getFilename(), 'backupbolt-')) {
            continue;
          }
          $bytestotal += $object->getSize();
        } catch (Exception $e) {
          continue;
        }
      }
    }
    return $bytestotal / 1024 / 1024; //MB format
  }

  public static function count_directories($path)
  {

    $path = realpath($path);
    $filecount = 0;
    if ($path !== false && $path != '' && file_exists($path)) {
      $fileslist = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

      $filecount = 0;
      foreach ($fileslist as $file) {
        if (is_dir($file) || stripos($file, 'backupbolt-') !== FALSE) continue;

        $filecount++;
      }

      // foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $object) {
      //   if ($object->isDir()) {
      //     $dircount++;
      //   } else {
      //     $filecount++;
      //   }
      // }
    }

    return $filecount;
  }
}
