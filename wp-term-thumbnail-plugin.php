<?php
/**
 * Plugin Name:  Добавляет возможность загружать изображения для таксономий
 * Plugin URL:   https://rwsite.ru
 * Description:  Плагин добавляет возможность загружать изображения для произвольных таксономий.
 * Version:      1.0.0
 * Text Domain:  wp-term-thumbnail
 * Domain Path:  /languages
 * Author:       Aleksey Tikhomirov
 * Author URI:   https://rwsite.ru
 *
 * Tags: wp-addon, addon
 * Requires at least: 4.6
 * Tested up to: 6.3.0
 * Requires PHP: 8.0+
 *
 *
 * Получить ID картинки термина: $image_id = get_term_meta( $term_id, 'term_image_id', 1 );
 * Затем получить URL картинки: $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
 */

if (!class_exists('TermThumbnail')) {

    require_once 'wp-term-thumbnail-settings.php';
    require_once 'functions.php';

    class TermThumbnail
    {
        /** @var array */
        public $taxonomies; // для каких таксономий должен работать код

        /**
         * @var string plugin file path
         */
        public string $file;
        public TermThumbnailSettings $plugin_settings;


        public function __construct($file = null)
        {
            $this->file = $file ?? __FILE__;
            $this->plugin_settings = new TermThumbnailSettings($this->file);
            $this->taxonomies = $this->plugin_settings->settings['taxonomies'] ?: [];
        }

        public function add_actions()
        {
            $this->plugin_settings->add_actions();
            foreach ($this->taxonomies as $taxname) {

                if (!taxonomy_exists($taxname)) {
                    continue;
                }

                add_action("{$taxname}_add_form_fields", [$this, 'add_term_image']);
                add_action("{$taxname}_edit_form_fields", [$this, 'update_term_image'], 10, 2);
                add_action("created_{$taxname}", [$this, 'updated_term_image'], 10, 2);
                add_action("edited_{$taxname}", [$this, 'updated_term_image'], 10, 2);

                add_filter("manage_edit-{$taxname}_columns", [$this, 'add_image_column']);
                add_filter("manage_{$taxname}_custom_column", [$this, 'fill_image_column'], 10, 3);
            }
        }

        /**
         * Add a form field in the new category page
         */
        public function add_term_image()
        {
            wp_enqueue_media(); // подключим стили медиа, если их нет
            add_action('admin_print_footer_scripts', [$this, 'add_script'], 99);
            ?>
            <div class="form-field term-group">
                <label for="term_image_id"><span class="dashicons dashicons-format-image"></span> Thumbnail</label>
                <input type="hidden" id="term_image_id" name="term_image_id" class="custom_media_url" value="">
                <div id="term__image__wrapper"></div>
                <p>
                    <a href="#" class="button button-secondary tax_media_button"><?php _e('Add'); ?></a>
                    <a href="#" class="button button-secondary tax_media_remove"><?php _e('Remove'); ?></a>
                </p>
            </div>
            <?php
        }

        /**
         * Edit the form field
         */
        public function update_term_image($term, $taxonomy)
        {
            wp_enqueue_media(); // подключим стили медиа, если их нет

            add_action('admin_print_footer_scripts', [$this, 'add_script'], 99);

            $image_id = get_term_meta($term->term_id, 'term_image_id', true);
            ?>
            <tr class="form-field term-group-wrap">
                <th scope="row">
                    <label for="term_image_id"><span class="dashicons dashicons-format-image"></span> Thumbnail</label>
                </th>
                <td>
                    <input type="hidden" id="term_image_id" name="term_image_id" value="<?php echo $image_id; ?>">
                    <div id="term__image__wrapper">
                        <?php if ($image_id) {
                            echo wp_get_attachment_image($image_id, 'thumbnail');
                        } ?>
                    </div>
                    <p>
                        <a href="#" class="button button-secondary tax_media_button"><?php _e('Add'); ?></a>
                        <a href="#" class="button button-secondary tax_media_remove"><?php _e('Remove'); ?></a>
                    </p>
                </td>
            </tr>
            <?php
        }

        ## Update the form field value
        public function updated_term_image($term_id, $tt_id)
        {
            if (!isset($_POST['term_image_id'])) {
                return;
            }
            update_term_meta($term_id, 'term_image_id', sanitize_text_field($_POST['term_image_id']));
            do_action('save_term_image', $term_id, $tt_id);
        }

        ## Add script
        public function add_script()
        {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    function ct_media_upload(button_class) {
                        let orig_send_attachment = wp.media.editor.send.attachment;

                        $('body').on('click', button_class, function (e) {
                            e.preventDefault();

                            wp.media.editor.send.attachment = function (props, attachment) {
                                let src = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                                $('#term_image_id').val(attachment.id);
                                $('#term__image__wrapper').html('<img class="custom_media_image" src="" style="max-height:125px; border-radius: 0.3rem;" />');
                                $('#term__image__wrapper .custom_media_image').attr('src', src).css('display', 'block');
                            }

                            wp.media.editor.open($(this).attr('id'));

                            return false;
                        });
                    }

                    ct_media_upload('.tax_media_button.button');

                    $('body').on('click', '.tax_media_remove', function (e) {
                        e.preventDefault();
                        $('#term_image_id').val('');
                        $('#term__image__wrapper').html('<img class="custom_media_image" src="" />');
                    });

                    
                    $(document).ajaxComplete(function (event, xhr, settings) {
                        let queryStringArr = settings.data.split('&');
                        if ($.inArray('action=add-tag', queryStringArr) !== -1) {
                            let xml = xhr.responseXML;
                            $response = $(xml).find('term_id').text();
                            if ($response !== '') {
                                $('#term__image__wrapper').html(''); // Clear the thumb image
                            }
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
         * @param string $content     The current content of the column.
         * @param string $column_name The name of the column.
         * @param int    $term_id     ID of requested taxonomy.
         *
         * @return string
         */
        public function fill_image_column($content, $column_name, $term_id)
        {
            switch ( $column_name ) {
                case 'thumbnail':
                    $thumbnail_id = get_term_meta($term_id, 'term_image_id', true);
                    if(!empty($thumbnail_id)){
                        $width = $height = '90';
                        if(function_exists( 'kama_thumb_img')){
                            $thumb = kama_thumb_a_img( [
                                'width' => $width,
                                'height'=> $height,
                                'crop'  => true,
                                'a_attr'  => 'data-rel="lightcase"',
                            ], $thumbnail_id );
                        } else {
                            $thumb = wp_get_attachment_image( $thumbnail_id, [$width, $height], true );
                        }
                    }
                    return $thumb ?? '<span class="dashicons dashicons-format-image"></span>';
                break;
                default:
                    return $content;
            }
        }
    }

    add_action('init', function () {
        (new TermThumbnail())->add_actions();
    });
}