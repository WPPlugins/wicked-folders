<?php

/**
 * Represents a Wicked Folders plugin folder object.
 */
class Wicked_Folders_Folder {

    /**
     * The folder's ID.  The folder ID should be unique for a given post type
     * and taxonomy combination.
     *
     * @var string
     */
    public $id = false;

    /**
     * The ID of the folder's parent.
     *
     * @var string
     */
    public $parent = '0';

    /**
     * The folder's name.
     *
     * @var string
     */
    public $name;

    /**
     * The post type the folder belongs to.
     *
     * @var string
     */
    public $post_type;

    /**
     * The taxonomy the folder belongs to.
     *
     * @var string
     */
    public $taxonomy;

    /**
     * Whether or not the folder can be moved into other folders.
     */
    public $movable = true;

    /**
     * Whether or not the folder can be edited or deleted.
     */
    public $editable = true;

    public function __construct( array $args ) {
        // TODO: throw error if ID argument is set and contains reserved characters
        // such as periods
        $args = wp_parse_args( $args, array(
            'parent'    => '0',
            'name'      => __( 'Untitled folder', 'wicked-folders' ),
        ) );
        foreach ( $args as $property => $arg ) {
            $this->{$property} = $arg;
        }
        /*
        if ( false === $this->id ) {
            throw new Exception( __( 'Folder requires an ID.', 'wicked-folders' ) );
        }
        */
        if ( ! $this->post_type ) {
            throw new Exception( __( 'Folder requires a post type.', 'wicked-folders' ) );
        }
        if ( ! $this->taxonomy ) {
            $this->taxonomy = "wicked_{$this->post_type}_folders";
        }
        // Change IDs to strings so that they compare correctly regardless of type
        $this->id       = ( string ) $this->id;
        $this->parent   = ( string ) $this->parent;
    }

    public function ancestors() {
        return array();
    }

    public function fetch_posts() {
        return array();
    }

}
