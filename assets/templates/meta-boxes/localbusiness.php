<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<script>
window.simple_seo_improvements = window.simple_seo_improvements || [];
window.simple_seo_improvements.local_business = window.simple_seo_improvements.local_business || [];
window.simple_seo_improvements.local_business.delete_type_row = function() {
	jQuery('.simple-seo-improvements-local-business-delete').on('click', function() {
		jQuery(this).closest('tr').detach();
	});
};
jQuery( document ).ready( function($) {
	$('#simple-seo-improvements-local-business-add').on( 'click', function(event) {
		var template = wp.template('local-business-type');
		var args = {
			id: 0,
			value: '',
			name: '<?php echo esc_attr( $args['name'] ); ?>'
		};
		event.preventDefault;
		$('#simple-seo-improvements-local-business-list').append( template(args));
		window.simple_seo_improvements.local_business.delete_type_row();
	});
	window.simple_seo_improvements.local_business.delete_type_row();
});
</script>
<?php
wp_nonce_field( $args['nonce']['action'], $args['nonce']['name'] );
function local_business_type_one_row( $args = array() ) {
	$options = apply_filters( 'iworks_simple_seo_improvements_get_lb_types', array() );
	$attr    = wp_parse_args(
		$args,
		array(
			'id'    => '{{{data.id}}}',
			'value' => '{{{data.value}}}',
			'name'  => '{{{data.name}}}',
		)
	);
	echo '<tr><td>';
	printf( '<select name="%s[type][]">', $attr['name'] );
	foreach ( $options as $option_value => $option_label ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $option_value ),
			selected( $attr['value'], $option_value ),
			esc_html( $option_label )
		);
	}
	echo '</select>';
	echo '</td><td>';
	printf(
		'<button class="simple-seo-improvements-local-business-delete"><span class="dashicons dashicons-trash"></span><span class="screen-reader-text">%s</span></button>',
		esc_html__( 'Delete Local Business Type', 'simple-seo-improvements' )
	);
	echo '</td></tr>';
}

?>
<table class="form-table" role="presentation"><tbody>
<tr>
<th scope="row"><?php echo esc_html_e( 'Type', 'simple-seo-improvements' ); ?></th>
<td>
<table class="wp-list-table striped">
<tbody id="simple-seo-improvements-local-business-list">
<?php
$values = $args['value']['type'];
if ( ! is_array( $values ) ) {
	$values = array( $values );
}
foreach ( $values as $one ) {
	local_business_type_one_row(
		array(
			'id'    => $one,
			'value' => $one,
			'name'  => $args['name'],
		)
	);
}
?>
</tbody>
</table>
<hr>
<button id="simple-seo-improvements-local-business-add" class="button"><?php esc_html_e( 'Add Type', 'simple-seo-improvements' ); ?></button>
</td>
</tr>
</tbody>
</table>
<?php
foreach ( $args['fields'] as $group ) {
	?>
<div>
	<h3><?php echo $group['label']; ?></h3>
</div>
<table class="form-table" role="presentation"><tbody>
	<?php
	foreach ( $group['fields']  as $name => $field ) {
		$field_type = $group['type'];
		$label      = $field;
		if ( is_array( $field ) ) {
			$label      = $field['label'];
			$field_type = $field['type'];
		}
		$value = isset( $args['value'][ $name ] ) ? $args['value'][ $name ] : '';
		?>
<tr>
<th scope="row"><?php echo esc_html( $label ); ?></th>
<td>
		<?php
		switch ( $field_type ) {
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
			case 'select':
				printf( '<select name="%s[%s]">', esc_attr( $args['name'] ), esc_attr( $name ) );
				foreach ( $field['options'] as $option_value => $option_label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $option_value ),
						selected( $value, $option_value ),
						esc_html( $option_label )
					);
				}
				echo '</select>';
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
?>
<script type="text/html" id="tmpl-local-business-type">
<?php
echo local_business_type_one_row(
	array(
		'name' => $args['name'],
	)
);
?>
</script>
