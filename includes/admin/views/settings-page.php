<?php
/**
 * Settings Page View
 *
 * @package AdvancedImageOptimizer\Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="aio-settings-page">
        <form method="post" action="options.php">
            <?php
            settings_fields('wyoshi_img_opt_options');
            do_settings_sections('wyoshi-img-opt-settings');
            ?>
            
            <div class="aio-settings-sections">
                <!-- General Settings -->
                <div class="aio-settings-section">
                    <div class="aio-section-header">
                        <h2><?php _e('General Settings', 'wyoshi-image-optimizer'); ?></h2>
                        <p class="description"><?php _e('Configure basic optimization behavior.', 'wyoshi-image-optimizer'); ?></p>
                    </div>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="auto_optimize"><?php _e('Auto Optimize', 'wyoshi-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $options = get_option('wyoshi_img_opt_options', []);
                                    $auto_optimize = isset($options['auto_optimize']) ? $options['auto_optimize'] : false;
                                    ?>
                                    <label>
                                        <input type="checkbox" name="wyoshi_img_opt_options[auto_optimize]" id="auto_optimize" value="1" <?php checked(1, $auto_optimize); ?> />
                                        <?php _e('Automatically optimize images when uploaded', 'wyoshi-image-optimizer'); ?>
                                    </label>
                                    <p class="description"><?php _e('When enabled, images will be automatically optimized upon upload to the media library.', 'wyoshi-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="generate_webp"><?php _e('Generate WebP', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $generate_webp = isset($options['generate_webp']) ? $options['generate_webp'] : false; ?>
                                    <label>
                                        <input type="checkbox" name="wyoshi_img_opt_options[generate_webp]" id="generate_webp" value="1" <?php checked(1, $generate_webp); ?> />
                                        <?php _e('Generate WebP versions of images', 'advanced-image-optimizer'); ?>
                                    </label>
                                    <p class="description"><?php _e('WebP format provides better compression than JPEG and PNG while maintaining quality.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="generate_avif"><?php _e('Generate AVIF', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $generate_avif = isset($options['generate_avif']) ? $options['generate_avif'] : false; ?>
                                    <label>
                                        <input type="checkbox" name="wyoshi_img_opt_options[generate_avif]" id="generate_avif" value="1" <?php checked(1, $generate_avif); ?> <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')) echo 'disabled'; ?> />
                                        <?php _e('Generate AVIF versions of images', 'advanced-image-optimizer'); ?>
                                        <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')): ?>
                                            <span class="aio-pro-badge"><?php _e('Pro', 'advanced-image-optimizer'); ?></span>
                                        <?php endif; ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('AVIF format provides even better compression than WebP.', 'advanced-image-optimizer'); ?>
                                        <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')): ?>
                                            <a href="#" class="aio-pro-link"><?php _e('Upgrade to Pro', 'advanced-image-optimizer'); ?></a>
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="backup_original"><?php _e('Backup Original', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $backup_original = isset($options['backup_original']) ? $options['backup_original'] : true; ?>
                                    <label>
                                        <input type="checkbox" name="wyoshi_img_opt_options[backup_original]" id="backup_original" value="1" <?php checked(1, $backup_original); ?> />
                                        <?php _e('Keep backup copies of original images', 'advanced-image-optimizer'); ?>
                                    </label>
                                    <p class="description"><?php _e('Recommended. Allows you to restore original images if needed.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Quality Settings -->
                <div class="aio-settings-section">
                    <div class="aio-section-header">
                        <h2><?php _e('Quality Settings', 'advanced-image-optimizer'); ?></h2>
                        <p class="description"><?php _e('Adjust compression quality for different formats.', 'advanced-image-optimizer'); ?></p>
                    </div>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="webp_quality"><?php _e('WebP Quality', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $webp_quality = isset($options['webp_quality']) ? $options['webp_quality'] : 80; ?>
                                    <input type="number" name="wyoshi_img_opt_options[webp_quality]" id="webp_quality" value="<?php echo esc_attr($webp_quality); ?>" min="1" max="100" class="small-text" />
                                    <span class="aio-quality-slider-container">
                                        <input type="range" class="aio-quality-slider" data-target="webp_quality" min="1" max="100" value="<?php echo esc_attr($webp_quality); ?>" />
                                    </span>
                                    <p class="description"><?php _e('Higher values mean better quality but larger file sizes. Recommended: 75-85', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="avif_quality"><?php _e('AVIF Quality', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $avif_quality = isset($options['avif_quality']) ? $options['avif_quality'] : 70; ?>
                                    <input type="number" name="wyoshi_img_opt_options[avif_quality]" id="avif_quality" value="<?php echo esc_attr($avif_quality); ?>" min="1" max="100" class="small-text" <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')) echo 'disabled'; ?> />
                                    <span class="aio-quality-slider-container">
                                        <input type="range" class="aio-quality-slider" data-target="avif_quality" min="1" max="100" value="<?php echo esc_attr($avif_quality); ?>" <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')) echo 'disabled'; ?> />
                                    </span>
                                    <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')): ?>
                                        <span class="aio-pro-badge"><?php _e('Pro', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                    <p class="description"><?php _e('AVIF can achieve similar quality at lower values. Recommended: 65-75', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="jpeg_quality"><?php _e('JPEG Quality', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $jpeg_quality = isset($options['jpeg_quality']) ? $options['jpeg_quality'] : 85; ?>
                                    <input type="number" name="wyoshi_img_opt_options[jpeg_quality]" id="jpeg_quality" value="<?php echo esc_attr($jpeg_quality); ?>" min="1" max="100" class="small-text" />
                                    <span class="aio-quality-slider-container">
                                        <input type="range" class="aio-quality-slider" data-target="jpeg_quality" min="1" max="100" value="<?php echo esc_attr($jpeg_quality); ?>" />
                                    </span>
                                    <p class="description"><?php _e('Quality for JPEG optimization. Recommended: 80-90', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Advanced Settings -->
                <div class="aio-settings-section">
                    <div class="aio-section-header">
                        <h2><?php _e('Advanced Settings', 'advanced-image-optimizer'); ?></h2>
                        <p class="description"><?php _e('Advanced configuration options for power users.', 'advanced-image-optimizer'); ?></p>
                    </div>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="max_width"><?php _e('Maximum Width', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $max_width = isset($options['max_width']) ? $options['max_width'] : 0; ?>
                                    <input type="number" name="wyoshi_img_opt_options[max_width]" id="max_width" value="<?php echo esc_attr($max_width); ?>" min="0" max="10000" class="regular-text" />
                                    <p class="description"><?php _e('Maximum width in pixels. Images wider than this will be resized. Set to 0 for no limit.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="max_height"><?php _e('Maximum Height', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $max_height = isset($options['max_height']) ? $options['max_height'] : 0; ?>
                                    <input type="number" name="wyoshi_img_opt_options[max_height]" id="max_height" value="<?php echo esc_attr($max_height); ?>" min="0" max="10000" class="regular-text" />
                                    <p class="description"><?php _e('Maximum height in pixels. Images taller than this will be resized. Set to 0 for no limit.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="enable_logging"><?php _e('Enable Logging', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $enable_logging = isset($options['enable_logging']) ? $options['enable_logging'] : false; ?>
                                    <label>
                                        <input type="checkbox" name="wyoshi_img_opt_options[enable_logging]" id="enable_logging" value="1" <?php checked(1, $enable_logging); ?> />
                                        <?php _e('Enable detailed logging for debugging', 'advanced-image-optimizer'); ?>
                                    </label>
                                    <p class="description"><?php _e('Logs optimization activities and errors. Useful for troubleshooting but may impact performance.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="cleanup_interval"><?php _e('Cleanup Interval', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $cleanup_interval = isset($options['cleanup_interval']) ? $options['cleanup_interval'] : 30; ?>
                                    <select name="wyoshi_img_opt_options[cleanup_interval]" id="cleanup_interval">
                                        <option value="7" <?php selected(7, $cleanup_interval); ?>><?php _e('7 days', 'advanced-image-optimizer'); ?></option>
                                        <option value="14" <?php selected(14, $cleanup_interval); ?>><?php _e('14 days', 'advanced-image-optimizer'); ?></option>
                                        <option value="30" <?php selected(30, $cleanup_interval); ?>><?php _e('30 days', 'advanced-image-optimizer'); ?></option>
                                        <option value="60" <?php selected(60, $cleanup_interval); ?>><?php _e('60 days', 'advanced-image-optimizer'); ?></option>
                                        <option value="90" <?php selected(90, $cleanup_interval); ?>><?php _e('90 days', 'advanced-image-optimizer'); ?></option>
                                        <option value="0" <?php selected(0, $cleanup_interval); ?>><?php _e('Never', 'advanced-image-optimizer'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How often to clean up old backup files and logs.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Binary Settings -->
                <div class="aio-settings-section">
                    <div class="aio-section-header">
                        <h2><?php _e('Binary Settings', 'advanced-image-optimizer'); ?></h2>
                        <p class="description"><?php _e('Configure optimization binary paths and options.', 'advanced-image-optimizer'); ?></p>
                    </div>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="custom_binary_path"><?php _e('Custom Binary Path', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $custom_binary_path = isset($options['custom_binary_path']) ? $options['custom_binary_path'] : ''; ?>
                                    <input type="text" name="wyoshi_img_opt_options[custom_binary_path]" id="custom_binary_path" value="<?php echo esc_attr($custom_binary_path); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Custom path to optimization binaries. Leave empty to use bundled binaries.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="timeout"><?php _e('Process Timeout', 'advanced-image-optimizer'); ?></label>
                                </th>
                                <td>
                                    <?php $timeout = isset($options['timeout']) ? $options['timeout'] : 30; ?>
                                    <input type="number" name="wyoshi_img_opt_options[timeout]" id="timeout" value="<?php echo esc_attr($timeout); ?>" min="5" max="300" class="small-text" />
                                    <span><?php _e('seconds', 'advanced-image-optimizer'); ?></span>
                                    <p class="description"><?php _e('Maximum time to wait for optimization processes to complete.', 'advanced-image-optimizer'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php submit_button(); ?>
        </form>
        
        <!-- Reset Settings -->
        <div class="aio-reset-section">
            <h3><?php _e('Reset Settings', 'advanced-image-optimizer'); ?></h3>
            <p><?php _e('Reset all settings to their default values.', 'advanced-image-optimizer'); ?></p>
            <button type="button" class="button button-secondary" id="aio-reset-settings">
                <?php _e('Reset to Defaults', 'advanced-image-optimizer'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Quality sliders
    $('.aio-quality-slider').on('input', function() {
        const target = $(this).data('target');
        $('#' + target).val($(this).val());
    });
    
    // Update sliders when number inputs change
    $('input[type="number"]').on('input', function() {
        const id = $(this).attr('id');
        const slider = $('.aio-quality-slider[data-target="' + id + '"]');
        if (slider.length) {
            slider.val($(this).val());
        }
    });
    
    // Reset settings
    $('#aio-reset-settings').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to reset all settings to their default values?', 'advanced-image-optimizer'); ?>')) {
            return;
        }
        
        // Reset form values to defaults
        $('#auto_optimize').prop('checked', false);
        $('#generate_webp').prop('checked', false);
        $('#generate_avif').prop('checked', false);
        $('#backup_original').prop('checked', true);
        $('#webp_quality').val(80);
        $('#avif_quality').val(70);
        $('#jpeg_quality').val(85);
        $('#max_width').val(0);
        $('#max_height').val(0);
        $('#enable_logging').prop('checked', false);
        $('#cleanup_interval').val(30);
        $('#custom_binary_path').val('');
        $('#timeout').val(30);
        
        // Update sliders
        $('.aio-quality-slider').each(function() {
            const target = $(this).data('target');
            $(this).val($('#' + target).val());
        });
        
        alert('<?php _e('Settings have been reset to defaults. Click "Save Changes" to apply.', 'advanced-image-optimizer'); ?>');
    });
    
    // Pro feature tooltips
    $('.aio-pro-badge').on('click', function(e) {
        e.preventDefault();
        alert('<?php _e('This feature is available in the Pro version. Upgrade to unlock advanced optimization capabilities.', 'advanced-image-optimizer'); ?>');
    });
});
</script>