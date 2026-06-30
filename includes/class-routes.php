<?php
if (!defined('ABSPATH')) {
    exit;
}

class AMM_Routes {
    /** @var AMM_Settings */
    private $settings;

    /** @var AMM_Markdown */
    private $markdown;

    public function __construct($settings, $markdown) {
        $this->settings = $settings;
        $this->markdown = $markdown;
    }

    public function hooks() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'maybe_serve_special_routes'), 0);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^llms\.txt$', 'index.php?amm_llms=1', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?amm_llms_full=1', 'top');
        add_rewrite_rule('^(.+?)\.md$', 'index.php?amm_md_path=$matches[1]', 'top');
        add_rewrite_rule('^index\.md$', 'index.php?amm_md_path=index', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'amm_llms';
        $vars[] = 'amm_llms_full';
        $vars[] = 'amm_md_path';
        return $vars;
    }

    public function maybe_serve_special_routes() {
        $options = $this->settings->get_options();

        if (get_query_var('amm_llms')) {
            if (empty($options['enable_llms'])) {
                $this->not_found();
            }
            $this->send_text_headers(!empty($options['noindex_llms']));
            echo $this->markdown->render_llms_txt(false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        if (get_query_var('amm_llms_full')) {
            if (empty($options['enable_llms_full'])) {
                $this->not_found();
            }
            $this->send_text_headers(!empty($options['noindex_llms_full']));
            echo $this->markdown->render_llms_txt(true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        $path = get_query_var('amm_md_path');
        if ($path) {
            if (empty($options['enable_md_pages'])) {
                $this->not_found();
            }

            $post = $this->markdown->find_post_for_markdown_path($path);
            if (!$post || !$this->settings->is_post_type_included($post->post_type)) {
                $this->not_found();
            }

            if ($this->settings->is_disabled_for_post($post->ID, 'all') || $this->settings->is_disabled_for_post($post->ID, 'md')) {
                $this->not_found();
            }

            $canonical = get_permalink($post);
            $this->send_markdown_headers(!empty($options['noindex_md']), $canonical);
            echo $this->markdown->render_single_post_markdown($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }
    }

    private function send_text_headers($noindex = true) {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/plain; charset=' . get_bloginfo('charset'));
            if ($noindex) {
                header('X-Robots-Tag: noindex, nofollow', true);
            }
            nocache_headers();
        }
    }

    private function send_markdown_headers($noindex = true, $canonical = '') {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/markdown; charset=' . get_bloginfo('charset'));
            if ($canonical) {
                header('Link: <' . esc_url_raw($canonical) . '>; rel="canonical"; type="text/html"', false);
            }
            if ($noindex) {
                header('X-Robots-Tag: noindex, nofollow', true);
            }
            nocache_headers();
        }
    }

    private function not_found() {
        global $wp_query;
        if ($wp_query) {
            $wp_query->set_404();
        }
        status_header(404);
        nocache_headers();
        echo esc_html__('Not found.', 'ai-markdown-mirror');
        exit;
    }
}
