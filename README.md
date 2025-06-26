
# Video Library

**Contributors:** nexuslink  
**Tags:** video library, ACF, REST API, custom post type, shortcode  
**Requires PHP:** 8.0  
**Stable tag:** 1.0.0  

A lightweight plugin that registers a Video Library custom post type with category support, AJAX-based frontend filtering, and REST API access secured with JWT and secret key headers.

---

## Description

The Video Library plugin allows you to manage and expose a video archive through:

- A custom post type: `video_library`
- AJAX-based video filtering and listing for frontend
- Custom REST API endpoints for video listing, detail, and categories
- Authorization via JWT token and a secret key for secure access

Translation-ready with domain: `video-library`.

---

## Installation

1. Upload the plugin files to the `/wp-content/plugins/video-library` directory or install via ZIP or Git.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure ACF is installed — plugin attempts to activate or install it if missing.

---

## Shortcodes

### `[video_listing]`
Displays the list of videos with category filter and search.

### `[vlp_bunny_video_embed]`
Embeds a Bunny.net video using an iframe.

**Attributes:**

| Attribute | Type    | Default | Description                                |
|-----------|---------|---------|--------------------------------------------|
| width     | string  | 720     | Width of the video iframe.                 |
| height    | string  | 400     | Height of the video iframe.                |
| autoplay  | boolean | true    | Whether the video should autoplay.         |
| muted     | boolean | true    | Whether the video should be muted.         |

**Example Usage:**

```shortcode
[vlp_bunny_video_embed width="800" height="450" autoplay="true" muted="true"]
```

---

## Configuration

### Define Secret Key

To enable secure access to custom REST API endpoints, add this constant to your `wp-config.php` file:

```php
define('BF_VIDEO_SECRET_KEY', 'YOUR_SECRET_KEY');
```

### Bunny.net Integration

To enable Bunny.net integration for video and subtitle uploads, add the following constants to `wp-config.php`:

```php
define('VIDEO_LIBRARY_ACCESS_KEY', 'YOUR_BUNNY_API_KEY');
define('VIDEO_LIBRARY_ID', 'YOUR_BUNNY_LIBRARY_ID');
define('VIDEO_LIBRARY_HOSTNAME', 'YOUR_BUNNY_STREAMING_HOSTNAME');
```

---

## ACF Fields

Create these ACF fields for the `video_library` post type:

- `video_upload` (File field): Used to upload the Bunny-hosted video.
- `subtitle_upload` (File field): Used to upload subtitle files (VTT or SRT format).

---

## REST API

**Namespace:** `/wp-json/custom-vl/v1/`

### Endpoints

| Endpoint               | Method | Description                               |
|------------------------|--------|-------------------------------------------|
| `/video-listing`       | GET    | Returns a list of videos (with filters).  |
| `/video-categories`    | GET    | Returns list of video categories.         |
| `/video-detail/{id}`   | GET    | Returns detail for a single video.        |

### Authentication & Headers

All REST API requests require:

- **JWT Authentication** via the *JWT Authentication for WP REST API* plugin.
- A valid `BF_VIDEO_SECRET_KEY` passed via HTTP headers.

**Required Headers:**

```
Authorization: Bearer YOUR_JWT_TOKEN
BF_VIDEO_SECRET_KEY: YOUR_SECRET_KEY
```

---

## API Usage

### 1. Video Listing

**Endpoint:**

```
GET /wp-json/custom-vl/v1/video-listing
```

**Query Parameters:**

| Parameter  | Type    | Description                                 |
|------------|---------|---------------------------------------------|
| `search`     | string  | Search keyword (in title or content).       |
| `category`   | integer | Category term ID (taxonomy filter).         |
| `orderby`    | string  | Sort by: `title`, `date`.                   |
| `order`      | string  | Sorting order: `ASC` or `DESC`.             |
| `page`       | integer | Page number (starts from 1).                |
| `per_page`   | integer | Number of results per page (default is 10). |

**Example:**

```
GET /wp-json/custom-vl/v1/video-listing?search=design&category=8&orderby=date&order=DESC&page=1&per_page=10
```

---

### 2. Get Video Categories

**Endpoint:**

```
GET /wp-json/custom-vl/v1/video-categories
```

**Example:**

```
GET /wp-json/custom-vl/v1/video-categories
```

---

### 3. Get Video Details

**Endpoint:**

```
GET /wp-json/custom-vl/v1/video-detail/{video_id}
```

**Example:**

```
GET /wp-json/custom-vl/v1/video-detail/36
```

---

## Changelog

### 1.0.0

- Initial release

