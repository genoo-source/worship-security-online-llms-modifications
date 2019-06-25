<?php
/*
Plugin Name: Worship Security Online LLMS Modifications
Plugin URI: https://github.com/genoo-source/worship-security-online-llms-modifications
Description: For managing the Account Manager user role
Version: 0.1
Author: Genoo LLC
Author URI: http://genoo.com/
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {

	include_once 'updater.php';

	define( 'WP_GITHUB_FORCE_UPDATE', true );

	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => 'worship-security-online-llms-modifications',
			'api_url' => 'https://api.github.com/repos/genoo-source/worship-security-online-llms-modifications',
			'raw_url' => 'https://raw.github.com/genoo-source/worship-security-online-llms-modifications/master',
			'github_url' => 'https://github.com/genoo-source/worship-security-online-llms-modifications',
			'zip_url' => 'https://github.com/genoo-source/worship-security-online-llms-modifications/archive/master.zip',
			'sslverify' => true,
			'requires' => '3.0',
			'tested' => '3.3',
			'readme' => 'README.md',
			'access_token' => '',
		);

		new WP_GitHub_Updater( $config );

	}

}

function create_user_in_membership() {
	if ( $_GET['action'] == "create_user_in_membership" ) {
		$membership = $_GET['membership'];
		$username = $_GET['username'];
    $email = $_GET['email'];
    $password = $_GET['password'];

    $user_id = username_exists( $username );
    if ( !$user_id && email_exists($email) == false ) {
        $user_id = wp_create_user( $username, $password, $email );
        if( !is_wp_error($user_id) ) {
            $user = get_user_by( 'id', $user_id );
            $user->set_role( 'student' );
        }
    }

		$student = new LLMS_Student( $user_id );
		llms_enroll_student( $student, $membership );
		return true;
	}
}
add_action('wp_ajax_create_user_in_membership', 'create_user_in_membership');

// Add metabox
function enroll_new_students_meta_box_add() {
	add_meta_box( 'enroll-new-students', 'Enroll a new Student', 'enroll_new_students', 'llms_membership', 'normal', 'low' );
}
add_action( 'add_meta_boxes', 'enroll_new_students_meta_box_add' );

function enroll_new_students() {
	global $post;

	?>
	<?= wp_nonce_field('add_transfer','create_user_in_membership') ?>
	<input name="action" value="create_user_in_membership" type="hidden">
	<p>
		<label>Username</label><br />
		<input type="text" name="enrollNewStudent_username" />
	</p>
	<p>
		<label>Email Address</label><br />
		<input type="text" name="enrollNewStudent_email" />
	</p>
	<p>
		<label>Password</label><br />
		<input type="password" name="enrollNewStudent_password" />
	</p>
	<!-- <i>Click update to create the new user</i> -->
	<button onclick="enrollNewStudent()">Create new student</button>
	<br />
	<br />
	<script>
		function enrollNewStudent(){
			const postURL = "<?= admin_url('admin-ajax.php') ?>";
			$.ajax({
				type : "GET",
				dataType : "json",
				url : "/wp-admin/admin-ajax.php",
				data : {
					action: 'create_user_in_membership',
					create_user_in_membership: document.querySelector('#create_user_in_membership').value,
					membership: <?= $post->ID ?>,
					username: document.querySelector("[name='enrollNewStudent_username']").value,
					password: document.querySelector("[name='enrollNewStudent_password']").value,
					email: document.querySelector("[name='enrollNewStudent_email']").value
				}
			});
			// refresh
			window.location = window.location;
		}
	</script>
  <?php
}


function get_user_role() {
    global $current_user;

    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    return $user_role;
}

function custom_admin_styles() {
	if ( get_user_role() != 'administrator' ) return;
  echo '<style class="custom-css">
    .llms-metabox-section.llms-metabox-students-add-new,
		#lifterlms-membership,
		#lifterlms-product {
			display: none;
		}
  </style>';
}
add_action('admin_head', 'custom_admin_styles');

?>
