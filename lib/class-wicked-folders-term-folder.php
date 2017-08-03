<?php

/**
 * Represents a folder object that is represented as a taxonomy term.
 */
class Wicked_Folders_Term_Folder extends Wicked_Folders_Folder {

    public function __construct( $args ) {
        parent::__construct( $args );
    }

    public function ancestors() {
        return get_ancestors( $this->id, $this->taxonomy, 'taxonomy' );
    }

    public function fetch_posts() {

        return get_posts( array(
            'post_type'         => $this->post_type,
            'orderby'           => 'title',
            'order'             => 'ASC',
            'posts_per_page'    => -1,
            'tax_query' => array(
                array(
                    'taxonomy'          => $this->taxonomy,
                    'field'             => 'term_id',
                    'terms'             => ( int )$this->id,
                    'include_children'  => false,
                ),
            ),
        ) );

    }

}
