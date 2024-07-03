<?php

/**
 * Default template used to show a single data result.
 *
 * @since $ver$
 * @var Field[] $fields The fields.
 * @var array   $data   The item $data.
 */

use DataKit\DataViews\Field\Field;

?>

<button data-close-modal>Close modal</button>
<table class="dataviews-view-table">
	<?php foreach ( $fields as $field ): ?>
        <tr>
            <th><?php echo $field->header() ?></th>
            <td><?php echo $field->get_value( $data ) ?></td>
        </tr>
	<?php endforeach; ?>
</table>
