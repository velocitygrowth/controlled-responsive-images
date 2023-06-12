<?php

// ====== Register sizing information for sections ======

/**
 * # cri/register_sections
 *
 * Use this action to register sections that may display responsive images.
 * 
 * - An upper bound size is required for each section. This is a size with `screen_min_width` but no `screen_max_width`.
 * - Multiple sizes can be registered for each section.
 *
 * @example
 * ```
 * add_action('cri/register_sections', 'my_register_sections');
 * function my_register_sections() {
 *    register_responsive_image_section( array(
 *      'id' => 'single-content',
 *      'sizes' => array(
 *        array(
 *          'screen_min_width' => '1200px',
 *          'section_max_width' => '800px',
 *        ),
 *      )
 *    ));
 * }
 */

/**
 * @param array $section_def {
 *    @type string $id - The unique identifier for this section. Required.
 *
 *    @type array<array {
 *      @type string $screen_min_width - A CSS dimension of the minimum screen width for the maximum width of the section.
 *      @type string $screen_max_width - A CSS dimension of the maximum screen width for the maximum width of the section.
 *      @type string $container_max_width - A CSS dimension of the maximum width of the section.
 *    }> $sizes - An array of screen sizes and containers widths.
 * }
 */
function register_responsive_image_section($section_def): void
{
    ControlledResponsiveImagesPlugin::getInstance()->register_section($section_def);
}

// ====== Template functions to begin and end sections ======
/**
 * Call this to begin a section that may display responsive images.
 *
 * @param string $section_id - The unique identifier for the section.
 * @param array $additional_args - Optional contextual information to pass to filters of `cri/image_sizes`
 *
 * @example
 * single.php
 * ```php
 * <?php begin_responsive_image_section('single-content'); ?>
 *   <div class="content"><?php the_content(); ?></div>
 * <?php end_responsive_image_section('single-content'); ?>
 * ```
 */
function begin_responsive_image_section(string $section_id, $additional_args = null): void
{
    ControlledResponsiveImagesPlugin::getInstance()->begin_section($section_id, $additional_args);
}

/**
 * Call this to end a section and revert to the previously declared section.
 *
 * The `$section_id` is passed to verify that the section being ended is the same as the section being ended.
 * When debugging is enabled, a warning is triggered if the section being ended is not the same as the section being ended.
 *
 * @param string $section_id - The unique identifier for the section.
 */
function end_responsive_image_section(string $section_id): void
{
    ControlledResponsiveImagesPlugin::getInstance()->end_section($section_id);
}

// ====== Debugging ======
/**
 * Call this to enable debugging warnings and output.
 */
function debug_controlled_responsive_images(): void
{
    ControlledResponsiveImagesPlugin::getInstance()->debug(true);
}

// ====== Advance ======

/**
 * # cri/image_sizes
 *
 * Use this filter to modify the image sizes for a section.
 * The additional arguments passed to `begin_responsive_image_section` are passed to this filter.
 * 
 * The arguments passed to the callback are:
 * 1. `string $updated_sizes` - The `sizes` attribute of the `<img>` tag generated by this plugin.
 * 2. `string|int[] $size` - Requested image size. Can be any registered image size name, or an array of width and height values in pixels (in that order).
 * 3. `string|null $image_src` - The URL to the image file or null.
 * 4. `array|null $image_meta` - The image meta data as returned by wp_get_attachment_metadata() or null.
 * 5. `int $attachment_id` - Image attachment ID of the original image or 0.
 * 6. `array $section` - The active section's definition.
 * 7. `mixed $additional_args` - The additional arguments passed to `begin_responsive_image_section`.
 * 
 * 
 * @see https://developer.wordpress.org/reference/hooks/wp_calculate_image_sizes/
 * 
 * @example
 * ```php
 * <?php
 * add_filter('cri/image_sizes', 'my_image_sizes', 10, 7);
 * function my_image_sizes($updated_sizes, $size, $image_src, $image_meta, $attachment_id, $section, $additional_args): string {
 *    return $updated_sizes;
 * }
 * ```
 */