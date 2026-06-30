<?php
if (!defined('ABSPATH')) {
    exit;
}

class AMM_Markdown {
    /** @var AMM_Settings */
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function get_markdown_url($post) {
        $url = get_permalink($post);
        if (!$url) {
            return '';
        }
        if ((int) get_option('page_on_front') === (int) $post->ID) {
            return home_url('/index.md');
        }
        return untrailingslashit($url) . '.md';
    }

    public function find_post_for_markdown_path($path) {
        $path = trim((string) $path, '/');
        if ('index' === $path) {
            $front_id = (int) get_option('page_on_front');
            return $front_id ? get_post($front_id) : null;
        }

        $post_id = url_to_postid(home_url('/' . $path . '/'));
        if (!$post_id) {
            $post_id = url_to_postid(home_url('/' . $path));
        }
        if ($post_id) {
            return get_post($post_id);
        }

        $types = $this->settings->get_ordered_post_types();
        $post = get_page_by_path($path, OBJECT, $types);
        return $post ?: null;
    }

    public function render_single_post_markdown($post) {
        $post = get_post($post);
        if (!$post) {
            return '';
        }

        $lines = array();
        $lines[] = '# ' . $this->clean_text(get_the_title($post));
        $lines[] = '';
        $lines[] = 'Canonical HTML: ' . get_permalink($post);
        $lines[] = 'Markdown: ' . $this->get_markdown_url($post);
        $lines[] = '';
        $lines[] = 'Published: ' . get_the_date('c', $post);
        $author = get_the_author_meta('display_name', (int) $post->post_author);
        if ($author) {
            $lines[] = 'Author: ' . $this->clean_text($author);
        }

        $image = get_the_post_thumbnail_url($post, 'full');
        if ($image) {
            $alt = get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true);
            $lines[] = '';
            $lines[] = '![' . $this->clean_text($alt ?: get_the_title($post)) . '](' . esc_url_raw($image) . ')';
        }

        $content = apply_filters('the_content', $post->post_content);
        $markdown = $this->html_to_markdown($content);
        if ($markdown) {
            $lines[] = '';
            $lines[] = $markdown;
        }

        return trim(implode("\n", $lines)) . "\n";
    }

    public function render_llms_txt($full = false) {
        $options = $this->settings->get_options();
        $title = $options['custom_title'] ? $options['custom_title'] : get_bloginfo('name');
        $lines = array('# ' . $this->clean_text($title), '');

        if (!empty($options['custom_description'])) {
            $lines[] = trim($options['custom_description']);
            $lines[] = '';
        }
        if (!empty($options['custom_after_description'])) {
            $lines[] = trim($options['custom_after_description']);
            $lines[] = '';
        }

        foreach ($this->settings->get_ordered_post_types() as $type) {
            $object = get_post_type_object($type);
            if (!$object) {
                continue;
            }
            $posts = get_posts(array(
                'post_type'      => $type,
                'post_status'    => 'publish',
                'posts_per_page' => (int) $options['max_posts_per_type'],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ));
            if (!$posts) {
                continue;
            }

            $lines[] = '## ' . $this->clean_text($object->labels->name);
            $lines[] = '';

            foreach ($posts as $post) {
                if ($this->settings->is_disabled_for_post($post->ID, 'all')) {
                    continue;
                }
                if (!$full && $this->settings->is_disabled_for_post($post->ID, 'llms')) {
                    continue;
                }
                if ($full && $this->settings->is_disabled_for_post($post->ID, 'llms_full')) {
                    continue;
                }

                $md_url = $this->get_markdown_url($post);
                $lines[] = '- [' . $this->clean_text(get_the_title($post)) . '](' . $md_url . '): ' . $this->entry_summary($post, (int) $options['max_words']);

                if (!empty($options['include_meta'])) {
                    $lines[] = '  - HTML: ' . get_permalink($post);
                    $lines[] = '  - Published: ' . get_the_date('Y-m-d', $post);
                }

                if (!empty($options['include_taxonomies'])) {
                    $tax = $this->taxonomy_line($post);
                    if ($tax) {
                        $lines[] = '  - ' . $tax;
                    }
                }

                if (!empty($options['include_featured_image'])) {
                    $image = get_the_post_thumbnail_url($post, 'full');
                    if ($image) {
                        $lines[] = '  - Featured image: ' . esc_url_raw($image);
                    }
                }

                if ($full || !empty($options['include_content'])) {
                    $lines[] = '';
                    $lines[] = $this->render_single_post_markdown($post);
                }
            }
            $lines[] = '';
        }

        if (!empty($options['custom_footer'])) {
            $lines[] = '---';
            $lines[] = '';
            $lines[] = trim($options['custom_footer']);
        }

        return trim(implode("\n", $lines)) . "\n";
    }

    private function entry_summary($post, $max_words) {
        $custom = get_post_meta($post->ID, '_amm_custom_summary', true);
        if ($custom) {
            return $this->limit_words($this->clean_text($custom), $max_words);
        }
        $excerpt = has_excerpt($post) ? get_the_excerpt($post) : '';
        if (!$excerpt) {
            $excerpt = wp_strip_all_tags(strip_shortcodes($post->post_content));
        }
        return $this->limit_words($this->clean_text($excerpt), $max_words);
    }

    private function taxonomy_line($post) {
        $bits = array();
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        foreach ($taxonomies as $taxonomy => $object) {
            if (empty($object->public)) {
                continue;
            }
            $terms = get_the_terms($post, $taxonomy);
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }
            $names = wp_list_pluck($terms, 'name');
            $bits[] = $object->labels->name . ': ' . implode(', ', array_map(array($this, 'clean_text'), $names));
        }
        return implode('; ', $bits);
    }

    private function html_to_markdown($html) {
        $html = (string) $html;
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace_callback('#<h([1-6])[^>]*>(.*?)</h\1>#is', function ($m) {
            return "\n" . str_repeat('#', (int) $m[1]) . ' ' . $this->clean_text($m[2]) . "\n\n";
        }, $html);
        $html = preg_replace_callback('#<img[^>]+>#i', function ($m) {
            $src = $this->attr($m[0], 'src');
            $alt = $this->attr($m[0], 'alt');
            return $src ? "\n![" . $this->clean_text($alt) . "](" . esc_url_raw($src) . ")\n" : '';
        }, $html);
        $html = preg_replace_callback('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', function ($m) {
            return '[' . $this->clean_text($m[2]) . '](' . esc_url_raw(html_entity_decode($m[1])) . ')';
        }, $html);
        $html = preg_replace_callback('#<blockquote[^>]*>(.*?)</blockquote>#is', function ($m) {
            $text = trim($this->clean_text($m[1]));
            return "\n> " . str_replace("\n", "\n> ", $text) . "\n";
        }, $html);
        $html = preg_replace_callback('#<pre[^>]*>(.*?)</pre>#is', function ($m) {
            return "\n```\n" . trim(wp_strip_all_tags(html_entity_decode($m[1]))) . "\n```\n";
        }, $html);
        $html = preg_replace_callback('#<li[^>]*>(.*?)</li>#is', function ($m) {
            return "\n- " . $this->clean_text($m[1]);
        }, $html);
        $html = preg_replace('#</p>|<br\s*/?>#i', "\n\n", $html);
        $html = preg_replace('#<p[^>]*>#i', '', $html);
        $text = wp_strip_all_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function attr($tag, $name) {
        if (preg_match('/\s' . preg_quote($name, '/') . '=["\']([^"\']+)["\']/i', $tag, $m)) {
            return html_entity_decode($m[1]);
        }
        return '';
    }

    private function limit_words($text, $limit) {
        $words = preg_split('/\s+/', trim($text));
        if (count($words) <= $limit) {
            return trim($text);
        }
        return implode(' ', array_slice($words, 0, $limit)) . '…';
    }

    public function clean_text($text) {
        $text = wp_strip_all_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
