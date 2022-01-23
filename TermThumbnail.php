<?php
/**
 * Plugin Name:  # Добавляет возможность загружать изображения для таксономий
 * Plugin URL:   https://rwsite.ru
 * Description:  Добавляет возможность загружать изображения для произвольных таксономий.
 * Version:      1.1.0
 * Text Domain:  wp-addon
 * Domain Path:  /languages
 * Author:       Aleksey Tikhomirov
 * Author URI:   https://rwsite.ru
 *
 * Tags: wp-addon, addon
 * Requires at least: 4.6
 * Tested up to: 5.8.0
 * Requires PHP: 7.0+
 *
 * @package WordPress Addon
 *
 * Получить ID картинки термина: $image_id = get_term_meta( $term_id, 'term_image_id', 1 );
 * Затем получить URL картинки: $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
 *
 */
if (!class_exists('TermThumbnail')) {

    class TermThumbnail
    {
        /** @var array */
        public $for_taxes; // для каких таксономий должен работать код

        ## Initialize the class and start calling our hooks and filters
        public function __construct($for_taxes = [])
        {
            $this->for_taxes = $for_taxes ?: ['category', 'post_tag', 'product_tag'];

            foreach ($this->for_taxes as $taxname) {

                if(!taxonomy_exists($taxname)){
                    continue;
                }

                add_action("{$taxname}_add_form_fields", array(& $this, 'add_term_image'), 10, 2);
                add_action("{$taxname}_edit_form_fields", array(& $this, 'update_term_image'), 10, 2);
                add_action("created_{$taxname}", array(& $this, 'save_term_image'), 10, 2);
                add_action("edited_{$taxname}", array(& $this, 'updated_term_image'), 10, 2);

                add_filter("manage_edit-{$taxname}_columns", array($this, 'add_image_column'));
                add_filter("manage_{$taxname}_custom_column", array($this, 'fill_image_column'), 10, 3);
            }
        }

        ## Add a form field in the new category page
        public function add_term_image($taxonomy)
        {
            wp_enqueue_media(); // подключим стили медиа, если их нет

            add_action('admin_print_footer_scripts', array(& $this, 'add_script'), 99);
            ?>
            <div class="form-field term-group">
                <label for="term_image_id">
                    <?php _e('Image', 'wp-addon'); ?>
                </label>
                <input type="hidden" id="term_image_id" name="term_image_id" class="custom_media_url" value="">
                <div id="term__image__wrapper"></div>
                <p>
                    <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button"
                           name="ct_tax_media_button" value="<?php _e('Add Image', 'wp-addon'); ?>"/>
                    <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove"
                           name="ct_tax_media_remove" value="<?php _e('Remove Image', 'wp-addon'); ?>"/>
                </p>
            </div>
            <?php
        }

        ## Edit the form field
        public function update_term_image($term, $taxonomy)
        {
            wp_enqueue_media(); // подключим стили медиа, если их нет

            add_action('admin_print_footer_scripts', array(& $this, 'add_script'), 99);

            $image_id = get_term_meta($term->term_id, 'term_image_id', true);
            ?>
            <tr class="form-field term-group-wrap">
                <th scope="row">
                    <label for="term_image_id"><?php _e('Image', 'wp-addon'); ?></label>
                </th>
                <td>
                    <input type="hidden" id="term_image_id" name="term_image_id" value="<?php echo $image_id; ?>">
                    <div id="term__image__wrapper">
                        <?php if ($image_id) {
                            echo wp_get_attachment_image($image_id, 'thumbnail');
                        } ?>
                    </div>
                    <p>
                        <input type="button" class="button button-secondary ct_tax_media_button"
                               id="ct_tax_media_button" name="ct_tax_media_button"
                               value="<?php _e('Add Image', 'wp-addon'); ?>"/>
                        <input type="button" class="button button-secondary ct_tax_media_remove"
                               id="ct_tax_media_remove" name="ct_tax_media_remove"
                               value="<?php _e('Remove Image', 'wp-addon'); ?>"/>
                    </p>
                </td>
            </tr>
            <?php
        }

        ## Save the form field
        public function save_term_image($term_id, $tt_id)
        {
            if (isset($_POST['term_image_id']) && '' !== $_POST['term_image_id']) {
                $image = $_POST['term_image_id'];
                add_term_meta($term_id, 'term_image_id', $image, true);
            }
        }

        ## Update the form field value
        public function updated_term_image($term_id, $tt_id)
        {
            if (isset($_POST['term_image_id']) && '' !== $_POST['term_image_id']) {
                $image = $_POST['term_image_id'];
                update_term_meta($term_id, 'term_image_id', $image);
            } else
                update_term_meta($term_id, 'term_image_id', '');
        }

        ## Add script
        public function add_script()
        {
            // выходим если мы не на нужной странице таксономии
            //$cs = get_current_screen();
            //if( ! in_array($cs->base, array('edit-tags','term')) || ! in_array($cs->taxonomy, (array) $this->for_taxes) )
            //  return;

            ?>
            <script>
                jQuery(document).ready(function ($) {
                    function ct_media_upload(button_class) {
                        var _custom_media = true,
                            _orig_send_attachment = wp.media.editor.send.attachment;

                        $('body').on('click', button_class, function (e) {
                            var button_id = '#' + $(this).attr('id');
                            var send_attachment_bkp = wp.media.editor.send.attachment;
                            var button = $(button_id);

                            _custom_media = true;

                            wp.media.editor.send.attachment = function (props, attachment) {
                                if (_custom_media) {
                                    $('#term_image_id').val(attachment.id);
                                    $('#term__image__wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                                    $('#term__image__wrapper .custom_media_image').attr('src', attachment.sizes.thumbnail.url).css('display', 'block');
                                } else {
                                    return _orig_send_attachment.apply(button_id, [props, attachment]);
                                }
                            }
                            wp.media.editor.open(button);
                            return false;
                        });
                    }

                    ct_media_upload('.ct_tax_media_button.button');

                    $('body').on('click', '.ct_tax_media_remove', function () {
                        $('#term_image_id').val('');
                        $('#term__image__wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                    });

                    // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
                    $(document).ajaxComplete(function (event, xhr, settings) {
                        var queryStringArr = settings.data.split('&');

                        if ($.inArray('action=add-tag', queryStringArr) !== -1) {
                            var xml = xhr.responseXML;
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

        ## Добавляет колонкку картинки в таблицу терминов
        public function add_image_column($columns)
        {
            // подправим ширину колонки через css
            add_action('admin_notices', function () {
                echo '<style>.column-image{ width:80px; text-align:center; }</style>';
            });

            $num = 1; // после какой по счету колонки вставлять новые
            $new_columns = array('image' => __('Image', 'wp-addon'));
            return array_slice($columns, 0, $num) + $new_columns + array_slice($columns, $num);
        }

        public function fill_image_column($string, $column_name, $term_id)
        {
            // если есть картинка
            if ('image' === $column_name && $image_id = get_term_meta($term_id, 'term_image_id', true)) {
                $string = '<img src="' . wp_get_attachment_image_url($image_id, 'thumbnail') . '" width="80" height="80" alt="" style="border-radius:.3rem" />';
            }
            return $string;
        }
    }

    add_action('init', function () { return new TermThumbnail(); } ); // init
}