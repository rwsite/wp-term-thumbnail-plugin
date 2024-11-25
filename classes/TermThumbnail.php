<?php

namespace classes;

use TermThumbnailSettings;
use WP_Term;

/**
 * Main plugin class
 */
class TermThumbnail
{
    /** @var array */
    public $taxonomies; // для каких таксономий должен работать код

    /**
     * @var string plugin file path
     */
    public string $file;
    public TermThumbnailSettings $plugin_settings;
    public string $dir;
    public string $url;


    public function __construct($file = null)
    {
        $this->file = $file ?? __FILE__;
        $this->dir = plugin_dir_path($this->file);
        $this->url = plugin_dir_url($this->file);
        $this->plugin_settings = new TermThumbnailSettings($this->file);
        $this->taxonomies = $this->plugin_settings->settings['taxonomies']
            ?: [];
    }

    public function add_actions()
    {
        $this->plugin_settings->add_actions();

        foreach ($this->taxonomies as $taxname) {
            if (!taxonomy_exists($taxname)) {
                continue;
            }

            add_action("{$taxname}_add_form_fields",
                [$this, 'update_term_image']);
            add_action("{$taxname}_edit_form_fields",
                [$this, 'update_term_image']);

            add_action("created_{$taxname}", [$this, 'updated_term_image'], 10,
                2);
            add_action("edited_{$taxname}", [$this, 'updated_term_image'], 10,
                2);

            add_filter("manage_edit-{$taxname}_columns",
                [$this, 'add_image_column']);

            add_filter("manage_{$taxname}_custom_column",
                [$this, 'fill_image_column'], 10, 3);

            add_filter('get_post_thumbnail', function ($thumbnail, $post_id, $attr) use ($taxname){
                // $taxname = 'category';
                if(empty($thumbnail)){
                    $terms = get_the_terms(get_post($post_id), $taxname);
                    if($terms && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            if($term->taxonomy == $taxname && empty($thumbnail)) {
                                $thumbnail = get_default_post_thumbnail($term->term_id, $attr);
                            }
                        }
                    }
                }
                return $thumbnail;
            },10,3);
        }
    }

    /**
     * Edit the form field
     */
    public function update_term_image(WP_Term|string $term)
    {
        if (get_bloginfo('version') >= 3.5) {
            wp_enqueue_media();
        } else {
            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
        }

        add_action('admin_print_footer_scripts', [$this, 'add_script'], 99);

        $image_id = $image_url = $term_image_id = $term_image_url = '';
        if ($term instanceof WP_Term) {
            $term_image_id = get_term_meta($term->term_id, 'term_image_id',
                true);
            $term_image_url = wp_get_attachment_image_src($term_image_id,'thumbnail', true)[0];

            $image_id = get_term_meta($term->term_id, 'default_cover',true);
            $image_url = wp_get_attachment_url($image_id);
        }

        require $this->dir . 'template/form.php';
    }

    // Update the form field value
    public function updated_term_image($term_id)
    {
        if (isset($_POST['taxonomy_image'])) {
            update_term_meta($term_id, 'default_cover', intval($_POST['taxonomy_image']));
        } else {
            delete_term_meta($term_id, 'default_cover');
        }

        if (isset($_POST['term_image_id'])) {
            update_term_meta($term_id, 'term_image_id', intval($_POST['term_image_id']));
        } else {
            delete_term_meta($term_id, 'term_image_id');
        }
    }

    // Add script
    public function add_script()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#tax_media_button').click(function (e) {
                    e.preventDefault();
                    let image_frame;
                    if (image_frame) {
                        image_frame.open();
                        return;
                    }
                    image_frame = wp.media({
                        title: '<?php _e('Choose image','wp-term-thumbnail'); ?>',
                        button: {
                            text: '<?php _e('Choose image','wp-term-thumbnail'); ?>'
                        },
                        multiple: false
                    });
                    image_frame.on('select', function () {
                        let attachment = image_frame.state().get('selection').first().toJSON();
                        $('#term_image_id').val(attachment.id);
                        $('#term_image_preview').attr('src', attachment.url).show();
                    });
                    image_frame.open();
                });

                $('#tax_media_remove').click(function (e) {
                    e.preventDefault();
                    if (confirm('<?php _e('Are you sure you want to delete the image?','wp-term-thumbnail'); ?>')) {
                        $('#term_image_id').val('');
                        $('#term_image_preview').attr('src', '').hide();
                    }
                });
            });
        </script>

        <script>
            jQuery(document).ready(function ($) {
                $('#taxonomy_image_button').click(function (e) {
                    e.preventDefault();
                    let image_frame;
                    if (image_frame) {
                        image_frame.open();
                        return;
                    }
                    image_frame = wp.media({
                        title: '<?php _e('Choose image','wp-term-thumbnail'); ?>',
                        button: {
                            text: '<?php _e('Choose image','wp-term-thumbnail'); ?>'
                        },
                        multiple: false
                    });
                    image_frame.on('select', function () {
                        let attachment = image_frame.state().get('selection').first().toJSON();
                        $('#taxonomy_image').val(attachment.id);
                        $('#taxonomy_image_preview').attr('src', attachment.url).show();
                    });
                    image_frame.open();
                });

                $('#taxonomy_image_delete_button').click(function (e) {
                    e.preventDefault();
                    if (confirm('<?php _e('Are you sure you want to delete the image?','wp-term-thumbnail'); ?>')) {
                        $('#taxonomy_image').val('');
                        $('#taxonomy_image_preview').attr('src', '').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Добавляет колонку картинки в таблицу терминов
     */
    public function add_image_column($columns)
    {
        $num = 1; // после какой по счету колонки вставлять новые
        $new_columns = ['thumbnail' => '<span class="dashicons dashicons-format-image"></span>'];
        return array_slice($columns, 0, $num) + $new_columns + array_slice($columns, $num);
    }

    /**
     * Parses the column.
     *
     * @param  string  $content  The current content of the column.
     * @param  string  $column_name  The name of the column.
     * @param  int  $term_id  ID of requested taxonomy.
     *
     * @return string
     */
    public function fill_image_column($content, $column_name, $term_id)
    {
        switch ($column_name) {
            case 'thumbnail':

                $thumbnail_id = get_term_meta($term_id, 'term_image_id', true);
                $url = wp_get_attachment_image_src($thumbnail_id, 'thumbnail',
                    true)[0];

                if (false !== strpos($url, '.svg')) {
                    $thumb = '<img src="'.$url.'" width="100" height="100">';
                } else {
                    if (!empty($thumbnail_id)) {
                        $width = $height = '90';
                        if (function_exists('kama_thumb_img')) {
                            $thumb = kama_thumb_a_img([
                                'width'  => $width,
                                'height' => $height,
                                'crop'   => true,
                                'a_attr' => 'data-rel="lightcase"',
                            ], $thumbnail_id);
                        } else {
                            $thumb = wp_get_attachment_image($thumbnail_id,
                                [$width, $height], true);
                        }
                    }
                }
                return $thumb ??
                    '<span class="dashicons dashicons-format-image"></span>';
                break;
            default:
                return $content;
        }
    }
}