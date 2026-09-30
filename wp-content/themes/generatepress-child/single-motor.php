<?php
/**
 * Single Motor Post Template
 *
 * Entry template for CPT motor in GeneratePress Child Theme.
 *
 * @package GeneratePress_Child_Ryokourent
 * @since   1.0.0
 */

// Route to template partial
$template = locate_template(array('templates/single-motor.php'));
if ($template) {
    require $template;
} else {
    require __DIR__ . '/templates/single-motor.php';
}
