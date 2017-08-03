<?php

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

final class Wicked_Folders_Ajax {

	private static $instance;

	private function __construct() {

		add_action( 'wp_ajax_wicked_folders_save_state', 	array( $this, 'ajax_save_state' ) );
		add_action( 'wp_ajax_wicked_folders_move_object', 	array( $this, 'ajax_move_object' ) );
		add_action( 'wp_ajax_wicked_folders_add_folder', 	array( $this, 'ajax_add_folder' ) );
		add_action( 'wp_ajax_wicked_folders_edit_folder', 	array( $this, 'ajax_edit_folder' ) );
		add_action( 'wp_ajax_wicked_folders_delete_folder', array( $this, 'ajax_delete_folder' ) );
		add_action( 'wp_ajax_wicked_folders_get_contents', 	array( $this, 'ajax_get_folder_contents' ) );
		add_action( 'wp_ajax_wicked_folders_save_folder', 	array( $this, 'ajax_save_folder' ) );

	}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new Wicked_Folders_Ajax();
		}
		return self::$instance;
	}

	/**
	 * Admin AJAX callback for moving an item to a new folder.
	 *
	 * @uses Wicked_Folders::move_object
	 * @see Wicked_Folders::move_object
	 */
	public function ajax_move_object() {

		$result 				= array( 'error' => false, 'items' => array() );
		$nonce 					= isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : false;
		$object_type 			= isset( $_REQUEST['object_type'] ) ? $_REQUEST['object_type'] : false;
		$object_id 				= isset( $_REQUEST['object_id'] ) ? $_REQUEST['object_id'] : false;
		$destination_object_id 	= isset( $_REQUEST['destination_object_id'] ) ? (int) $_REQUEST['destination_object_id'] : false;
		$source_folder_id 		= isset( $_REQUEST['source_folder_id'] ) ? (int) $_REQUEST['source_folder_id'] : false;

		/*
		if ( ! wp_verify_nonce( $nonce, 'wicked_folders_move_object' ) ) {
			$result['error'] = true;
		}
		*/

		if ( ! $object_type || ! false === $object_id || ! false === $destination_object_id ) {
			$result['error'] = true;
		}

		if ( ! $result['error'] ) {
			$object_id = ( array ) $object_id;
			foreach ( $object_id as $id ) {
				Wicked_Folders::move_object( $object_type, ( int ) $id, $destination_object_id, $source_folder_id );
			}
		}

		echo json_encode( $result );

		wp_die();

	}

	public function ajax_save_state() {

		$result = array( 'error' => false );
		$data 	= json_decode( file_get_contents( 'php://input' ) );
		$nonce 	= $data->nonce;
		$screen = $data->screen;
		$state 	= new Wicked_Folders_Screen_State( $screen, get_current_user_id() );

		$state->folder 				= $data->folder->id;
		$state->folder_type 		= $data->folder->type;
		$state->expanded_folders 	= $data->expanded;
		$state->tree_pane_width 	= $data->treePaneWidth;
		$state->hide_assigned_items = $data->hideAssignedItems;

		$state->save();

		echo json_encode( $result );

		wp_die();

	}

	public function ajax_add_folder() {

		$this->ajax_edit_folder();

	}

	public function ajax_edit_folder() {

		$result 	= array( 'error' => false, 'message' => __( 'An error occurred. Please try again.', 'wicked-folders' ) );
		$nonce  	= isset( $_REQUEST['nounce'] ) ? $_REQUEST['nounce'] : false;
		$id 		= isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;
		$name 		= isset( $_REQUEST['name'] ) ? $_REQUEST['name'] : false;
		$parent 	= isset( $_REQUEST['parent'] ) ? $_REQUEST['parent'] : false;
		$post_type 	= isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : false;
		$tax_name 	= 'wicked_' . $post_type . '_folders';
		$url 		= admin_url( 'edit.php?post_type=' . $post_type . '&page=' . $tax_name );

		//if ( ! wp_verify_nonce( $nonce, 'wicked_folders_add_folder' ) ) {
		//	$result['error'] = true;
		//}

		if ( ! $name || ! $post_type ) {
			$result['message'] = __( 'Invalid name or post type.', 'wicked-folders' );
			$result['error'] = true;
		}

		if ( -1 == $parent  || false === $parent ) {
			$parent = 0;
		}

		if ( ! $result['error'] ) {
			if ( $id ) {
				$existing_term = get_term_by( 'name', $name, $tax_name );
				// Don't allow terms with the same name at the same level
				if ( $existing_term && $existing_term->parent == $parent ) {
					$term = new WP_Error( 'term_exists' );
				} else {
					$term = wp_update_term( $id, $tax_name, array(
						'name' 		=> $name,
						'parent' 	=> $parent,
					) );
				}
			} else {
				$term = wp_insert_term( $name, $tax_name, array(
					'parent' => $parent,
				) );
			}
			if ( is_wp_error( $term ) ) {
				if ( isset( $term->errors['term_exists'] ) ) {
					$result['message'] = __( 'A folder with that name already exists in the selected parent folder. Please enter a different name or select a different parent folder.', 'wicked-folders' );
				} else {
					$result['message'] = $term->get_error_message();
				}
				$result['error'] = true;
			} else {
				$select = wp_dropdown_categories( array(
					'orderby'           => 'name',
					'order'             => 'ASC',
					'show_option_none'  => '&mdash; ' . __( 'Parent Folder', 'wicked-folders' ) . ' &mdash;',
					'taxonomy'          => $tax_name,
					'depth'             => 0,
					'hierarchical'      => true,
					'hide_empty'        => false,
					'selected'          => $parent,
					'echo' 				=> false,
					'option_none_value' => 0,
				) );
				$result = array(
					'error' 	=> false,
					'folderId' 	=> $term['term_id'],
					'folderUrl' => add_query_arg( 'folder', $term['term_id'], $url ),
					'select' 	=> $select,
				);
			}
		}

		echo json_encode( $result );

		wp_die();

	}

	public function ajax_delete_folder() {

		// TODO: check nonce
		$result 	= array( 'error' => false );
		$nonce  	= isset( $_REQUEST['nounce'] ) ? $_REQUEST['nounce'] : false;
		$id 		= isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;
		$post_type 	= isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : false;
		$taxonomy 	= isset( $_REQUEST['taxonomy'] ) ? $_REQUEST['taxonomy'] : 'wicked_' . $post_type . '_folders';

		$delete_result = wp_delete_term( $id, $taxonomy );

		if ( is_wp_error( $delete_result ) ) {
			$result['error'] 	= true;
			$result['message'] 	= $delete_result->get_error_message();
		}

		echo json_encode( $result );

		wp_die();

	}

	public function ajax_get_folder_contents() {

		//add_filter( 'manage_pages_columns', array( $this, 'page_folder_view_columns' ) );

		// TODO: figure out if we can get WordPress to respect the items per
		// page setting for the post type without this filter
		add_filter( 'edit_posts_per_page', function( $per_page, $post_type ) {
			if ( ! empty( $_REQUEST['items_per_page'] ) ) {
				$per_page = ( int ) $_REQUEST['items_per_page'];
			}
			return $per_page;
		}, 10, 2 );

		if ( is_plugin_active( 'wicked-folders-pro/wicked-folders-pro.php' ) && 'attachment' == $post_type ) {
			$wp_list_table = new Wicked_Folders_Pro_Media_List_Table( array(
				//'screen' => $_GET['screen'],
			) );
		} else {
			$wp_list_table = new Wicked_Folders_Posts_List_Table( array(
				'screen' => $_GET['screen'],
			) );
		}

		$wp_list_table->get_columns();
		$wp_list_table->prepare_items();
		$wp_list_table->display();

		wp_die();

	}

	public function ajax_save_folder() {

		$response 	= array( 'error' => false );
		//$method 	= $_SERVER['REQUEST_METHOD'];
		$method 	= isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : 'POST';
		$method 	= isset( $_REQUEST['_method_override'] ) ? $_REQUEST['_method_override'] : $method;
		$folder		= json_decode( file_get_contents( 'php://input' ) );

		// Insert folder
		if ( 'POST' == $method ) {
			$term = wp_insert_term( $folder->name, $folder->taxonomy, array(
				'parent' => $folder->parent,
			) );
			if ( ! is_wp_error( $term ) ) {
				$folder->id = ( string ) $term['term_id'];
			}
		}

		// Update folder
		if ( 'PUT' == $method ) {
			$term = wp_update_term( $folder->id, $folder->taxonomy, array(
				'name' 		=> $folder->name,
				'parent' 	=> $folder->parent,
			) );
		}

		// Delete folder
		if ( 'DELETE' == $method ) {
			$term = wp_delete_term( ( int ) $_REQUEST['id'], $_REQUEST['taxonomy'] );
		}

		if ( is_wp_error( $term ) ) {
			if ( isset( $term->errors['term_exists'] ) ) {
				$response['message'] = __( 'A folder with that name already exists in the selected parent folder. Please enter a different name or select a different parent folder.', 'wicked-folders' );
			} else {
				$response['message'] = $term->get_error_message();
			}
			$response['error'] = true;
			status_header( 400 );
			echo json_encode( $response );
			die();
		} else {
			echo json_encode( $folder );
		}

		wp_die();

	}

}
