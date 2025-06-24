<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('rest_api_init', function () {

    register_rest_route('custom-vl/v1', '/video-listing', [
        'methods' => 'GET',
        'callback' => 'get_video_listing_data',
        'permission_callback' => 'custom_video_permission_check',
        'args' => [
            'search' => ['type' => 'string', 'required' => false],
            'category' => ['type' => 'integer', 'required' => false],
            'orderby' => ['type' => 'string', 'default' => 'date'],
            'order' => ['type' => 'string', 'default' => 'DESC'],
            'page' => ['type' => 'integer', 'default' => 1],
            'per_page' => ['type' => 'integer', 'default' => 10],
        ],
    ]);

    register_rest_route('custom-vl/v1', '/video-detail/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => 'get_video_details_data',
        'permission_callback' => 'custom_video_permission_check',
    ]);

    register_rest_route('custom-vl/v1', '/video-categories', [
        'methods' => 'GET',
        'callback' => 'get_video_categories',
        'permission_callback' => 'custom_video_permission_check',
    ]);

});

// Permission: only logged-in users
function custom_video_permission_check($request) {
    if (!is_user_logged_in()) {
        return new WP_Error(
            '401',
            __('You must be logged in to access details.', 'video-library'),
            ['status' => 401]
        );
    }

    return true;
}

/**
 * Callback function to fetch and return Video listings.
 *
 */
function get_video_listing_data($request) {
    if (!ai1st_validate_video_api_key()) {
        return new WP_REST_Response([
            'code'    => '403',
            'message' => __('Invalid API key.', 'video-library'),
            'data'    => ['status' => 403]
        ], 403);
    }
    $per_page = $request->get_param('per_page');
    $args = [
        'post_type'      => 'video_library',
        'post_status'    => 'publish',
        'paged'          => $request->get_param('page'),
        'posts_per_page' => $per_page,
        'orderby'        => sanitize_text_field($request->get_param('orderby')),
        'order'          => sanitize_text_field($request->get_param('order')),
        's'              => sanitize_text_field($request->get_param('search')),
    ];

    // Filter by category
    if ($category = $request->get_param('category')) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'video_category',
                'field'    => 'term_id',
                'terms'    => intval($category),
            ],
        ];
    }

    $query = new WP_Query($args);

    $videos = [];
    foreach ($query->posts as $post) {

        $bunny_thumbnail = get_post_meta($post->ID, '_bunny_thumbnail_url', true) ?: null;
        $bunny_embed = get_post_meta($post->ID, '_bunny_hls_url', true) ?: null;

        $img_url = "";
        if (has_post_thumbnail($post->ID)) {
            $img_url = get_the_post_thumbnail_url($post->ID, 'full');
        } elseif (!has_post_thumbnail($post->ID) && $bunny_thumbnail) {
            $img_url = $bunny_thumbnail;
        }

        $video_guid = get_post_meta($post->ID, '_bunny_video_id', true);
        $bunny_data = get_bunny_video_views_details($video_guid);
        $bunny_views = $bunny_data['views'] ?? 0;

        $duration_raw = $bunny_data['length'] ?? 0;
        $duration = get_bunny_video_format_duration($duration_raw);

        $content = apply_filters('the_content', $post->post_content);
        $content_without_tags = wp_strip_all_tags($content);
        $subtitle_file = get_field('subtitle_upload', $post->ID);

        $videos[] = [
            'id'              => $post->ID,
            'date'            => get_the_date('Y-m-d h:i:s', $post),
            'title'           => get_the_title($post),
            'link'            => get_permalink($post),
            'excerpt'         => get_the_excerpt($post),
            'content'         => $content_without_tags,
            'bunny_video'     => $bunny_embed,
            'subtitle_file'   => $subtitle_file['url'],
            'views'           => $bunny_views,
            'duration'        => $duration,
            'featured_image'  => $img_url,
            'categories'      => wp_get_post_terms($post->ID, 'video_category', ['fields' => 'names']),
            'tags'            => wp_get_post_terms($post->ID, 'video_tag', ['fields' => 'names']),
        ];
    }

    if (empty($videos)) {
        return new WP_REST_Response([
            'code'    => '404',
            'message' => __('No videos found', 'video-library'),
            'data'    => ['status' => 404]
        ], 404);
    }

    return new WP_REST_Response([
        'code'    => '200',
        'message' => __('Video listing retrieved successfully', 'video-library'),
        'data'    => [
            'videos'      => $videos,
            'total'       => $query->found_posts,
            'page'        => $request->get_param('page'),
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ],
    ], 200);
}

/**
 * Callback function to fetch and return Video details.
 *
 */
function get_video_details_data($request) {
    if (!ai1st_validate_video_api_key()) {
        return new WP_REST_Response([
            'code'    => '403',
            'message' => __('Invalid API key.', 'video-library'),
            'data'    => ['status' => 403]
        ], 403);
    }
    $id = (int) $request['id'];
    $post = get_post($id);

    if (!$post || $post->post_type !== 'video_library') {
        return new WP_REST_Response([
            'code'    => '404',
            'message' => __('Video not found', 'video-library'),
            'data'    => ['status' => 404]
        ], 404);
    }

    $bunny_thumbnail = get_post_meta($post->ID, '_bunny_thumbnail_url', true) ?: null;
    $bunny_embed = get_post_meta($post->ID, '_bunny_hls_url', true) ?: null;

    $img_url = "";
    if (has_post_thumbnail($post->ID)) {
        $img_url = get_the_post_thumbnail_url($post->ID, 'full');
    } elseif (!has_post_thumbnail($post->ID) && $bunny_thumbnail) {
        $img_url = $bunny_thumbnail;
    }

    $video_guid = get_post_meta($post->ID, '_bunny_video_id', true);
    $bunny_data = get_bunny_video_views_details($video_guid);
    $bunny_views = $bunny_data['views'] ?? 0;

    $duration_raw = $bunny_data['length'] ?? 0;
    $duration = get_bunny_video_format_duration($duration_raw);

    $content = apply_filters('the_content', $post->post_content);
    $content_without_tags = wp_strip_all_tags($content);
    $subtitle_file = get_field('subtitle_upload', $post->ID);

    return new WP_REST_Response([
        'code'    => '200',
        'message' => __('Video details retrieved successfully', 'video-library'),
        'data'    => [
            'id'              => $id,
            'date'            => get_the_date('Y-m-d h:i:s', $post),
            'title'           => get_the_title($id),
            'content'         => $content_without_tags,
            'bunny_video'     => $bunny_embed,
            'subtitle_file'   => $subtitle_file['url'],
            'featured_image'  => $img_url,
            'views'           => $bunny_views,
            'duration'        => $duration,
            'categories'      => wp_get_post_terms($post->ID, 'video_category', ['fields' => 'names']),
            'tags'            => wp_get_post_terms($post->ID, 'video_tag', ['fields' => 'names']),
        ],
    ], 200);
}

/**
 * Callback function to fetch and return Video categories.
 *
 */
function get_video_categories(WP_REST_Request $request) {
    if (!ai1st_validate_video_api_key()) {
        return new WP_REST_Response([
            'code'    => '403',
            'message' => __('Invalid API key.', 'video-library'),
            'data'    => ['status' => 403]
        ], 403);
    }
    $terms = get_terms([
        'taxonomy' => 'video_category',
        'hide_empty' => true,
    ]);

    if (is_wp_error($terms)) {
        return new WP_REST_Response([
            'code'    => '500',
            'message' => __('Unable to fetch video categories', 'video-library'),
            'data'    => ['status' => 500],
        ], 500);
    }

    $video_categories = [];
    foreach ($terms as $term) {
        $video_categories[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'parent' => $term->parent,
            'count' => $term->count,
        ];
    }

    return new WP_REST_Response([
        'code'    => '200',
        'message' => __('Videos categories retrieved successfully', 'video-library'),
        'data'    => $video_categories,
    ], 200);
}

function get_bunny_video_views_details($video_guid) {
    if (!$video_guid || !defined('VIDEO_LIBRARY_ID') || !defined('VIDEO_LIBRARY_ACCESS_KEY')) {
        return null;
    }

    $url = "https://video.bunnycdn.com/library/" . VIDEO_LIBRARY_ID . "/videos/{$video_guid}";

    $response = wp_remote_get($url, [
        'headers' => [
            'Accept'    => 'application/json',
            'AccessKey' => VIDEO_LIBRARY_ACCESS_KEY,
        ],
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($body) || isset($body['HttpStatusCode'])) {
        return null;
    }

    return $body;
}

function get_bunny_video_format_duration($seconds) {
    $seconds = (int) round($seconds);
    return gmdate($seconds >= 3600 ? "H:i:s" : "i:s", $seconds);
}