<?php

class IBS_WGCal_Events extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'class' => 'ibs_wgcal_events',
            'description' => 'A widget to display Google Calendar Events'
        );

        parent::__construct(
                'ibs_wgcal_events', 'IBS GCAL Events', $widget_ops
        );
    }

    public function form($instance) {
        $widget_defaults = array(
            'title' => 'IBS GCAL Events',
            'calendar' => '',
            'start' => 'now',
            'max' => '20'
        );

        $instance = wp_parse_args((array) $instance, $widget_defaults);
        $args = get_option('ibs_gcal_events_options')
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo'Title'; ?></label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        <p></p>
        <div class="widefat"><label for="<?php echo $this->get_field_id('calendar'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Calendar ID'; ?></span>
                <input type="text" id="<?php echo $this->get_field_id('calendar'); ?>" name="<?php echo $this->get_field_name('calendar'); ?>"  value="<?php echo esc_attr($instance['calendar']); ?>">
            </label></div>
        <p></p>
        <div class="widefat"><label for="<?php echo $this->get_field_id('start'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Start date'; ?></span>
                <input type="text" id="<?php echo $this->get_field_id('start'); ?>" name="<?php echo $this->get_field_name('start'); ?>"  value="<?php echo esc_attr($instance['start']); ?>">
            </label></div>
        <p></p>
        <div class="widefat"><label for="<?php echo $this->get_field_id('max'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Number of events to show'; ?></span>
                <input type="number" min=1 max=100 id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_name('max'); ?>"  value="<?php echo esc_attr($instance['max']); ?>">
            </label></div>
        <p></p>
        <?PHP
    }

    public function update($new_instance, $old_instance) {
        $old_instance = $new_instance;

        $instance['title'] = $new_instance['title'];

        return $old_instance;
    }

    public function widget($widget_args, $instance) {
        extract($widget_args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        $args = get_option('ibs_gcal_events_options');
        if (is_array($instance)) {
            foreach ($args as $key => $value) {
                if (isset($instance[strtolower($key)])) {
                    $args[$key] = $instance[strtolower($key)];
                }
            }
        }
        $args['width'] = '100%';
        $args['id'] = $widget_id;
        $id = $widget_id;
        $width = $args['width'];
        ?>
        <div id="ibs-wgcal-events-<?php echo $id;?>" ></div>
        <script type="text/javascript">
            new IBS_GCAL_EVENTS(jQuery, <?PHP echo json_encode($args); ?>, 'widget');
        </script> 
        <?php
        echo $after_widget;
    }

}

function ibs_wgcal_widget() {
    register_widget('IBS_WGCAL_Events');
}

add_action('widgets_init', 'ibs_wgcal_widget');
