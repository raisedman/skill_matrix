<?php

/*
Plugin Name: Skill Matrix
Description: Создать матрицу скиллов разработчиков
Version: 1.0
*/

class SkillMatrix {

	public function __construct() {

		add_action( 'admin_head', array( $this, 'register_script_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_user' ) );
		add_action( 'init', array( $this, 'regist_post' ) );
		add_action( 'init', array( $this, 'create_taxonomy' ) );
		add_action( 'show_user_profile', array( $this, 'my_show_extra_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'my_show_extra_profile_fields' ) );
		add_action( 'user_new_form', array( $this, 'my_show_extra_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'update_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_user_meta' ) );
		add_action( 'user_new_form', array( $this, 'update_user_meta' ) );
		$this->add_role();
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_hello', array( $this, 'processing_ajax' ) );
		add_action( 'wp_ajax_addposition', array( $this, 'add_position_developer' ) );
		add_action( 'wp_ajax_developers', array( $this, 'send_developers' ) );
		add_action( 'wp_ajax_categories', array( $this, 'send_category' ) );
		add_action( 'admin_head', array( $this, 'action_js' ) );
		add_shortcode( 'show_skill', array( $this, 'register_show_skill' ) );

	}


	public function send_developers() {
		$arrayJson  = array();
		$developers = get_users( array( 'role' => 'developer' ) );
		$arrayDev   = [];
		foreach ( $developers as $developer ) {
			$arrayDev[] = $developer->ID;
		}
		foreach ( $arrayDev as $iduser ) {
			$user        = get_user_by( 'id', $iduser );
			$login       = $user->user_login;
			$returnArray = array( 'name' => $login );
			$returnArray += [ 'id' => $iduser ];
			$returnArray += [ 'position' => get_user_meta( $iduser, 'position', true ) ];
			$allPosts    = get_posts( array(
				'post_type'      => 'skill',
				'posts_per_page' => - 1
			) );
			$skills      = array();
			foreach ( $allPosts as $post ) {
				$skills += [ $post->post_title => get_user_meta( $iduser, $post->post_title, true ) ];
			}
			$returnArray += [ 'skills' => $skills ];
			$arrayJson[] = $returnArray;
		}
		$arrayReturn     = [ 'developers' => $arrayJson ];
		$arrayCategories = [];
		$args            = array(
			'taxonomy'   => 'skillcategory',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		$terms           = get_terms( $args );

		foreach ( $terms as $category ) {
			$arraySkill = [];

			$loop = get_posts( array(
				'tax_query'      => array(
					array(
						'taxonomy' => 'skillcategory',
						'field'    => 'name',
						'terms'    => $category->name
					)
				),
				'post_type'      => 'skill',
				'posts_per_page' => - 1
			) );
			foreach ( $loop as $skil ) {
				$arraySkill[] = $skil->post_title;
			}
			$currentCategory   = [ $category->name => $arraySkill ];
			$arrayCategories[] = $currentCategory;
		}
		$arrayReturn += [ 'categories_response' => $arrayCategories ];
		echo( json_encode( $arrayReturn ) );
		wp_die();
	}

	public function processing_ajax() {

		if ( ! update_user_meta( $_POST['developer'], $_POST['skill'], $_POST['level'] ) ) {
			echo 'Пора включать панику, данные не обновились 0_o';
		}
		wp_die();
	}

	public function add_position_developer() {
		if ( ! update_user_meta( $_POST['developer'], 'position', $_POST['devposition'] ) ) {
			echo 'Пора включать панику, данные не обновились 0_o';
		}
		echo( 'helllo' );
		wp_die();
	}

	public function action_js() {
		wp_enqueue_script( 'ajax_admin' );
	}

	public function register_script_admin() {
		wp_register_style( 'matrix-style', plugins_url() . '/skillMatrix/assets/css/style.css' );
		wp_register_script( 'ajax_admin', plugins_url() . '/skillMatrix/assets/ajax/ajaxscript.js', array( 'jquery' ) );
	}

	public function enqueue_script_user() {
		wp_enqueue_script( 'ajax_user', plugins_url() . '/skillMatrix/assets/ajax/ajaxscript_user.js', array( 'jquery' ) );
		wp_localize_script( 'ajax_user', 'myPlugin', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function my_show_extra_profile_fields( $user ) { ?>
        <h3>Введите должность:</h3>
        <table class="form-table">
            <tr>
                <th><label for="twitter">Должность</label></th>
                <td>
                    <input type="text" name="position" id="position"
                           value="<?php echo esc_attr( get_the_author_meta( 'position', $user->ID ) ); ?>"
                           class="regular-text"/><br/>
                    <span class="description"></span>
                </td>
            </tr>
        </table>
	<?php }

	public function update_user_meta( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		update_usermeta( $user_id, 'position', $_POST['position'] );

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
				'new_item'           => __( 'Новое ____' ),
				'view_item'          => __( 'View Skill' ),
				'search_items'       => __( 'Искать ____' ),
				'not_found'          => __( 'Not found' ),
				'not_found_in_trash' => __( 'Не найдено в корзине' ),
				'parent_item_colon'  => __( '' ),
				'menu_name'          => __( 'Skills' ),
			),
			'description'         => 'Skills of developer',
			'public'              => true,
			'publicly_queryable'  => null,
			// зависит от public
			'exclude_from_search' => null,
			// зависит от public
			'show_ui'             => null,
			// зависит от public
			'show_in_menu'        => null,
			// показывать ли в меню адмнки
			'show_in_admin_bar'   => null,
			// по умолчанию значение show_in_menu
			'show_in_nav_menus'   => null,
			// зависит от public
			'show_in_rest'        => null,
			// добавить в REST API. C WP 4.7
			'rest_base'           => null,
			// $post_type. C WP 4.7
			'menu_position'       => null,
			'menu_icon'           => null,
			//'capability_type'   => 'post',
			//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
			//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor' ),
			// 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
			'taxonomies'          => array( 'skillcategory' ),
			'has_archive'         => false,
			'rewrite'             => true,
			'query_var'           => true,
		) );
	}

	public function create_taxonomy() {
		register_taxonomy( 'skillcategory', array( 'skill' ), array(
			'label'                 => '',
			// определяется параметром $labels->name
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
			// описание таксономии
			'public'                => true,
			'publicly_queryable'    => null,
			// равен аргументу public
			'show_in_nav_menus'     => null,
			// равен аргументу public
			'show_ui'               => null,
			// равен аргументу public
			'show_in_menu'          => null,
			// равен аргументу show_ui
			'show_tagcloud'         => null,
			// равен аргументу show_ui
			'show_in_rest'          => null,
			// добавить в REST API
			'rest_base'             => null,
			// $taxonomy
			'hierarchical'          => false,
			'update_count_callback' => '',
			'rewrite'               => true,
			//'query_var'             => $taxonomy, // название параметра запроса
			'capabilities'          => array(),
			'meta_box_cb'           => null,
			// callback функция. Отвечает за html код метабокса (с версии 3.8): post_categories_meta_box или post_tags_meta_box. Если указать false, то метабокс будет отключен вообще
			'show_admin_column'     => false,
			// Позволить или нет авто-создание колонки таксономии в таблице ассоциированного типа записи. (с версии 3.5)
			'_builtin'              => false,
			'show_in_quick_edit'    => null,
			// по умолчанию значение show_ui
		) );

	}

	public function add_role() {
		remove_role( 'developer' );
		add_role( 'developer', 'Developer',
			array(
				'read'          => false,
				'publish_posts' => true
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
				'install_plugins'      => true, // User cant add new plugins
				'update_plugin'        => true,
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
		$allPosts = get_posts( array(
			'post_type'      => 'skill',
			'posts_per_page' => - 1
		) ); ?>
        <h1>Фильтры:</h1>
        <p>
			<?php
			?>
        </p>
        <div class="div-filter" style="overflow: hidden; margin-bottom: 20px;">
            <div class="filter">
                <label for="all_skills_developers"
                       style="display: block;font-size: 15px; font-style: italic">Skills </label>
                <select id="all_skills_developers" style="margin-bottom: 10px;">
					<?php foreach ( $allPosts as $post ) { ?>
                        <option value="<?= $post->post_title ?>"><?= $post->post_title ?></option>
					<?php } ?>
                </select>
            </div>

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
					<?php foreach ( $allTerms as $term ) { ?>
                        <option value="<?= $term->name ?>"><?= $term->name ?></option>
					<?php } ?>
                </select>
            </div>

			<?php
			$allDevelopers = get_users( array( 'role' => 'developer' ) );
			?>

            <div class="filter">
                <label for="all_developers"
                       style="display: block;font-size: 15px; font-style: italic">Developers</label>
                <select multiple="multiple" id="all_developers" style="margin-bottom: 10px;">
					<?php foreach ( $allDevelopers as $developer ) { ?>
                        <option value="<?= $developer->ID ?>"><?= $developer->user_login ?></option>
					<?php } ?>
                </select>
            </div>
            <div class="filter">
                <button id="button_developers" style="margin-top: 10px; margin-right: 10px; width:50px">Show Dev</button>
            </div>
			<?php
			$positons = [];
			foreach ( $allDevelopers as $developer ) {
				$positons[] = get_user_meta( $developer->ID, 'position', true );
			}
			?>
            <div class="filterz" style="clear: right">
                <label for="positions"
                       style="display: block;font-size: 15px; font-style: italic">Position</label>
                <select id="positions" style="margin-bottom: 10px;">
					<?php foreach ( $positons as $position ) { ?>
                        <option value="<?= $position ?>"><?= $position ?></option>
					<?php } ?>
                </select>
            </div>

            <div class="filter">
            <button class="refresh" style="width:50px">Drop filter</button>
            </div>
        </div>

		<?php
	}

	public function skills_matrix_show() {
		wp_enqueue_style( 'matrix-style' );
		$this->set_default_user_meta();
		$this->show_filters();
		$args  = array(
			'taxonomy'   => 'skillcategory',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		$terms = get_terms( $args );
		?>
        <table class="matrix">
			<?php
			$developers = get_users( array( 'role' => 'developer' ) ); //all developers
			?>
            <tr>
                <th class="header-name">Skill Category</th>
                <th class="header-name">Skill</th>
				<?php
				$arrayDev = [];
				foreach ( $developers as $developer ) {
					echo '<th>';
					echo( $developer->user_login );
					echo '</th>';
					$arrayDev[] = $developer->ID;
				}
				?>
            </tr>
            <tr>
                <td>Position</td>
                <td></td>
				<?
				for ( $i = 0; $i < count( $arrayDev ); $i ++ ) {   //add position of developer
					?>
                    <td><?php if ( ! empty( $meta = get_user_meta( $arrayDev[ $i ], 'position', true ) ) ) {
							echo $meta;
						} else {
							?>
                            <input type="text" name="position" data-developer="<?= $arrayDev[ $i ] ?>"
                                   style="width:70% ; float:left">
                            <button class="button_position" data-developer="<?= $arrayDev[ $i ] ?>"
                                    style="width:20%; font-size:8px; padding-left:1px">&#10004;
                            </button>
							<?php ;
						} ?></td> <?php
				}
				?>
            </tr>
			<?php
			$arrayLevel = [ 'none', 'basic', 'good', 'excellent', 'expert', 'JESUS' ]; //array of users level
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
							for ( $j = 0; $j < count( $arrayDev ); $j ++ ) { ?>
                                <td>
                                    <select class="level_developer" data-skill="<?= $post->post_title ?>"
                                            data-developer="<?= $arrayDev[ $j ] ?>" name="level">
										<?php foreach ( $arrayLevel as $level ) { ?>
                                            <option value="<?= $level ?>" <?php if ( $level == get_user_meta( $arrayDev[ $j ], $post->post_title, true ) ) {
												echo 'selected';
											} ?>>
												<?= $level ?></option>
										<?php } ?>
                                    </select>
                                </td>

							<?php }
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

new SkillMatrix();