<?php
/**
 * Plugin Name: Instagram Feed for multiple accounts by Mradul
 * Description: Display Instagram feed for multiple Instagram accounts.
 * Version: 1.1
 * Author: Mradul Jaiswani
 * License: GPL2
 */

// Include WordPress functionality
require_once (ABSPATH . 'wp-load.php');

add_action('admin_menu', 'instagram_feed_menu');
function instagram_feed_menu() {
    add_options_page('Instagram Feed Settings', 'Instagram Feed', 'manage_options', 'instagram-feed-settings', 'instagram_feed_settings_page');
}

function instagram_feed_settings_page() {
    $options = get_option('instagram_feed_options');
    $access_tokens = isset($options['access_tokens']) ? $options['access_tokens'] : '';
    ?>
    <div class="wrap">
        <h2>Instagram Feed Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('instagram_feed_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Access Tokens (one per line):</th>
                    <td><textarea name="instagram_feed_options[access_tokens]" rows="10" cols="50"><?php echo esc_textarea($access_tokens); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'instagram_feed_register_settings');
function instagram_feed_register_settings() {
    register_setting('instagram_feed_options_group', 'instagram_feed_options');
}

function display_instagram_feed() {
    $options = get_option('instagram_feed_options');
    $access_tokens = isset($options['access_tokens']) ? array_map('trim', explode("\n", $options['access_tokens'])) : [];

    if (empty($access_tokens)) {
        return 'Access tokens are not set.';
    }

    $output = '<div class="instagram-feed">';
    
    foreach ($access_tokens as $access_token) {
        if (!$access_token) {
            continue;
        }

        $api_url = 'https://graph.instagram.com/me/media?fields=id,caption,media_url,permalink,media_type,thumbnail_url&access_token=' . $access_token;
        $response = wp_remote_get($api_url);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            $output .= 'Error: ' . $data['error']['message'] . '<br>';
            continue;
        }

        if (!empty($data['data'])) {
            foreach ($data['data'] as $post) {
                $media_url = $post['media_type'] === 'VIDEO' ? $post['thumbnail_url'] : $post['media_url'];
                $output .= '<div class="instagram-post">';
                $output .= '<a href="' . esc_url($post['permalink']) . '" target="_blank">';
                $output .= '<img src="' . esc_url($media_url) . '" alt="' . esc_attr($post['caption'] ?? '') . '">';
                $output .= '</a>';
                $output .= '</div>';
            }
        } else {
            $output .= 'No posts found for this account.<br>';
        }
    }
    
    $output .= '</div>';
    return $output;
}

// Change the shortcode name here
add_shortcode('multiple_instagram_feeds', 'display_instagram_feed');
