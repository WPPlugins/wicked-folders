<?php

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

final class Wicked_Folders_Admin {

	private static $instance;
	private static $admin_notices = array();

	private function __construct() {

		$post_types = Wicked_Folders::post_types();

		add_action( 'admin_enqueue_scripts',				array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init',							array( $this, 'admin_init' ) );
		add_action( 'admin_menu',							array( $this, 'admin_menu' ), 10000 );
		add_action( 'pre_get_posts',						array( $this, 'pre_get_posts' ) );
		add_action( 'admin_notices', 						array( $this, 'admin_notices' ) );
		add_action( 'manage_posts_custom_column' , 			array( $this, 'post_custom_column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', 				array( $this, 'restrict_manage_posts' ) );
		add_action( 'wp_before_admin_bar_render', 			array( $this, 'wp_before_admin_bar_render' ), 10000 );
		add_action( 'wp_enqueue_media', 					array( $this, 'wp_enqueue_media' ) );

		add_filter( 'wpseo_primary_term_taxonomies', 		array( $this, 'wpseo_primary_term_taxonomies' ), 3, 10 );
		add_filter( 'wp_terms_checklist_args', 				array( $this, 'wp_terms_checklist_args' ), 10, 2 );
		add_filter( 'post_row_actions', 					array( $this, 'post_row_actions' ), 10, 2);
		add_filter( 'page_row_actions', 					array( $this, 'page_row_actions' ), 10, 2);
		add_filter( 'admin_body_class', 					array( $this, 'admin_body_class' ) );
		add_filter( 'admin_footer_text', 					array( $this, 'admin_footer_text' ) );
		add_filter( 'update_footer', 						array( $this, 'update_footer' ), 20 );

		add_filter( 'plugin_action_links_wicked-folders/wicked-folders.php', array( $this, 'plugin_action_links' ) );

		if ( in_array( 'page', $post_types ) ) {
			add_filter( 'manage_pages_page_wicked_page_folders_columns', 	array( $this, 'page_folder_view_columns' ) );
			add_action( 'manage_pages_custom_column', 						array( $this, 'page_custom_column_content' ), 10, 2 );
		}

	}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new Wicked_Folders_Admin();
		}
		return self::$instance;
	}

	public static function add_admin_notice( $message, $class = 'notice notice-success' ) {

		// notice-success
		// notice-warning
		// notice-error
		Wicked_Folders_Admin::$admin_notices[] = array(
			'message' 	=> $message,
			'class' 	=> $class,
		);

	}

	public function admin_notices() {
		foreach ( Wicked_Folders_Admin::$admin_notices as $notice ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', $notice['class'], $notice['message'] );
		}
	}

	public function admin_body_class() {
		if ( $this->is_folders_page() ) {
			return 'wicked-folders-page';
		}
	}

	public function admin_footer_text( $text ) {

		if ( $this->is_folders_page() ) {
			$link = '<a href="https://wordpress.org/support/plugin/wicked-folders/reviews/#new-post" target="_blank">' . __( 'rate Wicked Folders', 'wicked-folders' ) . ' <span class="stars">★★★★★</span><span class="screen-reader-text">' . __( 'five stars', 'wicked-folders' ) . '</span></a>';
			$text = sprintf(
				__( 'Thanks for using Wicked Folders! Please %1$s to help spread the word!', 'wicked-folders' ),
				$link
			);
			$text = '<span id="wicked-footer-thankyou">' . $text . '</span>';
		}

		return $text;

	}

	public function update_footer( $content ) {

		if ( $this->is_folders_page() ) {
			$content = '';
		}

		return $content;

	}

    public function admin_enqueue_scripts() {

		wp_register_script( 'wicked-folders-app', plugin_dir_url( dirname( __FILE__ ) ) . 'js/app.js', array( 'jquery', 'jquery-ui-resizable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'backbone' ), Wicked_Folders::plugin_version() );

		wp_localize_script( 'wicked-folders-app', 'wickedFoldersL10n', array(
			'allMedia' 					=> __( 'All media', 'wicked-folders' ),
			'allFolders' 				=> __( 'All folders', 'wicked-folders' ),
			'delete' 					=> __( 'Delete', 'wicked-folders' ),
			'addNewFolderLink' 			=> __( 'Add New Folder', 'wicked-folders' ),
			'editFolderLink' 			=> __( 'Edit Folder', 'wicked-folders' ),
			'deleteFolderLink' 			=> __( 'Delete Folder', 'wicked-folders' ),
			'folderSelectDefault' 		=> __( 'Parent Folder', 'wicked-folders' ),
			'expandAllFoldersLink' 		=> __( 'Expand All', 'wicked-folders' ),
			'collapseAllFoldersLink' 	=> __( 'Collapse All', 'wicked-folders' ),
			'save' 						=> __( 'Save', 'wicked-folders' ),
			'deleteFolderConfirmation' 	=> __( "Are you sure you want to delete the selected folder? Sub folders will be assigned to the folder's parent. Items in the folder will not be deleted.", 'wicked-folders' ),
			'hideAssignedItems' 		=> __( 'Hide assigned items', 'wicked-folders' ),
			'hideAssignedItemsTooltip' 	=> __( "Check this box to hide items that have already been assigned to one or more folders.  This can be useful for determining which items you haven't already placed in a folder.", 'wicked-folders' ),
			'folders' 					=> __( 'Folders', 'wicked-folders' ),
			'attachmentFolders' 		=> __( 'Attachment Folders', 'wicked-folders' ),
			'toggleFolders'				=> __( 'Toggle folders', 'wicked-folders' ),
			'cancel'					=> __( 'Cancel', 'wicked-folders' ),
			'folderName'				=> __( 'Folder Name', 'wicked-folders' ),
			'assignToFolder'			=> __( 'Assign to folder...', 'wicked-folders' ),
		) );

		wp_register_style( 'wicked-folders-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin.css?', array(), Wicked_Folders::plugin_version() );

		wp_enqueue_script( 'wicked-folders-app' );
		wp_enqueue_style( 'wicked-folders-admin' );

		if ( Wicked_Folders_Admin::is_folders_page() ) {

			wp_enqueue_script( 'sticky-kit', plugin_dir_url( dirname( __FILE__ ) ) . 'vendor/sticky-kit/jquery.sticky-kit.min.js' );

		}

	}

	public function wp_enqueue_media() {

		// TODO: refactor and enqueue scripts here as well rather than calling
		// admin_enqueue_scripts which may load unnecessary scripts
		$this->admin_enqueue_scripts();

	}

	public function get_screen_state( $screen_id, $user_id = false ) {

		// TODO: consider statically caching screen states

		if ( ! $user_id ) $user_id = get_current_user_id();

		return new Wicked_Folders_Screen_State( $screen_id, $user_id );

	}

    public function admin_init() {

 		$post_type = Wicked_Folders_Admin::get_current_screen_post_type();

		// TODO: consider work-around for post types that are submenus of other
		// post types (for example, Venues under Events); possibly override
		// $_GET['post_type']
		if ( Wicked_Folders_Admin::is_folders_page() ) {
			// $post_type is not set at this point yet when the post type is 'post'
			if ( ! $post_type ) $post_type = 'post';
			$slug = 'wicked_' . $post_type . '_folders';
			add_filter( 'manage_' . Wicked_Folders_Admin::get_screen_id_by_menu_slug( $slug ) . '_columns', array( $this, 'post_folder_view_columns' ) );
		}

	}

	/**
	 * Returns the post type from the page querystring parameter.
	 */
	public static function folder_page_post_type() {
		$post_type = false;
		// Assumes page is in format wicked_{$post_type}_folders
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( preg_match( '/^wicked_([A-Z0-9_\-]*)_folders$/i', $page ) ) {
			// Remove wicked_ prefix
			$post_type = substr( $page, 7 );
			// Remove _folders suffix
			$post_type = substr( $post_type, 0, -8 );
		}
		return $post_type;
	}

	/**
	 * Based on https://gist.github.com/bradvin/1980309.
	 */
	public static function get_current_screen_post_type() {
		global $post, $typenow, $current_screen;

		// We have a post so we can just get the post type from that
		if ( $post && $post->post_type ) {
			return $post->post_type;
		} elseif( $typenow ) {
			// Check the global $typenow - set in admin.php
			return $typenow;
		} elseif( $current_screen && $current_screen->post_type ) {
			// Check the global $current_screen object - set in sceen.php
			if ( 'media_page_wicked_attachment_folders' == $current_screen->id ) {
				return 'attachment';
			} else {
				return $current_screen->post_type;
			}
		} elseif( isset( $_REQUEST['post_type'] ) && ! is_array( $_REQUEST['post_type'] ) ) {
			// Lastly check the post_type querystring
			return sanitize_key( $_REQUEST['post_type'] );
		}

		// We do not know the post type!
		return false;

	}

	/**
	 * Returns a screen ID for a given admin menu slug.  Note: this function
	 * must be called in admin_init or later.
	 *
	 * @param $slug
	 *  The slug that was used when the page was registered with add_menu_page
	 *  or add_submenu_page.
	 *
	 * @return string
	 *  A screen ID.
	 */
	public static function get_screen_id_by_menu_slug( $slug ) {
		global $_parent_pages;
	    $parent = is_array( $_parent_pages ) && array_key_exists( $slug, $_parent_pages ) ? $_parent_pages[ $slug ] : '';
	    return get_plugin_page_hookname( $slug, $parent );
	}

	public static function is_folders_page() {

		$post_types = Wicked_Folders::post_types();
		$screen 	= function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		// AJAX requests
		if ( isset( $_GET['action'] ) && 'wicked_folders_get_contents' == $_GET['action'] ) {
			return true;
		}

		foreach ( $post_types as $post_type ) {
			if ( empty( $screen ) ) {
				// In case it's too early for get_current_screen()...
				if ( isset( $_GET['page'] ) && false !== strpos( $_GET['page'], 'wicked_' . $post_type . '_folders' ) ) {
					return true;
				}
			} elseif ( false !== strpos( $screen->id, 'wicked_' . $post_type . '_folders' ) ) {
				return true;
			}
		}

		return false;

	}

    public function admin_menu() {

		$menu_items 			= array();
		$post_types 			= Wicked_Folders::post_type_objects();
		$enable_taxonomy_pages 	= get_option( 'wicked_folders_enable_taxonomy_pages', false );
		$filter_args 			= array(
			'post_types' => $post_types,
		);

		foreach ( $post_types as $post_type ) {
			// Folder page menu item
			$menu_item = array(
				'parent_slug' 	=> 'edit.php?post_type=' . $post_type->name,
				'capability' 	=> 'edit_posts',
				'menu_slug' 	=> 'wicked_' . $post_type->name . '_folders',
				'page_title' 	=> sprintf( __( '%1$s Folders', 'wicked-folders' ), $post_type->labels->singular_name ),
				//'menu_title' 	=> sprintf( __( '%1$s Folders', 'wicked-folders' ), $post_type->labels->singular_name ),
				'menu_title' 	=> __( 'Folders', 'wicked-folders' ),
				'callback' 		=> array( $this, 'folders_page' ),
			);
			if ( is_string( $post_type->show_in_menu ) ) {
				$menu_item['parent_slug'] = $post_type->show_in_menu;
			}
			if ( 'post' == $post_type->name ) {
				$menu_item['parent_slug'] = 'edit.php';
			}
			if ( $post_type->_builtin ) {
				$menu_item['page_title'] = __( 'Folders', 'wicked-folders' );
				$menu_item['menu_title'] = __( 'Folders', 'wicked-folders' );
			}
			$menu_items[] = $menu_item;

			if ( 'attachment' == $post_type->name ) continue;

			if ( $enable_taxonomy_pages ) {
				// Folder management (i.e. folder taxonomy) menu item
				$taxonomy 	= 'wicked_' . $post_type->name . '_folders';
				$menu_item 	= array(
					'parent_slug' 	=> 'edit.php?post_type=' . $post_type->name,
					'capability' 	=> 'edit_posts',
					'menu_slug' 	=> 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . $post_type->name,
					'page_title' 	=> sprintf( __( 'Manage %1$s Folders', 'wicked-folders' ), $post_type->labels->singular_name ),
					'menu_title' 	=> sprintf( __( 'Manage %1$s Folders', 'wicked-folders' ), $post_type->labels->singular_name ),
					'callback' 		=> null,
					'taxonomy' 		=> $taxonomy,
				);
				if ( 'post' == $post_type->name ) {
					$menu_item['parent_slug'] = 'edit.php';
				}
				if ( is_string( $post_type->show_in_menu ) ) {
					$menu_item['parent_slug'] = $post_type->show_in_menu;
				}
				if ( $post_type->_builtin ) {
					$menu_item['page_title'] = __( 'Manage Folders', 'wicked-folders' );
					$menu_item['menu_title'] = __( 'Manage Folders', 'wicked-folders' );
				}
				$menu_items[] = $menu_item;
			}
		}

		$menu_items = apply_filters( 'wicked_folders_admin_menu_items', $menu_items, $filter_args );

		foreach ( $menu_items as $menu_item ) {
			add_submenu_page( $menu_item['parent_slug'], $menu_item['page_title'], $menu_item['menu_title'], $menu_item['capability'], $menu_item['menu_slug'], $menu_item['callback'] );
		}

		// Add menu item for settings page
		$parent_slug 	= 'options-general.php';
		$page_title 	= __( 'Wicked Folders Settings', 'wicked-folders' );
		$menu_title 	= __( 'Wicked Folders', 'wicked-folders' );
		$capability 	= 'manage_options';
		$menu_slug 		= 'wicked_folders_settings';
		$callback 		= array( $this, 'settings_page' );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback );

    }

	/**
	 * Column headers for 'post' type.
	 */
	public function manage_posts_columns( $columns ) {
		return array(
			'wicked_move' 	=> '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div></div>',
			'cb' 			=> '<input type="checkbox" />',
			'title' 		=> 'Title',
			'author' 		=> 'Author',
			'date' 			=> 'Date',
		);
	}

	/**
	 * manage_pages_page_wicked_page_folders_columns filter.
	 *
	 * @return array
	 *  Array of columns when viewing pages in folder view.
	 */
	public function page_folder_view_columns( $columns ) {
		//$columns = apply_filters( 'manage_pages_columns', $columns );
		return array(
			'wicked_move' 	=> '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div></div>',
			'cb' 			=> '<input type="checkbox" />',
			'title' 		=> 'Title',
			'author' 		=> 'Author',
			'date' 			=> 'Date',
		);
	}

	/**
	 * Column header filter for non-page post types.
	 */
	public function post_folder_view_columns( $columns ) {

		return array(
			'wicked_move' 	=> '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div></div>',
			'cb' 			=> '<input type="checkbox" />',
			'title' 		=> 'Title',
			'author' 		=> 'Author',
			'date' 			=> 'Date',
		);

		$screen = get_current_screen();

		$wp_list_table = new Wicked_Folders_Posts_List_Table( array(
			'screen' => $screen,
		) );

		$columns = $wp_list_table->get_columns();

		return $columns;

	}

	public function page_custom_column_content( $column_name, $post_id ) {
		if ( 'wicked_move' == $column_name ) {
			echo '<div class="wicked-move-multiple" data-object-id="' . $post_id . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div data-object-id="' . $post_id . '">' . get_the_title() . '</div></div>';
		}
	}

	public function post_custom_column_content( $column_name, $post_id ) {
		if ( 'wicked_move' == $column_name ) {
			echo '<div class="wicked-move-multiple" data-object-id="' . $post_id . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div data-object-id="' . $post_id . '">' . get_the_title() . '</div></div>';
		}
	}

	/**
	 * The main folders page for a post type.
	 */
	public function folders_page() {

		$screen 					= get_current_screen();
		$post_type 					= $screen->post_type;

		if ( 'media_page_wicked_attachment_folders' == $screen->id ) {
			$post_type = 'attachment';
		}

		if ( ! $post_type ) $post_type = 'post';

		$active_folder 				= false;
		$active_folder_ancestors 	= array();
		$user_id 					= get_current_user_id();
		$state 						= $this->get_screen_state( $screen->id );
		$tree_pane_width 			= $state->tree_pane_width;
		$expanded_folders 			= $state->expanded_folders;
		$active_folder_id 			= isset( $_GET['folder'] ) ? $_GET['folder'] : false;
		$taxonomy 					= 'wicked_' . $post_type . '_folders';
		$url 						= menu_page_url( 'wicked_' . $post_type . '_folders', false );
		$post_type_object 			= get_post_type_object( $post_type );
		$show_contents_in_tree_view = ( bool ) get_option( 'wicked_folders_show_folder_contents_in_tree_view', false );
		$folders 					= Wicked_Folders::get_folders( $post_type, $taxonomy );
		$folder_data 				= array();
		$active_folder_type 		= isset( $_GET['folder_type'] ) ? $_GET['folder_type'] : false;;
		$search_submit_label 		= $post_type == 'attachment' ? __( 'Search Media', 'wicked-folders' ) : $post_type_object->labels->search_items;

		// Get items per page
		if ( 'attachment' == $post_type ) {
			$items_per_page = (int) get_user_option( 'upload_' . $post_type . '_per_page' );
		} else {
			$items_per_page = (int) get_user_option( 'edit_' . $post_type . '_per_page' );
		}

		if ( empty( $items_per_page ) || $items_per_page < 1 ) $items_per_page = 20;

		// TODO: come up with a better solution for this or use admin_url instead
		// menu_page_url uses esc_url which causes problems for add_query_arg
		$url = str_replace( '#038;', '&', $url );

		if ( false === $active_folder_id ) {
			$active_folder_id = $state->folder;
		}

		if ( false === $active_folder_type ) {
			$active_folder_type = $state->folder_type;
		}

		// Make sure the folder exists
		if ( ! Wicked_Folders::get_folder( $active_folder_id, $post_type ) && 'Wicked_Folders_Term_Folder' == $active_folder_type ) {
			$active_folder_id = '0';
		}

		// For other folder types, check folders array to make sure folder exists
		if ( 'Wicked_Folders_Term_Folder' != $active_folder_type ) {
			$folder_exists = false;
			foreach ( $folders as $folder ) {
				if ( $folder->id == $active_folder_id ) $folder_exists = true;
			}
			if ( ! $folder_exists ) $active_folder_id = '0';
		}

		// Query filters rely on $_GET parameters
		$_GET['folder'] 		= $active_folder_id;
		$_GET['folder_type'] 	= $active_folder_type;

		if ( ! isset( $_GET['hide_assigned'] ) ) {
			$_GET['hide_assigned'] = $state->hide_assigned_items;
		}

		foreach ( $folders as &$folder ) {
			$folder->type 	= get_class( $folder );
			$folder->posts 	= array();
			if ( $show_contents_in_tree_view ) {
				// TODO: defer fetching posts for collapsed folders until they
				// are expanded in the UI
				$posts = $folder->fetch_posts();
				foreach ( $posts as $post ) {
					$folder->posts[] = array(
						'id' 	=> $post->ID,
						'name' 	=> $post->post_title,
						'type' 	=> $post->post_type,
					);
				}
			}
		}

		if ( is_plugin_active( 'wicked-folders-pro/wicked-folders-pro.php' ) && 'attachment' == $post_type ) {
			$wp_list_table = new Wicked_Folders_Pro_Media_List_Table( array(
				'screen' => $screen,
			) );
		} else {
			$wp_list_table = new Wicked_Folders_Posts_List_Table( array(
				'screen' => $screen,
			) );
		}

		$wp_list_table->get_columns();
		$wp_list_table->prepare_items();

		/*
		$tree_view = new Wicked_Folders_Tree_View( $post_type, $taxonomy );
		$tree_view->add_folders( $folders );
		$tree_view->expanded_folder_ids = $expanded_folders;
		$tree_view->active_folder_id = $active_folder_id;
		$tree_view->fetch_objects = get_option( 'wicked_folders_show_folder_contents_in_tree_view', false );

		$active_folder = $tree_view->get_folder( $active_folder_id );

		$active_folder_ancestors = $tree_view->get_ancestors( $active_folder_id );
		$active_folder_ancestors = array_reverse( $active_folder_ancestors );
		*/

		include( dirname( dirname( __FILE__ ) ) . '/admin-templates/folder-page.php' );

	}

	public function settings_page() {

		$active_tab 	= 'general';
		$is_pro_active 	= is_plugin_active( 'wicked-folders-pro/wicked-folders-pro.php' );

		if ( ! empty( $_GET['tab'] ) ) {
			$active_tab = $_GET['tab'];
		}

		$tabs = array(
			array(
				'label' 	=> __( 'Settings', 'wicked-folders' ),
				'callback' 	=> array( $this, 'settings_page_general' ),
				'slug'		=> 'general',
			),
		);

		$tabs = apply_filters( 'wicked_folders_setting_tabs', $tabs );

		include( dirname( dirname( __FILE__ ) ) . '/admin-templates/settings-page.php' );

	}

	public function settings_page_general() {

		$is_pro_active 						= is_plugin_active( 'wicked-folders-pro/wicked-folders-pro.php' );
		$enabled_posts_types 				= Wicked_Folders::post_types();
		$dynamic_folders_enabled_posts_types= Wicked_Folders::dynamic_folder_post_types();
		$license_key 						= get_option( 'wicked_folders_pro_license_key', false );
		$show_folder_contents_in_tree_view 	= get_option( 'wicked_folders_show_folder_contents_in_tree_view', false );
		$attachment_post_type 				= get_post_type_object( 'attachment' );
		$post_types 						= get_post_types( array(
			'show_ui' => true,
		), 'objects' );

		if ( $is_pro_active ) {

			$license_data 		= get_option( 'wicked_folders_pro_license_data' );
			$license_status 	= '';

			if ( $license_data ) {
				if ( 'valid' == $license_data->license ) {
					$expiration = strtotime( $license_data->expires );
					if ( 'lifetime' == $license_data->expires ) {
						$license_status = '<em style="color: green;">' . __( 'Valid', 'wicked-folders' ) . '</em>';
					} else if ( time() > $expiration ) {
						$license_status = '<em style="color: red;">' . __( 'Expired', 'wicked-folders' ) . '</em>';
					} else {
						$license_status = '<em style="color: green;">' . sprintf( __( 'Valid. Expires %1$s.', 'wicked-folders' ), date( 'F j, Y', $expiration ) ). '</em>';
					}
				} else {
					$license_status = '<em style="color: red;">' . __( 'Invalid', 'wicked-folders' ) . '</em>';
				}
			}
		}


		include( dirname( dirname( __FILE__ ) ) . '/admin-templates/settings-page-general.php' );

	}

	public function pre_get_posts( $query ) {

		// Only filter admin queries
		if ( is_admin() ) {

			// Initalize variables
			$filter_query 		= false;
			$folder 			= false;
			$taxonomy 			= false;
			$action 			= isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
			$post_type 			= $query->get( 'post_type' );
			$folder_type 		= isset( $_GET['folder_type'] ) ? $_GET['folder_type'] : 'Wicked_Folders_Term_Folder';

			// Folder type parameter is different for attachment queries
			if ( ! empty( $_REQUEST['query']['wicked_folder_type'] ) ) {
				$folder_type = $_REQUEST['query']['wicked_folder_type'];
			}

			// Only filter certain queries...
			if ( $query->is_main_query() ) $filter_query = true;
			if ( 'query-attachments' == $action && isset( $_REQUEST['query']['wicked_attachment_folders'] ) ) $filter_query  = true;

			// Skip all other queries
			if ( ! $filter_query ) return;

			// If the post type isn't specified in the request, attempt to get
			// it from the screen
			// TODO: determine if this is still necessary now that post type is
			// being pulled form the query rather than the request
			if ( ! $post_type ) {
				if ( function_exists( 'get_current_screen' ) ) {
					$screen 	= get_current_screen();
					$post_type 	= ! empty( $screen->post_type ) ? $screen->post_type : false;
				}
				// If we still don't have a post type but we're on a folders page,
				// default to post
				if ( ! $post_type && $this->is_folders_page() ) {
					$post_type = 'post';
				}
			}

			if ( Wicked_Folders::enabled_for( $post_type ) ) {

				$taxonomy 		= "wicked_{$post_type}_folders";
				$folder 		= isset( $_GET['folder'] ) ? $_GET['folder'] : false;
				$hide_assigned 	= isset( $_GET['hide_assigned'] ) ? $_GET['hide_assigned'] : false;

				// Folder parameter is named differently on post list pages
				if ( isset( $_GET["wicked_{$post_type}_folder_filter"] ) ) {
					$folder = $_GET["wicked_{$post_type}_folder_filter"];
				}

				// Folder parameter in different in query attachment requests
				if ( isset( $_REQUEST['query']['wicked_attachment_folders'] ) ) {
					$folder = $_REQUEST['query']['wicked_attachment_folders'];
				}

				// Check if folder is in type.id format
				if ( false !== $index = strpos( $folder, '.' ) ) {
					$folder_type 	= substr( $folder, 0, $index );
					$folder 		= substr( $folder, $index + 1 );
					// We need to remove the tax query set up by the attachment
					// query when the folder ID is in this format as it won't
					// work
					if ( 'query-attachments' == $action ) {
						Wicked_Folders::remove_tax_query( $query, 'wicked_attachment_folders' );
					}
				}

				// Term folders
				if ( $folder && 'Wicked_Folders_Term_Folder' == $folder_type ) {
					$tax_query = array(
						array(
							'taxonomy' 			=> $taxonomy,
							'field' 			=> 'term_id',
							'terms' 			=> $folder,
							'include_children' 	=> false,
						),
					);
					$query->set( 'tax_query', $tax_query );
				}

				// Dynamic folders
				if ( $folder && $folder_type && 'Wicked_Folders_Term_Folder' != $folder_type && class_exists( $folder_type ) ) {

					// Folder tax queries won't work with dynamic folders so remove
					Wicked_Folders::remove_tax_query( $query, 'wicked_attachment_folders' );

					if ( $folder = Wicked_Folders::get_dynamic_folder( $folder_type, $folder, $post_type, $taxonomy ) ) {
						$folder->pre_get_posts( $query );
					}

				}

				// Hide assigned only applies to root folder
				if ( ! $folder && $hide_assigned ) {

					$folder_ids = get_terms( $taxonomy, array( 'fields' => 'ids', 'hide_empty' => false ) );

					$tax_query = array(
						array(
							'taxonomy' 	=> $taxonomy,
							'field' 	=> 'term_id',
							'terms' 	=> $folder_ids,
							'operator' 	=> 'NOT IN',
						),
					);

					$query->set( 'tax_query', $tax_query );

				}

			}

		}

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

		if ( ! wp_verify_nonce( $nonce, 'wicked_folders_move_object' ) ) {
			$result['error'] = true;
		}

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

		$user_id 			= get_current_user_id();
		$result 			= array( 'error' => false );
		$nonce 				= isset( $_REQUEST['nounce'] ) ? $_REQUEST['nounce'] : false;
		$screen_id 			= isset( $_REQUEST['screen_id'] ) ? $_REQUEST['screen_id'] : false;
		$folder 			= isset( $_REQUEST['folder'] ) ? $_REQUEST['folder'] : false;
		$expanded_folders 	= isset( $_REQUEST['expanded_folders'] ) ? $_REQUEST['expanded_folders'] : array();
		$tree_pane_width 	= isset( $_REQUEST['tree_pane_width'] ) ? $_REQUEST['tree_pane_width'] : false;
		$state 				= get_user_meta( $user_id, 'wicked_folders_plugin_state', true );

		// If state doesn't exist, initalize it to an empty array
		if ( ! is_array( $state ) ) $state = array();

		// Initialize screen state if not already present
		if ( ! isset( $state['screens'][ $screen_id ] ) ) {
			$state['screens'][ $screen_id ] = array(
				'tree_pane_width' 	=> false,
				'folder' 			=> 0,
				'expanded_folders' 	=> array(),
			);
		}

		// Update state
		if ( false !== $folder ) {
			$state['screens'][ $screen_id ]['folder'] = $folder;
		}

		if ( $expanded_folders ) {
			$state['screens'][ $screen_id ]['expanded_folders'] = $expanded_folders;
		}

		if ( $tree_pane_width ) {
			$state['screens'][ $screen_id ]['tree_pane_width'] = $tree_pane_width;
		}

		update_user_meta( $user_id, 'wicked_folders_plugin_state', $state );

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

		$result 	= array( 'error' => false, 'message' => __( 'An error occurred. Please try again.', 'wicked-folders' ) );
		$nonce  	= isset( $_REQUEST['nounce'] ) ? $_REQUEST['nounce'] : false;
		$id 		= isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;
		$post_type 	= isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : false;
		$tax_name 	= 'wicked_' . $post_type . '_folders';

		$result = wp_delete_term( $id, $tax_name );

		if ( is_wp_error( $term ) ) {
			$result['error'] 	= true;
			$result['message'] 	= $term->get_error_message();
		} else {
			$result = array(
				'error' 	=> false,
			);
		}

		echo json_encode( $result );

		wp_die();

	}

	public function ajax_get_folder_contents() {

		//add_filter( 'manage_pages_columns', array( $this, 'page_folder_view_columns' ) );

		if ( is_plugin_active( 'wicked-folders-pro/wicked-folders-pro.php' ) && 'attachment' == $post_type ) {
			$wp_list_table = new Wicked_Folders_Pro_Media_List_Table( array(
				'screen' => $screen,
			) );
		} else {
			$wp_list_table = new Wicked_Folders_Posts_List_Table( array(
				'screen' => $_GET['screen'],	//pages_page_wicked_page_folders
			) );
		}

		$wp_list_table->get_columns();
		$wp_list_table->prepare_items();
		$wp_list_table->display();

		wp_die();

	}

	public function post_row_actions( $actions, $post ) {
		return $actions;
	}

	public function page_row_actions( $actions, $post ) {

		if ( Wicked_Folders_Admin::is_folders_page() ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
		}

		return $actions;
	}

	/**
	 * wp_terms_checklist_args filter.
	 */
	public function wp_terms_checklist_args( $args, $post_id ) {

		$taxonomies = Wicked_Folders::taxonomies();

		// Remove Yoast primary category feature from folder taxonomies
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $args['taxonomy'] ) ) {
				if ( $taxonomy == $args['taxonomy'] ) {
					$args['checked_ontop'] = false;
				}
			}
		}

		return $args;

	}

	/**
	 * wpseo_primary_term_taxonomies filter.
	 */
	public function wpseo_primary_term_taxonomies( $taxonomies, $post_type, $all_taxonomies ) {

		$folder_taxonomies = Wicked_Folders::taxonomies();

		// Remove Yoast primary category feature from folder taxonomies
		foreach ( $folder_taxonomies as $taxonomy ) {
			if ( isset( $taxonomies[ $taxonomy ] ) ) {
				unset( $taxonomies[ $taxonomy ] );
			}
		}

		return $taxonomies;

	}

	/**
	 * Handles saving plugin settings.
	 */
	public function save_settings() {

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

		if ( 'wicked_folders_save_settings' == $action && wp_verify_nonce( $_REQUEST['nonce'], 'wicked_folders_save_settings' ) ) {
			$tab = $_REQUEST['wicked_folders_setting_tab'];
			if ( 'general' == $tab ) {
				$post_types 				= ( array ) $_POST['post_type'];
				$dynamic_folder_post_types 	= ( array ) $_POST['dynamic_folder_post_type'];
				$show_folder_contents_in_tree_view = isset( $_POST['show_folder_contents_in_tree_view'] );
				update_option( 'wicked_folders_post_types', $post_types );
				update_option( 'wicked_folders_dynamic_folder_post_types', $dynamic_folder_post_types );
				update_option( 'wicked_folders_show_folder_contents_in_tree_view', $show_folder_contents_in_tree_view );
				Wicked_Folders_Admin::add_admin_notice( __( 'Your changes have been saved.', 'wicked-folders' ) );
			}
		}

	}

	public function restrict_manage_posts( $post_type ) {

		if ( $post_type && Wicked_Folders::enabled_for( $post_type ) ) {

			$folder = 0;

			if ( isset( $_GET["wicked_{$post_type}_folder_filter"] ) ) {
				$folder = ( int ) $_GET["wicked_{$post_type}_folder_filter"];
			}

			wp_dropdown_categories( array(
				'orderby'           => 'name',
				'order'             => 'ASC',
				'show_option_none'  => __( 'All folders', 'wicked-folders' ),
				'taxonomy'          => "wicked_{$post_type}_folders",
				'depth'             => 0,
				'hierarchical'      => true,
				'hide_empty'        => false,
				'option_none_value' => 0,
				'name' 				=> "wicked_{$post_type}_folder_filter",
				'id' 				=> "wicked-{$post_type}-folder-filter",
				'selected' 			=> $folder,
			) );
		}

	}

	public function wp_before_admin_bar_render() {

		global $wp_admin_bar;

		// Only add folders menu to folder browser pages
		if ( ! $this->is_folders_page() ) return;

		if ( ! is_object( $wp_admin_bar ) ) return;

		$post_type = $this->get_current_screen_post_type();

		$url = menu_page_url( 'wicked_' . $post_type . '_folders', false );

		$wp_admin_bar->add_node( array(
			'id' 		=> 'wicked-folders',
			'title' 	=> __( 'Folders', 'wicked-folders' ),
			'href' 		=> $url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' 	=> 'wicked-folders',
			'id' 		=> 'wicked-folders-add-new-folder',
			'title' 	=> __( 'Add New Folder', 'wicked-folders' ),
			'href' 		=> $url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' 	=> 'wicked-folders',
			'id' 		=> 'wicked-folders-edit-folder',
			'title' 	=> __( 'Edit Folder', 'wicked-folders' ),
			'href' 		=> $url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' 	=> 'wicked-folders',
			'id' 		=> 'wicked-folders-delete-folder',
			'title' 	=> __( 'Delete Folder', 'wicked-folders' ),
			'href' 		=> $url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' 	=> 'wicked-folders',
			'id' 		=> 'wicked-folders-expand-all',
			'title' 	=> __( 'Expand All', 'wicked-folders' ),
			'href' 		=> $url,
		) );

		$wp_admin_bar->add_node( array(
			'parent' 	=> 'wicked-folders',
			'id' 		=> 'wicked-folders-collapse-all',
			'title' 	=> __( 'Collapse All', 'wicked-folders' ),
			'href' 		=> $url,
		) );

	}

	public function plugin_action_links( $links ) {

        $settings_link = '<a href="' . esc_url( menu_page_url( 'wicked_folders_settings', 0 ) ) . '">' . __( 'Settings', 'wicked-folders' ) . '</a>';

        array_unshift( $links, $settings_link );

        return $links;

    }

}
