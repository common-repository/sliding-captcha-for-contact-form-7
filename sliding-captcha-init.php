<?php
/**
 * Plugin Name: Sliding Captcha Field for Contact Form 7
 * Description: Protect Contact Form 7 forms from spam entries. Requires contact form 7
 * Plugin URL: https://wordpress.org/plugins/sliding-captcha-for-contact-form-7/
 * Version: 1.0.3
 * Author: Nitin Rathod
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Text Domain: sliding-captcha-for-contact-form-7
 * Domain Path: /languages/
 */
// If this file is called directly, abort.
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Define Plugin URL, Directory Path and Localization text-domain
 */
define('SCCF7_URL', plugins_url('/', __FILE__));  // Define Plugin URL
define('SCCF7_PATH', plugin_dir_path(__FILE__));  // Define Plugin Directory Path
define('SCCF7_DOMAIN', 'sliding-captcha-for-contact-form-7'); // Define Plugin text-domain

new wpcf7_sliding_captcha();

// check to make sure contact form 7 is installed and active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {

    // give warning if contact form 7 is not active
    function wpcf7_sliding_captcha_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e('<b>Contact Form 7 - Sliding Captcha Add-on:</b> Contact Form 7 is not installed and / or active! Please install <a target="_blank" href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7</a>.', SCCF7_DOMAIN); ?></p>
        </div>
        <?php
    }

    // end public function wpcf7_sliding_captcha_admin_notice

    add_action('admin_notices', 'wpcf7_sliding_captcha_admin_notice');
}

class wpcf7_sliding_captcha {

    public function __construct() {
        include_once( plugin_dir_path(__FILE__) . 'sliding-captcha-verify.php' );
        add_action('plugins_loaded', array($this, 'init'), 20);
    }

    // end public function __construct

    public function init() {

        if (!function_exists('wpcf7_add_form_tag')) {
            return;
        }

        wpcf7_add_form_tag('slidingcaptcha', array($this, 'sliding_captcha_handler'), true);
        add_action('admin_init', array($this, 'add_sliding_generator'), 25);
        add_action('wp_enqueue_scripts', array($this, 'wpcf7_slide_captcha_script'));
        add_filter('wpcf7_validate_slidingcaptcha', array($this, 'wpcf7_slidingcaptcha_validation_filter'), 20, 2);
        add_filter('wpcf7_messages', array($this, 'wpcf7_sliding_captcha_error_messages'));

        // plugin localization code
        load_plugin_textdomain(SCCF7_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // end public function init

    public function sliding_captcha_handler($tag) {
        $html = '<span class="wpcf7-form-control-wrap ' . $tag->name . '">
                    <span class="wpcf7-sliding-captcha"></span>
		</span>
		<div style="clear:both;"></div>';
        return $html;
    }

    // end public function shortcode_handler

    public function add_sliding_generator() {
        // called on init to add the tag generator or cf7
        // wpcf7_add_tag_generator($name, $title, $elm_id, $callback, $options = array())
        if (!function_exists('wpcf7_add_tag_generator')) {
            return;
        }
        $name = 'slidingcaptcha';
        $title = esc_html(__('Sliding Captcha Field', SCCF7_DOMAIN));
        $elm_id = 'wpcf7-tg-pane-slidingcaptcha';
        $callback = array($this, 'slidingcaptcha_tag_callback');
        wpcf7_add_tag_generator($name, $title, $elm_id, $callback);
    }

    // end public function add_tag_generator

    public function slidingcaptcha_tag_callback($form, $args = '') {
        // output the code for CF7 tag generator
        $args = wp_parse_args($args, array());
        $desc = esc_html(__('Generate a form-tag for a Sliding Captcha field.', SCCF7_DOMAIN));
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo sprintf(esc_html($desc), $desc_link); ?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', SCCF7_DOMAIN)); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="slidingcaptcha" class="tag code" readonly="readonly" onfocus="this.select()" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', SCCF7_DOMAIN)); ?>" />
            </div>
        </div>
        <?php
    }

    // end public function slidingcaptcha_tag_callback

    public function wpcf7_slide_captcha_script() {
        wp_register_style('slider-captcha-style', plugin_dir_url(__FILE__) . ('./assets/css/sliding-catcha.css'));
        wp_enqueue_style('slider-captcha-style');
        wp_enqueue_script('jquery-ui-touch', plugin_dir_url(__FILE__) . ( './assets/js/jquery.ui.touch.js' ), array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'), "1.8.2", true);
        wp_enqueue_script('QapTcha-jquery', plugin_dir_url(__FILE__) . ( './assets/js/QapTcha.jquery.js' ), array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-touch'), '0.2.3', true);
        wp_enqueue_script('slider-captcha-script', plugin_dir_url(__FILE__) . ( './assets/js/scripts.js' ), array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-touch', 'QapTcha-jquery'), '1.0.0', true);
        wp_localize_script('slider-captcha-script', 'script_url', array('plugin_ajax_url' => admin_url("admin-ajax.php")));
    }

    // end public function wpcf7_slide_captcha_script

    public function wpcf7_slidingcaptcha_validation_filter($result, $tag) {

        if ('slidingcaptcha' == $tag->type) {
            $action = sanitize_text_field($_POST["action"]);

            if (htmlentities($action, ENT_QUOTES, 'UTF-8') != 'sliding-captcha') {
                $result->invalidate($tag, wpcf7_get_message('invalid_sliding_captcha'));
            }
        }

        return $result;
    }

    // end public function wpcf7_slidingcaptcha_validation_filter

    public function wpcf7_sliding_captcha_error_messages($messages) {
        return array_merge($messages, array(
            'invalid_sliding_captcha' => array(
                'description' => __("Sliding Captcha error message when the sender doesn't slide it.", SCCF7_DOMAIN),
                'default' => __('Please slide captcha for form processing.', SCCF7_DOMAIN)
            )
        ));
    }

    // end public function wpcf7_sliding_captcha_error_messages
}

// end class wpcf7_sliding_captcha
?>