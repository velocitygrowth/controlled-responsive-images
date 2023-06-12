# Controlled Responsive Images

WordPress' default [`sizes` attribute](https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/sizes) is good for a generic solution.
But because WordPress can't examine the rendered layout to know the true max-width of an image, it has to assume the image is 100% width of the screen.
When the image is skinner than the screen, the browser may download a larger than needed image.

This plugin gives you control of the generated `sizes` in your theme.
This does require change code in your theme to use it.

## How-to Use

You will defined sections of your layout with the section's `max-width` and the breakpoints.
In your template, you'll add template tags for which section's definition to use.

### Define a section

Use the `cri/register_sections` hook for when to register your sections.
Call the `register_responsive_image_section` function to register sections.

The `sizes` defined the max-width of the section's container at a specific screen size.
You can have multiple sizes, but you are required to have one rule with a `screen_min_width` and `section_max_width`.

```php
<?php
add_action('cri/register_sections', 'go_cri_register_sections');
function go_cri_register_sections() {

  // Register the content section when it displays on a page with a sidebar.
  // The layout is (66%/33%) split and the both maxes out at 1200px.
  // This means that the content is never larger than `800px`.
  register_responsive_image_section( array(
    // The ID will be used within the templates
    'id' => 'content-with-sidebar',
    // You can defined multiple sizes, but are required to have the upper bound.
    'sizes' => array(
      // Required
      array(
        'screen_min_width' => '1200px',
        'section_max_width' => '800px',
      ),
      // Optional additional sizes.
      array(
        'screen_max_width' => '900px',
        'screen_min_width' => '700px',
        'section_max_width' => '600px',
      ),
    )
  ) );
}
```

### Update theme templates
Wrap the sections in your templates with `begin_responsive_image_section()` and `end_responsive_image_section()`.

```php
<?php begin_responsive_image_section('content-with-sidebar'); ?>  
  <div class="content-area entry-content">
    <?php the_content(); ?>
    <nav><?php wp_link_pages(); ?></nav>
  </div>
<?php end_responsive_image_section('content-with-sidebar'); ?>
<div class="sidebar">
  <?php get_sidebar(); ?>
</div>
```

__The reason the section ID is required for ending the section is catch missing section ends.__

## API

View [api.php](./api.php) for the documented API code.

## Velocity Growth

[Velocity Growth](https://velocitygrowth.com/) helps companies grow via industry-leading marketing strategy and award-winning hands-on execution. 