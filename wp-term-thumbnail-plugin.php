<?php
/**
 * Plugin Name:  Добавляет возможность загружать изображения для таксономий
 * Plugin URL:   https://rwsite.ru
 * Description:  Плагин добавляет возможность загружать изображения для произвольных таксономий.
 * Version:      1.0.1
 * Text Domain:  wp-term-thumbnail
 * Domain Path:  /languages
 * Author:       Aleksey Tikhomirov
 * Author URI:   https://rwsite.ru
 *
 * Tags: taxonomy, thumbnail, cover, post
 * Requires at least: 6.3
 * Tested up to: 6.5.0
 * Requires PHP: 8.3+
 *
 *
 * Получить ID картинки термина: $image_id = get_term_meta( $term_id, 'term_image_id', 1 );
 * Затем получить URL картинки: $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
 */

use classes\TermThumbnail;

defined('ABSPATH') or die('Nothing here!');


require_once 'includes/wp-term-thumbnail-settings.php';
require_once 'includes/functions.php';
require_once 'classes/TermThumbnail.php';

add_action('init', function () {
    (new TermThumbnail(__FILE__))->add_actions();
}, 99);

