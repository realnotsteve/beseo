<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="be-schema-social-tab-content" class="be-schema-social-tab-panel">
    <h2><?php esc_html_e( 'Open Graph', 'beseo' ); ?></h2>

    <div class="be-schema-social-section">
        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Global Defaults', 'beseo' ); ?></h4>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Global Default Image', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-image-field">
                            <input type="text"
                                   id="be_schema_global_default_image"
                                   name="be_schema_global_default_image"
                                   value="<?php echo esc_url( $global_default_image ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_global_default_image"
                                    data-target-preview="be_schema_global_default_image_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_global_default_image"
                                    data-target-preview="be_schema_global_default_image_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php esc_html_e(
                                'Used as a final fallback when there is no featured image and no platform-specific default image. Recommended specs | Aspect Ratio: 1.91:1 | Resolution: 1200x630 | Formats: JPEG or PNG.',
                                'beseo'
                            ); ?>
                        </p>
                        <div class="be-schema-image-field" style="margin-top: 12px;">
                            <label for="be_schema_global_image_1_1" class="screen-reader-text"><?php esc_html_e( 'Square Default Image (1:1 @ 1200x1200)', 'beseo' ); ?></label>
                            <input type="text"
                                   id="be_schema_global_image_1_1"
                                   name="be_schema_global_image_1_1"
                                   value="<?php echo esc_url( $global_image_1_1 ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_global_image_1_1"
                                    data-target-preview="be_schema_global_image_1_1_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_global_image_1_1"
                                    data-target-preview="be_schema_global_image_1_1_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php esc_html_e(
                                'Optional square Open Graph image for platforms that prefer 1:1. Recommended size: 1200x1200.',
                                'beseo'
                            ); ?>
                        </p>
                        <div id="be_schema_global_image_1_1_preview"
                             class="be-schema-image-preview">
                            <?php if ( $global_image_1_1 ) : ?>
                                <img src="<?php echo esc_url( $global_image_1_1 ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <div class="be-schema-optional-controls"
                             data-optional-scope="global-default-images"
                             data-optional-hidden="be_schema_global_images_optional"
                             data-optional-singleton="image_16_9,image_5_4,image_4_5,image_1_1_91,image_9_16">
                            <label class="screen-reader-text" for="be-schema-global-images-optional"><?php esc_html_e( 'Add global default image', 'beseo' ); ?></label>
                            <select id="be-schema-global-images-optional" aria-label="<?php esc_attr_e( 'Add global default image', 'beseo' ); ?>">
                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                <option value="image_1_91" disabled><?php esc_html_e( '1.91:1 @ 1200x630 (default)', 'beseo' ); ?></option>
                                <option value="image_1_1" disabled><?php esc_html_e( '1:1 @ 1200x1200 (square)', 'beseo' ); ?></option>
                                <option value="image_16_9"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></option>
                                <option value="image_5_4"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></option>
                                <option value="image_4_5"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></option>
                                <option value="image_1_1_91"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></option>
                                <option value="image_9_16"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></option>
                            </select>
                            <button type="button"
                                    class="button be-schema-optional-add"
                                    data-optional-add="global-default-images"
                                    disabled>
                                +
                            </button>
                            <input type="hidden" name="be_schema_global_images_optional" id="be_schema_global_images_optional" value="<?php echo esc_attr( $global_images_optional_serialized ); ?>" />
                        </div>
                        <div class="be-schema-optional-fields" id="be-schema-global-images-optional-fields">
                            <div class="be-schema-optional-field<?php echo in_array( 'image_16_9', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                <div class="be-schema-image-field">
                                    <span class="be-schema-optional-label"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></span>
                                    <label for="be_schema_global_image_16_9" class="screen-reader-text"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></label>
                                    <input type="text"
                                           id="be_schema_global_image_16_9"
                                           name="be_schema_global_image_16_9"
                                           value="<?php echo esc_url( $global_image_16_9 ); ?>"
                                           class="regular-text" />
                                    <button type="button"
                                            class="button be-schema-image-select"
                                            data-target-input="be_schema_global_image_16_9"
                                            data-target-preview="be_schema_global_image_16_9_preview">
                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                    </button>
                                    <button type="button"
                                            class="button be-schema-image-clear"
                                            data-target-input="be_schema_global_image_16_9"
                                            data-target-preview="be_schema_global_image_16_9_preview">
                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                    </button>
                                </div>
                                <div id="be_schema_global_image_16_9_preview"
                                     class="be-schema-image-preview">
                                    <?php if ( $global_image_16_9 ) : ?>
                                        <img src="<?php echo esc_url( $global_image_16_9 ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'image_5_4', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_5_4">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_5_4">−</button>
                                <div class="be-schema-image-field">
                                    <span class="be-schema-optional-label"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></span>
                                    <label for="be_schema_global_image_5_4" class="screen-reader-text"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></label>
                                    <input type="text"
                                           id="be_schema_global_image_5_4"
                                           name="be_schema_global_image_5_4"
                                           value="<?php echo esc_url( $global_image_5_4 ); ?>"
                                           class="regular-text" />
                                    <button type="button"
                                            class="button be-schema-image-select"
                                            data-target-input="be_schema_global_image_5_4"
                                            data-target-preview="be_schema_global_image_5_4_preview">
                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                    </button>
                                    <button type="button"
                                            class="button be-schema-image-clear"
                                            data-target-input="be_schema_global_image_5_4"
                                            data-target-preview="be_schema_global_image_5_4_preview">
                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                    </button>
                                </div>
                                <div id="be_schema_global_image_5_4_preview"
                                     class="be-schema-image-preview">
                                    <?php if ( $global_image_5_4 ) : ?>
                                        <img src="<?php echo esc_url( $global_image_5_4 ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'image_4_5', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_5">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_5">−</button>
                                <div class="be-schema-image-field">
                                    <span class="be-schema-optional-label"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></span>
                                    <label for="be_schema_global_image_4_5" class="screen-reader-text"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></label>
                                    <input type="text"
                                           id="be_schema_global_image_4_5"
                                           name="be_schema_global_image_4_5"
                                           value="<?php echo esc_url( $global_image_4_5 ); ?>"
                                           class="regular-text" />
                                    <button type="button"
                                            class="button be-schema-image-select"
                                            data-target-input="be_schema_global_image_4_5"
                                            data-target-preview="be_schema_global_image_4_5_preview">
                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                    </button>
                                    <button type="button"
                                            class="button be-schema-image-clear"
                                            data-target-input="be_schema_global_image_4_5"
                                            data-target-preview="be_schema_global_image_4_5_preview">
                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                    </button>
                                </div>
                                <div id="be_schema_global_image_4_5_preview"
                                     class="be-schema-image-preview">
                                    <?php if ( $global_image_4_5 ) : ?>
                                        <img src="<?php echo esc_url( $global_image_4_5 ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'image_1_1_91', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1_91">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1_91">−</button>
                                <div class="be-schema-image-field">
                                    <span class="be-schema-optional-label"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></span>
                                    <label for="be_schema_global_image_1_1_91" class="screen-reader-text"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></label>
                                    <input type="text"
                                           id="be_schema_global_image_1_1_91"
                                           name="be_schema_global_image_1_1_91"
                                           value="<?php echo esc_url( $global_image_1_1_91 ); ?>"
                                           class="regular-text" />
                                    <button type="button"
                                            class="button be-schema-image-select"
                                            data-target-input="be_schema_global_image_1_1_91"
                                            data-target-preview="be_schema_global_image_1_1_91_preview">
                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                    </button>
                                    <button type="button"
                                            class="button be-schema-image-clear"
                                            data-target-input="be_schema_global_image_1_1_91"
                                            data-target-preview="be_schema_global_image_1_1_91_preview">
                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                    </button>
                                </div>
                                <div id="be_schema_global_image_1_1_91_preview"
                                     class="be-schema-image-preview">
                                    <?php if ( $global_image_1_1_91 ) : ?>
                                        <img src="<?php echo esc_url( $global_image_1_1_91 ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'image_9_16', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                <div class="be-schema-image-field">
                                    <span class="be-schema-optional-label"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></span>
                                    <label for="be_schema_global_image_9_16" class="screen-reader-text"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></label>
                                    <input type="text"
                                           id="be_schema_global_image_9_16"
                                           name="be_schema_global_image_9_16"
                                           value="<?php echo esc_url( $global_image_9_16 ); ?>"
                                           class="regular-text" />
                                    <button type="button"
                                            class="button be-schema-image-select"
                                            data-target-input="be_schema_global_image_9_16"
                                            data-target-preview="be_schema_global_image_9_16_preview">
                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                    </button>
                                    <button type="button"
                                            class="button be-schema-image-clear"
                                            data-target-input="be_schema_global_image_9_16"
                                            data-target-preview="be_schema_global_image_9_16_preview">
                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                    </button>
                                </div>
                                <div id="be_schema_global_image_9_16_preview"
                                     class="be-schema-image-preview">
                                    <?php if ( $global_image_9_16 ) : ?>
                                        <img src="<?php echo esc_url( $global_image_9_16 ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
