<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
wp_nonce_field( $args['nonce']['action'], $args['nonce']['name'] );
?>
<table class="form-table" role="presentation">
<tr>
<th scope="row"><?php echo esc_html_e( 'Type', 'simple-seo-improvements' ); ?></th>
<td>
<select name="<?php echo $args['name']; ?>[type]">
	<option value=""><?php esc_html_e( '--- select ---', 'simple-seo-improvements' ); ?></option>
<?php
foreach ( $args['types'] as $option_value => $option_label ) {
	printf(
		'<option value="%s" %s>%s</option>',
		esc_attr( $option_value ),
		selected( $args['value']['type'], $option_value ),
		esc_html( $option_label )
	);
}
?>
</select>
</td>
</tr>
</table>


<?php
foreach ( $args['fields'] as $group ) {
	?>
<div>
	<h3><?php echo $group['label']; ?></h3>
</div>
<table class="form-table" role="presentation"><tbody>
	<?php
	foreach ( $group['fields']  as $name => $label ) {
		$value = isset( $args['value'][ $name ] ) ? $args['value'][ $name ] : '';
		?>
<tr>
<th scope="row"><?php echo esc_html( $label ); ?></th>
<td>
		<?php
		switch ( $group['type'] ) {
			case 'text':
				?>
<input type="text" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( $value ); ?>" >
				<?php
				break;
			case 'hours':
				$value = wp_parse_args(
					$value,
					array(
						'opens'  => '',
						'closes' => '',
					)
				);
				?>
<ul>
	<li><?php esc_html_e( 'Opens:', 'simple-seo-improvements' ); ?> <input placeholder="hh:mm" class="small-text" pattern="[0-9]{2}:[0-9]{2}" type="text" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $name ); ?>][opens]" value="<?php echo esc_attr( $value['opens'] ); ?>" ></li>
	<li><?php esc_html_e( 'Closes:', 'simple-seo-improvements' ); ?> <input placeholder="hh:mm" class="small-text" pattern="[0-9]{2}:[0-9]{2}" type="text" name="<?php echo esc_attr( $args['name'] ); ?>[<?php echo esc_attr( $name ); ?>][closes]" value="<?php echo esc_attr( $value['closes'] ); ?>" ></li>
</ul>
				<?php
				break;
		}
		?>
</td>
</tr>
		<?php
	}
	?>
</tbody>
</table>
	<?php
}


