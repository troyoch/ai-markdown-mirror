<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMM_Plugin {
    private static $instance = null;

    /** @var AMM_Settings */
    public $settings;

    /** @var AMM_Markdown */
    public $markdown;

    /** @var AMM_Routes */
    public $routes;

    /** @var AMM_Metaboxes */
    public $metaboxes;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings  = new AMM_Settings();
        $this->markdown  = new AMM_Markdown($this->settings);
        $this->routes    = new AMM_Routes($this->settings, $this->markdown);
        $this->metaboxes = new AMM_Metaboxes($this->settings);

        $this->settings->hooks();
        $this->routes->hooks();
        $this->metaboxes->hooks();

        add_action('wp_head', array($this, 'print_head_links'), 3);
        add_action('send_headers', array($this, 'send_http_link_header'));
    }

    public static function activate() {
        AMM_Settings::maybe_create_defaults();

        if (!class_exists('AMM_Settings')) {
            require_once AMM_PLUGIN_DIR . 'includes/class-settings.php';
        }
        if (!class_exists('AMM_Markdown')) {
            require_once AMM_PLUGIN_DIR . 'includes/class-markdown.php';
        }
        if (!class_exists('AMM_Routes')) {
            require_once AMM_PLUGIN_DIR . 'includes/class-routes.php';
        }

        $settings = new AMM_Settings();
        $markdown = new AMM_Markdown($settings);
        $routes   = new AMM_Routes($settings, $markdown);
        $routes->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function print_head_links() {
        if (!is_singular()) {
            return;
        }

        $options = $this->settings->get_options();
        if (empty($options['enable_md_pages']) || empty($options['add_head_links'])) {
            return;
        }

        $post = get_post();
        if (!$post || !$this->settings->is_post_type_included($post->post_type)) {
            return;
        }

        if ($this->settings->is_disabled_for_post($post->ID, 'md')) {
            return;
        }

        $md_url = $this->markdown->get_markdown_url($post);
        if (!$md_url) {
            return;
        }

        printf(
            '<link rel="alternate" type="text/markdown" title="%s" href="%s">' . "\n",
            esc_attr__('Markdown version', 'ai-markdown-mirror'),
            esc_url($md_url)
        );

        if (!empty($options['enable_llms'])) {
            printf(
                '<link rel="alternate" type="text/plain" title="%s" href="%s">' . "\n",
                esc_attr__('LLMs.txt', 'ai-markdown-mirror'),
                esc_url(home_url('/llms.txt'))
            );
        }

        if (!empty($options['enable_llms_full'])) {
            printf(
                '<link rel="alternate" type="text/plain" title="%s" href="%s">' . "\n",
                esc_attr__('LLMs Full Text', 'ai-markdown-mirror'),
                esc_url(home_url('/llms-full.txt'))
            );
        }
    }

    public function send_http_link_header() {
        if (!is_singular() || headers_sent()) {
            return;
        }

        $options = $this->settings->get_options();
        if (empty($options['enable_md_pages']) || empty($options['add_http_link_header'])) {
            return;
        }

        $post = get_post();
        if (!$post || !$this->settings->is_post_type_included($post->post_type)) {
            return;
        }

        if ($this->settings->is_disabled_for_post($post->ID, 'md')) {
            return;
        }

        $md_url = $this->markdown->get_markdown_url($post);
        if (!$md_url) {
            return;
        }

        header('Link: <' . esc_url_raw($md_url) . '>; rel="alternate"; type="text/markdown"', false);
    }
}
