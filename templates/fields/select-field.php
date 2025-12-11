<?php
/**
 * Select Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <div class="wpmn-select">   
        <select id="<?php echo esc_attr( $field['name'] ); ?>" 
            name="<?php echo isset( $field['name'] ) ? esc_attr( $field['name'] ) : ''; ?>"
            <?php if (!empty($field['data_hide'])) : ?>
                data-hide="<?php echo esc_attr($field['data_hide']); ?>"
            <?php endif; ?>>

            <?php foreach ($field['options'] as $key => $label) : 
                $data_show = isset($field['data_show'][$key]) ? $field['data_show'][$key] : '';
                $disabled_options = isset( $field['disabled_options'] ) ? $field['disabled_options'] : array(); ?>
                <option
                    value="<?php echo esc_attr($key); ?>"
                    data-show="<?php echo esc_attr($data_show); ?>"
                    <?php echo in_array( $key, $disabled_options ) ? 'disabled' : ''; ?>
                    <?php selected($field_Val, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
</td>