<?php
/**
 * Settings page template
 *
 * @package wpsync-webspark
 */

?>

<div class="wrap">
	<h1> <?php esc_html_e( 'WPSYNC WEBSPARK SETTINGS', 'wpsync-webspark' ); ?> </h1>
	<?php settings_errors(); ?>
	<form action="options.php" method="post">
		<h2>Sync Settings</h2>
		<?php
		settings_fields( 'wpsync_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="enable-sync-checkbox"><?php esc_html_e( 'Enable Synchronization', 'wpsync-webspark' ); ?></label></th>
				<td><input type="checkbox" id="enable-sync-checkbox" name="wpsync_enable_sync" value="1" <?php checked( 1, get_option( 'wpsync_enable_sync' ) ); ?>></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
