<?php

use Builds\BuildController;
use Builds\BuildTable;
use WP_REST_Request;
/**
 * Vercel Builds
 * 
 * @package     Vercel Builds
 * @author      Stefano Fasoli <stefanofasoli17@gmail.com>
 * @version     1.0.0
 * 
 * @wordpress-plugin
 * Plugin Name:     Vercel Builds
 * Description:     Vercel Builds
 * Version:         1.0.0
 * Author:          Stefano Fasoli <stefanofasoli17@gmail.com>
 * Text Domain:     vercel_builds
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
	register_rest_route('builds', 'update', [
		'methods' => 'POST',
		'callback' => [BuildController::class, 'update'],
		'permission_callback' => function (WP_REST_Request $request) {
			if (! defined('VERCEL_SIGNATURE_KEY')) {
				return new WP_Error(501, 'Not implemented', []);
			}
		
			$signature = $request->get_header('x-vercel-signature');
			$hash = hash_hmac('sha1', $request->get_body(), constant('VERCEL_SIGNATURE_KEY'));
		
			return $signature === $hash;
		}
	]);
});

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

add_action(
	'admin_menu', 
	fn () => add_submenu_page(
        'index.php',
        'Build status',
        'Build status',
        'manage_options',
        'build-status',
        'vercel_builds_display_table'
    ), 
	priority: 8,
);

function vercel_builds_display_table() {
    $table = BuildTable::make();
    $table->render();
?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( 'Vercel deployments' );
?></h1>

<hr class="wp-header-end">

<?php
$table->views();
?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<input type="hidden" name="post_status" value="<?php echo isset( $_REQUEST['post_status'] ) ? esc_attr( $_REQUEST['post_status'] ) : ''; ?>" />
	<?php $table->search_box( 'Search messages', 'builds' ); ?>
	<?php $table->display(); ?>
</form>

</div>
<?php
}
