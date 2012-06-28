<?php
namespace Wired;

use Wired\Twitter;

class Admin {

  var $page_slug = 'wp-jatp';

  public function __construct() {
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  public function admin_menu() {
    add_options_page('Twitter (jatp)', 'Twitter (jatp)', 'edit_users', $this->page_slug, array($this, 'options_page'));
  }

  public function options_page(){

    $wpnonce = array(
      'action' => 'submit_wpjatp_form',
      'name' => 'wpjatp_form_wpnonce'
    );

    if(isset($_POST['refresh_cache'])){
      if ( !check_admin_referer($wpnonce['action'],$wpnonce['name']) ){
        trigger_error('There was a problem with the wpnonce.', E_USER_NOTICE);
      }else{
        // refresh twitter cache
        Twitter::refresh_cache();
      }
    }
    ?>
    <div class="wrap">
      <div id="icon-options-general" class="icon32"></div>
      <h2>Twitter Settings <small>(jatp)</small></h2>
    </div>

    <p>wp-jatp (just another twitter plugin) refreshes it's cache based on the time set either in the widget settings or within the theme files. But if you want to manually refresh the cache press the button below.</p>

    <p>Please note this removes all cache, so if your using the plugin in several places to show tweets from mulitple twitter users, all users cache will be refreshed.</p>

    <form method="post" action="options-general.php?page=<?php echo $this->page_slug ?>">
      <p class="submit">
        <input type="hidden" name="refresh_cache" value="yup" />
        <?php wp_nonce_field($wpnonce['action'], $wpnonce['name']); ?>
        <input id="submit" class="button-primary" type="submit" value="Clear Cache" name="submit" />
      </p>
    </form>
    <?php
  }

}

new Admin;