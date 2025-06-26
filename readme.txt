=== Video Library ===
Contributors: nexuslink
Tags: video library, ACF, REST API, custom post type, shortcode
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin that registers a Video Library custom post type with category support, AJAX-based frontend filtering, and REST API access secured with JWT and secret key headers.

== Description ==

The Video Library plugin allows you to manage and expose a video archive through:

* A custom post type: `video_library`
* AJAX-based video filtering and listing for front-end
* Custom REST API endpoints for video listing, detail, and categories
* Authorization via JWT token and a secret key for secure access

Translation-ready with domain: `video-library`.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/video-library` directory or install via ZIP or Git.
2. Activate the plugin through the ‘Plugins’ screen.
3. Ensure ACF are installed — plugin attempts to activate or install if missing.

== Shortcode ==

=== [video_listing] ===
Displays the list of videos with category filter and search

=== [vlp_bunny_video_embed] ===
Embeds a Bunny.net video using an iframe.

**Attributes:**

| Attribute | Type    | Default | Description                                            |
|-----------|---------|---------|------------------------------------------------------- |
| `width`   | string  | `720`   | Width of the video iframe.                             |
| `height`  | string  | `400`   | Height of the video iframe.                            |
| `autoplay`| boolean | `true`  | Whether the video should autoplay (`true` or `false`). |
| `muted`   | boolean | `true`  | Whether the video should be muted (`true` or `false`). |

**Example Usage:**
[vlp_bunny_video_embed width="800" height="450" autoplay="true" muted="true"]

== Configuration ==

=== Define Secret Key ===

To enable secure access to custom REST API endpoints, add this constant to your `wp-config.php` file:

define('BF_VIDEO_SECRET_KEY', 'YOUR_SECRET_KEY');

=== Bunny.net Integration ===

To enable Bunny.net integration for video library video and subtitle uploads, you must define the following constants in your `wp-config.php` file:

define('VIDEO_LIBRARY_ACCESS_KEY', 'YOUR_BUNNY_API_KEY');
define('VIDEO_LIBRARY_ID', 'YOUR_BUNNY_LIBRARY_ID');
define('VIDEO_LIBRARY_HOSTNAME', 'YOUR_BUNNY_STREAMING_HOSTNAME');

== ACF Fields ==

Add the following custom ACF fields to your Video Library post type:

* video_upload (File field): Used to upload the Bunny-hosted video.
* subtitle_upload (File field): Used to upload subtitle files (VTT or SRT format) for the corresponding video.

== REST API ==

Namespace: /wp-json/custom-vl/v1/

The following endpoints are available:

| Endpoint               | Method | Description                               |
| ---------------------- | ------ | ----------------------------------------- |
| `/video-listing`       | GET    | Returns a list of videos (with filters)   |
| `/video-categories`    | GET    | Returns list of video categories          |
| `/video-detail`        | GET    | Returns detail for a single video (by ID) |

=== Authentication & Headers ===

All REST API requests require:
* JWT Authentication using the JWT Authentication for WP REST API plugin.
* A valid BF_VIDEO_SECRET_KEY passed via HTTP headers.

Required Headers:
Authorization: Bearer YOUR_JWT_TOKEN
BF_VIDEO_SECRET_KEY: YOUR_SECRET_KEY

== API Usage ==

=== Video Listing ===

Endpoint:
`GET /wp-json/custom-vl/v1/video-listing`

Description:
Returns a paginated list of videos, with optional filters.

Query parameters:

| Parameter   | Type     | Description |
|-------------|----------|-------------|
| `search`    | string   | Search keyword (in video title or content). |
| `category`  | integer  | Video category term ID (taxonomy filter). |
| `orderby`   | string   | Sort by: `title`, `date`. |
| `order`     | string   | Sorting order: `ASC` or `DESC`. |
| `page`      | integer  | Page number for pagination. Starts from `1`. |
| `per_page`  | integer  | Number of results per page. Default is `10`.  |

Example:
GET /wp-json/custom-vl/v1/video-listing?search=design&category=8&orderby=date&order=DESC&page=1&per_page=10

=== Get Video Categories ===

Endpoint:
`GET /wp-json/custom-vl/v1/video-categories`

Description:
Returns a list of video categories.

Example:
GET /wp-json/custom-vl/v1/video-categories

=== Get video Details ===

Endpoint:
`GET /wp-json/custom-vl/v1/video-detail/{video_id}`

Description:
Returns detailed information for a specific video.

Example:
GET /wp-json/custom-vl/v1/video-detail/36

== Changelog ==

= 1.0.0 =
* Initial release