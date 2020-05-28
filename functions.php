<?php

// Call theme setup functions
add_action('wp_enqueue_scripts', 'mytheme_setup'); // Enqueue stylesheet and script files
add_action('after_setup_theme','mytheme_add_support'); // Configurate theme support
add_action('get_header', 'mytheme_remove_admin_bar'); // Remove admin bar from the header

// Add custom post taxonomy for Werke
add_action('init', 'db_register_custom_taxonomy_werke'); 

// Add custom post taxonomy for Ausstellungen
add_action('init', 'db_register_custom_taxonomy_ausstellungen'); 

// Add custom post types Werke, Projekte, Kunst am Bau, Ausstellungen
// CPTs must be added after custom taxonomy, otherwise they don't work and pages throw 404 error
add_action('init', 'db_register_custom_post_types'); 


/**
 * Enqueue stylesheet and script files.
 */
function mytheme_setup() {
    
    // Enqueue Wordpress dashicons [https://developer.wordpress.org/resource/dashicons/]
    wp_enqueue_style('dashicons'); 

    // Enqueue Main CSS stylesheet "style.css" from theme top folder 
    wp_enqueue_style('style', get_stylesheet_uri(), 1.0, NULL);

    // Enqueue main JavaScript file from js-folder
    wp_enqueue_script('main', get_theme_file_uri('/js/main.js'), array( 'jquery' ), 1.0, false);

    /* Enqueue stylesheets from external websites
    ** wp_enqueue_style('mytheme_font', '//fonts.googleapis.com/css?family=Roboto'); 
    */

    /* Enqueue examples for diverse assets without version number or jquery
    ** wp_enqueue_style('wpg-slideshow', get_theme_file_uri('/assets/wpg-slideshow/style.css'), NULL, NULL);
    ** wp_enqueue_script('polyfill', get_theme_file_uri('/assets/polyfill/polyfill.js'), NULL, NULL, false); 
    */
}

/**
 * Registers theme support for given features.
 */ 
function mytheme_add_support() {
    // Basic features only
    add_theme_support('post-thumbnails'); // Adds thumbnails (featured image) to all post types
    add_theme_support('title-tag'); // This feature enables plugins and themes to manage the document title tag
    add_theme_support('html5', // This feature allows the use of HTML5 markup for the search forms, comment forms, comment lists, gallery, and caption.
        array('gallery')
        //,array('comment_list', 'comment-form', 'search-form', 'gallery')
    );
}

/**
 * Remove admin bar from the header when logged in.
 * Admin bar adds a top-margin of 32px to the top html element.
 */
function mytheme_remove_admin_bar() {
    remove_action('wp_head', '_admin_bar_bump_cb');
}

/**
 * Get taxonomy name by term id.
 *
 * @param {int} - Term id.
 * @return {string} - Taxonomy name.
 *
 */
function get_taxonomy_name_by_term_id($term_id) {
        // Check for value
        if ( $term_id != 0 || $term_id != null ) { 
            $term = get_term( $term_id );
            if ($term != false) {
                $taxonomy = $term->taxonomy;
            }
            return $taxonomy;
        } else return;
}

/**
 * Get taxonomy name by term id.
 *
 * @param {string} - Taxonomy name.
 * @param {string} - Term Name.
 * @return {int} - Term id.
 *
 */
function get_term_id_by_taxonomy_name($taxonomy_name, $term_name) {
    $term = get_term_by( "name", $term_name, $taxonomy_name );
    return $term->id;
}

/**
 * Gets all posts of a post type in specific order.
 * 
 * @param {string} - Post type.
 * @param {string} - Order e.g. 'DESC', 'ASC'.
 * @return {object} - Posts.
 */
function get_posts_by_post_type($post_type, $order) {
    $args = array (
        'post_type'         => $post_type,                 
        'post_status'       => 'publish',
        'order'             => $order,
        'posts_per_page'    => -1,                  // show all
    );
    $the_query = new WP_Query ( $args );    // Execute database query
    return $the_query;
}

/**
 * Gets a limited number of posts of a specific post type.
 * 
 * @param {string} - Post type.
 * @param {string} - Number of queried posts.
 * @param {string} - Order e.g. 'DESC', 'ASC'.
 * @return {object} - Posts.
 */
function get_limited_number_of_posts_by_post_type($post_type, $numberOfPosts, $order) {
    $args = array (
        'post_type'         => $post_type,                 
        'post_status'       => 'publish',
        'order'             => $order,
        'posts_per_page'    => $numberOfPosts,
        'no_found_rows'     => true                 
    );
    $the_query = new WP_Query ( $args );    // Execute database query
    return $the_query;
}

/**
 * Gets posts of a specific post type by term name of taxonomy "projekt".
 * @param {string} - Projektname.
 * @return {object} - All artworks of the project.
 */
function get_posts_of_taxonomy_projekte($post_type, $projekt_name) {
    $taxonomy_name = "";
    // Set taxonomy according to post type
    if ($post_type == "projekte") { 
        $taxonomy_name = "werke_projekte";
    } else if ($post_type == "ausstellungen") {
        $taxonomy_name = "ausstellungen_projekte";
    }
    get_term_id_by_taxonomy_name($taxonomy_name, $projekt_name);
    // Build args for WP_Query
    $args = array (
        'post_type'         => 'werke',                 
        'post_status'       => 'publish',
        'order'             => 'DESC',
        'posts_per_page'    => -1,                  // show all
        'tax_query' => array (
            array (
                'taxonomy' => $taxonomy_name,       // werke_projekte || werke_ausstellungen
                'field' => 'name',
                'terms' => array ( $projekt_name )  // e. g. "schwarzberuhigend"
            )
        )
    );
    $the_query = new WP_Query ( $args );    // Execute database query
    return $the_query;
}

/**
 * Get posts by term id.
 *
 * @param {string} - Post type like "werke" or "ausstellungen"
 * @param {string} - Term id e. g. from "get_queried_object_id();"
 * @return {object} - Posts with a certain term.
 * 
 */
function get_posts_by_term($post_type, $term_id, $order_by, $order) {
    $taxonomy_name = get_taxonomy_name_by_term_id($term_id); // Get taxonomy name of term  
    $args = array(
        'post_type'         => $post_type,      // e. g. "werke" oder "ausstellungen"
        'post_status'       => 'publish',       // only published
        'posts_per_page'    => -1,              // show all
        'orderby'           => $order_by,        // order by field
        'order'             => $order,          // ASC or DESC
        'tax_query' => array(
            array(
            'taxonomy' => $taxonomy_name,       // e. g. "werke_techniken"
            'terms' => array ( $term_id ),      // e. g. 10, 25 or 30
            )
        )
    );
    $the_query = new WP_Query ( $args );        // Execute database query
    return $the_query;
}

/**
 * Return the number of posts in a custom taxonomy
 * https://wordpress.stackexchange.com/questions/76229/count-posts-in-custom-taxonomy/285958
 */
function get_taxonomy_postcount($post_type, $term_name, $taxonomy_name) {
    $args = array(
      'post_type'     => $post_type,
      'post_status'   => 'publish',
      'posts_per_page' => -1,               // show all
      'tax_query' => array(
        array(
          'taxonomy' => $taxonomy_name,     // taxonomy name, e. g. "werke_techniken"
          'field' => 'name',
          'terms' => array( $term_name )    // term name, e. g. "Ölmalerei"
        )
      )
    );
    $the_query = new WP_Query( $args);
    $postcount = $the_query->post_count;
    //echo "term_name: " . $term_name . " tax_name: " . $taxonomy_name . " postcount: " . $postcount . "<br>";
    return (int)$postcount;
}

/**
 * Register custom post types.
 * Werke, Ausstellungen, Projekte.
 */
function db_register_custom_post_types() {
    // Labels for post type 'Werke'
    $wLabels = array(
        'name'                => __( 'Werke' ),
		'singular_name'       => __( 'Werk'),
		'menu_name'           => __( 'Werke'),
		'parent_item_colon'   => __( 'Übergeordnetes Werk'),
		'all_items'           => __( 'Alle Werke'),
		'view_item'           => __( 'Werk anzeigen'),
		'add_new_item'        => __( 'Werk hinzufügen'),
		'add_new'             => __( 'Neues Werk'),
		'edit_item'           => __( 'Werk ändern'),
		'update_item'         => __( 'Werk aktualisieren'),
		'search_items'        => __( 'Werk suchen'),
		'not_found'           => __( 'Nicht gefunden'),
		'not_found_in_trash'  => __( 'Nicht im Papierkorb gefunden')
    );

    // Labels for post type 'Ausstellungen'
    $aLabels = array(
        'name'                => __( 'Ausstellungen' ),
		'singular_name'       => __( 'Ausstellung'),
		'menu_name'           => __( 'Ausstellungen'),
        'parent_item_colon'   => __( 'Übergeordnete Ausstellung'),
		'all_items'           => __( 'Alle Ausstellungen'),
		'view_item'           => __( 'Ausstellung anzeigen'),
		'add_new_item'        => __( 'Ausstellung hinzuufügen'),
		'add_new'             => __( 'Neue Ausstellung'),
		'edit_item'           => __( 'Ausstellung ändern'),
		'update_item'         => __( 'Ausstellung aktualisieren'),
		'search_items'        => __( 'Ausstellung suchen'),
		'not_found'           => __( 'Nicht gefunden'),
		'not_found_in_trash'  => __( 'Nicht im Papierkorb gefunden')
    );

    // Labels for post type 'Projekte'
    $pLabels = array(
        'name'                => __( 'Projekte' ),
		'singular_name'       => __( 'Projekt'),
		'menu_name'           => __( 'Projekte'),
        'parent_item_colon'   => __( 'Übergeordnete Projekt'),
		'all_items'           => __( 'Alle Projekte'),
		'view_item'           => __( 'Projekt anzeigen'),
		'add_new_item'        => __( 'Projekt hinzuufügen'),
		'add_new'             => __( 'Neues Projekt'),
		'edit_item'           => __( 'Projekte ändern'),
		'update_item'         => __( 'Projekt aktualisieren'),
		'search_items'        => __( 'Projekt suchen'),
		'not_found'           => __( 'Nicht gefunden'),
		'not_found_in_trash'  => __( 'Nicht im Papierkorb gefunden')
    );

    // Labels for post type 'Kunst am Bau'
    $kLabels = array(
        'name'                => __( 'Kunst am Bau' ),
		'singular_name'       => __( 'Kunst am Bau'),
		'menu_name'           => __( 'Kunst am Bau'),
        'parent_item_colon'   => __( 'Übergeordnete Kunst am Bau'),
		'all_items'           => __( 'Alle Baukünste'),
		'view_item'           => __( 'Baukunst anzeigen'),
		'add_new_item'        => __( 'Baukunst hinzuufügen'),
		'add_new'             => __( 'Neue Baukunst'),
		'edit_item'           => __( 'Baukunst ändern'),
		'update_item'         => __( 'Baukunst aktualisieren'),
		'search_items'        => __( 'Baukunst suchen'),
		'not_found'           => __( 'Nicht gefunden'),
		'not_found_in_trash'  => __( 'Nicht im Papierkorb gefunden')
    );

    // Arguments for post type 'Werke'
    $wArgs = array (
        'label'               => __( 'Werke'),
		'description'         => __( 'Werke von Gerhard Brandl'),
		'labels'              => $wLabels,
		'supports'            => array( 'title', 'editor', 'gutenberg', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
		'public'              => true,
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-images-alt2',
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'menu_position'       => 2,
		'has_archive'         => true,
		'can_export'          => true,
		'exclude_from_search' => false,
        'yarpp_support'       => true,
		'publicly_queryable'  => true, // alle Datenbankeinträge werden angelegt, aber keine öffentliche Seite (kein single)
		'capability_type'     => 'page'
    );

    // Arguments for post type 'Ausstellungen'
    $aArgs = array (
        'label'               => __( 'Ausstellungen'),
		'description'         => __( 'Ausstellungen von Gerhard Brandl'),
		'labels'              => $aLabels,
		'supports'            => array( 'title', 'editor', 'gutenberg', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
		'public'              => true,
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-art',
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'menu_position'       => 4,
		'has_archive'         => true,
		'can_export'          => true,
		'exclude_from_search' => false,
        'yarpp_support'       => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page'
    );

    // Arguments for post type 'Projekte'
    $pArgs = array (
        'label'               => __( 'Projekte'),
		'description'         => __( 'Projekte von Gerhard Brandl'),
		'labels'              => $pLabels,
		'supports'            => array( 'title', 'editor', 'gutenberg', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
		'public'              => true,
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-clipboard',
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'menu_position'       => 3,
		'has_archive'         => true,
		'can_export'          => true,
		'exclude_from_search' => false,
        'yarpp_support'       => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page'
    );

    // Arguments for post type 'Kunst am Bau'
    $kArgs = array (
        'label'               => __( 'Kunst am Bau'),
		'description'         => __( 'Kunst am Bau'),
		'labels'              => $kLabels,
		'supports'            => array( 'title', 'editor', 'gutenberg', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
		'public'              => true,
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-hammer',
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'menu_position'       => 3,
		'has_archive'         => true,
		'can_export'          => true,
		'exclude_from_search' => false,
        'yarpp_support'       => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page'
    );

    // Register post types
    register_post_type('werke', $wArgs);
    register_post_type('ausstellungen', $aArgs);
    register_post_type('projekte', $pArgs);
    register_post_type('kunst-am-bau', $kArgs);
}

/**
 * Register custom taxonomys for CPT "Werke".
 * Status: Status des Kunstwerks like "Zum Verkauf" oder "In Galerie".
 * Techniken: Techniken bildender Künste like Ölmalerei, Druckgrafik or Skulptur.
 * Projekte: Projektzugehörigkeit des Werkes.
 */
function db_register_custom_taxonomy_werke() {
    // Labels for taxonomy "Techniken"
    $tLabels = array(
        'name' => _x( 'Techniken', 'taxonomy general name' ),
        'singular_name' => _x( 'Technik', 'taxonomy singular name' ),
        'search_items' =>  __( 'Suche Technik' ),
        'all_items' => __( 'Alle Techniken' ),
        'parent_item' => __( 'Übergeordnete Technik' ),
        'parent_item_colon' => __( 'Übergeordnete Technik:' ),
        'edit_item' => __( 'Technik bearbeiten' ), 
        'update_item' => __( 'Technik aktualisieren' ),
        'add_new_item' => __( 'Neue Technik hinuzufügen' ),
        'new_item_name' => __( 'Neuer Technik Name' ),
        'menu_name' => __( 'Techniken' ),
    ); 	

    // Labels for taxonomy "Projekte"
    $pLabels = array(
        'name' => _x( 'Projekte', 'taxonomy general name' ),
        'singular_name' => _x( 'Projekt', 'taxonomy singular name' ),
        'search_items' =>  __( 'Suche Projekt' ),
        'all_items' => __( 'Alle Projekte' ),
        'parent_item' => __( 'Übergeordnetes Projekt' ),
        'parent_item_colon' => __( 'Übergeordnetes Projekte:' ),
        'edit_item' => __( 'Projekt bearbeiten' ), 
        'update_item' => __( 'Projekt aktualisieren' ),
        'add_new_item' => __( 'Neues Projekt hinuzufügen' ),
        'new_item_name' => __( 'Neuer Projekt Name' ),
        'menu_name' => __( 'Projekte' ),
    );
    
    // Labels for taxonomy "Status"
    $sLabels = array(
        'name' => _x( 'Status', 'taxonomy general name' ),
        'singular_name' => _x( 'Status', 'taxonomy singular name' ),
        'search_items' =>  __( 'Suche Status' ),
        'all_items' => __( 'Status anzeigen' ),
        'parent_item' => __( 'Übergeordneter Status' ),
        'parent_item_colon' => __( 'Übergeordneter Status:' ),
        'edit_item' => __( 'Status bearbeiten' ), 
        'update_item' => __( 'Status aktualisieren' ),
        'add_new_item' => __( 'Neuen Status hinuzufügen' ),
        'new_item_name' => __( 'Neuer Status Name' ),
        'menu_name' => __( 'Status' ),
    ); 	

    // Register taxonomy "Status" for CPT "Werke"
    register_taxonomy('werke_status', array('werke'), array(
        'hierarchical' => true,
        'labels' => $sLabels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'werke/status' , 'with_front' => false)
    ));

    // Register taxonomy "Techniken" for CPT "Werke"
     register_taxonomy('werke_techniken', array('werke'), array(
        'hierarchical' => true,
        'labels' => $tLabels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'werke/techniken', 'with_front' => false)
    ));

    // Register taxonomy "Projekte" for CPT "Werke"
    register_taxonomy('werke_projekte', array('werke'), array(
        'hierarchical' => true,
        'labels' => $pLabels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'werke/projekte', 'with_front' => false)
    ));
}

/**
 * Register custom taxonomys for CPT "Ausstellungen".
 * Status: Status der Ausstellungen wie Angekündigt, Laufend oder Abgeschlossen.
 * Projekte: Projektzugehörigkeit der Ausstellung.
 */
function db_register_custom_taxonomy_ausstellungen() {
    // Labels for taxonomy "Status"
    $sLabels = array(
        'name' => _x( 'Status', 'taxonomy general name' ),
        'singular_name' => _x( 'Status', 'taxonomy singular name' ),
        'search_items' =>  __( 'Suche Status' ),
        'all_items' => __( 'Status anzeigen' ),
        'parent_item' => __( 'Übergeordneter Status' ),
        'parent_item_colon' => __( 'Übergeordneter Status:' ),
        'edit_item' => __( 'Status bearbeiten' ), 
        'update_item' => __( 'Status aktualisieren' ),
        'add_new_item' => __( 'Neuen Status hinuzufügen' ),
        'new_item_name' => __( 'Neuer Status Name' ),
        'menu_name' => __( 'Status' ),
    );

    // Labels for taxonomy "Projekte"
    $pLabels = array(
        'name' => _x( 'Projekte', 'taxonomy general name' ),
        'singular_name' => _x( 'Projekt', 'taxonomy singular name' ),
        'search_items' =>  __( 'Suche Projekt' ),
        'all_items' => __( 'Alle Projekte' ),
        'parent_item' => __( 'Übergeordnetes Projekt' ),
        'parent_item_colon' => __( 'Übergeordnetes Projekte:' ),
        'edit_item' => __( 'Projekt bearbeiten' ), 
        'update_item' => __( 'Projekt aktualisieren' ),
        'add_new_item' => __( 'Neues Projekt hinuzufügen' ),
        'new_item_name' => __( 'Neuer Projekt Name' ),
        'menu_name' => __( 'Projekte' ),
    );

    // Register taxonomy "Status" for CPT "Ausstellungen"
    register_taxonomy('ast_status', array('ausstellungen'), array(
        'hierarchical' => true,
        'labels' => $sLabels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'ausstellungen/status', 'with_front' => false)
    ));

    // Register taxonomy "Projekte" for CPT "Ausstellungen"
    register_taxonomy('ast_projekte', array('ausstellungen'), array(
        'hierarchical' => true,
        'labels' => $pLabels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'ausstellungen/projekte', 'with_front' => false),
    ));
}

function debug_output($msg, $var) {
    echo "<br>" . $msg . ": " . $var . "<br>";
}
?>

