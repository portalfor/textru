<?php
/*
Plugin Name: Text.ru
Author: hmjim	
Version: 1.0
Description: Plugin for checking content uniqueness
Author URI: 
*/
register_activation_hook( __FILE__, 'my_activation' );
function my_activation() {
	wp_clear_scheduled_hook( 'my_hourly_event' );
	wp_schedule_event( time(), 'hourly', 'my_hourly_event' );

}


add_action( 'my_hourly_event', 'do_this_hourly' );
function do_this_hourly() {

	$today    = getdate();
	$args     = array(
		'post_type'      => 'post',
		'posts_per_page' => - 1,
		'orderby'        => 'ASC',
		'date_query'     => array(
			array(
				'year'  => $today['year'],
				'month' => $today['mon'],
				'day'   => $today['mday'],
			),
		),
		'post_status'    => 'publish',
	);
	$featured = new WP_Query( $args );


	foreach ( $featured->posts as $key => $val ) {
		if ( empty( get_post_meta( $val->ID, "textru", true ) ) ) {
			$postQuery                 = array();
			$postQuery['text']         = wp_strip_all_tags( $val->post_content );
			$postQuery['userkey']      = "8c2f74e4b7160064cde4e7b626c9b28f";
			$postQuery['exceptdomain'] = "bit.news, www.bit.news";
			$postQuery['visible']      = "vis_on";
			$postQuery['copying']      = "noadd";


			$postQuery = http_build_query( $postQuery, '', '&' );

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, 'http://api.text.ru/post' );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $postQuery );
			$json  = curl_exec( $ch );
			$errno = curl_errno( $ch );
			if ( ! $errno ) {
				$resAdd = json_decode( $json );
				if ( isset( $resAdd->text_uid ) ) {
					echo $text_uid = $resAdd->text_uid . '</br>';


				} else {
					echo $error_code = $resAdd->error_code . '</br>';
					echo $error_desc = $resAdd->error_desc . '</br>';
				}
			} else {
				$errmsg = curl_error( $ch );
			}

			curl_close( $ch );
			update_post_meta( $val->ID, "textru", $resAdd->text_uid );

		} else {
			if ( ! file_exists( ABSPATH . 'textru/' . $val->ID . '.txt' ) ) {

				$outt             = '';
				$postQuery        = array();
				$postQuery['uid'] = get_post_meta( $val->ID, "textru", true );

				$postQuery['userkey']     = "8c2f74e4b7160064cde4e7b626c9b28f";
				$postQuery['jsonvisible'] = "detail";

				$postQuery = http_build_query( $postQuery, '', '&' );

				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, 'http://api.text.ru/post' );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $postQuery );
				$json  = curl_exec( $ch );
				$errno = curl_errno( $ch );

				if ( ! $errno ) {
					$resCheck = json_decode( $json );

					if ( isset( $resCheck->text_unique ) ) {
						$outt .= $resCheck->text_unique . '</br>';
						$outt .= $resCheck->result_json;
					} else {
						$outt .= $resCheck->error_code . '</br>';
						$outt .= $resCheck->error_desc . '</br>';
					}
				} else {
					$outt .= curl_error( $ch ) . '</br>';
				}
				$oout_text = json_decode( $resCheck->result_json );
				curl_close( $ch );

				$cachefile = ABSPATH . 'textru/' . $val->ID . '.txt';

				$current_text_file = '</br></br>' . '<b>ДАТА:</b> ' . $oout_text->date_check . '</br>';
				$current_text_file .= '</br></br>' . '<b>Уникальность:</b> ' . $oout_text->unique . '</br>';
				$current_text_file .= '</br></br>' . '<b>Чистый текст:</b> ' . $oout_text->clear_text . '</br>';
				$current_text_file .= '</br></br>' . '<b>Микс слов:</b> ' . $oout_text->mixed_words . '</br>';
				$current_text_file .= '</br></br>' . '<b>uid:</b> ' . get_post_meta( $val->ID, "textru", true ) . '</br>';
				if ( is_array( $oout_text->urls ) ) {
					foreach ( $oout_text->urls as $kkey => $vval ) {
						$current_text_file .= '</br></br>' . '<b>Ссылка:</b> ' . $vval->url . '</br>';
						$current_text_file .= '</br></br>' . '<b>Плагиат:</b> ' . $vval->plagiat . '</br>';
						$current_text_file .= '</br></br>' . '<b>Слова:</b> ' . $vval->words . '</br>';
					}
				}
				$current_text_file .= PHP_EOL;

				file_put_contents( $cachefile, $current_text_file, FILE_APPEND );

			}

		}

	}
}


register_deactivation_hook( __FILE__, 'my_deactivation' );
function my_deactivation() {
	wp_clear_scheduled_hook( 'my_hourly_event' );
}
