jQuery(document).ready(function($) {
    // Initial load
    if ($("#vlp-video-list-wrapper").length >= 1) {
        loadVideos();
    }

    function loadVideos(page = 1) {
        $('#vlp-video-list-loading').show();
        $('#vlp-video-list-results').hide();

        const data = {
            action: 'load_ajax_videos',
            search: $('#vlp-video-list-search').val(),
            category: $('#vlp-video-list--category').val(),
            type: $('#vlp-video-list--type').val(),
            page: page,
            security: video_library_ajax_params.nonce
        };

        $.post(course_ajax_params.ajax_url, data, function(response) {
            $('#vlp-video-list-results').html(response).fadeIn();
            $('#vlp-video-list-loading').hide();
        });
    }

    // Search on Enter
    $('#vlp-video-list-search').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadVideos(1);
        }
    });

    // Filter by category
    $('#vlp-video-list--category').on('change', function() {
        loadVideos(1);
    });

    // Pagination click
    $(document).on('click', '.vlp-page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadVideos(page);
    });
});