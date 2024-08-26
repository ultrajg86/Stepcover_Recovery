<?php

/**
 * Fired during plugin activation
 *
 * @link       https://steppay.kr
 * @since      1.0.0
 *
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 * @author     StepPay <dev@steppay.kr>
 */
class Stepcover_Recovery_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
	    $post_data = [
	        [
                'post_id_key' => 'stepcover_endpoint_recovery_id',
                'post_title' => '결제 복구',
                'post_name' => 'recover',
                'post_content' => '[stepcover-recovery]',
                'post_version_key' => 'stepcover_recovery_version',
                'post_version' => 1
            ],
            [
                'post_id_key' => 'stepcover_endpoint_recovery_complete_id',
                'post_title' => '결제 복구 완료',
                'post_name' => 'recover-complete',
                'post_content' => '[stepcover-recovery-complete]',
                'post_version_key' => 'stepcover_recovery_complete_version',
                'post_version' => 1
            ],
            [
                'post_id_key' => 'stepcover_endpoint_change_date_id',
                'post_title' => '결제일 변경',
                'post_name' => 'change-date',
                'post_content' => '[stepcover-change-date]',
                'post_version_key' => 'stepcover_change_date_version',
                'post_version' => 1
            ],
            [
                'post_id_key' => 'stepcover_endpoint_change_date_complete_id',
                'post_title' => '결제일 변경 완료',
                'post_name' => 'change-date-complete',
                'post_content' => '[stepcover-change-date-complete]',
                'post_version_key' => 'stepcover_change_date_complete_version',
                'post_version' => 1
            ],
            [
                'post_id_key' => 'stepcover_endpoint_delay_id',
                'post_title' => '나중에 알림',
                'post_name' => 'delay',
                'post_content' => '[stepcover-delay]',
                'post_version_key' => 'stepcover_delay_version',
                'post_version' => 1
            ],
            [
                'post_id_key' => 'stepcover_endpoint_recover_failed_id',
                'post_title' => '결제 복구 실패',
                'post_name' => 'recover-failed',
                'post_content' => '[stepcover-recover-failed]',
                'post_version_key' => 'stepcover_recovery_failed_version',
                'post_version' => 1
            ]
        ];
	    foreach ($post_data as $post_datum) {
	        $post_id = get_option($post_datum['post_id_key']);
	        if (!$post_id) {
                $post_id = wp_insert_post([
                    'comment_status' => 'close',
                    'ping_status' => 'close',
                    'post_author' => get_current_user_id(),
                    'post_title' => $post_datum['post_title'],
                    'post_name' => $post_datum['post_name'],
                    'post_status' => 'publish',
                    'post_content' => $post_datum['post_content'],
                    'post_type' => 'page'
                ]);
                update_post_meta($post_id, $post_datum['post_version_key'], $post_datum['post_version']);
                update_option($post_datum['post_id_key'], $post_id);
            }
        }
	}

}
