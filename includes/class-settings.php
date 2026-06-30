<?php
if (!defined('ABSPATH')) {
    exit;
}

class AMM_Settings {
    const OPTION_NAME = 'amm_options';

    public function hooks() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_amm_flush_routes', array($this, 'handle_flush_routes'));
        add_filter('plugin_action_links_' . plugin_basename(AMM_PLUGIN_FILE), array($this, 'plugin_action_links'));
    }

    public static function maybe_create_defaults() {
        if (false === get_option(self::OPTION_NAME, false)) {
            add_option(self::OPTION_NAME, self::get_default_options());
        }
    }

    public static function get_default_options() {
        return array(
            'enable_md_pages'       => 1,
            'add_head_links'        => 1,
            'add_http_link_header'  => 1,
            'enable_llms'           => 1,
            'enable_llms_full'      => 1,
            'noindex_md'            => 1,
            'noindex_llms'          => 1,
            'noindex_llms_full'     => 1,
            'post_types'            => array('page', 'post'),
            'post_type_order'       => "page\npost",
            'max_posts_per_type'    => 100,
            'max_words'             => 250,
            'include_meta'          => 1,
            'include_excerpts'      => 1,
            'include_content'       => 0,
            'include_taxonomies'    => 1,
            'include_featured_image'=> 1,
            'custom_title'          => get_bloginfo('name'),
            'custom_description'    => get_bloginfo('description'),
            'custom_after_description' => '',
            'custom_footer'         => '',
        );
    }

    public function get_options() {
        $saved = get_option(self::OPTION_NAME, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        return wp_parse_args($saved, self::get_default_options());
    }

    public function register_settings() {
        register_setting('amm_settings_group', self::OPTION_NAME, array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input = is_array($input) ? $input : array();
        $defaults = self::get_default_options();
        $out = array();

        foreach (array('enable_md_pages','add_head_links','add_http_link_header','enable_llms','enable_llms_full','noindex_md','noindex_llms','noindex_llms_full','include_meta','include_excerpts','include_content','include_taxonomies','include_featured_image') as $key) {
            $out[$key] = empty($input[$key]) ? 0 : 1;
        }

        $public_types = array_keys($this->get_public_post_types());
        $post_types = isset($input['post_types']) && is_array($input['post_types']) ? $input['post_types'] : array();
        $post_types = array_values(array_intersect(array_map('sanitize_key', $post_types), $public_types));
        $out['post_types'] = $post_types ? $post_types : $defaults['post_types'];

        $out['post_type_order'] = isset($input['post_type_order']) ? sanitize_textarea_field(wp_unslash($input['post_type_order'])) : $defaults['post_type_order'];
        $out['max_posts_per_type'] = isset($input['max_posts_per_type']) ? max(1, min(500, absint($input['max_posts_per_type']))) : 100;
        $out['max_words'] = isset($input['max_words']) ? max(25, min(5000, absint($input['max_words']))) : 250;

        foreach (array('custom_title','custom_description','custom_after_description','custom_footer') as $key) {
            $out[$key] = isset($input[$key]) ? sanitize_textarea_field(wp_unslash($input[$key])) : '';
        }

        flush_rewrite_rules(false);
        return $out;
    }

    public function get_public_post_types() {
        $types = get_post_types(array('public' => true), 'objects');
        unset($types['attachment']);
        return $types;
    }

    public function get_ordered_post_types() {
        $options = $this->get_options();
        $selected = (array) $options['post_types'];
        $order = preg_split('/\r\n|\r|\n/', (string) $options['post_type_order']);
        $ordered = array();

        foreach ($order as $type) {
            $type = sanitize_key(trim($type));
            if ($type && in_array($type, $selected, true)) {
                $ordered[] = $type;
            }
        }

        foreach ($selected as $type) {
            if (!in_array($type, $ordered, true)) {
                $ordered[] = $type;
            }
        }

        return array_values(array_unique($ordered));
    }

    public function is_post_type_included($post_type) {
        $options = $this->get_options();
        return in_array($post_type, (array) $options['post_types'], true);
    }

    public function is_disabled_for_post($post_id, $scope) {
        if ('all' === $scope) {
            return (bool) get_post_meta($post_id, '_amm_exclude_all', true);
        }
        if ('md' === $scope) {
            return (bool) get_post_meta($post_id, '_amm_disable_md', true);
        }
        if ('llms' === $scope) {
            return (bool) get_post_meta($post_id, '_amm_exclude_llms', true);
        }
        if ('llms_full' === $scope) {
            return (bool) get_post_meta($post_id, '_amm_exclude_llms_full', true);
        }
        return false;
    }

    public function add_settings_page() {
        add_options_page(
            __('AI Markdown Mirror', 'ai-markdown-mirror'),
            __('AI Markdown Mirror', 'ai-markdown-mirror'),
            'manage_options',
            'ai-markdown-mirror',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $options = $this->get_options();
        $types = $this->get_public_post_types();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI Markdown Mirror', 'ai-markdown-mirror'); ?></h1>
            <p><?php esc_html_e('Create AI-readable .md pages, /llms.txt, /llms-full.txt, and hidden discovery links.', 'ai-markdown-mirror'); ?></p>
            <?php if (isset($_GET['amm_routes']) && 'refreshed' === $_GET['amm_routes']) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Markdown and LLM routes refreshed.', 'ai-markdown-mirror'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('amm_settings_group'); ?>
                <h2><?php esc_html_e('Core Outputs', 'ai-markdown-mirror'); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->render_checkbox('enable_md_pages', __('Enable .md pages', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('add_head_links', __('Add hidden Markdown links in the HTML head', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('add_http_link_header', __('Add HTTP Link header for Markdown pages', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('enable_llms', __('Enable /llms.txt', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('enable_llms_full', __('Enable /llms-full.txt', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('noindex_md', __('Send noindex header for .md pages', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('noindex_llms', __('Send noindex header for /llms.txt', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('noindex_llms_full', __('Send noindex header for /llms-full.txt', 'ai-markdown-mirror'), $options); ?>
                </table>

                <h2><?php esc_html_e('Content Settings', 'ai-markdown-mirror'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><?php esc_html_e('Post Types', 'ai-markdown-mirror'); ?></th><td>
                    <?php foreach ($types as $type => $object) : ?>
                        <label style="display:block;margin-bottom:6px;"><input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[post_types][]" value="<?php echo esc_attr($type); ?>" <?php checked(in_array($type, (array) $options['post_types'], true)); ?>> <?php echo esc_html($object->labels->name); ?> <code><?php echo esc_html($type); ?></code></label>
                    <?php endforeach; ?>
                    </td></tr>
                    <tr><th scope="row"><label for="amm_post_type_order"><?php esc_html_e('Post Type Order', 'ai-markdown-mirror'); ?></label></th><td><textarea id="amm_post_type_order" name="<?php echo esc_attr(self::OPTION_NAME); ?>[post_type_order]" rows="5" class="large-text code"><?php echo esc_textarea($options['post_type_order']); ?></textarea></td></tr>
                    <tr><th scope="row"><label for="amm_max_posts"><?php esc_html_e('Maximum posts per type', 'ai-markdown-mirror'); ?></label></th><td><input type="number" min="1" max="500" id="amm_max_posts" name="<?php echo esc_attr(self::OPTION_NAME); ?>[max_posts_per_type]" value="<?php echo esc_attr($options['max_posts_per_type']); ?>"></td></tr>
                    <tr><th scope="row"><label for="amm_max_words"><?php esc_html_e('Maximum words per short entry', 'ai-markdown-mirror'); ?></label></th><td><input type="number" min="25" max="5000" id="amm_max_words" name="<?php echo esc_attr(self::OPTION_NAME); ?>[max_words]" value="<?php echo esc_attr($options['max_words']); ?>"></td></tr>
                    <?php $this->render_checkbox('include_meta', __('Include meta information', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('include_excerpts', __('Include excerpts / meta descriptions', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('include_content', __('Include detailed content in /llms.txt', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('include_taxonomies', __('Include taxonomies', 'ai-markdown-mirror'), $options); ?>
                    <?php $this->render_checkbox('include_featured_image', __('Include featured images', 'ai-markdown-mirror'), $options); ?>
                </table>

                <h2><?php esc_html_e('Custom LLMs.txt Content', 'ai-markdown-mirror'); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->render_textarea('custom_title', __('LLMS.txt Title', 'ai-markdown-mirror'), $options, 2); ?>
                    <?php $this->render_textarea('custom_description', __('LLMS.txt Description', 'ai-markdown-mirror'), $options, 6); ?>
                    <?php $this->render_textarea('custom_after_description', __('LLMS.txt After Description', 'ai-markdown-mirror'), $options, 6); ?>
                    <?php $this->render_textarea('custom_footer', __('LLMS.txt End File Description', 'ai-markdown-mirror'), $options, 8); ?>
                </table>
                <?php submit_button(__('Save Settings', 'ai-markdown-mirror')); ?>
            </form>

            <hr>
            <h2><?php esc_html_e('Cache and Route Tools', 'ai-markdown-mirror'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="amm_flush_routes">
                <?php wp_nonce_field('amm_flush_routes'); ?>
                <?php submit_button(__('Refresh Markdown and LLM Routes', 'ai-markdown-mirror'), 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    private function render_checkbox($key, $label, $options) {
        ?>
        <tr><th scope="row"><?php echo esc_html($label); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($options[$key])); ?>> <?php esc_html_e('Enabled', 'ai-markdown-mirror'); ?></label></td></tr>
        <?php
    }

    private function render_textarea($key, $label, $options, $rows) {
        ?>
        <tr><th scope="row"><label for="amm_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><textarea id="amm_<?php echo esc_attr($key); ?>" rows="<?php echo esc_attr($rows); ?>" class="large-text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($options[$key]); ?></textarea></td></tr>
        <?php
    }

    public function handle_flush_routes() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do this.', 'ai-markdown-mirror'));
        }
        check_admin_referer('amm_flush_routes');
        flush_rewrite_rules();
        wp_safe_redirect(add_query_arg(array('page' => 'ai-markdown-mirror', 'amm_routes' => 'refreshed'), admin_url('options-general.php')));
        exit;
    }

    public function plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=ai-markdown-mirror')) . '">' . esc_html__('Settings', 'ai-markdown-mirror') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
