<?php
namespace NeatWP\MediaTags;

/**
 * Main plugin class
 */
class NeatMediaTagsPlugin
{
    public function __construct()
    {
        add_action('init', [$this, 'registerNeatMediaTagsTaxonomy'], 0);
        add_filter('attachment_fields_to_edit', [$this, 'addNeatMediaTagsField'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'saveNeatMediaTags'], 10, 2);
        add_action('wp_ajax_neat_media_tags_autocomplete', [$this, 'neatMediaTagsAutocomplete']);
        add_action('wp_ajax_save_bulk_edit_neat_media_tags', [$this, 'saveNeatMediaTagsBulkEdit']);
        add_filter('manage_media_columns', [$this, 'addNeatMediaTagsColumn']);
        add_action('manage_media_custom_column', [$this, 'displayNeatMediaTagsColumn'], 10, 2);
        add_action('bulk_edit_custom_box', [$this, 'addNeatMediaTagsBulkEdit'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'registerNeatMediaTagsAssets'], 999);
        add_shortcode('neat_media_tag_gallery', [$this, 'neatMediaTagGalleryShortcode']);
        add_action('pre_get_posts', [$this, 'filterMediaLibraryByTag']);
    }

    public function registerNeatMediaTagsTaxonomy()
    {
        $labels = [
            'name'              => __('Media Tags', 'neat-media-tags'),
            'singular_name'     => __('Media Tag', 'neat-media-tags'),
            'search_items'      => __('Search Media Tags', 'neat-media-tags'),
            'all_items'         => __('All Media Tags', 'neat-media-tags'),
            'edit_item'         => __('Edit Media Tag', 'neat-media-tags'),
            'update_item'       => __('Update Media Tag', 'neat-media-tags'),
            'add_new_item'      => __('Add New Media Tag', 'neat-media-tags'),
            'new_item_name'     => __('New Media Tag Name', 'neat-media-tags'),
            'menu_name'         => __('Media Tags', 'neat-media-tags'),
        ];

        $args = [
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'public'            => true,
            'publicly_queryable' => false,
            'update_count_callback' => '_update_generic_term_count',
        ];

        register_taxonomy('neat_media_tag', ['attachment'], $args);
    }

    public function filterMediaLibraryByTag($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment') {
            return;
        }

        if (isset($_GET['neat_media_tag']) && !empty($_GET['neat_media_tag'])) {
            $tax_query = [
                [
                    'taxonomy' => 'neat_media_tag',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET['neat_media_tag']),
                ]
            ];
            $query->set('tax_query', $tax_query);
        }
    }

    public function addNeatMediaTagsField($form_fields, $post)
    {
        $screen = get_current_screen();
        if ($screen && $screen->base === 'post' && $screen->id === 'attachment') {
            return $form_fields;
        }

        static $field_added = false;
        if ($field_added || isset($form_fields['neat_media_tag'])) {
            error_log(
                'Neat Media Tags field skipped: already added or exists for post ID: ' . $post->ID
            );
            return $form_fields;
        }

        $tags = wp_get_object_terms($post->ID, 'neat_media_tag', ['fields' => 'names']);
        $tags = $tags ? implode(', ', $tags) : '';

        $form_fields['neat_media_tag'] = [
            'label' => __('Media Tags', 'neat-media-tags'),
            'input' => 'html',
            'html'  => sprintf(
                '<input type="text" name="neat_media_tag" id="neat_media_tag_%d" ' .
                'class="neat-media-tags-input regular-text" value="%s" />',
                $post->ID,
                esc_attr($tags)
            ),
            'helps' => __('Separate multiple tags with commas', 'neat-media-tags'),
        ];

        error_log('Neat Media Tags field added for post ID: ' . $post->ID);
        $field_added = true;
        return $form_fields;
    }

    public function saveNeatMediaTags($post, $attachment)
    {
        $tags_input = null;

        if (isset($attachment['neat_media_tag'])) {
            $tags_input = $attachment['neat_media_tag'];
        } elseif (!empty($_POST['attachments'][$post['ID']]['neat_media_tag'])) {
            $tags_input = $_POST['attachments'][$post['ID']]['neat_media_tag'];
        }

        if ($tags_input !== null && is_string($tags_input)) {
            $tags = explode(',', $tags_input);
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);

            $result = wp_set_object_terms($post['ID'], $tags, 'neat_media_tag', false);

            if (!is_wp_error($result)) {
                $terms = wp_get_object_terms($post->ID, 'neat_media_tag', ['fields' => 'ids']);
                foreach ($terms as $term_id) {
                    wp_update_term_count_now($term_id, 'neat_media_tag');
                }
            }
        }

        return $post;
    }

    public function neatMediaTagsAutocomplete()
    {
        try {
            if (!check_ajax_referer('neat_media_tags_nonce', 'nonce', false)) {
                throw new Exception(__('Security verification failed. Please refresh the page.', 'neat-media-tags'));
            }

            if (!current_user_can('upload_files')) {
                throw new Exception(__('You do not have permission to perform this action.', 'neat-media-tags'));
            }

            $term = isset($_REQUEST['term']) ? sanitize_text_field($_REQUEST['term']) : '';
            if (empty($term)) {
                wp_send_json_success([]);
            }

            $search_words = array_filter(array_map('trim', explode(' ', $term)));
            $all_tags = get_terms([
                'taxonomy'   => 'neat_media_tag',
                'fields'     => 'names',
                'hide_empty' => false,
                'number'     => 0
            ]);

            if (is_wp_error($all_tags)) {
                throw new Exception($all_tags->get_error_message());
            }

            $matched_tags = [];
            foreach ($all_tags as $tag) {
                $tag_words = array_filter(array_map('trim', explode(' ', $tag)));
                $all_words_match = true;

                foreach ($search_words as $word) {
                    $word_matched = false;
                    foreach ($tag_words as $tag_word) {
                        if (stripos($tag_word, $word) !== false) {
                            $word_matched = true;
                            break;
                        }
                    }
                    if (!$word_matched) {
                        $all_words_match = false;
                        break;
                    }
                }

                if ($all_words_match) {
                    $matched_tags[] = $tag;
                    if (count($matched_tags) >= 300) {
                        break;
                    }
                }
            }
            wp_send_json_success($matched_tags);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage(), 500);
        }
    }

    public function addNeatMediaTagsBulkEdit($column_name, $post_type)
    {
        if ($post_type === 'attachment' && $column_name === 'neat_media_tags') {
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label>
                        <span class="title"><?php _e('Media Tags', 'neat-media-tags'); ?></span>
                        <input type="text" name="neat_media_tags" class="neat-media-tags-input" value="">
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }

    public function saveNeatMediaTagsBulkEdit()
    {
        $post_ids = !empty($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        if (!empty($post_ids) && isset($_POST['neat_media_tags'])) {
            $tags = explode(',', sanitize_text_field($_POST['neat_media_tags']));
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);

            foreach ($post_ids as $post_id) {
                wp_set_object_terms($post_id, $tags, 'neat_media_tag', false);
                $terms = wp_get_object_terms($post_id, 'neat_media_tag', ['fields' => 'ids']);
                foreach ($terms as $term_id) {
                    wp_update_term_count_now($term_id, 'neat_media_tag');
                }
            }
        }
        wp_die();
    }

    public function addNeatMediaTagsColumn($columns)
    {
        $columns['neat_media_tags'] = __('Media Tags', 'neat-media-tags');
        return $columns;
    }

    public function displayNeatMediaTagsColumn($column_name, $post_id)
    {
        if ($column_name === 'neat_media_tags') {
            $tags = wp_get_object_terms($post_id, 'neat_media_tag', ['fields' => 'names']);
            if ($tags) {
                $tag_links = [];
                foreach ($tags as $tag) {
                    $tag_links[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg('neat_media_tag', sanitize_title($tag), admin_url('upload.php'))),
                        esc_html($tag)
                    );
                }
                echo implode(', ', $tag_links);
            } else {
                echo '—';
            }
        }
    }

    public function registerNeatMediaTagsAssets($hook)
    {
        global $post;

        $is_attachment_edit = ($hook === 'post.php' && isset($post) && $post->post_type === 'attachment');

        $allowed_pages = ['post.php', 'post-new.php', 'upload.php', 'media-upload.php'];
        if (!in_array($hook, $allowed_pages) && !$is_attachment_edit) {
            return;
        }

        if ($is_attachment_edit) {
            wp_enqueue_script('quicktags');
            add_action('admin_print_footer_scripts', function () {
                if (wp_script_is('quicktags')) {
                    ?>
                    <script>
                    if (typeof QTags !== "undefined") {
                        QTags.addButton("neat_media_tags", "Media Tags", "", "", "", "Media Tags", 201);
                    }
                    </script>
                    <?php
                }
            }, 20);
        }

        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_script(
            'neat-media-tags-autocomplete',
            plugin_dir_url(__FILE__) . 'js/neat-media-tags.js',
            ['jquery', 'jquery-ui-autocomplete'],
            filemtime(plugin_dir_path(__FILE__) . 'js/neat-media-tags.js'),
            true
        );

        wp_localize_script('neat-media-tags-autocomplete', 'neatMediaTagsConfig', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neat_media_tags_nonce')
        ]);

        wp_add_inline_style('wp-admin', '
            .ui-autocomplete-neat-media-tags {
                z-index: 999999 !important;
                position: fixed !important;
                max-height: 300px !important;
                overflow-y: auto !important;
                background: #ffffff !important;
                border: 1px solid #ccd0d4 !important;
                box-shadow: 0 3px 5px rgba(0,0,0,0.2) !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .ui-autocomplete-neat-media-tags .ui-menu-item {
                padding: 6px 12px !important;
                border-bottom: 1px solid #f0f0f0 !important;
                cursor: pointer !important;
                margin: 0 !important;
                color: #23282d !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                             Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
                font-size: 13px !important;
                line-height: 1.5 !important;
                list-style: none !important;
            }
            .ui-autocomplete-neat-media-tags .ui-menu-item:hover {
                background-color: #f5f5f5 !important;
                color: #23282d !important;
            }
        ');
    }

    public function neatMediaTagGalleryShortcode($atts)
    {
        wp_enqueue_style('wp-block-gallery');

        $atts = shortcode_atts([
            'tag' => '',
            'logic' => 'OR',
            'size' => 'medium',
            'columns' => 3,
            'link' => 'file',
            'orderby' => 'date',
            'order' => 'DESC',
            'limit' => 0
        ], $atts);

        if (empty($atts['tag'])) {
            return '<div class="wp-block-gallery has-nested-images columns-default is-cropped ' .
                   'wp-block-gallery-1 is-layout-flex wp-block-gallery-is-layout-flex"><p>' .
                   __('Please specify media tag(s)', 'neat-media-tags') . '</p></div>';
        }

        $tags = array_map('trim', explode(',', sanitize_text_field($atts['tag'])));
        $tax_query = [];

        if ($atts['logic'] === 'AND') {
            $tax_query['relation'] = 'AND';
            foreach ($tags as $tag) {
                $tax_query[] = [
                    'taxonomy' => 'neat_media_tag',
                    'field' => 'slug',
                    'terms' => $tag
                ];
            }
        } else {
            $tax_query[] = [
                'taxonomy' => 'neat_media_tag',
                'field' => 'slug',
                'terms' => $tags
            ];
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => $atts['limit'] > 0 ? (int)$atts['limit'] : -1,
            'tax_query' => $tax_query
        ];

        if (strtoupper($atts['orderby']) === 'RAND') {
            $args['orderby'] = 'rand';
        } else {
            $args['orderby'] = $atts['orderby'];
            $args['order'] = $atts['order'];
        }

        $attachments = get_posts($args);

        if (empty($attachments)) {
            return '<div class="wp-block-gallery has-nested-images columns-default is-cropped ' .
                   'wp-block-gallery-1 is-layout-flex wp-block-gallery-is-layout-flex"><p>' .
                   sprintf(
                       __('No media found with tag(s): %s', 'neat-media-tags'),
                       esc_html($atts['tag'])
                   ) . '</p></div>';
        }

        $output = '<figure class="wp-block-gallery has-nested-images columns-' .
                  absint($atts['columns']) . ' is-cropped wp-block-gallery-1 is-layout-flex ' .
                  'wp-block-gallery-is-layout-flex">';

        foreach ($attachments as $attachment) {
            $image = wp_get_attachment_image($attachment->ID, $atts['size'], false, [
                'class' => 'wp-image-' . $attachment->ID,
                'loading' => 'lazy',
                'decoding' => 'async'
            ]);

            $caption = $attachment->post_excerpt;
            $link = '';

            if ($atts['link'] === 'attachment') {
                $link = get_attachment_link($attachment->ID);
            } elseif ($atts['link'] === 'file') {
                $link = wp_get_attachment_url($attachment->ID);
            }

            $output .= '<figure class="wp-block-image">';

            if (!empty($link)) {
                $output .= '<a href="' . esc_url($link) . '">' . $image . '</a>';
            } else {
                $output .= $image;
            }

            if ($caption) {
                $output .= '<figcaption>' . esc_html($caption) . '</figcaption>';
            }

            $output .= '</figure>';
        }

        $output .= '</figure>';

        return $output;
    }
}