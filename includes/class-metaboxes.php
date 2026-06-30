<?php
if (!defined('ABSPATH')) {
    exit;
}

class AMM_Metaboxes {
    /** @var AMM_Settings */
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function hooks() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
    }

    public function add_meta_box() {
        foreach ($this->settings->get_public_post_types() as $post_type => $object) {
            add_meta_box(
                'amm_post_settings',
                __('AI Markdown / LLM Settings', 'ai-markdown-mirror'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('amm_save_post_meta', 'amm_post_meta_nonce');

        $exclude_all       = (bool) get_post_meta($post->ID, '_amm_exclude_all', true);
        $disable_md        = (bool) get_post_meta($post->ID, '_amm_disable_md', true);
        $exclude_llms      = (bool) get_post_meta($post->ID, '_amm_exclude_llms', true);
        $exclude_llms_full = (bool) get_post_meta($post->ID, '_amm_exclude_llms_full', true);
        $custom_summary    = (string) get_post_meta($post->ID, '_amm_custom_summary', true);
        ?>
        <p><label><input type="checkbox" name="amm_exclude_all" value="1" <?php checked($exclude_all); ?>> <?php esc_html_e('Exclude from all AI Markdown outputs', 'ai-markdown-mirror'); ?></label></p>
        <p><label><input type="checkbox" name="amm_disable_md" value="1" <?php checked($disable_md); ?>> <?php esc_html_e('Disable .md page for this item', 'ai-markdown-mirror'); ?></label></p>
        <p><label><input type="checkbox" name="amm_exclude_llms" value="1" <?php checked($exclude_llms); ?>> <?php esc_html_e('Exclude from /llms.txt', 'ai-markdown-mirror'); ?></label></p>
        <p><label><input type="checkbox" name="amm_exclude_llms_full" value="1" <?php checked($exclude_llms_full); ?>> <?php esc_html_e('Exclude from /llms-full.txt', 'ai-markdown-mirror'); ?></label></p>
        <p>
            <label for="amm_custom_summary"><strong><?php esc_html_e('Custom LLM summary', 'ai-markdown-mirror'); ?></strong></label>
            <textarea id="amm_custom_summary" name="amm_custom_summary" rows="5" style="width:100%;"><?php echo esc_textarea($custom_summary); ?></textarea>
            <span class="description"><?php esc_html_e('Optional short summary used in llms files.', 'ai-markdown-mirror'); ?></span>
        </p>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['amm_post_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['amm_post_meta_nonce'])), 'amm_save_post_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $this->save_checkbox($post_id, '_amm_exclude_all', 'amm_exclude_all');
        $this->save_checkbox($post_id, '_amm_disable_md', 'amm_disable_md');
        $this->save_checkbox($post_id, '_amm_exclude_llms', 'amm_exclude_llms');
        $this->save_checkbox($post_id, '_amm_exclude_llms_full', 'amm_exclude_llms_full');

        if (isset($_POST['amm_custom_summary'])) {
            update_post_meta($post_id, '_amm_custom_summary', sanitize_textarea_field(wp_unslash($_POST['amm_custom_summary'])));
        } else {
            delete_post_meta($post_id, '_amm_custom_summary');
        }
    }

    private function save_checkbox($post_id, $meta_key, $field_name) {
        if (!empty($_POST[$field_name])) {
            update_post_meta($post_id, $meta_key, '1');
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
}
