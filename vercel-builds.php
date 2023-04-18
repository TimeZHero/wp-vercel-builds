<?php

use Builds\Build;
use Builds\BuildController;
use Builds\BuildTable;
/**
 * Vercel Builds
 * 
 * @package     Vercel Builds
 * @author      Stefano Fasoli <stefanofasoli17@gmail.com>
 * @version     2.0.0
 * 
 * @wordpress-plugin
 * Plugin Name:     Vercel Builds
 * Description:     Vercel Builds
 * Version:         2.0.0
 * Author:          Stefano Fasoli <stefanofasoli17@gmail.com>
 * Text Domain:     vercel_builds
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

// Register option with current build status
register_activation_hook(__FILE__, fn () => add_option('vercel_current_build'));

// Register the "vercel_builds" post_type
add_action('init', fn () => register_post_type('vercel_builds', [
	'supports'              => [],
	'hierarchical'          => false,
	'public'                => false,
	'show_ui'               => false,
	'show_in_menu'          => false,
	'menu_icon'             => false,
	'show_in_admin_bar'     => false,
	'show_in_nav_menus'     => false,
	'can_export'            => false,
	'has_archive'           => false,
	'rewrite'               => false,
	'exclude_from_search'   => true,
	'publicly_queryable'    => false,
]));

// Whether the given request comes from Vercel
$vercelRequestMiddleware = function (WP_REST_Request $request): bool {
	if (! defined('VERCEL_SIGNATURE_KEY')) {
		return false;
	}

	$signature = $request->get_header('x-vercel-signature');
	$hash = hash_hmac('sha1', $request->get_body(), constant('VERCEL_SIGNATURE_KEY'));

	return $signature === $hash;
};

// API Endpoints
add_action('rest_api_init', function () use ($vercelRequestMiddleware) {
	/**
	 * Receive build events from Vercel
	 * /wp-json/builds/update
	 */
	register_rest_route('builds', 'update', [
		'methods' 	=> WP_REST_Server::EDITABLE,
		'callback' 	=> [BuildController::class, 'update'],
		'permission_callback' => $vercelRequestMiddleware
	]);

	/**
	 * Fetch latest build status from the server
	 * /wp-json/builds/status
	 */
	register_rest_route('builds', 'status', [
		'methods' 	=> WP_REST_Server::READABLE,
		'callback' 	=> [BuildController::class, 'poll'],
		'permission_callback' => '__return_true',
	]);
});

// Print the build table page on the admin screen
$buildPageTemplate = function () {
	$table = BuildTable::make();
    $table->render();
	
	?>
	<div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo esc_html('Vercel deployments'); ?>
        </h1>

	    <?php 
            if (! empty($_REQUEST['s'])) {
                echo sprintf('<span class="subtitle">'
                    . 'Search results for &#8220;%s&#8221;'
                    . '</span>', esc_html($_REQUEST['s']));
            }
        ?>

		<hr class="wp-header-end">

		<?php $table->views(); ?>

		<form method="get" action="">
			<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
			<input type="hidden" name="s" value="<?php echo esc_attr($_REQUEST['s'] ?? ''); ?>" />
			<?php $table->search_box(__('Search builds', 'vercel_builds'), 's'); ?>
			<?php $table->display(); ?>
		</form>

	</div>
	<?php
};

// Add menu page
add_action(
	'admin_menu', 
	fn () => add_submenu_page(
        'index.php',
        __('Build status', 'vercel_builds'),
        __('Build status', 'vercel_builds'),
        apply_filters('vercel_builds_capability', 'manage_options'),
        'build-status',
        $buildPageTemplate,
    ),
	priority: 8,
);

// Add badge displaying latest build
add_action('admin_bar_menu', function (WP_Admin_Bar $bar) {
	$status = get_option('vercel_current_build');
	
	$bar->add_node([
		'id' 		=> 'vercel_badge',
		'title' 	=> sprintf('<img id="vercel_badge_status" src="%s" alt />', Build::status($status)->badge),
		'href' 		=> admin_url('index.php?page=build-status'),
		'parent' 	=> 'top-secondary',
	]);
});

// Badge polling & CSS
add_action('admin_enqueue_scripts', function () {
	$assetDir = plugin_dir_url(__FILE__).'src/assets';
	
	wp_register_style('vercel_badge_style', $assetDir.'/css/badge.css');
	wp_enqueue_style('vercel_badge_style');
	wp_register_script('vercel_badge_polling', $assetDir.'/js/badge.js');
	wp_enqueue_script('vercel_badge_polling');
});
