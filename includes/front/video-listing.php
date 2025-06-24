<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function vlp_video_library_shortcode() {
    ob_start(); ?>
    <div id="vlp-video-list-wrapper">
        <form id="vlp-video-list-ilter-form">
            <input type="text" name="search" id="vlp-video-list-search" placeholder="<?php esc_attr_e('Search videos...', 'video-library'); ?>">
            <select name="category" id="vlp-video-list--category">
                <option value=""><?php esc_html_e('All Categories', 'video-library'); ?></option>
                <?php
                $categories = get_terms(['taxonomy' => 'video_category', 'hide_empty' => false]);
                foreach ($categories as $cat) {
                    echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
                }
                ?>
            </select>
            <input type="hidden" name="page" value="1">
        </form>

        <div id="vlp-video-list-loading" style="display:none;text-align:center;margin:20px 0;">
            <img src="<?php echo esc_url(includes_url('images/spinner.gif')); ?>" alt="Loading..." />
        </div>

        <div id="vlp-video-list-results"></div>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('video_listing', 'vlp_video_library_shortcode');

// AJAX Handler
function vlp_ajax_fetch_videos() {
    check_ajax_referer('load_viedos_nonce', 'security');

    $search   = sanitize_text_field($_POST['search'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $paged    = max(1, intval($_POST['page'] ?? 1));
    $per_page = PER_PAGE;

    $args = [
        'post_type' => 'video_library',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        's' => $search,
    ];

    if ($category) {
        $args['tax_query'] = [[
            'taxonomy' => 'video_category',
            'field' => 'slug',
            'terms' => $category,
        ]];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()): $query->the_post(); ?>
            <div class="vlp-video-list-item">
                <a href="<?php the_permalink(); ?>">
                    <?php
                    $id = get_the_ID();
                    $bunny_thumbnail = get_post_meta($id, '_bunny_thumbnail_url', true);

                    if (has_post_thumbnail($id)) {
                        $img_url = get_the_post_thumbnail_url($id, 'full');
                    } elseif (!has_post_thumbnail($id) && $bunny_thumbnail) {
                        $img_url = $bunny_thumbnail;
                    } else {
                        $img_url = VLP_PLUGIN_URL.'assets/images/video_list.png';
                    }
                    $excerpt = wp_trim_words(strip_shortcodes(get_the_content()), 15, '...');
                    ?>
                    <div class="image-wrapper" style="position: relative;">
                        <img src="<?php echo $img_url;?>" alt="" width="300" height="200">
                        <div class="image-overlay"></div>
                        <!-- Play Button -->
                        <div class="play-button">
                            <svg viewBox="0 0 100 100" width="50" height="50">
                                <circle cx="50" cy="50" r="48" fill="rgba(0, 0, 0, 0.5)" />
                                <polygon points="40,30 70,50 40,70" fill="#fff"/>
                            </svg>
                        </div>
                    </div>
                    <p class="video-date"><?php echo get_the_date("l, d. F Y")?></p>
                    <h3><?php the_title(); ?></h3>
                    <p class="vlp-video-excerpt"><?php echo esc_html($excerpt); ?></p>
                </a>
            </div>
        <?php endwhile;

        // Pagination
        $total_pages = $query->max_num_pages;
        $current = $paged;

        if ($total_pages > 1) {
            echo '<div class="course-pagination-wrapper">';
            echo '<div class="course-pagination vlp-video-list-pagination">';

            if ($current > 1) {
                echo '<a href="#" class="vlp-page-link" data-page="' . ($current - 1) . '">&laquo; ' . esc_html__('Prev', 'video-library') . '</a>';
            }

            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $current) {
                    echo '<span class="current">' . $i . '</span>';
                } else {
                    echo '<a href="#" class="vlp-page-link" data-page="' . $i . '">' . $i . '</a>';
                }
            }

            if ($current < $total_pages) {
                echo '<a href="#" class="vlp-page-link" data-page="' . ($current + 1) . '">' . esc_html__('Next', 'video-library') . ' &raquo;</a>';
            }

            echo '</div>';
        }
    } else {
        echo '<p class="vlp-video-list-no-data">'.esc_html_e('No videos found.', 'video-library').'</p>';
    }

    wp_reset_postdata();
    wp_die();
}
add_action('wp_ajax_load_ajax_videos', 'vlp_ajax_fetch_videos');
add_action('wp_ajax_nopriv_load_ajax_videos', 'vlp_ajax_fetch_videos');