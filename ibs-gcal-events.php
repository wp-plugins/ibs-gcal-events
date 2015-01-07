<?php
/*
  Plugin Name: IBS GCal Events
  Plugin URI: http://wordpress.org/extend/plugins/
  Description: Lists Google Calendar V3 Events plugin
  Author: HMoore71
  Version: 0.1
  Author URI: http://indianbendsolutions.net
  License: GPL2
  License URI: none
 */

/*
  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

define('IBS_GCAL_EVENTS_VERSION', '0.1');
register_activation_hook(__FILE__, 'ibs_gcal_events_defaults');

function ibs_gcal_events_defaults() {
    IBS_GCAL_EVENTS::defaults();
}

register_deactivation_hook(__FILE__, 'ibs_gcal_events_deactivate');

function ibs_gcal_events_deactivate() {
    delete_option('ibs_gcal_events_options');
}

class IBS_GCAL_EVENTS {

    static $add_script = 0;
    static $options = array();
    static $options_defaults = array(
        "version" => IBS_GCAL_EVENTS_VERSION,
        "calendar" => 'en.usa#holiday@group.v.calendar.google.com', //Google public holidays feed
        "apiKey" => 'AIzaSyDU0aiNYlY1sRHPuZadvnfAkIRMhEFobP4', // see href="https://developers.google.com/api-client-library/python/guide/aaa_apikeys
        "width" => "100%",
        "align" => "alignleft",
        "dateFormat" => "MMM DD, YYYY",
        "timeFormat" => "HH:mm",
        "max" => 100,
        "calendar" => '',
        "apiKey" => '',
        "descending" => false,
        "start" => 'now',
        "qtip" => array('style' => "qtip-bootstrap", 'rounded' => false, 'shadow' => false)
    );

    static function extendA($a, &$b) {
        foreach ($a as $key => $value) {
            if (!isset($b[$key])) {
                $b[$key] = $value;
            }
            if (is_array($value)) {
                self::extendA($value, $b[$key]);
            }
        }
    }

    static function fixBool(&$item, $key) {
        switch (strtolower($item)) {
            case "null" : $item = null;
                break;
            case "true" :
            case "yes" : $item = true;
                break;
            case "false" :
            case "no" : $item = false;
                break;
            default :
        }
    }

    static function init() {
        self::$options = get_option('ibs_gcal_events_options');
        if (isset(self::$options['version']) === false || self::$options['version'] !== IBS_GCAL_EVENTS_VERSION) {
            self::defaults();  //development set new options
        } else {
            self::extendA(self::$options_defaults, self::$options);
            array_walk_recursive(self::$options, array(__CLASS__, 'fixBool'));
        }
        add_action('admin_init', array(__CLASS__, 'admin_options_init'));
        add_action('admin_menu', array(__CLASS__, 'admin_add_page'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
        add_shortcode('ibs-gcal-events', array(__CLASS__, 'handle_shortcode'));
        add_action('init', array(__CLASS__, 'register_script'));
        add_action('wp_head', array(__CLASS__, 'print_script_header'));
        add_action('wp_footer', array(__CLASS__, 'print_script_footer'));
        add_action('admin_print_scripts', array(__CLASS__, 'print_admin_scripts'));
    }

    static function defaults() { //jason_encode requires double quotes
        $options = get_option('ibs_gcal_events_options');
        self::extendA(self::$options_defaults, $options);
        array_walk_recursive($options, array(__CLASS__, 'fixBool'));
        $options['version'] = IBS_GCAL_EVENTS_VERSION;
        self::$options = $options;
        update_option('ibs_gcal_events_options', $options);
    }

    static function admin_options_init() {
        register_setting('ibs_gcal_events_options', 'ibs_gcal_events_options');
        add_settings_section('section-gcal', '', array(__CLASS__, 'admin_general_header'), 'gcal-events');
        add_settings_field('calendar', 'Google Calendar ID', array(__CLASS__, 'field_calendar'), 'gcal-events', 'section-gcal');
        add_settings_field('apiKey', 'Google API key', array(__CLASS__, 'field_apiKey'), 'gcal-events', 'section-gcal');
        add_settings_field('startDate', 'Starting Date', array(__CLASS__, 'field_start'), 'gcal-events', 'section-gcal');
        add_settings_field('max', 'Max Events', array(__CLASS__, 'field_max'), 'gcal-events', 'section-general');
        add_settings_field('align', 'List Align', array(__CLASS__, 'field_align'), 'gcal-events', 'section-gcal');
        add_settings_field('width', 'List Width', array(__CLASS__, 'field_width'), 'gcal-events', 'section-gcal');
        add_settings_field('timeFormat', 'Time Format', array(__CLASS__, 'field_timeFormat'), 'gcal-events', 'section-gcal');
        add_settings_field('dateFormat', 'Date Format', array(__CLASS__, 'field_dateFormat'), 'gcal-events', 'section-gcal');
        add_settings_field('descending', 'Sort descending', array(__CLASS__, 'field_descending'), 'gcal-events', 'section-gcal');
        add_settings_field('shortcode', 'Shortcode', array(__CLASS__, 'field_shortcode'), 'gcal-events', 'section-gcal');

        add_settings_section('section-gcal-qtip', '', array(__CLASS__, 'admin_general_qtip_header'), 'gcal-events-qtip');
        add_settings_field('rounded', 'Rounded', array(__CLASS__, 'field_qtip_rounded'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('shadow', 'Shadow', array(__CLASS__, 'field_qtip_shadow'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('style', 'Style', array(__CLASS__, 'field_qtip_style'), 'gcal-events-qtip', 'section-gcal-qtip');
    }

    static function admin_general_header() {
        echo '<div class="ibs-admin-bar">Shortcode [ibs-gcal-events] default settings</div>';
    }

    static function admin_general_qtip_header() {
        echo '<div class="ibs-admin-bar">Widget Qtip settings</div>';
    }

    static function field_start() {
        $value = self::$options['start'];
        echo "<input type='text' name='ibs_gcal_events_options[start]'  value='$value'  />" . '<span> "now" or "yyyy-mm-dd" </span>';
    }

    static function field_descending() {
        $checked = self::$options['descending'] ? "checked" : '';
        echo '<input type = "checkbox" name = "ibs_gcal_events_options[descending]" value = "true"' . $checked . ' / >';
    }

    static function field_align() {
        echo '<select name = "ibs_gcal_events_options[align]" />';
        $selected = self::$options['align'] == "alignleft" ? 'selected' : '';
        echo '<option value = "alignleft" ' . $selected . '>left</option>';
        $selected = self::$options['align'] == "aligncenter" ? 'selected' : '';
        echo '<option value = "aligncenter" ' . $selected . '>center</option>';
        $selected = self::$options['align'] == "alignright" ? 'selected' : '';
        echo '<option value = "alignright" ' . $selected . '>right</option>';
        echo '</select>';
    }

    static function field_max() {
        $value = self::$options['max'];
        echo "<input type = 'number' name = 'ibs_gcal_events_options[max]' value = '$value' />";
    }

    static function field_width() {
        $value = self::$options['width'];
        echo '<input name = "ibs_gcal_events_options[width]" type = "text" size = "25" value = "' . $value . '"/>';
    }

    static function field_dateFormat() {
        $value = self::$options['dateFormat'];
        echo '<input name = "ibs_gcal_events_options[dateFormat]" type = "text" size = "25" value = "' . $value . '"/><a href = "http://momentjs.com/docs/#/displaying/" target = "_blank" title = "moment.js formatting">help</a>';
    }

    static function field_timeFormat() {
        $value = self::$options['timeFormat'];
        echo '<input name = "ibs_gcal_events_options[timeFormat]" type = "text" size = "25" value = "' . $value . '"/><a href = "http://momentjs.com/docs/#/displaying/" target = "_blank" title = "moment.js formatting">help</a>';
    }

    static function field_calendar() {
        $value = self::$options['calendar'];
        echo '<input name = "ibs_gcal_events_options[calendar]" type = "text" size = "100" value = "' . $value . '" placeholder = "Google Calendar ID (example@gmail.com)"/><a href = "https://support.appmachine.com/hc/en-us/articles/203645966-Find-your-Google-Calendar-ID-for-the-Events-block" target = "_blank" title = "moment.js formatting">help</a>';
    }

    static function field_apiKey() {
        $value = self::$options['apiKey'];
        echo '<input name = "ibs_gcal_events_options[apiKey]" type = "text" size = "100" value = "' . $value . '" placeholder = "Optional Google API Key"/><a href = "https://developers.google.com/api-client-library/python/guide/aaa_apikeys" target = "_blank" title = "Google Developer Guide">help</a>';
    }

    static function field_qtip_rounded() {
        $checked = self::$options['qtip']['shadow'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[qtip][shadow]" value="qtip-rounded"' . $checked . '/>';
    }

    static function field_qtip_shadow() {
        $checked = self::$options['qtip']['rounded'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[qtip][rounded]" value="qtip-shadow"' . $checked . '/>';
    }

    static function field_qtip_style() {
        echo "<select name='ibs_events_options[list][qtip][style]'> ";
        $value = self::$options['qtip']['style'];
        $selected = $value === '' ? "selected" : '';
        echo "<option id='qtip-none'     $selected  value=''  selected >none</option>";
        $selected = $value === 'qtip-light' ? "selected" : '';
        echo "<option id='qtip-light'    $selected value='qtip-light' >light coloured style</option>";
        $selected = $value === 'qtip-dark' ? "selected" : '';
        echo "<option id='qtip-dark'     $selected value='qtip-dark' >dark style</option>";
        $selected = $value === 'qtip-cream' ? "selected" : '';
        echo "<option id='qtip-cream'    $selected value='qtip-cream' >cream</option>";
        $selected = $value === 'qtip-red' ? "selected" : '';
        echo "<option id='qtip-red'      $selected value='qtip-red' >Alert-ful red style </option>";
        $selected = $value === 'qtip-green' ? "selected" : '';
        echo "<option id='qtip-green'   $selected value='qtip-green' >Positive green style </option>";
        $selected = $value === 'qtip-blue' ? "selected" : '';
        echo "<option id='qtip-blue'     $selected value='qtip-blue' >Informative blue style </option>";
        $selected = $value === 'qtip-bootstrap' ? "selected" : '';
        echo "<option id='qtip-bootstrap'$selected value='qtip-bootstrap' >Twitter Bootstrap style </option>";
        $selected = $value === 'qtip-youtube' ? "selected" : '';
        echo "<option id='qtip-youtube'  $selected value='qtip-youtube' >Google's new YouTube style</option>";
        $selected = $value === 'qtip-tipsy' ? "selected" : '';
        echo "<option id='qtip-tipsy'    $selected value='qtip-tipsy' >Minimalist Tipsy style </option>";
        $selected = $value === 'qtip-tipped' ? "selected" : '';
        echo "<option id='qtip-tipped'   $selected value='qtip-tipped' >Tipped libraries</option>";
        $selected = $value === 'qtip-jtools' ? "selected" : '';
        echo "<option id='qtip-jtools'   $selected value='qtip-jtools' >Tools tooltip style </option>";
        $selected = $value === 'qtip-cluetip' ? "selected" : '';
        echo "<option id='qtip-cluetip'  $selected value='qtip-cluetip' >Good ole'' ClueTip style </option>";
        echo "</select>";
    }

    static function field_shortcode() {
        $value = '[ibs-gcal-events calendar="some-calendar@gmail.com" align="alignleft" width="100%" max="100" dateFormat="dddd MMM DD" timeFormat="HH:mm" start="now" decsending="no" ]';
        echo "<textarea class='widefat'>$value</textarea>";
    }

    static function admin_add_page() {
        add_options_page('IBS GCAL Events', 'IBS GCAL Events', 'manage_options', 'ibs_gcal_events', array(__CLASS__, 'admin_options_page'));
    }

    static function admin_options_page() {
        ?>
        <form action="options.php" method="post">
            <?php settings_fields('ibs_gcal_events_options'); ?>
            <div>
                <?php do_settings_sections('gcal-events'); ?>
                <?php do_settings_sections('gcal-events-qtip'); ?>
                <?php submit_button(); ?>
            </div>
        </form>
        <?PHP
    }

    static function handle_shortcode($atts, $content = null) {
        self::$add_script += 1;
        $args = self::$options;
        if (is_array($atts)) {
            foreach ($args as $key => $value) {
                if (isset($atts[strtolower($key)])) {
                    $args[$key] = $atts[strtolower($key)];
                }
            }
        }
        $args['id'] = self::$add_script;
        $id = self::$add_script;
        ob_start();
        ?>
        <div id="ibs-gcal-events-<?php echo $id; ?>" class="<?php echo $args['align']; ?> gcal-events" style="width:<?php echo $args['width']; ?>" ></div>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                new IBS_GCAL_EVENTS(jQuery, <?PHP echo json_encode($args); ?>, 'shortcode');
            });
        </script> 
        <?PHP
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    static function register_script() {
        wp_register_style('gcal-events-style', plugins_url("css/gcal-events.css", __FILE__));
        wp_register_script('gcal-events-script', plugins_url("js/gcal-events.js", __FILE__), self::$core_handles);
        wp_register_script('ibs-moment-script', plugins_url("js/moment.min.js", __FILE__));
        wp_register_style('ibs-admin-style', plugins_url("css/admin.css", __FILE__));

        wp_register_style('ibs-qtip_style', plugins_url("css/jquery.qtip.css", __FILE__));
        wp_register_script('ibs-qtip-script', plugins_url("js/jquery.qtip.min.js", __FILE__));
    }

    static $core_handles = array(
        'jquery',
        'json2'
    );
    static $script_handles = array(
        'gcal-events-script',
        'ibs-moment-script',
        'ibs-qtip-script'
    );
    static $style_handles = array(
        'gcal-events-style',
        'ibs-qtip-style'
    );

    static function enqueue_scripts() {
        foreach (self::$core_handles as $handle) {
            wp_enqueue_script($handle);
        }
        if (is_active_widget('', '', 'ibs_wgcal_events', true)) {
            self::print_admin_scripts();
            wp_enqueue_style(self::$style_handles);
            wp_enqueue_script(self::$script_handles);
        }
    }

    static function admin_enqueue_scripts($page) {
        if ($page === 'settings_page_ibs_gcal_events') {

            wp_enqueue_style(self::$style_handles);
            wp_enqueue_script(self::$script_handles);
            wp_enqueue_style('ibs-admin-style');
        }
    }

    static function print_admin_scripts() {
        ?>
        <?PHP
    }

    static function print_script_header() {
        
    }

    static function print_script_footer() {
        if (self::$add_script > 0) {
            self::print_admin_scripts();
            wp_print_styles(self::$style_handles);
            wp_print_scripts(self::$script_handles);
        }
    }

}

IBS_GCAL_EVENTS::init();
include( 'widget-gcal-events.php' );
