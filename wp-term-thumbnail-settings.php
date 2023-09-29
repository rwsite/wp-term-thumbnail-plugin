<?php
/**
 * Admin settings
 */

class TermThumbnailSettings
{
    public string $file;
    public const key = 'term_thumbnail';
    public const settings_page = 'media';
    public ?array $settings;

    public function __construct($file)
    {
        $this->file = $file;
        $this->settings = self::get_settings();
    }

    public function add_actions()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            // рендер секции настроек
            add_action((is_multisite() ? 'network_admin_menu' : 'admin_menu'), [$this, 'admin_options']);
            // ссылка на настойки со страницы плагинов
            add_filter('plugin_action_links', [$this, 'setting_page_link'], 10, 2);
            // ловля обновления опций
            if (is_multisite()) {
                add_action('network_admin_edit_' . self::key, [$this, 'network_options_update']);
            }

            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'],100);

        }
    }

    public function admin_enqueue_scripts(){

        $ver = defined('WP_DEBUG_SCRIPT') ? current_time('timestamp') : wp_get_theme()->get( 'Version' );
        $dir_uri = get_template_directory_uri();
        $dir_path = get_template_directory();

        // js
        if( file_exists($dir_path . '/assets/js/plugins/lightcase.js') ) {
            wp_register_script('lightcase', $dir_uri . '/assets/js/plugins/lightcase.js', ['jquery'], $ver, false);
        } else {
            wp_register_script('lightcase', 'https://cdnjs.cloudflare.com/ajax/libs/lightcase/2.5.0/js/lightcase.min.js',  ['jquery'], $ver, false);
        }
        // css
        if( file_exists($dir_path . '/assets/css/plugins/lightcase.min.css') ) {
            wp_register_style('lightcase', $dir_uri . '/assets/css/plugins/lightcase.min.css', $ver);
        } else {
            wp_register_style('lightcase', 'https://cdnjs.cloudflare.com/ajax/libs/lightcase/2.5.0/css/lightcase.min.css', $ver);
        }

        wp_enqueue_script('lightcase');
        wp_enqueue_style('lightcase');

        wp_add_inline_script('lightcase', 'jQuery(document).ready(function($){
                $("a[data-rel^=lightcase]").lightcase({
                    typeMapping: {
                        "image": "webp,jpg,jpeg,gif,png,bmp",
                    },
                });
        });');

        wp_add_inline_style('lightcase', '
        .thumbnail .dashicons.dashicons-format-image {
                width: 80px;
                height: 80px;
                font-size: 90px;
                text-align: center;
                color: #cfd5de;
            }
            .thumbnail img {
                border-radius: 8px;
            }
            .manage-column.column-thumbnail {
                width: 100px;
                text-align: center;
        }');

    }

    public static function get_settings(){
        $opts = is_multisite() ? get_site_option(self::key) : get_option(self::key);
        $def_opt = self::get_default_settings();
        return array_merge($def_opt, (array)$opts);
    }

    public static function get_default_settings()
    {
        return apply_filters('term_thumbnail_defaults', [
            'taxonomies' => array_keys(get_taxonomies(['publicly_queryable' => true])),
        ]);
    }

    /**
     * для вывода сообщений в админке
     */
    public static function show_message($text = '', $class = 'updated')
    {
        add_action('admin_notices', function () use ($text, $class) {
            echo '<div id="message" class="' . $class . ' notice is-dismissible"><p>' . $text . '</p></div>';
        });
    }

    public function admin_options()
    {
        // для мультисайта создается отдельная страница в настройках сети
        if (is_multisite()) {
            $hook = add_submenu_page('settings.php', __('Term Thumbnail Settings', 'thumbnail'),
                __('Term Thumbnail', 'thumbnail' ), 'manage_network_options', self::settings_page,
                [$this,'_network_options_page']
            );
        }

        add_settings_section(self::key, __('Term Thumbnail Settings', 'thumbnail'), '', self::settings_page);
        add_settings_field(self::key,
            __('Выберите поддерживаемые таксономии', 'thumbnail'),
            [$this, 'options_field'],
            self::settings_page,
            self::key
        );

        register_setting(self::settings_page, self::key, [$this, 'sanitize_options']);
    }

    public function _network_options_page()
    {
        echo '<form method="POST" action="edit.php?action=' . self::key . '" style="max-width:900px;">';
        wp_nonce_field(self::settings_page);
        do_settings_sections(self::settings_page);
        submit_button();
        echo '</form>';
    }

    public function options_field()
    {
        ?>
        <select class="select" multiple name="<?=self::key?>[taxonomies][]">
            <?php
                foreach (get_taxonomies(['publicly_queryable' => true], 'label') as $key => $wp_tax){
                    $selected = is_array($this->settings['taxonomies']) && in_array($key, $this->settings['taxonomies']) ? 'selected' : '';
                    echo '<option value="'.$key.'" '.$selected.'>'.$wp_tax->label.'</option>';
                }
            ?>
        </select>
        <?php
    }

    /**
     * Save option and validate
     *
     * @param $opts
     * @return mixed
     */
    function sanitize_options( $opts ){
        $default = self::get_default_settings();

        foreach( $opts as $key => &$val ){
            if(is_string($val)) {
                $val = sanitize_text_field($val);
            } else {
                array_walk_recursive($val, 'sanitize_text_field');
            }
        }

        return $opts;
    }

    /**
     * update options from network settings.php
     */
    public function network_options_update()
    {
        // nonce check
        check_admin_referer(self::settings_page);
        $new_opts = wp_unslash($_POST[self::key]);
        update_site_option(self::key, $new_opts);
        wp_redirect(add_query_arg('updated', 'true', network_admin_url('settings.php?page=' . self::settings_page)));
        exit();
    }

    public function setting_page_link($actions, $plugin_file)
    {
        if (!strpos($plugin_file, basename($this->file))) {
            return $actions;
        }

        $settings_link = '<a href="' . admin_url('options-media.php') . '">' . __('Settings', 'thumbnail') . '</a>';
        array_unshift($actions, $settings_link);

        return $actions;
    }
}
