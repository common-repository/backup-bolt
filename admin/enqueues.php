<?php

namespace BABO\Admin;

/**
 * Admin side scripts and styles enqueue
 * 
 * @since 1.0.0 
 */
class Babo_Enqueues
{
  public function __construct()
  {
    //text domain
    add_action('plugins_loaded', [$this, 'babo_load_plugin_textdomain']);

    //style & scripts
    add_action('admin_enqueue_scripts', [$this, 'babo_enqueue_styles']);
    add_action('admin_enqueue_scripts', [$this, 'babo_enqueue_scripts']);
  }

  public function babo_load_plugin_textdomain()
  {
    load_plugin_textdomain(BABO_TX, FALSE, basename(dirname(__FILE__)) . '/languages/');
  }

  public function babo_enqueue_scripts()
  {
    wp_enqueue_script(BABO_NAME . '-alerts', BABO_URL . 'js/sweetalert2.all.min.js', array('jquery'), BABO_VERSION, true);

    wp_enqueue_script(BABO_NAME . '-popper', BABO_URL . 'js/popper.min.js', array('jquery'), BABO_VERSION, true);
    wp_enqueue_script(BABO_NAME . '-tippy', BABO_URL . 'js/tippy-bundle.iife.min.js', array('jquery', BABO_NAME . '-popper'), BABO_VERSION, true);

    wp_enqueue_script(BABO_NAME . '-js', BABO_URL . 'js/main.js', array('jquery', BABO_NAME . '-tippy'), BABO_VERSION, true);
  }

  public function babo_enqueue_styles()
  {
    wp_enqueue_style(BABO_NAME . '-css', BABO_URL . 'css/main.min.css', FALSE, BABO_VERSION, 'all');
    wp_enqueue_style(BABO_NAME . '-alertscss', BABO_URL . 'css/sweetalert2.min.css', FALSE, BABO_VERSION, 'all');
  }
}
