<?php

use DataKit\DataViews\DataView\DataItem;

/**
 * Default template used to show a single data item.
 *
 * @since $ver$
 *
 * @var DataItem $data_item The data item.
 */
?>
<table class="dataviews-view-table">
	<?php foreach ( $data_item->fields() as $field ) : ?>
		<tr>
			<th><?php echo esc_html( $field->label() ); ?></th>
			<td>
				<?php
				echo $field->get_value( $data_item->data() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The field renders the HTML.
				?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
