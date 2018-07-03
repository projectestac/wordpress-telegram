<?php

/**
 * Settings API wrapper class
 * forked from https://github.com/tareq1988/wordpress-settings-api-class
 *
 * @author Manzoor Wani <@manzoorwanijk>
 */

if ( ! class_exists( 'WPTelegram_Settings_API' ) ):
class WPTelegram_Settings_API {

    /**
     * header before nav-tab-wrapper
     *
     * @var array
     */
    protected $header = '';

    /**
     * settings sections array
     *
     * @var array
     */
    protected $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    protected $settings_fields = array();

    /**
     * Options array
     *
     * @var array
     */
    protected $options = array();

    /**
     * The slug of the settings page
     *
     * @var string
     */
    protected $page = '';

    public function __construct( $page ) {
        $this->page = $page;
    }

    /**
     * Set settings sections
     *
     * @param array   $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;

        return $this;
    }

    /**
     * Add a single section
     *
     * @param array   $section
     */
    function add_section( $section ) {
        $this->settings_sections[] = $section;

        return $this;
    }

    /**
     * Set settings fields
     *
     * @param array   $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;

        return $this;
    }

    function add_field( $section, $field ) {
        $defaults = array(
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
        );

        $arg = wp_parse_args( $field, $defaults );
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    /**
     * Set options
     *
     */
    private function set_options() {
        foreach ( $this->settings_sections as $section ) {
            $this->options[ $section['id'] ] = get_option( $section['id'] );
        }
    }

    /**
     * Initializes and registers the settings sections and fields to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    function admin_init() {
        //register settings sections
        foreach ( $this->settings_sections as $section ) {
            if ( false == get_option( $section['id'] ) ) {
                add_option( $section['id'] );
            }
        }
        // set options once and for all
        $this->set_options();

        //register settings fields
        foreach ( $this->settings_fields as $section => $field ) {
            foreach ( $field as $option ) {

                $name = $option['name'];
                $type = isset( $option['type'] ) ? $option['type'] : 'text';
                $label = isset( $option['label'] ) ? $option['label'] : '';
                $callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

                $args = array(
                    'id'                => $name,
                    'class'             => isset( $option['class'] ) ? $option['class'] : $name,
                    'desc_tip'             => isset( $option['desc_tip'] ) ? $option['desc_tip'] : '',
                    'label_for'         => "{$section}[{$name}]",
                    'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
                    'name'              => $label,
                    'section'           => $section,
                    'size'              => isset( $option['size'] ) ? $option['size'] : null,
                    'options'           => isset( $option['options'] ) ? $option['options'] : '',
                    'std'               => isset( $option['default'] ) ? $option['default'] : '',
                    'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
                    'type'              => $type,
                    'as_array'          => isset( $option['as_array'] ) ? $option['as_array'] : false,
                    'json_encoded'      => isset( $option['json_encoded'] ) ? $option['json_encoded'] : false,
                    'emoji_container'      => isset( $option['emoji_container'] ) ? $option['emoji_container'] : false,
                    'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
                    'min'               => isset( $option['min'] ) ? $option['min'] : '',
                    'max'               => isset( $option['max'] ) ? $option['max'] : '',
                    'step'              => isset( $option['step'] ) ? $option['step'] : '',
                    'multiple'          => isset( $option['multiple'] ) ? 'multiple' : '',
                    'grouped'          => isset( $option['grouped'] ) ? $option['grouped'] : false,
                    'events'            => isset( $option['events'] ) ? $option['events'] : '',
                    'button'            => isset( $option['button'] ) ? $option['button'] : '',
                );

                add_settings_field( "{$section}[{$name}]", $label, $callback, $this->page, $section, $args );
            }
        }

        // creates our settings in the options table
        foreach ( $this->settings_sections as $section ) {
            register_setting( $this->page, $section['id'], array( $this, 'sanitize_options' ) );
        }
    }

    /**
     * Get field description for display
     *
     * @param array   $args settings field args
     */
    private function get_field_description( $args ) {
        if ( ! empty( $args['desc'] ) ) {
            $desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
        } else {
            $desc = '';
        }
        return $desc;
    }

    /**
     * Get events associated with an input
     *
     * @param array   $args settings field args
     */
    private function get_events( $events ) {
        if ( ! empty( $events ) ) {
            $res = '';
            foreach ( $events as $attr => $value ) {
                $res .= esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
            }
            return $res;
        }
        return '';
    }

    /**
     * Get button html associated with an input
     *
     * @param array   $args settings field args
     */
    private function get_button( $args ) {
        if ( ! empty( $args['button'] ) ) {
            $btn = $args['button'];
            $id = isset( $btn['id'] ) ? $btn['id'] : '';
            $class = isset( $btn['class'] ) ? $btn['class'] : '';
            $events = isset( $btn['events'] ) ? $btn['events'] : '';
            $name = isset( $btn['name'] ) ? $btn['name'] : '';
            $html = sprintf( '<button id="%1$s" type="button" class="%2$s" %3$s>%4$s</button>', $id, $class, $this->get_events( $events ), esc_html__( $name ) );
            return $html;
        }
        return '';
    }

    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_text( $args ) {

        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $events = $this->get_events( $args['events'] );

        $html        = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/ %7$s>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $events );
        $html       .= $this->get_button( $args ) . $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a number field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_number( $args ) {
        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'number';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $min         = empty( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
        $max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
        $step        = empty( $args['max'] ) ? '' : ' step="' . $args['step'] . '"';

        $html        = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
        $html       .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a checkbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

        $html  = '<fieldset>';
        $html  .= sprintf( '<label for="%1$s[%2$s]">', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="checkbox" class="checkbox %1$s" id="%2$s[%3$s]" name="%2$s[%3$s]" value="on" %4$s />', $args['class'], $args['section'], $args['id'], checked( $value, 'on', false ) );
        $html  .= sprintf( '%1$s</label>', $args['desc_tip'] );
        $html  .= $this->get_field_description( $args ).'</fieldset>';

        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array   $args settings field args
     */
    function callback_multicheck( $args ) {

        $value = (array) $this->get_option( $args['id'], $args['section'], $args['std'] );

        $html  = '<fieldset>';
        
        if ( $args['as_array'] ) {
            /* Add hidden field
             * to avoid auto checking of the default value
             * when none of the checkboxes is checked
             */
            $html  .= sprintf( '<input type="hidden" name="%1$s[%2$s][0]" value="1" />', $args['section'], $args['id'] );
        }
            
        foreach ( $args['options'] as $key => $label ) {
            $arr_key = ($args['as_array'])? '' : $key;
            $checked = in_array($key, $value) ? 'checked' : '';
            $html    .= sprintf( '<label for="%1$s_%2$s_%3$s">', $args['section'], $args['id'], $key );
            $html    .= sprintf( '<input type="checkbox" class="checkbox %1$s" id="%2$s_%3$s_%4$s" name="%2$s[%3$s]['.$arr_key.']" value="%4$s" %5$s />', $args['class'], $args['section'], $args['id'], $key, $checked );
            $html    .= sprintf( '%1$s</label><br>',  $label );
        }

        $html .= $this->get_field_description( $args );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a radio settings field
     *
     * @param array   $args settings field args
     */
    function callback_radio( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<label for="%1$s[%2$s][%3$s]">',  $args['section'], $args['id'], $key );
            $html .= sprintf( '<input type="radio" class="radio %1$s" id="%2$s[%3$s][%4$s]" name="%2$s[%3$s]" value="%4$s" %5$s />', $args['class'], $args['section'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '%1$s</label><br>', $label );
        }

        $html .= $this->get_field_description( $args );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_select( $args ) {

        $values = (array) $this->get_option( $args['id'], $args['section'], $args['std'] );
        $values = array_map('esc_attr', $values);

        $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s %2$s" name="%3$s[%4$s][]" id="%3$s[%4$s]" %5$s>', $size, $args['class'], $args['section'], $args['id'], $args['multiple'] );
        if ( isset( $args['grouped'] ) && $args['grouped'] ) {
            foreach ( $args['options'] as $label => $option ) {
                $html .= sprintf( '<optgroup label="%s">', $label );
                foreach ( $option as $key => $label ) {
                    $selected = (in_array($key, $values))? 'selected':'';
                    $html .= sprintf( '<option value="%s"%s>%s</option>', $key, $selected, $label );
                }
                $html .= '</optgroup>';
            }
        } else{
            foreach ( $args['options'] as $key => $label ) {
                $selected = (in_array($key, $values))? 'selected':'';
                $html .= sprintf( '<option value="%s"%s>%s</option>', $key, $selected, $label );
            }
        }

        $html .= sprintf( '</select>' );
        $html .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_textarea( $args ) {

        $value       = $this->get_option( $args['id'], $args['section'], $args['std'] );
        if ( $args['json_encoded'] ) {
            $value = json_decode($value);
        }
        $value = esc_textarea( $value );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="'.$args['placeholder'].'"';

        $html        = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="'.$this->page.'_%3$s" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
        $html        .= $this->get_field_description( $args );
        if ( $args['emoji_container'] ) {
            echo '<div id="' .$this->page . '_' . $args['id'] . '-container"></div>';
        }
        echo $html;
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     * @return string
     */
    function callback_html( $args ) {
        echo $this->get_field_description( $args );
    }

    /**
     * Sanitize callback for Settings API
     *
     * @return mixed
     */
    function sanitize_options( $options ) {

        if ( ! $options ) {
            return $options;
        }
        $filtered = array();
        foreach( $options as $option_slug => $option_value ) {
            $sanitize_callback = $this->get_sanitize_callback( $option_slug );

            // If callback is set, call it
            if ( $sanitize_callback ) {
                $filtered[ sanitize_text_field( $option_slug ) ] = call_user_func( $sanitize_callback, $option_value );
            } else{
                $filtered[ sanitize_text_field( $option_slug ) ] = $this->default_sanitize( $option_value );
            }
        }

        return $filtered;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     *
     * @return mixed string or bool false
     */
    function get_sanitize_callback( $slug = '' ) {
        if ( empty( $slug ) ) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach( $this->settings_fields as $section => $options ) {
            foreach ( $options as $option ) {
                if ( $option['name'] != $slug ) {
                    continue;
                }

                // Return the callback name
                return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * The default sanitzation
     *
     * @param string|array $option option value
     *
     * @return mixed string or array
     */
    function default_sanitize( $option ) {
        if ( is_array( $option ) ) {
            $filtered = array();
            foreach ( (array) $option as $key => $value ) {
                $filtered[ sanitize_text_field( $key ) ] = $this->default_sanitize( $value );
            }
            return $filtered;
        }
        return sanitize_text_field( $option );
    }

    /**
     * Get the value of a settings field
     *
     * @param string  $option  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    function get_option( $option, $section, $default = '' ) {

        if ( isset( $this->options[ $section ][ $option ] ) ) {
            return $this->options[ $section ][ $option ];
        }

        return $default;
    }

    /**
     * Show header
     *
     */
    function show_header() {
        // add <h2></h2> to keep admin notices above the nav-tab-wrapper
        $html = '<h2></h2>';

        $html .= '<div id="wptelegram-header">';

        $html .= $this->header;

        $html .= '</div>';

        echo $html;
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    function show_navigation() {

        $html = '<h2 class="nav-tab-wrapper">';

        $count = count( $this->settings_sections );

        // don't show the navigation if only one section exists
        if ( $count === 1 ) {
            return;
        }

        foreach ( $this->settings_sections as $tab ) {
            // don't show the tab if no setting fields
            if (!array_key_exists($tab['id'], $this->settings_fields)) {
                continue;
            }
            $icon_src = isset( $tab['icon_src'] ) ? $tab['icon_src'] : WPTELEGRAM_URL . '/admin/icons/icon-30x30.svg';
            $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab"><img src="%2$s" alt=""><span>%3$s</span></a>', $tab['id'], $icon_src, $tab['title'] );
        }

        $html .= '</h2>';

        echo $html;
    }

    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_forms() {
        ?>
        <div class="metabox-holder">
            <?php settings_errors();?>
            <?php echo apply_filters( 'wptelegram_before_settings', '' ); ?>
            <form method="post" action="options.php">
            <?php settings_fields( $this->page ); ?>
                <?php foreach ( $this->settings_sections as $section ) { ?>
                    <div id="<?php echo $section['id']; ?>" class="group">
                        
                            <?php
                            if ( $section['title'] )
                                echo "<h2>{$section['title']}</h2>\n<hr>";
                     
                            if ( isset( $section['desc'] ) ){
                                echo '<div class="inside"><p>' . esc_html__( $section['desc'] ) . '</p></div>';
                            } elseif ( isset( $section['callback'] ) ) {
                                call_user_func( $section['callback'] );
                            }
                            do_action( 'wptelegram_section_top_' . $section['id'], $section );
                            echo '<table class="form-table">';
                            do_settings_fields( $this->page, $section['id'] );
                            echo '</table>';
                            do_action( 'wptelegram_section_bottom_' . $section['id'], $section );
                            
                            ?>
                        
                    </div>
                <?php } ?>
                <?php
                do_action( 'wptelegram_before_submit_button' );
                submit_button(); ?>
            </form>
            <?php echo apply_filters( 'wptelegram_after_settings', '' ); ?>
        </div>
        <?php
        $this->script();
    }

    /**
     * Tabbable JavaScript codes & Initiate Color Picker
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
        ?>
        <script>
            jQuery(document).ready(function($) {
                
                $('.group').hide();
                var activetab = '';
                if (typeof(localStorage) != 'undefined' ) {
                    activetab = localStorage.getItem("activetab");
                }
                if (activetab != '' && $(activetab).length ) {
                    $(activetab).fadeIn();
                } else {
                    $('.group:first').fadeIn();
                }
                $('.group .collapsed').each(function(){
                    $(this).find('input:checked').parent().parent().parent().nextAll().each(
                    function(){
                        if ($(this).hasClass('last')) {
                            $(this).removeClass('hidden');
                            return false;
                        }
                        $(this).filter('.hidden').removeClass('hidden');
                    });
                });

                if (activetab != '' && $(activetab + '-tab').length ) {
                    $(activetab + '-tab').addClass('nav-tab-active');
                }
                else {
                    $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                }
                $('.nav-tab-wrapper a').click(function(evt) {
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active').blur();
                    var clicked_group = $(this).attr('href');
                    if (typeof(localStorage) != 'undefined' ) {
                        localStorage.setItem("activetab", $(this).attr('href'));
                    }
                    $('.group').hide();
                    $(clicked_group).fadeIn();
                    evt.preventDefault();
                });
        });
        </script>
        <?php
        $this->_style_fix();
    }

    function _style_fix() {
        global $wp_version;

        if (version_compare($wp_version, '3.8', '<=')):
        ?>
        <style type="text/css">
            /** WordPress 3.8 Fix **/
            .form-table th { padding: 20px 10px; }
            #wpbody-content .metabox-holder { padding-top: 5px; }
        </style>
        <?php
        endif;
    }

}
endif;