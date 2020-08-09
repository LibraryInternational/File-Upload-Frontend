<?php
/**
* Plugin Name: File Upload Frontend Wordpress Plugin
* Plugin URI: http://www.library.international/
* Description: Display File Upload using a shortcode [file-frontend-upload] to insert in a page or post
* Version: 1.0
* Text Domain: file-upload-frontend-library
* Author: Library International
* Author URI: http://www.library.international/
*/
function library_frontend_file_upload($atts=array()) {
	 // set up default parameters
    extract(shortcode_atts(array(
     'license' => ''
    ), $atts));
  $html='<form method="post" action="" enctype="multipart/form-data">
    <input type="file" name="file_uploads[]" required  multiple>
    <input type="hidden" name="license_file" value="'.$license.'">
    <input type="submit" name="submit_files" value="Upload Files">
  </form>';
return $html;
}
add_shortcode('file-frontend-upload', 'library_frontend_file_upload');
function upload_user_file( $file = array(),$license,$alert ) {

require_once( ABSPATH . 'wp-admin/includes/admin.php' );
include_once(ABSPATH . 'wp-includes/pluggable.php');

$file_return = wp_handle_upload( $file, array('test_form' => false ) );

if( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {
return false;
} else {

$filename = $file_return['file'];

$attachment = array(
'post_mime_type' => $file_return['type'],
'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
'post_content' => '',
'post_status' => 'inherit',
'guid' => $file_return['url']
);

$attachment_id = wp_insert_attachment( $attachment, $file_return['url'] );

require_once(ABSPATH . 'wp-admin/includes/image.php');
$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
wp_update_attachment_metadata( $attachment_id, $attachment_data );
$file_url = wp_get_attachment_url($attachment_id );

$post = [
'file_urls' => $file_url,
'license' => $license,
'website' => site_url(),
'fileuploader'=> fileuploadername(),
];
$ch = curl_init('https://www.library.international/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
// execute!
$response = curl_exec($ch);
// close the connection, release resources used
curl_close($ch);
// do anything you want with your response
//var_dump($response);

if( 0 < intval( $attachment_id ) ) {
  if($alert==1):
$alertmsg = count($_FILES['file_uploads']['name']).' files has been uploaded';
echo "<script>alert('".$alertmsg."');</script>";
  endif;
return $attachment_id;
}
}

return false;
}
if(isset($_POST['submit_files']))
{
if( ! empty( $_FILES ) )
{
$license=esc_sql($_POST['license_file']);
$files=$_FILES['file_uploads'];
$countfiles=count($files['name']);
// file array
$x=0;
foreach ($files as $key => $value) {
$file = array(
'name'     => $files['name'][$x],
'type'     => $files['type'][$x],
'tmp_name' => $files['tmp_name'][$x],
'error'    => $files['error'][$x],
'size'     => $files['size'][$x]
);
$upload_dir=wp_upload_dir();

if(!is_dir($path)) { mkdir($path); }
$alert=0;
if($x==($countfiles-1)):
$alert=1;
  endif;
$attachment_id = upload_user_file( $file,$license, $alert);
$x++;
}
}
}
function fileuploadername()
{
  if(is_user_logged_in()):
  $user=wp_get_current_user();
    $name=$user->user_nicename; 
    $fileuploader = $name;
  else:
    $fileuploader='anonymous';
  endif;
  return $fileuploader;
}
// apply tags to attachments
function wptp_add_tags_to_attachments() {
    register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'wptp_add_tags_to_attachments' );