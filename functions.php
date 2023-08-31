<?php



// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

/**
 * Retrieve Term Thumbnail ID.
 *
 * @param int $term_id Optional. Term ID.
 *
 * @return int|bool Attachment ID or FALSE
 *
 * @since 0.0
 */
function get_term_thumbnail_id($term_id = null)
{
    $thumbnail_id = get_term_meta($term_id, 'term_image_id', true);

    return !empty($thumbnail_id) ? $thumbnail_id : false;
}

/**
 * Conditional tag.
 *
 * @param int $term_id Term ID
 *
 * @return bool Term has thumbnail
 *
 **/
function has_term_thumbnail($term_id = '')
{
    $thumbnail_id = get_term_thumbnail_id($term_id);
    return false !== $thumbnail_id;
}

/**
 * Display term thumbnail.
 *
 * @param array $attr
 *
 * @since 1.0.0
 */
function the_term_thumbnail($attr)
{
    if (is_category()) :
        $attr['term_id'] = get_query_var('cat'); elseif (is_tag()) :
        $attr['term_id'] = get_query_var('tag'); elseif (is_tax()) :
        $attr['term_id'] = get_queried_object()->term_id;
    endif;

    echo get_term_thumbnail($attr);
}

/**
 * Get term thumbnail.
 *
 * @return string img HTML output
 *
 * @since 1.0.0
 **/
function get_term_thumbnail($attr = null)
{
    $thumbnail = null;
    $attr = wp_parse_args($attr, [
        'width'            => '100',
        'height'           => '100',
        'crop'             => true,
        'term_id'          => get_queried_object()->term_id ?? null,
        'show_placeholder' => true,
        'attach_id'        => null,
        'class'            => 'rounded',
        'attr'             => ''
    ]);
    extract($attr);

    if ($show_placeholder) {
        $thumbnail = get_option('kama_thumbnail', ['no_photo_url' => RW_PLUGIN_URL . '/wp-content/uploads/2023/08/no_img.jpg'])['no_photo_url'];
    }

    $attach_id = !empty($attach_id) ? $attach_id : get_term_thumbnail_id($term_id);

    if (function_exists('kama_thumb_img')) {
        $thumbnail = kama_thumb_img([
            'width'     => $width,
            'height'    => $height,
            'crop'      => $crop,
            'class'     => $class,
            'stub_url'  => $show_placeholder ? $thumbnail : '',
            'attach_id' => $attach_id ?: null,
            'attr'      => $attr
        ], 'notset' );
    } else {
        $thumbnail = !empty($attach_id) ? wp_get_attachment_image($attach_id, [$width, $height], true) : null;
    }

    return $thumbnail;
}