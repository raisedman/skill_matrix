<?php

/*
Plugin Name: Skill Matrix
Description: Создать матрицу скиллов разработчиков
Version: 1.0
*/
define( 'um_url', plugin_dir_url( __FILE__ ) );

class SkillMatrix {
	public function __construct() {

		add_action( 'admin_head', array( $this, 'register_script_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_user' ) );
		add_action( 'init', array( $this, 'regist_post' ) );
		add_action( 'init', array( $this, 'create_taxonomy' ) );
		register_activation_hook( __FILE__, array( $this, 'add_role' ) );
		add_action( 'show_user_profile', array( $this, 'my_show_extra_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'my_show_extra_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'update_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_user_meta' ) );
		add_action( 'user_new_form', array( $this, 'update_user_meta' ) );
		add_action( 'admin_init', array( $this, 'add_developer' ) );
		add_action( 'admin_init', array( $this, 'add_skill' ) );
		add_action( 'admin_init', array( $this, 'add_category' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'wp_ajax_changelevel', array( $this, 'processing_ajax' ) );
		add_action( 'wp_ajax_addposition', array( $this, 'add_position_developer' ) );
		add_action( 'wp_ajax_categories', array( $this, 'send_category' ) );
		add_action( 'wp_ajax_senddev', array( $this, 'send_data_dev' ) );
		add_shortcode( 'show_skill', array( $this, 'register_show_skill' ) );
	}

	public function send_category() {
		$posts = get_posts( array(
			'tax_query'      => array(
				array(
					'taxonomy' => 'skillcategory',
					'field'    => 'name',
					'terms'    => $_POST['term']
				)
			),
			'post_type'      => 'skill',
			'posts_per_page' => - 1
		) );
		ob_start(); ?>
        <label for="all_skills_developers"
               style="display: block;font-size: 15px; font-style: italic">Skills </label>
        <select id="all_skills_developers" style="width:100px; margin-bottom: 10px;">
            <option value="">Select skill</option>
			<?php foreach ( $posts as $post ) { ?>
                <option value="<?= $post->ID ?>"><?= $post->post_title ?></option>
			<?php } ?>
        </select>
		<?php
		if ( $html = ob_get_clean() ) {
			wp_send_json_success( $html );
		} else {
			wp_send_json_error();
		}

	}

	public function send_data_dev() {  //create json for template of table
		$arrayJson = array();
		if ( ! empty( $_POST['position'] ) ) {
			$developers = get_users(
				array(
					'meta_key'    => 'position',
					'meta_value'  => $_POST['position'],
					'number'      => - 1,
					'count_total' => false
				)
			);
		} elseif ( empty( $_POST['developers'] ) ) {
			$developers = get_users( array( 'role' => 'developer' ) );
			$developers = (array) $developers;
		} else {
			$toarrayDev = (array) $_POST['developers'];
			$developers = array();
			foreach ( $toarrayDev as $dev ) {
				$developers[] = get_user_by( 'id', $dev );
			}
		}
		$arrayDev         = array();
		$arrayPosition    = array();
		$arrayObjCategory = array();
		foreach ( $developers as $developer ) {
			$user            = get_user_by( 'id', $developer->ID );
			$login           = $user->display_name;
			$arrayDev[]      = $login;
			$position        = get_user_meta( $developer->ID, 'position', true );
			$arrayPosition[] = $position;
		}
		if ( empty( $_POST['category'] ) ) {
			$args  = array(
				'taxonomy'   => 'skillcategory',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			);
			$terms = get_terms( $args );
			if ( is_object( $terms ) ) {
				$terms = (array) $terms;
			}
		} else {
			$terms   = array();
			$terms[] = get_term_by( 'name', $_POST['category'], 'skillcategory' );
		}
		foreach ( $terms as $term ) {
			if ( $term->count > 0 ) {
				$objCat      = [ 'category' => $term->name ];
				$loop        = get_posts( array(
					'tax_query'      => array(
						array(
							'taxonomy' => 'skillcategory',
							'field'    => 'name',
							'terms'    => $term->name
						)
					),
					'post_type'      => 'skill',
					'posts_per_page' => - 1
				) );
				$objSkills   = array();
				$arraySkills = array();
				if ( empty( $_POST['skill'] ) ) {
					foreach ( $loop as $post ) {
						$objSkills['skill_name'] = $post->post_title;
						$levelDeveloper          = array();
						$idDevelopers            = array();
						foreach ( $developers as $developer ) {
							$levelDeveloper[] = get_user_meta( $developer->ID, $post->post_title, true );
							$idDevelopers[]   = $developer->ID;
						}
						$objSkills['developer_level'] = $levelDeveloper;
						$objSkills['developer_id']    = $idDevelopers;
						$arraySkills[]                = $objSkills;
					}
				} else {
					$post                    = get_post( $_POST['skill'], OBJECT );
					$objSkills['skill_name'] = $post->post_title;
					$levelDeveloper          = array();
					$idDevelopers            = array();
					foreach ( $developers as $developer ) {
						$levelDeveloper[] = get_user_meta( $developer->ID, $post->post_title, true );
						$idDevelopers[]   = $developer->ID;
					}
					$objSkills['developer_level'] = $levelDeveloper;
					$objSkills['developer_id']    = $idDevelopers;
					$arraySkills[]                = $objSkills;
				}
				$objCat             += [ 'skills' => $arraySkills ];
				$arrayObjCategory[] = $objCat;
			}
		}
		$arrayJson[] = $arrayDev;
		$arrayJson[] = $arrayPosition;
		$arrayJson[] = $arrayObjCategory;
		$arrayJson[] = array( 'none', 'basic', 'good', 'excellent', 'expert' );
		wp_send_json_success( $arrayJson );
	}


	public function processing_ajax() {
		if ( ! update_user_meta( $_POST['id'], $_POST['skill'], $_POST['level'] ) ) {
			wp_send_json_error();
		}
		wp_send_json_success();
	}

	public function add_position_developer() {
		if ( ! update_user_meta( $_POST['developer'], 'position', $_POST['devposition'] ) ) {
			wp_send_json_error();
		}
		wp_send_json_success();
	}

	public function register_script_admin() {
		wp_enqueue_script( 'position-of-developer', um_url . 'assets/js/devposition.js' );
		wp_localize_script('position-of-developer', 'myPlugin',array(
		        'adminUrl'=> admin_url(),
        ));
		wp_register_style( 'chosen-css', um_url . 'assets/css/chosen.min.css' );
		wp_register_style( 'matrix-style', um_url . 'assets/css/style.css' );
		wp_register_script( 'chosen', um_url . 'assets/js/chosen.jquery.min.js' );
		wp_register_script( 'ajax_admin', um_url . 'assets/js/ajax/ajax-template-admin.js', array(
			'jquery',
			'chosen',
			'wp-util',
		) );
		wp_register_script( 'script', um_url . 'assets/js/script.js', array( 'jquery' ) );
	}

	public function enqueue_script_user() {
		wp_enqueue_script( 'ajax_user', um_url . 'assets/js/ajax/ajaxscript_user.js', array( 'jquery' ) );
		wp_localize_script( 'ajax_user', 'myPlugin', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function my_show_extra_profile_fields( $user ) { ?>
        <h3>Input position:</h3>
        <table class="form-table">
            <tr>
                <th><label for="position">Position</label></th>
                <td>
					<?php $args = array(
						'taxonomy'   => 'position',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					);
					$terms      = (array) get_terms( $args ); ?>
                    <select id="position" name="position">
						<?php foreach ( $terms as $position ) { ?>
                            <option <?php if ( get_the_author_meta( 'position', $user->ID ) == $position->name ) {
								echo 'selected';
							} ?> ><?php echo $position->name; ?>
                            </option>
						<?php } ?>
                    </select>
                    <span class="description"></span>
                </td>
            </tr>
        </table>
	<?php }


	public function update_user_meta( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		update_user_meta( $user_id, 'position', $_POST['position'] );

		return true;
	}

	public function is_user_role( $role, $user_id = null ) {
		$user = is_numeric( $user_id ) ? get_userdata( $user_id ) : wp_get_current_user();

		if ( ! $user ) {
			return false;
		}

		return in_array( $role, (array) $user->roles );
	}


	public function regist_post() {
		register_post_type( 'skill', array(
			'label'               => null,
			'labels'              => array(
				'name'               => __( 'Skills' ),
				'singular_name'      => __( 'Skill' ),
				'add_new'            => __( 'Add Skill' ),
				'add_new_item'       => __( 'Add Skill' ),
				'edit_item'          => __( 'Edit Skill' ),
				'new_item'           => __( 'New Skill' ),
				'view_item'          => __( 'View Skill' ),
				'search_items'       => __( 'Search Skill' ),
				'not_found'          => __( 'Not found' ),
				'not_found_in_trash' => __( 'Not found in trash' ),
				'parent_item_colon'  => __( '' ),
				'menu_name'          => __( 'Skills' ),
			),
			'description'         => 'Skills of developer',
			'public'              => true,
			'publicly_queryable'  => null,
			'exclude_from_search' => null,
			'show_ui'             => null,
			'show_in_menu'        => null,
			'show_in_admin_bar'   => null,
			'show_in_nav_menus'   => null,
			'show_in_rest'        => null,
			'rest_base'           => null,
			'menu_position'       => 2,
			'menu_icon'           => null,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor' ),
			'taxonomies'          => array( 'skillcategory' ),
			'has_archive'         => false,
			'rewrite'             => true,
			'query_var'           => true,
		) );
	}

	public function create_taxonomy() {
		register_taxonomy( 'skillcategory', array( 'skill' ), array(
			'labels'                => array(
				'name'              => __( 'Category of skills' ),
				'singular_name'     => __( 'Category of skill' ),
				'search_items'      => __( 'Search skill category' ),
				'all_items'         => __( 'All skill category' ),
				'view_item '        => __( 'View skill category' ),
				'parent_item'       => __( 'Parent skill category' ),
				'parent_item_colon' => __( 'Parent skill category:' ),
				'edit_item'         => __( 'Edit skill category' ),
				'update_item'       => __( 'Update skill category' ),
				'add_new_item'      => __( 'Add New skill category' ),
				'new_item_name'     => __( 'New skill category Name' ),
				'menu_name'         => __( 'Skill category' ),
			),
			'description'           => 'Category of developer skills',
			'public'                => true,
			'publicly_queryable'    => null,
			'show_in_nav_menus'     => null,
			'show_ui'               => null,
			'show_in_menu'          => null,
			'show_tagcloud'         => null,
			'show_in_rest'          => null,
			'rest_base'             => null,
			'hierarchical'          => false,
			'update_count_callback' => '',
			'rewrite'               => true,
			'capabilities'          => array(),
			'meta_box_cb'           => null,
			'show_admin_column'     => false,
			'_builtin'              => false,
			'show_in_quick_edit'    => null,
		) );

		register_taxonomy( 'position', '', array(
			'label'                 => '',
			'labels'                => array(
				'name'              => __( 'Category of position' ),
				'singular_name'     => __( 'Category of position' ),
				'search_items'      => __( 'Search user position' ),
				'all_items'         => __( 'All user position' ),
				'view_item '        => __( 'View user position' ),
				'parent_item'       => null,
				'parent_item_colon' => null,
				'edit_item'         => __( 'Edit user position' ),
				'update_item'       => __( 'Update user position' ),
				'add_new_item'      => __( 'Add New user position' ),
				'new_item_name'     => __( 'New position' ),
				'menu_name'         => __( 'position category' ),
			),
			'public'                => false,
			'query_var'             => false,
			'rewrite'               => false,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'update_count_callback' => 'user_tag_update_count_callback'
		) );
	}

	public function add_role() {
		remove_role( 'developer' );
		add_role( 'developer', 'Developer',
			array(
				'read'          => false,
				'publish_posts' => false
			)
		);

		remove_role( 'hr' );
		add_role( 'hr', 'HR',
			array(
				'read'                 => true, // true allows this capability
				'edit_posts'           => true, // Allows user to edit their own posts
				'delete_posts'         => true,
				'edit_pages'           => true, // Allows user to edit pages
				'edit_others_posts'    => true, // Allows user to edit others posts not just their own
				'create_posts'         => true, // Allows user to create new posts
				'manage_categories'    => true, // Allows user to manage post categories
				'publish_posts'        => true, // Allows the user to publish, otherwise posts stays in draft mode
				'edit_themes'          => true, // false denies this capability. User can’t edit your theme
				'edit_users'           => true,
				'create_users'         => true,
				'remove_users'         => true,
				'delete_users'         => true,
				'promote_users'        => true,
				'list_users'           => true,
				'manage_network_users' => true
			)
		);

	}


	public function set_default_user_meta() {
		$developers = get_users( array( 'role' => 'developer' ) );
		$posts      = get_posts( array( 'post_type' => 'skill', 'posts_per_page' => - 1 ) );
		foreach ( $developers as $developer ) {
			foreach ( $posts as $post ) {
				add_user_meta( $developer->ID, $post->post_title, 'none', true );
			}
		}
	}

	public function admin_menu() {
		add_menu_page( 'Skills Matrix', 'SkillsMatrix', 'delete_posts', 'skills-matrix', array(
			$this,
			'skills_matrix_show'
		), '', 4 );
	} //use WP-Util for creating table

	public function register_submenu() {
		add_submenu_page( 'skills-matrix', 'Form user', 'Add developer', 'manage_options',
			'add-developer', array( $this, 'form_add_developer' ) );
		add_submenu_page( 'skills-matrix', 'Add position', 'Add position of developer', 'manage_options', 'edit-tags.php?taxonomy=position', '' );
		add_submenu_page( 'skills-matrix', 'Add skill', 'Add skill', 'manage_options',
			'add-skill', array( $this, 'form_add_skill' ) );
		add_submenu_page( 'skills-matrix', 'Add skill category', 'Add skill category', 'manage_options',
			'add-skill-category', array( $this, 'form_add_category' ) );
	}

	public function add_category() {
		if ( ! empty( $_POST['hs_insert_category'] ) ) {
			if ( $_POST['category_name'] ) {
				$post_id = wp_insert_term( $_POST['category_name'], 'skillcategory' );
				if ( is_wp_error( $post_id ) ) {
					wp_safe_redirect( add_query_arg( array( 'error' => '1' ), get_permalink() ) );
					exit;
				} else {
					wp_safe_redirect( add_query_arg( array( 'successfull' => '1' ), get_permalink() ) );
					exit;
				}
			}
			$_POST['none_category'] = 'not_exist';
		}
	}

	public function form_add_category() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}
		wp_enqueue_script( 'script' );
		wp_enqueue_style( 'matrix-style' );
		?>
        <div class="developerForm">
            <form class="add_developer" method="post" action="">
                <h3><?php _e( 'Add skill category' ) ?></h3>
				<?php if ( ! empty( $_GET['error'] ) ) { ?>
                    <span class="span_error" style="color:red"><?php _e( 'Error' ) ?></span>
				<?php } ?>
                <div class="add_category_skill">
                    <label for="category"><?php _e( 'Category' ) ?></label>
                    <input type="text" name="category_name" id="category">
                    <div class="error_input <?= $_POST['none_category'] ?>">
                        <label></label>
                        <span style="clear: both; overflow: hidden; color:red; "><?php _e( 'Input category name' ) ?></span>
                    </div>
                </div>
                <div class="add_category_skill">
                    <label></label>
                    <button class="button button-primary" type="submit"><?php _e( 'Add' ) ?></button>
                    <button class="button" type="reset"><?php _e( 'Reset' ) ?></button>
                </div>
                <input type="hidden" name="hs_insert_category" value="1"/>
            </form>
        </div>
		<?php
	}


	public function add_skill() {
		if ( ! empty( $_POST['hs_insert_skill'] ) ) {
			if ( $_POST['post_name'] ) {
				$post    = array(
					'post_type'   => 'skill',
					'post_title'  => $_POST['post_name'],
					'tags_input'  => array( $_POST['post_term'] ),
					'post_status' => 'publish'
				);
				$post_id = wp_insert_post( $post, true );
				if ( is_wp_error( $post_id ) ) {
					wp_safe_redirect( add_query_arg( array( 'error' => '1' ), get_permalink() ) );
					exit;
				} else {
					wp_set_post_terms( $post_id, $_POST['post_term'], 'skillcategory', true );
					wp_safe_redirect( add_query_arg( array( 'successfull' => '1' ), get_permalink() ) );
					exit;
				}
			}
			$_POST['none_post'] = 'not_exist';
		}
	}

	public function form_add_skill() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}
		wp_enqueue_script( 'script' );
		wp_enqueue_style( 'matrix-style' );
		?>
        <div class="developerForm">
            <form class="add_developer" method="post" action="">
                <h3><?php _e( 'Add skill' ) ?></h3>
				<?php if ( ! empty( $_GET['error'] ) ) { ?>
                    <span class="span_error" style="color:red"><?php _e( 'Error' ) ?></span>
				<?php } ?>
                <div class="add_skill_element">
                    <label for="skill"><?php _e( 'Skill' ) ?></label>
                    <input type="text" name="post_name" id="skill">
                    <div class="error_input <?= $_POST['none_post'] ?>">
                        <label></label>
                        <span style="clear: both; overflow: hidden; color:red; "><?php _e( 'Input post name' ) ?></span>
                    </div>
                </div>
                <div>
                    <label for="category_skill"> <?php _e( 'Category' ) ?></label>
					<?php $args = array(
						'taxonomy'   => 'skillcategory',
						'hide_empty' => false,
					);
					$terms      = get_terms( $args ); ?>
                    <select name="post_term" id="category_skill">
						<?php foreach ( $terms as $term ) { ?>
                            <option value="<?php echo $term->name; ?>"><?php echo $term->name ?></option>
						<?php } ?>
                    </select>
                </div>
                <div>
                    <label></label>
                    <button class="button button-primary" type="submit"><?php _e( 'Add' ) ?></button>
                    <button class="button" type="reset"><?php _e( 'Reset' ) ?></button>
                </div>
                <input type="hidden" name="hs_insert_skill" value="1"/>
            </form>
        </div>
		<?php
	}

	public function add_developer() {
		if ( ! empty( $_POST['hs_insert_developer'] ) ) {
			$user_data = array(
				'user_pass'       => ! empty( $_POST['password'] ) ? $_POST['password'] : '',
				'user_login'      => ! empty( $_POST['user_name'] ) ? $_POST['user_name'] : '',
				'user_nicename'   => '',
				'user_url'        => '',
				'user_email'      => ! empty( $_POST['email'] ) ? $_POST['email'] : '',
				'first_name'      => ! empty( $_POST['first_name'] ) ? $_POST['first_name'] : '',
				'last_name'       => ! empty( $_POST['last_name'] ) ? $_POST['last_name'] : '',
				'rich_editing'    => 'true',
				'user_registered' => '',
				'role'            => 'developer'
			);
			if ( $user_data['user_login'] && $user_data['user_pass'] ) {
				$post_id = wp_insert_user( $user_data );
				if ( is_wp_error( $post_id ) ) {
					wp_safe_redirect( add_query_arg( array( 'error' => '1' ), get_permalink() ) );
					exit;
				} else {
					wp_safe_redirect( add_query_arg( array( 'successfull' => '1' ), get_permalink() ) );
					add_user_meta( $post_id, 'position', $_POST['role'] );
					exit;
				}
			}
			$_POST['password'] = 'not_exist';
			$_POST['user']     = empty( $_POST['user_name'] ) ? 'not_exist' : '';
		}
	}

	public function form_add_developer() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}
		wp_enqueue_script( 'script' );
		wp_enqueue_style( 'matrix-style' );
		?>
        <div class="developerForm">
            <form class="add_developer" method="post" action="">
                <h3><?php _e( 'Add developer' ) ?></h3>

				<?php if ( ! empty( $_GET['error'] ) ) { ?>
                    <span class="span_error" style="color:red"><?php _e( 'Error' ) ?></span>
				<?php } ?>
                <div class="add_developer_element">
                    <label for="nameDev"><?php _e( 'Username' ) ?></label>
                    <input type="text" name="user_name" id="nameDev"
                           value="<?php echo isset( $_POST['user_name'] ) ? $_POST['user_name'] : '' ?>">
                    <div class="error_input <?= $_POST['user'] ?>">
                        <label></label>
                        <span style="clear: both; overflow: hidden; color:red; "><?php _e( 'Input username' ) ?></span>
                    </div>
                </div>
                <div class=" add_developer_element">
                    <label for="emailDev"><?php _e( 'Email' ) ?></label>
                    <input type="text" name="email" id="emailDev"
                           value="<?php echo isset( $_POST['email'] ) ? $_POST['email'] : '' ?>">
                </div>
                <div class="add_developer_element">
                    <label for="firstNameDev"><?php _e( 'First Name' ) ?></label>
                    <input type="text" name="first_name" id="firstNameDev"
                           value="<?php echo isset( $_POST['first_name'] ) ? $_POST['first_name'] : '' ?>">
                </div>
                <div class="add_developer_element">
                    <label for="lastNameDev"><?php _e( 'Last Name' ) ?></label>
                    <input type="text" name="last_name" id="lastNameDev"
                           value="<?php echo isset( $_POST['last_name'] ) ? $_POST['last_name'] : '' ?>">
                </div>
                <div class="add_developer_element">
                    <label for="passwordDev"><?php _e( 'Password' ) ?></label>
                    <input type="password" name="password" id="passwordDev">
                    <button class="showPass button" type="button" style="outline:none; height: 25px "><span
                                class="show_pass_text"><?php _e( 'show' ) ?></span></button>
                    <div class="error_input <?= $_POST['password'] ?>">
                        <label></label>
                        <span style="clear: both; overflow: hidden; color:red; "><?php _e( 'Input password' ) ?></span>
                    </div>
                </div>
                <div>
                    <label for="roleDev"> <?php _e( 'Position' ) ?></label>
					<?php $args = array(
						'taxonomy'   => 'position',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					);
					$terms      = (array) get_terms( $args ); ?>
                    <select name="role" id="roleDev">
						<?php foreach ( $terms as $term ) { ?>
                            <option value="<?php echo $term->name; ?>"><?php echo $term->name ?></option>
						<?php } ?>
                    </select>
                </div>
                <div>
                    <label></label>
                    <button class="button button-primary" type="submit"><?php _e( 'Add' ) ?></button>
                    <button class="button" type="reset"><?php _e( 'Reset' ) ?></button>
                </div>
                <input type="hidden" name="hs_insert_developer" value="1"/>
            </form>
        </div>
		<?php
	}

	public function register_show_skill() {
		$current_user = wp_get_current_user();
		if ( $this->is_user_role( 'developer', $current_user->ID ) ) {
			$args  = array(
				'taxonomy'   => 'skillcategory',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			);
			$terms = get_terms( $args );
			?>
            <table border="3" id="skill_developer">
                <tr>
                    <th>Skill Category</th>
                    <th>Skill</th>
                    <th><?php echo $current_user->user_login ?></th>
                </tr>
                <tr>
                    <td>Position</td>
                    <td></td>
                    <td><?php echo( get_user_meta( $current_user->ID, 'position', true ) ) ?></td>
                </tr>
				<?php $arrayLevel = [ 'none', 'basic', 'good', 'excellent', 'expert', 'JESUS' ];
				foreach ( $terms as $term ) {
					$loop = get_posts( array(
						'tax_query'      => array(
							array(
								'taxonomy' => 'skillcategory',
								'field'    => 'name',
								'terms'    => $term->name
							)
						),
						'post_type'      => 'skill',
						'posts_per_page' => - 1
					) );

					?>
                    <tr>
                        <td rowspan="<?php echo $row = ( count( $loop ) ) ? count( $loop ) : '1' ?>"> <?php echo( $term->name ) ?> </td>

						<?php
						$k = 0;
						if ( count( $loop ) ) {
							foreach ( $loop as $post ) {
								if ( $k ) {
									echo '<tr>';
								}
								echo '<td>';
								echo $post->post_title;
								echo '</td>';
								?>
                                <td>
                                    <select class="level_developer" data-skill="<?= $post->post_title ?>"
                                            data-developer="<?= $current_user->ID ?>" name="level">
										<?php foreach ( $arrayLevel as $level ) { ?>
                                            <option value="<?= $level ?>" <?php if ( $level == get_user_meta( $current_user->ID, $post->post_title, true ) ) {
												echo 'selected';
											} ?>>
												<?= $level ?></option>
										<?php } ?>
                                    </select>
                                </td>

								<?php
								if ( $k ) {
									echo '</tr>';
								}
								$k ++; //output <td>
							}
						}
						?>
                    </tr>
					<?php
				} ?>
            </table>
			<?php
		}
	}

	public function show_filters() {
		$allDevelopers = get_users( array( 'role' => 'developer' ) );
		if ( ! count( (array) $allDevelopers ) ) {
			echo '<h3>Please add developers, skill category and skill</h3>';
		} else {
			; ?>
            <h1>Filters:</h1>
			<?php
			?>

            <div class="div-filter" style=" padding-top: 10px; margin-bottom: 20px;">

				<?php
				$args     = array(
					'taxonomy'   => 'skillcategory',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				);
				$allTerms = get_terms( $args );
				?>

                <div class="filter">
                    <label for="skill_categories"
                           style="display: block;font-size: 15px; font-style: italic">Categories</label>
                    <select id="skill_categories" style="margin-bottom: 10px;">
                        <option value="">Select Category</option>
						<?php foreach ( $allTerms as $term ) { ?>
                            <option value="<?= $term->name ?>"><?= $term->name ?></option>
						<?php } ?>
                    </select>
                </div>

                <div class="filter skills-of-term">

                </div>

                <div class="filter">
                    <label for="all_developers"
                           style="display: block;font-size: 15px; font-style: italic">Developers</label>
                    <select multiple="multiple" id="all_developers" style="margin-bottom: 10px; position:absolute;">
						<?php foreach ( $allDevelopers as $developer ) { ?>
                            <option value="<?= $developer->ID ?>"><?= $developer->display_name ?></option>
						<?php } ?>
                    </select>
                </div>
				<?php
				$args  = array(
					'taxonomy'   => 'position',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				);
				$terms = (array) get_terms( $args );
				?>
                <div class="filter">
                    <label for="positions"
                           style="display: block;font-size: 15px; font-style: italic">Position</label>
                    <select id="positions" style="margin-bottom: 10px;">
                        <option value="">Select position</option>
						<?php foreach ( $terms as $position ) { ?>
                            <option value="<?= $position->name ?>"><?= $position->name ?></option>
						<?php } ?>
                    </select>
                </div>
            </div>

			<?php
		}
	}

	public function skills_matrix_show() {
		wp_enqueue_script( 'ajax_admin' );
		wp_enqueue_style( 'matrix-style' );
		wp_enqueue_style( 'chosen-css' );
		$this->set_default_user_meta();
		$this->show_filters();
		?>

        <div class="preloader-ajax" style="">
            <img src="<?php echo um_url ?>/img/Spinner-1.6s-200px.svg" height="80px">
        </div>

        <script type="text/html" id="tmpl-my-template">
            <table class="bordered">
                <tr>
                    <th>Skill Category</th>
                    <th>Skill</th>
                    <# data.developers.forEach(function(item,i,arr){#>
                    <th>{{{item}}}</th>
                    <# }) #>
                </tr>
                <tr>
                    <td>Position</td>
                    <td></td>
                    <# data.positions.forEach(function(item,i,arr){#>
                    <td>{{{item}}}</td>
                    <# }) #>
                </tr>
                <#data.arrayObj.forEach(function(item,i,arr){
                var key = true;
                let count= item.skills.length;
                #>
                <tr>
                    <td rowspan="{{{count}}}">
                        {{{ item.category }}}
                    </td>
                    <# if(key){ #>
                    <td>
                        <# var skillname=item.skills[0].skill_name; #>
                        {{{ skillname }}}
                    </td>
                    <# item.skills[0].developer_level.forEach(function(level,i,arr){ #>
                    <td>
                        <select class="level_developer" data-skill="{{{skillname}}}"
                                data-developer="{{{item.skills[0].developer_id[i]}}}" name="level">
                            <# data.skill_level.forEach(function(all_level,i,arr){#>
                            <option
                            <# if(all_level==level){ #>{{{'selected'}}} <# } #> >{{{all_level}}}</option>
                            <# }) #>
                        </select>
                    </td>
                    <# }) #>
                    <# key=false;
                    } #>
                </tr>
                <# for (var j = 1; j < item.skills.length; j++) { #>
                <tr>
                    <# var skillname=item.skills[j].skill_name; #>
                    <td>{{{skillname}}}</td>
                    <# item.skills[j].developer_level.forEach(function(skill,i,arr){ #>
                    <td>
                        <select class="level_developer" data-skill="{{{skillname}}}"
                                data-developer="{{{item.skills[j].developer_id[i]}}}" name="level">
                            <# data.skill_level.forEach(function(all_level,i,arr){#>
                            <option
                            <# if(all_level==skill){ #>{{{'selected'}}} <# } #> >{{{all_level}}}</option>
                            <# }) #>
                        </select>
                    </td>
                    <# }) #>
                </tr>
                <# }
                }) #>
            </table>
        </script>

        <div class="my-element"></div> <!--insert template -->

		<?php
	}


}

new SkillMatrix();