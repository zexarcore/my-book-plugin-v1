<?php
/*
Template Name: Single Libro
*/

get_header(); ?>

<div class="book-detail">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post(); ?>
            <h1><?php the_title(); ?></h1>
            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>" class="book-thumbnail">
            <?php endif; ?>
            <p><strong>Género:</strong> <?php echo implode(', ', wp_get_post_terms(get_the_ID(), 'genero', array('fields' => 'names'))); ?></p>
            <p><strong>Autor:</strong> <?php the_field('autor'); ?></p>
            <p><strong>Fecha de publicación:</strong> <?php the_field('fecha_de_publicacion'); ?></p>
            <div class="book-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile;
    else :
        echo '<p>No se encontraron detalles del libro.</p>';
    endif;
    ?>
</div>

<?php get_footer(); ?>