<?php
/**
 * Plugin Name: CB Preloader Bar
 * Description: A lightweight top preloader bar with animated gradient that completes on window load.
 * Version: 1.2.0
 * Author: Cipherbaze
 * License: GPLv2 or later
 * Text Domain: dp-preloader-bar
 */

if (!defined('ABSPATH')) exit;

class DP_Preloader_Bar {
    const VERSION = '1.2.0';

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_assets']);

        if (function_exists('wp_body_open')) {
            add_action('wp_body_open', [$this, 'render_markup']);
        } else {
            add_action('wp_footer', [$this, 'render_markup'], 5);
        }
    }

    // ...existing code...
    public function enqueue_assets() {
        $base = plugin_dir_url(__FILE__);
        wp_enqueue_style(
            'dp-preloader-css',
            $base . 'assets/css/preloader.css',
            [],
            self::VERSION
        );

        // Register GSAP from CDN
        wp_register_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
            [],
            '3.12.2',
            true
        );

        wp_enqueue_script(
            'dp-preloader-js',
            $base . 'assets/js/preloader.js',
            ['gsap'],
            self::VERSION,
            true
        );

        // Provide options to frontend and inject simple CSS variables
        $opts = wp_parse_args( (array) get_option('dp_preloader_options', []), [
            'text_color'   => '#ffffff',
            'bar_color'    => '#ffffff',
            'text'         => '',
            'logo'         => '',
            'display'      => 'text', // text | logo | both
            'duration'     => 3200,
        ] );

        // Inject CSS variables so the stylesheet can use them
        $css_vars = sprintf(
            ":root { --dp-preloader-text-color: %s; --dp-preloader-bar-color: %s; --dp-preloader-duration: %sms; }",
            esc_attr($opts['text_color']),
            esc_attr($opts['bar_color']),
            intval($opts['duration'])
        );
        wp_add_inline_style('dp-preloader-css', $css_vars);

        // Pass config to JS
        $config = [
            'duration' => intval($opts['duration']),
            'text'     => sanitize_text_field($opts['text']),
            'logo'     => esc_url_raw($opts['logo']),
            'display'  => in_array($opts['display'], ['text','logo','both']) ? $opts['display'] : 'text',
            'text_color' => esc_attr($opts['text_color']),
            'bar_color' => esc_attr($opts['bar_color']),
        ];
        wp_localize_script('dp-preloader-js', 'DP_PRELOADER_CONFIG', $config);
    }

    // Admin assets for settings page (media uploader)
    public function admin_enqueue_assets($hook) {
        // only load on our settings page
        if ($hook !== 'settings_page_dp-preloader') return;
        $base = plugin_dir_url(__FILE__);
        wp_enqueue_media();
        wp_enqueue_script('dp-preloader-admin-js', $base . 'assets/js/preloader.js', ['jquery'], self::VERSION, true);
    }

    // ...existing code...
    public function render_markup() {
        if (apply_filters('dp_preloader_enabled', true) !== true) return;

        $opts = wp_parse_args( (array) get_option('dp_preloader_options', []), [
            'text_color'   => '#ffffff',
            'bar_color'    => '#ffffff',
            'text'         => '',
            'logo'         => '',
            'display'      => 'text',
            'duration'     => 3200,
        ] );

        $site_text = $opts['text'] !== '' ? esc_html($opts['text']) : esc_html(get_bloginfo('name'));
        $logo_url = esc_url($opts['logo']);
        $display = in_array($opts['display'], ['text','logo','both']) ? $opts['display'] : 'text';
        ?>
        <div class="dp-preloader" aria-hidden="true">
            <div class="dp-site-name">
                <?php if ($display === 'logo' || $display === 'both') : ?>
                    <?php if ($logo_url) : ?>
                        <img class="dp-preloader-logo" src="<?php echo $logo_url; ?>" alt="<?php echo esc_attr($site_text); ?>" />
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($display === 'text' || $display === 'both') : ?>
                    <span style="color: var(--dp-preloader-text-color);"><?php echo $site_text; ?></span>
                <?php endif; ?>
            </div>

            <div class="dp-preloader-gutters" aria-hidden="true">
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
                <div class="dp-bar"><div class="dp-inner-bar"></div></div>
            </div>

            <div class="dp-preloader-overlay" aria-hidden="true"></div>
        </div>
        <noscript>
            <style>#dp-preloader, .dp-preloader { display:none !important; }</style>
        </noscript>
        <?php
    }

    /* Admin: add settings page */
    public function add_settings_page() {
        add_options_page(
            __('DP Preloader', 'dp-preloader-bar'),
            __('DP Preloader', 'dp-preloader-bar'),
            'manage_options',
            'dp-preloader',
            [$this, 'settings_page_markup']
        );
    }

    /* Admin: register settings + fields */
    public function register_settings() {
        register_setting('dp_preloader_options_group', 'dp_preloader_options', [$this, 'sanitize_options']);

        add_settings_section(
            'dp_preloader_main_section',
            __('Preloader Settings', 'dp-preloader-bar'),
            function() { echo '<p>' . esc_html__('Configure preloader appearance and timing.', 'dp-preloader-bar') . '</p>'; },
            'dp-preloader'
        );

        add_settings_field(
            'dp_preloader_text',
            __('Preloader Text', 'dp-preloader-bar'),
            [$this, 'field_text_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );

        add_settings_field(
            'dp_preloader_logo',
            __('Logo', 'dp-preloader-bar'),
            [$this, 'field_logo_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );

        add_settings_field(
            'dp_preloader_display',
            __('Display', 'dp-preloader-bar'),
            [$this, 'field_display_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );

        add_settings_field(
            'dp_preloader_text_color',
            __('Text Color', 'dp-preloader-bar'),
            [$this, 'field_text_color_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );

        add_settings_field(
            'dp_preloader_bar_color',
            __('Bar Color', 'dp-preloader-bar'),
            [$this, 'field_bar_color_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );

        add_settings_field(
            'dp_preloader_duration',
            __('Animation Timing (ms)', 'dp-preloader-bar'),
            [$this, 'field_duration_cb'],
            'dp-preloader',
            'dp_preloader_main_section'
        );
    }

    public function sanitize_options($input) {
        $out = [];
        $out['text']       = isset($input['text']) ? sanitize_text_field($input['text']) : '';
        $out['logo']       = isset($input['logo']) ? esc_url_raw($input['logo']) : '';
        $out['display']    = isset($input['display']) && in_array($input['display'], ['text','logo','both']) ? $input['display'] : 'text';
        $out['text_color'] = isset($input['text_color']) ? sanitize_hex_color($input['text_color']) : '#ffffff';
        $out['bar_color']  = isset($input['bar_color']) ? sanitize_hex_color($input['bar_color']) : '#ffffff';
        $out['duration']   = isset($input['duration']) ? absint($input['duration']) : 3200;
        return $out;
    }

    /* Field callbacks */
    public function field_text_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $text = isset($opts['text']) ? esc_attr($opts['text']) : '';
        printf(
            '<input type="text" id="dp_preloader_options_text" name="dp_preloader_options[text]" value="%s" class="regular-text" />',
            $text
        );
        echo '<p class="description">Override the site name shown in the preloader. Leave empty to use site title.</p>';
    }

    public function field_logo_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $logo = isset($opts['logo']) ? esc_url($opts['logo']) : '';
        ?>
        <div>
            <input type="text" id="dp_preloader_options_logo" name="dp_preloader_options[logo]" value="<?php echo esc_attr($logo); ?>" class="regular-text" />
            <input type="button" id="dp-preloader-upload-btn" class="button" value="<?php esc_attr_e('Upload / Select', 'dp-preloader-bar'); ?>" />
            <input type="button" id="dp-preloader-clear-btn" class="button" value="<?php esc_attr_e('Clear', 'dp-preloader-bar'); ?>" />
            <p class="description">Provide a logo URL or use the Upload/Select button.</p>
        </div>
        <?php
    }

    public function field_display_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $display = isset($opts['display']) ? $opts['display'] : 'text';
        ?>
        <select name="dp_preloader_options[display]" id="dp_preloader_options_display">
            <option value="text" <?php selected($display, 'text'); ?>><?php esc_html_e('Text only', 'dp-preloader-bar'); ?></option>
            <option value="logo" <?php selected($display, 'logo'); ?>><?php esc_html_e('Logo only', 'dp-preloader-bar'); ?></option>
            <option value="both" <?php selected($display, 'both'); ?>><?php esc_html_e('Both', 'dp-preloader-bar'); ?></option>
        </select>
        <p class="description">Choose whether to show text, logo or both in the preloader.</p>
        <?php
    }

    public function field_text_color_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $color = isset($opts['text_color']) ? esc_attr($opts['text_color']) : '#ffffff';
        printf(
            '<input type="color" id="dp_preloader_options_text_color" name="dp_preloader_options[text_color]" value="%s" />',
            $color
        );
        echo '<p class="description">Color used for preloader text.</p>';
    }

    public function field_bar_color_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $color = isset($opts['bar_color']) ? esc_attr($opts['bar_color']) : '#ffffff';
        printf(
            '<input type="color" id="dp_preloader_options_bar_color" name="dp_preloader_options[bar_color]" value="%s" />',
            $color
        );
        echo '<p class="description">Color used for the bars (solid color).</p>';
    }

    public function field_duration_cb() {
        $opts = (array) get_option('dp_preloader_options', []);
        $duration = isset($opts['duration']) ? intval($opts['duration']) : 3200;
        printf(
            '<input type="number" min="200" step="50" id="dp_preloader_options_duration" name="dp_preloader_options[duration]" value="%d" />',
            $duration
        );
        echo '<p class="description">Base animation timing in milliseconds. Used by frontend script/CSS.</p>';
    }

    /* Settings page HTML */
    public function settings_page_markup() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('DP Preloader Settings', 'dp-preloader-bar'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dp_preloader_options_group');
                do_settings_sections('dp-preloader');
                submit_button();
                ?>
            </form>
            <h2>Short Notes</h2>
            <p>Change the preloader text, logo, accent color for text and bars, and base animation timing. CSS variables are injected so you can further customize in your theme CSS if needed.</p>
        </div>
        <?php
    }

}

new DP_Preloader_Bar();