<?php
/**
 * Plugin Name:  Taxonomy Thumbnail
 * Plugin URL:   https://rwsite.ru
 * Description:  Taxonomy Thumbnail + Default Post Taxonomy Thumbnail. The plugin adds the ability to upload images for custom taxonomies.
 * Version:      1.0.3
 * Text Domain:  wp-term-thumbnail
 * Domain Path:  /languages
 * Author:       Aleksei Tikhomirov
 * Author URI:   https://rwsite.ru
 *
 * Tags: taxonomy, thumbnail, cover, post
 * Requires at least: 6.3
 * Tested up to: 6.7.2
 * Requires PHP: 8.0+
 *
 * How to use:
 * Получить ID картинки термина: $image_id = get_term_meta( $term_id, 'term_image_id', 1 );
 * Затем получить URL картинки: $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
 */

use classes\TermThumbnail;

defined('ABSPATH') or die('Nothing here!');


require_once 'includes/wp-term-thumbnail-settings.php';
require_once 'includes/functions.php';
require_once 'classes/TermThumbnail.php';

add_action('init', function () {
	load_plugin_textdomain( 'wp-term-thumbnail', false, dirname(plugin_basename(__FILE__)) . '/languages' );

	(new TermThumbnail(__FILE__))->add_actions();
}, 99);

