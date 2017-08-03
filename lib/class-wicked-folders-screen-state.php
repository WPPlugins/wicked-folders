<?php

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Holds details about a screen's state.
 */
final class Wicked_Folders_Screen_State {

    public $screen_id           = false;
    public $user_id             = false;
    public $folder              = false;
	public $folder_type 		= 'Wicked_Folders_Term_Folder';
    public $expanded_folders    = array( '0' );
    public $tree_pane_width     = 400;
	public $hide_assigned_items = true;

    public function __construct( $screen_id, $user_id ) {

        $this->screen_id    = $screen_id;
        $this->user_id      = $user_id;

        $state = get_user_meta( $user_id, 'wicked_folders_plugin_state', true );

        if ( isset( $state['screens'][ $screen_id ] ) ) {

            $screen_state = $state['screens'][ $screen_id ];

            if ( isset( $screen_state['folder'] ) ) {
                $this->folder = ( string ) $screen_state['folder'];
            }

			if ( isset( $screen_state['folder_type'] ) ) {
                $this->folder_type = ( string ) $screen_state['folder_type'];
            }

            if ( ! empty( $screen_state['expanded_folders'] ) ) {
                $this->expanded_folders = ( array ) $screen_state['expanded_folders'];
            }

            if ( isset( $screen_state['tree_pane_width'] ) ) {
                $this->tree_pane_width = ( int ) $screen_state['tree_pane_width'];
            }

			if ( isset( $screen_state['hide_assigned_items'] ) ) {
				$this->hide_assigned_items = ( bool ) $screen_state['hide_assigned_items'];
			}

        }

		$this->expanded_folders = array_unique( $this->expanded_folders );

        return $this;

    }

	public function save() {

		$states = get_user_meta( $this->user_id, 'wicked_folders_plugin_state', true );
		$state 	= array(
			'tree_pane_width' 		=> $this->tree_pane_width,
			'folder' 				=> $this->folder,
			'expanded_folders' 		=> $this->expanded_folders,
			'hide_assigned_items' 	=> $this->hide_assigned_items,
			'folder_type' 			=> $this->folder_type,
		);

		if ( ! isset( $states['screens'][ $this->screen_id ] ) ) {
			$states['screens'][ $this->screen_id ] = array();
		}

		$states['screens'][ $this->screen_id ] = $state;

		update_user_meta( $this->user_id, 'wicked_folders_plugin_state', $states );

	}

}
