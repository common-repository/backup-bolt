<?php

/**
 *
 * BackupBolt - Super simple WordPress backup plugin
 *
 * Plugin Name:       Backup Bolt
 * Plugin URI:        https://backupbolt.com
 * Description:       Super simple WordPress backup plugin
 * Version:           1.4.0
 * Author:            Backup Bolt
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       backup-bolt
 * Domain Path:       /languages
 *
 * @category    Plugin
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */

//exit on direct access
if (!defined('WPINC')) {
  die();
}

/**
 * Definitions
 */

define('BABO_VERSION', '1.4.0');
define('BABO_BASENAME', plugin_basename(__FILE__));
define('BABO_NAME', 'Backup Bolt');
define('BABO_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('BABO_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('BABO_SLUG', 'backup-bolt');
define('BABO_TX',  'backup-bolt'); //textdomain

/** 1.0.1 */

if (!function_exists('bb_fs')) {
  // Create a helper function for easy SDK access.
  function bb_fs()
  {
    global $bb_fs;

    if (!isset($bb_fs)) {
      // Include Freemius SDK.
      require_once dirname(__FILE__) . '/freemius/start.php';

      $bb_fs = fs_dynamic_init(array(
        'id'                  => '11420',
        'slug'                => 'backup-bolt',
        'type'                => 'plugin',
        'public_key'          => 'pk_75eb294c9091ca7e931035980b0bc',
        'is_premium'          => false,
        'has_addons'          => false,
        'has_paid_plans'      => false,
        'menu'                => array(
          'slug'           => 'backup-bolt',
          'contact'        => false,
        ),
      ));
    }

    return $bb_fs;
  }

  // Init Freemius.
  bb_fs();
  // Signal that SDK was initiated.
  do_action('bb_fs_loaded');
}

/**
 * Autoloader
 */
require_once __DIR__ . '/vendor/autoload.php';


/**
 * Activator & De-activator
 * 
 * @since 1.0.0
 */

use \BABO\Babo_Activator;
use \BABO\Babo_Deactivator;

function babo_activate()
{
  new Babo_Activator();
}

function babo_deactivate()
{
  new Babo_Deactivator();
}

register_activation_hook(__FILE__, 'babo_activate');
register_deactivation_hook(__FILE__, 'babo_deactivate');

/**
 * Core File
 */
require_once BABO_PATH . 'inc/main.php';
