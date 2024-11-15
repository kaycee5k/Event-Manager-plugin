<?php
/*
Plugin Name: Event Manager
Description: A plugin to manage and display events with dynamic countdown and auto-replacement of expired events.
Version: 1.8
Author: Kaycee Onyia
*/

// Register Custom Post Type for Events
function wp_event_manager_register_event_post_type() {
    register_post_type('event', [
        'labels' => [
            'name'          => 'Events',
            'singular_name' => 'Event',
            'add_new'       => 'Add New Event',
            'add_new_item'  => 'Add New Event',
            'edit_item'     => 'Edit Event',
            'new_item'      => 'New Event',
            'view_item'     => 'View Event',
            'search_items'  => 'Search Events',
            'not_found'     => 'No Events found',
            'not_found_in_trash' => 'No Events found in Trash',
        ],
        'public'        => true,
        'has_archive'   => true,
        'rewrite'       => ['slug' => 'events'],
        'supports'      => ['title', 'editor', 'thumbnail'],
        'show_in_rest'  => true,
    ]);
}
add_action('init', 'wp_event_manager_register_event_post_type');

// Enqueue CSS and JS files
function wp_event_manager_enqueue_assets() {
    wp_enqueue_style(
        'wp-event-manager-style',
        plugin_dir_url(__FILE__) . 'css/event-manager.css',
        [],
        '1.8'
    );
    wp_enqueue_script(
        'wp-event-manager-script',
        plugin_dir_url(__FILE__) . 'js/event-manager.js',
        ['jquery'],
        '1.8',
        true
    );
}
add_action('wp_enqueue_scripts', 'wp_event_manager_enqueue_assets');

// Enqueue FontAwesome for icons
function wp_event_manager_enqueue_fontawesome() {
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', 
        [], 
        '6.0.0'
    );
}
add_action('wp_enqueue_scripts', 'wp_event_manager_enqueue_fontawesome');

// Add meta boxes for event details
function wp_event_manager_add_event_meta_boxes() {
    add_meta_box('event_details', 'Event Details', 'wp_event_manager_event_meta_box_callback', 'event', 'side', 'default');
}
add_action('add_meta_boxes', 'wp_event_manager_add_event_meta_boxes');

function wp_event_manager_event_meta_box_callback($post) {
    $date = get_post_meta($post->ID, '_event_date', true);
    $location = get_post_meta($post->ID, '_event_location', true);
    $description = get_post_meta($post->ID, '_event_description', true);
    ?>
    <p>
        <label for="event_date">Date:</label>
        <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($date); ?>" />
    </p>
    <p>
        <label for="event_location">Location:</label>
        <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>" />
    </p>
    <p>
        <label for="event_description">Description:</label>
        <textarea id="event_description" name="event_description"><?php echo esc_attr($description); ?></textarea>
    </p>
    <?php
}

// Save custom field data
function wp_event_manager_save_event_meta_data($post_id) {
    if (!isset($_POST['event_date'], $_POST['event_location'], $_POST['event_description'])) {
        return;
    }
    
    update_post_meta($post_id, '_event_date', sanitize_text_field($_POST['event_date']));
    update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['event_location']));
    update_post_meta($post_id, '_event_description', sanitize_textarea_field($_POST['event_description']));
}
add_action('save_post', 'wp_event_manager_save_event_meta_data');

// Shortcode to display upcoming events with dynamic countdown and auto-replacement of expired events
function wp_event_manager_display_upcoming_events($atts) {
    $atts = shortcode_atts(['limit' => 3], $atts, 'upcoming_events');
    $today = date('Y-m-d');
    $args = [
        'post_type'      => 'event',
        'meta_key'       => '_event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'posts_per_page' => $atts['limit'],
        'meta_query' => [
            [
                'key'     => '_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE'
            ]
        ]
    ];

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return '<p>No upcoming events.</p>';
    }

    $output = '<div class="upcoming-events-horizontal">';
    while ($query->have_posts()) {
        $query->the_post();
        
        $event_date = get_post_meta(get_the_ID(), '_event_date', true);
        $event_location = get_post_meta(get_the_ID(), '_event_location', true);
        $event_description = get_post_meta(get_the_ID(), '_event_description', true);
        
        $date_diff = (new DateTime($event_date))->diff(new DateTime($today))->days;
        $is_past = (new DateTime($event_date)) < new DateTime($today);
        $is_today = (new DateTime($event_date))->format('Y-m-d') === $today;

        $output .= '<div class="event-card">';
        $output .= '<h3>' . get_the_title() . '</h3>';
        $output .= '<p class="description">' . esc_html($event_description) . '</p>';
        $output .= '<p class="location"><i class="fa fa-map-marker" aria-hidden="true"></i> ' . esc_html($event_location) . '</p>';
        $output .= '<div class="badge-container">';
        $output .= '<span class="badge date-badge">Date: ' . esc_html($event_date) . '</span>';

        if ($is_today) {
            $output .= '<span class="badge countdown-badge">Ongoing Event</span>';
        } elseif ($is_past) {
            $output .= '<span class="badge countdown-badge">Event Has Passed</span>';
        } else {
            // Handle pluralization for countdown text
            $countdown_text = $date_diff === 1 ? "1 day remaining" : "$date_diff days remaining";
            $output .= '<span class="badge countdown-badge">Countdown: ' . esc_html($countdown_text) . '</span>';
        }

        $output .= '</div>';
        $output .= '</div>';
    }
    wp_reset_postdata();
    $output .= '</div>';

    return $output;
}
add_shortcode('upcoming_events', 'wp_event_manager_display_upcoming_events');
?>
