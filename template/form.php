<?php
/**
 * @var int|null $term_image_id
 * @var string|null $term_image_url
 * @var int|null $image_id
 * @var string|null $image_url
 */
?>
<tr class="form-field">
    <th scope="row">
        <label for="term_image_id">
            <span class="dashicons dashicons-format-image"></span>
            <?php _e('Taxonomy thumbnail', 'wp-term-thumbnail') ?>
        </label>
    </th>
    <td>
        <input type="hidden" id="term_image_id" name="term_image_id"
               value="<?php echo esc_attr($term_image_id); ?>">
        <div id="term__image__wrapper">
            <img src="<?= esc_url($term_image_url) ?>"
                 style="max-width: 90%; height: auto; display: <?php echo $image_url ? 'block' : 'none'; ?>;"
                 id="term_image_preview"
            >
        </div>
        <p>
            <button id="tax_media_button" class="button" type="button"
               class="button button-secondary tax_media_button">
                <?php _e('Upload image', 'wp-term-thumbnail'); ?>
            </button>
            <button id="tax_media_remove" class="button" type="button" class="button button-secondary tax_media_remove">
                <?php _e('Remove image', 'wp-term-thumbnail'); ?>
            </button>
        </p>
    </td>
</tr>
<tr class="form-field">
    <th scope="row" valign="top">
        <label for="taxonomy_image">
            <span class="dashicons dashicons-format-image"></span>
            <?php _e('Default Post Taxonomy Thumbnail', 'wp-term-thumbnail'); ?>
        </label>
    </th>
    <td>
        <input type="hidden" id="taxonomy_image" name="taxonomy_image"
               value="<?php echo esc_attr($image_id); ?>"/>
        <img src="<?php echo esc_url($image_url); ?>"
             style="max-width: 90%; height: auto; display: <?php echo $image_url ? 'block' : 'none'; ?>;"
             id="taxonomy_image_preview"/>
        <p>
        <button class="button" id="taxonomy_image_button" type="button">
            <?php _e('Upload image', 'wp-term-thumbnail'); ?>
        </button>
        <button class="button" id="taxonomy_image_delete_button" type="button">
            <?php _e('Remove image', 'wp-term-thumbnail'); ?>
        </button>
        </p>
    </td>
</tr>
