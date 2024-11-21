<?php
/*
Plugin Name: My Book Plugin
Plugin URI: http://example.com/my-book-plugin
Description: A plugin to manage books on your WordPress site.
Version: 1.0.0
Author: Julio Cesar
Author URI: http://example.com
License: MIT License
Text Domain: my-book-plugin
*/

function my_book_plugin_enqueue_styles() {
    wp_enqueue_style( 'my-book-plugin-style', plugins_url( 'my-book-plugin.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'my_book_plugin_enqueue_styles' );


function my_book_plugin_template( $template ) {
    global $post;

    if ( $post->post_type == 'libro' ) {
        $template = plugin_dir_path( __FILE__ ) . 'single-libro.php';
    }

    return $template;
}

add_filter( 'single_template', 'my_book_plugin_template' );

function register_post_type_book() {
    $labels = array(
        'name'                  => 'Libros',
        'singular_name'         => 'Libro',
        'menu_name'             => 'Mis Libros',
        'name_admin_bar'        => 'Libro',
        'add_new'               => 'Agregar Libro',
        'add_new_item'          => 'Agregar Nuevo Libro',
        'edit_item'             => 'Editar Libro',
        'new_item'              => 'Nuevo Libro',
        'view_item'             => 'Ver Libro',
        'view_items'            => 'Ver Libros',
        'search_items'          => 'Buscar Libros',
        'not_found'             => 'No se encontraron libros',
        'not_found_in_trash'    => 'No se encontraron libros en la papelera',
        'all_items'             => 'Todos los Libros',
        'archives'              => 'Archivos de Libros',
        'attributes'            => 'Atributos de Libro',
        'insert_into_item'     => 'Insertar en libro',
        'uploaded_to_this_item' => 'Subido a este libro',
        'featured_image'        => 'Imagen destacada',
        'set_featured_image'    => 'Establecer imagen destacada',
    );
    $args = array(
		'attributes' 			=> true,
        'label'                 => 'libros',
        'description'           => 'Libros de mi biblioteca',
        'labels'                => $labels,
		'supports' 				=> array( 'title', 'editor', 'thumbnail', 'excerpt', 'attributes', 'author', 'custom-fields', 'page-attributes', 	'comments', 'revisions', 'gutenberg' ),
        'public'                => true,
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 5,
        'menu_icon'            => 'dashicons-book',
		'template' => array(
            array(
                'post_type' => 'libro',
                'status' => 'publish',
                'template' => 'template-book.php',
            ),
        ),
    );
    register_post_type( 'libro', $args );
}

add_action( 'init', 'register_post_type_book' );

function register_taxonomy_gener() {
   $labels = array(
       'name'              => 'Géneros',
       'singular_name'     => 'Género',
       'search_items'      => 'Buscar Géneros',
       'all_items'         => 'Todos los Géneros',
       'parent_item'       => 'Género Padre',
       'parent_item_colon' => 'Género Padre:',
       'edit_item'         => 'Editar Género',
       'update_item'       => 'Actualizar Género',
       'add_new_item'      => 'Agregar Nuevo Género',
       'new_item_name'     => 'Nuevo Género',
       'menu_name'         => 'Géneros',

   );
   $args = array(
       'hierarchical'      => true,
       'labels'            => $labels,
       'show_ui'           => true,
       'show_admin_column' => true,
       'query_var'         => true,
   );
   register_taxonomy( 'genero', 'libro', $args );
}

add_action( 'init', 'register_taxonomy_gener' );

function list_books_shortcode() {
    $args = array(
        'post_type'      => 'libro',
        'posts_per_page' => -1,
        'order'          => 'ASC',
    );

    if (isset($_GET['s']) || isset($_GET['autor']) || isset($_GET['genero'])) {
        $args['s'] = sanitize_text_field($_GET['s']);
    }

    $the_query = new WP_Query($args);

    ob_start();
	
    if ($the_query->have_posts()) : ?>
        <ul class="book-list">
            <?php while ($the_query->have_posts()) : $the_query->the_post(); ?>
                <li class="book-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <img class="book-thumbnail" src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
                    <?php endif; ?>
                    <h2 class="book-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p class="book-genre">Género: <?php echo implode(', ', wp_get_post_terms(get_the_ID(), 'genero', array('fields' => 'names'))); ?></p>
                    <p class="book-author">Autor: <?php the_field('autor'); ?></p>
                    <p class="book-date">Fecha de publicación: <?php the_field('fecha_de_publicacion'); ?></p>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php wp_reset_postdata();
    else :
        echo '<p>No se encontraron libros.</p>';
    endif;
    
    return ob_get_clean();
}

add_shortcode( 'list_books', 'list_books_shortcode' );

function book_search_form() {
    ob_start(); ?>
    <form action="" method="get" class="book-search-form">
        <input type="text" name="s" placeholder="Buscar por título" value="<?php echo get_search_query(); ?>" class="search-input">
        <input type="text" name="autor" placeholder="Buscar por autor" class="search-input">
        <select name="genero" class="search-select">
            <option value="">Seleccionar género</option>
            <?php
            $generos = get_terms('genero');
            foreach ($generos as $genero) {
                echo '<option value="' . esc_attr($genero->slug) . '">' . esc_html($genero->name) . '</option>';
            }
            ?>
        </select>
        <input type="submit" value="Buscar" class="search-button">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('book_search_form', 'book_search_form');


function custom_book_search_query($query) {
    if ($query->is_search && !is_admin()) {
        $meta_query = array('relation' => 'OR');

        if (!empty($_GET['autor'])) {
            $meta_query[] = array(
                'key'     => 'autor',
                'value'   => sanitize_text_field($_GET['autor']),
                'compare' => 'LIKE',
            );
        }

        if (!empty($_GET['genero'])) {
            $tax_query = array(
                array(
                    'taxonomy' => 'genero',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET['genero']),
                )
            );
            $query->set('tax_query', $tax_query);
        }

        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }
    }
}
add_action('pre_get_posts', 'custom_book_search_query');

function register_pages() {
    if ( ! get_page_by_title( 'Mis Libros' ) ) {
        $page_data = array(
            'post_title'    => 'Mis Libros',
            'post_content'  => '[book_search_form][list_books]',
            'post_status'    => 'publish',
            'post_type'     => 'page',
        );
        wp_insert_post( $page_data );
    }
}
add_action( 'init', 'register_pages' );