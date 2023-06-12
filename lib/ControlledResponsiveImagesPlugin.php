<?php

/**
 * This manages all the logic for the Controlled Responsive Images plugin.
 * 
 * You should not need to call this class directly. Instead, use 
 * the functions and hooks defined in `api.php`.
 */
class ControlledResponsiveImagesPlugin
{
    // ====== Start Singleton Pattern ======
    private static $instances = [];
    /**
     * Singletons should not be cloneable.
     */
    protected function __clone()
    {}

    /**
     * Singletons should not be restorable from strings.
     */
    public function __wakeup()
    {
        throw new \Exception ("Cannot unserialize a singleton.");
    }

    public static function getInstance(): ControlledResponsiveImagesPlugin
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }
    // ====== End Singleton Pattern ======

    /**
     * @type array<string, ResponsiveImageSectionConfig>
     */
    protected $sections = [];
    /**
     * @type bool
     */
    protected bool $debugging = false;

    /**
     * @type array<string>
     */
    protected $section_stack = [];
    /**
     * @type array<mixed>
     */
    protected $sections_additional_args_stacks = [];

    protected function __construct()
    {
        add_action('init', [$this, 'action_init'], 20, 0);
    }

    /**
     * Callback for `init` action.
     */
    public function action_init(): void
    {
        do_action('cri/register_sections');

        if (count($this->sections) > 0) {
            add_filter('wp_calculate_image_sizes', [$this, 'filter_wp_calculate_image_sizes'], 10, 5);
        }
    }

    public function register_section(array $section_def): void
    {
        $validation_result = $this->validate_section_def($section_def);
        if (is_string($validation_result)) {
            $this->debug_log($validation_result);
            return;
        }

        if ($this->debugging && array_key_exists($section_def['id'], $this->sections)) {
            $this->debug_log("Section '{$section_def['id']}' was registered more than once.");
        }
        $this->sections[$section_def['id']] = $section_def;
    }

    public function begin_section(string $section_id, $additional_args): void
    {
        if (!$this->is_section_registered($section_id)) {
            if ($this->debugging) {
                trigger_error("Section '$section_id' does not exist.", E_USER_WARNING);
            }
            return;
        }

        array_push($this->section_stack, $section_id);
        array_push($this->sections_additional_args_stacks, $additional_args);
    }

    public function end_section(string $section_id): void
    {
        if ($this->is_stack_empty()) {
            $this->debug_log("Section '$section_id' was ended without being started.");
            return;
        }

        if (!$this->is_section_registered($section_id)) {
            $this->debug_log("Section '$section_id' does not exist.");
            return;
        }

        if (!$this->is_top_of_stack($section_id)) {
            $this->debug_log("Section '$section_id' was ended, but %%section_stack_top%% was the last section started.");

            if (!in_array($section_id, $this->section_stack)) {
                $this->debug_log("Section '$section_id' was ended without being started.");
                return;
            }

            array_pop($this->section_stack);
            array_pop($this->sections_additional_args_stacks);
            while (!$this->is_stack_empty() && !$this->is_top_of_stack($section_id)) {
                $this->debug_log("Ending %%section_stack_top%% section to find $section_id");
                array_pop($this->section_stack);
                array_pop($this->sections_additional_args_stacks);
            }
            return;
        }

        array_pop($this->section_stack);
    }

    /**
     * Enable/disable debugging messages.
     */
    public function debug(bool $debug): void
    {
        $this->debugging = $debug;
    }

    /**
     * Callback for `wp_calculate_image_sizes` filter.
     */
    public function filter_wp_calculate_image_sizes($sizes, $size, $image_src, $image_meta, $attachment_id)
    {
        if ($this->is_stack_empty()) {
            return $sizes;
        }

        $section_id = end($this->section_stack);
        $section = $this->sections[$section_id];
        $additional_args = end($this->sections_additional_args_stacks);

        $image_width = $this->parse_image_width($sizes);
        $updated_sizes = $this->generate_sizes($image_width, $section);
        $filtered_sizes = apply_filters('cri/image_sizes', $updated_sizes, $size, $image_src, $image_meta, $attachment_id, $section, $additional_args);

        return $filtered_sizes;
    }

    protected function debug_log(string $message): void
    {
        if ($this->debugging) {
            $formatted_message = str_replace('%%section_stack_top%%', end($this->section_stack), $message);
            error_log($formatted_message);
        }
    }

    protected function is_stack_empty(): bool
    {
        return count($this->section_stack) === 0;
    }

    protected function is_section_registered(string $section_id): bool
    {
        return key_exists($section_id, $this->sections);
    }

    protected function is_top_of_stack(string $section_id): bool
    {
        $last_section = end($this->section_stack);
        return $last_section == $section_id;
    }

    protected function validate_section_def(array $section_def)
    {
        if (!is_array($section_def)) {
            return 'Section definition must be an array.';
        }

        if (!array_key_exists('id', $section_def) || !is_string($section_def['id']) || strlen($section_def['id']) === 0) {
            return 'Section definition must have an id.';
        }

        if (!array_key_exists('sizes', $section_def) || !is_array($section_def['sizes']) || count($section_def['sizes']) === 0) {
            return 'Section definition must have at least one size.';
        }

        if (count(array_filter($section_def['sizes'], function ($size) {
            return array_key_exists('screen_min_width', $size) && !array_key_exists('screen_max_width', $size);
        })) != 1) {
            return 'Section definition must have one size that has screen_min_width but not screen_max_width.';
        }

        return true;
    }

    protected function parse_image_width(string $size): int {
        if (!preg_match( '/,\s*(\d+)px\s*$/', $size, $matches)) {
            throw new \Exception("Could not parse image size '$size'.");
        }

        return intval($matches[1], 10);
    }

    protected function generate_sizes(int $image_width, array $section): string {
        $section_sizes = $section['sizes'];
        usort($section_sizes, function ($a, $b): int {
            return intval($a['screen_min_width']) - intval($b['screen_min_width']);
        });

        $sizes = array_map(function ($size): string {
            $screen_min_width = array_key_exists('screen_min_width', $size) ? $size['screen_min_width'] : null;
            $screen_max_width = array_key_exists('screen_max_width', $size) ? $size['screen_max_width'] : null;
            $section_max_width = $size['section_max_width'];

            if (!empty($screen_min_width) && !empty($screen_max_width)) {
                return sprintf('(min-width: %1s) and (max-width: %2s) %3s', $screen_min_width, $screen_max_width, $section_max_width);
            }

            if (!empty($screen_min_width)) {
                return sprintf('(min-width: %1s) %2s', $screen_min_width, $section_max_width);
            }

            return sprintf('(max-width: %1s) %2s', $screen_max_width, $section_max_width);
        }, $section_sizes);

        $sizes[] = sprintf('%1$dpx', $image_width);

        return implode(', ', $sizes);
    }
}
