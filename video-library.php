<?php
/**
 * Plugin Name: Video Library
 * Description: Adds a Video Library CPT with AJAX listing and REST API support.
 * Version: 1.0.0
 * Author: Nexuslink
 * License: GPLv2 or later
 * Text Domain: video-library
 * Domain Path: /languages
*/

defined('ABSPATH') || exit;

define( 'VLP_PLUGIN_URL', plugin_dir_url(__FILE__) );

register_activation_hook(__FILE__, 'vlp_plugin_activation');

function vlp_plugin_activation() {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/misc.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    if (!function_exists('plugins_api')) {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    }

    // === Check ACF plugin ===
    $acf_plugin_slug = 'advanced-custom-fields/acf.php';
    $acf_installed = file_exists(WP_PLUGIN_DIR . '/' . $acf_plugin_slug);

    if (!$acf_installed) {
        // Install ACF plugin from WP repo
        $api = plugins_api('plugin_information', ['slug' => 'advanced-custom-fields']);

        if (is_wp_error($api)) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Failed to retrieve ACF plugin info. Please install it manually from WordPress.org.', 'video-library')
            );
        }

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $install_result = $upgrader->install($api->download_link);

        if (is_wp_error($install_result)) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Failed to install ACF plugin:', 'video-library') . ' ' . $install_result->get_error_message()
            );
        }
    }

    // === Activate ACF if not already ===
    if (!is_plugin_active($acf_plugin_slug)) {
        $result = activate_plugin($acf_plugin_slug);
        if (is_wp_error($result)) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('ACF plugin could not be activated. Please activate it manually.', 'video-library')
            );
        }
    }
}

add_action('admin_notices', 'vlp_plugin_admin_dependency_notice');
function vlp_plugin_admin_dependency_notice() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    if (!is_plugin_active('advanced-custom-fields/acf.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        echo '<div class="notice notice-error"><p>';
        _e('<strong>Video Library</strong> requires <strong>Advanced Custom Fields (ACF)</strong>. Please install and activate it.', 'video-library');
        echo '</p></div>';
    }
}

require_once plugin_dir_path(__FILE__) . 'includes/front/video-listing.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/video-library-api.php';

function enqueue_video_library_ajax_script() {
    wp_enqueue_style('vlp-video-css', plugin_dir_url(__FILE__) . 'assets/css/video-library.css', array(), '29052025', 'all');
    wp_enqueue_script('vlp-video-script', plugin_dir_url(__FILE__) . 'assets/js/video-library.js', array('jquery'), null, true);

    wp_localize_script('vlp-video-script', 'video_library_ajax_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('load_viedos_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_video_library_ajax_script');

function your_plugin_load_textdomain() {
    load_plugin_textdomain(
        'video-library',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
//add_action( 'plugins_loaded', 'your_plugin_load_textdomain' );

/**
 * Register the Video Library custom post type.
 */
function vlp_register_video_library_cpt() {
    $labels = [
        'name'               => _x('Video Library', 'post type general name', 'video-library'),
        'singular_name'      => _x('Video Library', 'post type singular name', 'video-library'),
        'menu_name'          => _x('Video Library', 'admin menu', 'video-library'),
        'name_admin_bar'     => _x('Video', 'add new on admin bar', 'video-library'),
        'add_new'            => __('Add New', 'video-library'),
        'add_new_item'       => __('Add New Video', 'video-library'),
        'new_item'           => __('New Video', 'video-library'),
        'edit_item'          => __('Edit Video', 'video-library'),
        'view_item'          => __('View Video', 'video-library'),
        'all_items'          => __('All Videos', 'video-library'),
        'search_items'       => __('Search Videos', 'video-library'),
        'parent_item_colon'  => __('Parent Videos:', 'video-library'),
        'not_found'          => __('No videos found.', 'video-library'),
        'not_found_in_trash' => __('No videos found in Trash.', 'video-library'),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_in_menu'       => true,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-video-alt3',
        'supports'           => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'videos'],
        'show_in_rest'       => true, // enables Gutenberg and REST API
        'capability_type'    => 'post',
    ];

    register_post_type('video_library', $args);
}
add_action('init', 'vlp_register_video_library_cpt');

/**
 * Register taxonomy for Video Library.
 */
function vlp_register_video_category_taxonomy() {
    $labels = [
        'name'              => _x('Video Categories', 'taxonomy general name', 'video-library'),
        'singular_name'     => _x('Video Category', 'taxonomy singular name', 'video-library'),
        'search_items'      => __('Search Categories', 'video-library'),
        'all_items'         => __('All Categories', 'video-library'),
        'parent_item'       => __('Parent Category', 'video-library'),
        'parent_item_colon' => __('Parent Category:', 'video-library'),
        'edit_item'         => __('Edit Category', 'video-library'),
        'update_item'       => __('Update Category', 'video-library'),
        'add_new_item'      => __('Add New Category', 'video-library'),
        'new_item_name'     => __('New Category Name', 'video-library'),
        'menu_name'         => __('Categories', 'video-library'),
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true, // allows Gutenberg and REST API access
        'query_var'         => true,
        'rewrite'           => ['slug' => 'video-category'],
    ];

    register_taxonomy('video_category', ['video_library'], $args);
}
add_action('init', 'vlp_register_video_category_taxonomy');

/**
 * Register tags for Video Library.
 */
function vlp_register_video_tags_taxonomy() {
    $labels = [
        'name'              => _x('Video Tags', 'taxonomy general name', 'video-library'),
        'singular_name'     => _x('Video Tag', 'taxonomy singular name', 'video-library'),
        'search_items'      => __('Search Tags', 'video-library'),
        'popular_items'     => __('Popular Tags', 'video-library'),
        'all_items'         => __('All Tags', 'video-library'),
        'edit_item'         => __('Edit Tag', 'video-library'),
        'update_item'       => __('Update Tag', 'video-library'),
        'add_new_item'      => __('Add New Tag', 'video-library'),
        'new_item_name'     => __('New Tag Name', 'video-library'),
        'separate_items_with_commas' => __('Separate tags with commas', 'video-library'),
        'add_or_remove_items'        => __('Add or remove tags', 'video-library'),
        'choose_from_most_used'      => __('Choose from the most used tags', 'video-library'),
        'menu_name'         => __('Tags', 'video-library'),
    ];

    $args = [
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'rewrite'           => ['slug' => 'video-tag'],
    ];

    register_taxonomy('video_tag', 'video_library', $args);
}
add_action('init', 'vlp_register_video_tags_taxonomy');

function ai1st_validate_video_api_key() {
    if (!defined('BF_VIDEO_SECRET_KEY')) {
        return false;
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];

    $normalized_headers = [];
    foreach ($headers as $key => $value) {
        $normalized_headers[strtolower($key)] = $value;
    }

    $provided_key = $normalized_headers['bf_video_secret_key'] ?? null;

    return $provided_key === BF_VIDEO_SECRET_KEY;
}

/**
 * Bunny.net video upload in video library
 *
 */
add_action('acf/save_post', 'vlp_upload_acf_video_to_bunny', 20);
function vlp_upload_acf_video_to_bunny($post_id) {
    if (get_post_type($post_id) !== 'video_library') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (acf_is_block_editor()) return;

    $current_file = get_field('video_upload', $post_id);
    if (!$current_file || !isset($current_file['ID'])) return;

    $current_video_id = $current_file['ID'];
    $old_uploaded_id = get_post_meta($post_id, '_uploaded_video_file_id', true);
    // If same file, skip upload
    if ($current_video_id == $old_uploaded_id) return;

    $video_path = get_attached_file($current_video_id);
    if (!file_exists($video_path)) {
        error_log("Video file does not exist: " . $video_path);
        return;
    }

    $video_name = sanitize_file_name($current_file['filename']);

    // Bunny API credentials for course
    $library_id = VIDEO_LIBRARY_ID;
    $hostname = VIDEO_LIBRARY_HOSTNAME;
    $api_key = VIDEO_LIBRARY_ACCESS_KEY;

    // Prepare video creation payload
    $video_payload = ['title' => $video_name];

    // Create Bunny video entry
    $create_video = wp_remote_post("https://video.bunnycdn.com/library/$library_id/videos", [
        'headers' => [
            'AccessKey'    => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($video_payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($create_video)) {
        error_log("Failed to create video: " . $create_video->get_error_message());
        return;
    }

    $video_body = json_decode(wp_remote_retrieve_body($create_video), true);
    $new_video_id = $video_body['guid'] ?? null; // Video GUID returned from the API

    if (!$new_video_id) {
        error_log("Failed to get new Bunny video ID.");
        return;
    }

    // Upload video content to Bunny
    $video_data = file_get_contents($video_path);
    $upload = wp_remote_request("https://video.bunnycdn.com/library/$library_id/videos/$new_video_id", [
        'method' => 'PUT',
        'headers' => [
            'AccessKey' => $api_key,
            'Content-Type' => 'application/octet-stream',
        ],
        'body' => $video_data,
        'timeout' => 120,
    ]);

    if (is_wp_error($upload)) {
        error_log("Upload error: " . $upload->get_error_message());
        return;
    }

    // Upload subtitle (SRT file)
    $subtitle_file = get_field('subtitle_upload', $post_id);
    if ($subtitle_file && isset($subtitle_file['ID'])) {
        $subtitle_path = get_attached_file($subtitle_file['ID']);

        if (file_exists($subtitle_path)) {
            $srt_content = file_get_contents($subtitle_path);
            $base64_caption = base64_encode($srt_content);

            $caption_payload = [
                'srclang'      => 'de', // Change if needed
                'label'        => 'German',
                'captionsFile' => $base64_caption,
            ];

            $caption_response = wp_remote_post("https://video.bunnycdn.com/library/$library_id/videos/$new_video_id/captions/de", [
                'headers' => [
                    'AccessKey'    => $api_key,
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode($caption_payload),
                'timeout' => 30,
            ]);


            if (is_wp_error($caption_response)) {
                // error_log("Caption upload failed: " . $caption_response->get_error_message());
                return;
            }
        } else {
                // error_log("Subtitle file not found at: " . $subtitle_path);
            return;
        }
    } else {
        error_log("No subtitle file attached.");
        return;
    }

    // Save the new video info
    update_post_meta($post_id, '_bunny_video_id', $new_video_id);
    update_post_meta($post_id, '_uploaded_video_file_id', $current_video_id);

    if ($library_id && $new_video_id) {
        $thumbnail_url = "https://{$hostname}/{$new_video_id}/thumbnail.jpg";
        update_post_meta($post_id, '_bunny_thumbnail_url', esc_url_raw($thumbnail_url));
    }

    $playlist_url = "https://{$hostname}/{$new_video_id}/playlist.m3u8";
    update_post_meta($post_id, '_bunny_hls_url', esc_url_raw($playlist_url));
}

/**
 * Uploaded video embed shortcode.
 *
 */
add_shortcode('vlp_bunny_video_embed', 'vlp_bunny_video_embed');
function vlp_bunny_video_embed($atts) {
    // Optional: allow overriding via shortcode attributes
    $atts = shortcode_atts([
        'post_id' => get_the_ID(),
        'width'   => 720,
        'height'  => 400,
        'autoplay' => 'true',
        'muted'   => 'true',
    ], $atts, 'vlp_bunny_video_embed');

    $post_id = $atts['post_id'];
    $video_id = get_post_meta($post_id, '_bunny_video_id', true);
    $library_id = defined('VIDEO_LIBRARY_ID') ? VIDEO_LIBRARY_ID : '';

    if ($video_id && $library_id) {
        $src = sprintf(
            'https://iframe.mediadelivery.net/embed/%s/%s?autoplay=%s&muted=%s',
            esc_attr($library_id),
            esc_attr($video_id),
            esc_attr($atts['autoplay']),
            esc_attr($atts['muted'])
        );

        return sprintf(
            '<iframe id="bunny-stream-embed" src="%s" width="%d" height="%d" frameborder="0" allow="autoplay; fullscreen"></iframe>',
            esc_url($src),
            intval($atts['width']),
            intval($atts['height'])
        );
    }

    return '<p>Video not available.</p>';
}
