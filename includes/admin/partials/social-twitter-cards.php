<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$twitter_cards_section_id = isset( $be_schema_social_twitter_cards_section_id )
    ? (string) $be_schema_social_twitter_cards_section_id
    : '';
$twitter_cards_split_sections = ! empty( $be_schema_social_twitter_cards_split_sections );
$twitter_summary_description = isset( $be_schema_social_twitter_summary_description )
    ? (string) $be_schema_social_twitter_summary_description
    : __( 'Used for twitter:image when the Summary Card type is selected. If empty, Twitter falls back to the Global default image (if set).', 'beseo' );
$twitter_cards_section_attr = $twitter_cards_section_id ? ' id="' . esc_attr( $twitter_cards_section_id ) . '"' : '';
?>
<?php if ( $twitter_cards_split_sections ) : ?>
    <div class="be-schema-social-section">
        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Cards', 'beseo' ); ?></h4>
        <table class="form-table">
            <tbody>
                <tr class="be-schema-optional-row">
                    <th scope="row">
                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-optional-controls"
                             data-optional-scope="twitter"
                             data-optional-hidden="be_schema_twitter_optional"
                             data-optional-singleton="twitter_site,twitter_creator,twitter_image_alt">
                            <label class="screen-reader-text" for="be-schema-twitter-optional"><?php esc_html_e( 'Add optional Twitter property', 'beseo' ); ?></label>
                            <select id="be-schema-twitter-optional" aria-label="<?php esc_attr_e( 'Add optional Twitter property', 'beseo' ); ?>">
                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                <option value="twitter_site"><?php esc_html_e( '@Your Handle', 'beseo' ); ?></option>
                                <option value="twitter_creator"><?php esc_html_e( '@Author Handle', 'beseo' ); ?></option>
                                <option value="twitter_image_alt"><?php esc_html_e( 'Accessible Image Description', 'beseo' ); ?></option>
                            </select>
                            <button type="button"
                                    class="button be-schema-optional-add"
                                    data-optional-add="twitter"
                                    disabled>
                                +
                            </button>
                            <input type="hidden" name="be_schema_twitter_optional" id="be_schema_twitter_optional" value="<?php echo esc_attr( $twitter_optional_serialized ); ?>" />
                        </div>

                        <div class="be-schema-optional-fields" id="be-schema-twitter-optional-fields">
                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_site', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_site">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_site">−</button>
                                <label for="be_schema_twitter_site" class="screen-reader-text"><?php esc_html_e( 'Twitter Site Handle', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_site"
                                       id="be_schema_twitter_site"
                                       value="<?php echo esc_attr( $twitter_site ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:site" content="@…"> using this handle (with @ added if missing).',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_creator', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_creator">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_creator">−</button>
                                <label for="be_schema_twitter_creator" class="screen-reader-text"><?php esc_html_e( 'Twitter Creator Handle', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_creator"
                                       id="be_schema_twitter_creator"
                                       value="<?php echo esc_attr( $twitter_creator ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:creator" content="@…"> using this handle (with @ added if missing).',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_image_alt', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_image_alt">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_image_alt">−</button>
                                <label for="be_schema_twitter_image_alt" class="screen-reader-text"><?php esc_html_e( 'Twitter Image Alt Text', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_image_alt"
                                       id="be_schema_twitter_image_alt"
                                       value="<?php echo esc_attr( $twitter_image_alt ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:image:alt" content="..."> when provided.',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="be-schema-social-section">
        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Card Type', 'beseo' ); ?></th>
                    <td>
                        <fieldset>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="radio"
                                       name="be_schema_twitter_card_type"
                                       value="summary_large_image"
                                       <?php checked( 'summary_large_image', $twitter_card_type ); ?>
                                       data-target-enable="be_schema_twitter_default_image"
                                       data-target-disable="be_schema_twitter_default_image_alt" />
                                <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                            </label>
                            <label style="display:block;">
                                <input type="radio"
                                       name="be_schema_twitter_card_type"
                                       value="summary"
                                       <?php checked( 'summary', $twitter_card_type ); ?>
                                       data-target-enable="be_schema_twitter_default_image_alt"
                                       data-target-disable="be_schema_twitter_default_image" />
                                <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-image-field">
                            <input type="text"
                                   id="be_schema_twitter_default_image"
                                   name="be_schema_twitter_default_image"
                                   value="<?php echo esc_url( $twitter_default_image ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_twitter_default_image"
                                    data-target-preview="be_schema_twitter_default_image_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_twitter_default_image"
                                    data-target-preview="be_schema_twitter_default_image_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php esc_html_e(
                                'Used for twitter:image when there is no featured image on a page. If empty, Twitter falls back to the Global default image (if set).',
                                'beseo'
                            ); ?>
                        </p>
                        <div id="be_schema_twitter_default_image_preview"
                             class="be-schema-image-preview">
                            <?php if ( $twitter_default_image ) : ?>
                                <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-image-field">
                            <input type="text"
                                   id="be_schema_twitter_default_image_alt"
                                   name="be_schema_twitter_default_image_alt"
                                   value="<?php echo esc_url( $twitter_default_image_alt ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_twitter_default_image_alt"
                                    data-target-preview="be_schema_twitter_default_image_alt_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_twitter_default_image_alt"
                                    data-target-preview="be_schema_twitter_default_image_alt_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php echo esc_html( $twitter_summary_description ); ?>
                        </p>
                        <div id="be_schema_twitter_default_image_alt_preview"
                             class="be-schema-image-preview">
                            <?php if ( $twitter_default_image_alt ) : ?>
                                <img src="<?php echo esc_url( $twitter_default_image_alt ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
<?php else : ?>
    <div class="be-schema-social-section"<?php echo $twitter_cards_section_attr; ?>>
        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Cards', 'beseo' ); ?></h4>
        <table class="form-table">
            <tbody>
                <tr class="be-schema-optional-row">
                    <th scope="row">
                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-optional-controls"
                             data-optional-scope="twitter"
                             data-optional-hidden="be_schema_twitter_optional"
                             data-optional-singleton="twitter_site,twitter_creator,twitter_image_alt">
                            <label class="screen-reader-text" for="be-schema-twitter-optional"><?php esc_html_e( 'Add optional Twitter property', 'beseo' ); ?></label>
                            <select id="be-schema-twitter-optional" aria-label="<?php esc_attr_e( 'Add optional Twitter property', 'beseo' ); ?>">
                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                <option value="twitter_site"><?php esc_html_e( '@Your Handle', 'beseo' ); ?></option>
                                <option value="twitter_creator"><?php esc_html_e( '@Author Handle', 'beseo' ); ?></option>
                                <option value="twitter_image_alt"><?php esc_html_e( 'Accessible Image Description', 'beseo' ); ?></option>
                            </select>
                            <button type="button"
                                    class="button be-schema-optional-add"
                                    data-optional-add="twitter"
                                    disabled>
                                +
                            </button>
                            <input type="hidden" name="be_schema_twitter_optional" id="be_schema_twitter_optional" value="<?php echo esc_attr( $twitter_optional_serialized ); ?>" />
                        </div>

                        <div class="be-schema-optional-fields" id="be-schema-twitter-optional-fields">
                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_site', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_site">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_site">−</button>
                                <label for="be_schema_twitter_site" class="screen-reader-text"><?php esc_html_e( 'Twitter Site Handle', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_site"
                                       id="be_schema_twitter_site"
                                       value="<?php echo esc_attr( $twitter_site ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:site" content="@…"> using this handle (with @ added if missing).',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_creator', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_creator">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_creator">−</button>
                                <label for="be_schema_twitter_creator" class="screen-reader-text"><?php esc_html_e( 'Twitter Creator Handle', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_creator"
                                       id="be_schema_twitter_creator"
                                       value="<?php echo esc_attr( $twitter_creator ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:creator" content="@…"> using this handle (with @ added if missing).',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>

                            <div class="be-schema-optional-field<?php echo in_array( 'twitter_image_alt', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_image_alt">
                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_image_alt">−</button>
                                <label for="be_schema_twitter_image_alt" class="screen-reader-text"><?php esc_html_e( 'Twitter Image Alt Text', 'beseo' ); ?></label>
                                <input type="text"
                                       name="be_schema_twitter_image_alt"
                                       id="be_schema_twitter_image_alt"
                                       value="<?php echo esc_attr( $twitter_image_alt ); ?>"
                                       class="regular-text" />
                                <p class="description be-schema-social-description">
                                    <?php esc_html_e(
                                        'Outputs <meta name="twitter:image:alt" content="..."> when provided.',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Card Type', 'beseo' ); ?></th>
                    <td>
                        <fieldset>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="radio"
                                       name="be_schema_twitter_card_type"
                                       value="summary_large_image"
                                       <?php checked( 'summary_large_image', $twitter_card_type ); ?>
                                       data-target-enable="be_schema_twitter_default_image"
                                       data-target-disable="be_schema_twitter_default_image_alt" />
                                <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                            </label>
                            <label style="display:block;">
                                <input type="radio"
                                       name="be_schema_twitter_card_type"
                                       value="summary"
                                       <?php checked( 'summary', $twitter_card_type ); ?>
                                       data-target-enable="be_schema_twitter_default_image_alt"
                                       data-target-disable="be_schema_twitter_default_image" />
                                <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-image-field">
                            <input type="text"
                                   id="be_schema_twitter_default_image"
                                   name="be_schema_twitter_default_image"
                                   value="<?php echo esc_url( $twitter_default_image ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_twitter_default_image"
                                    data-target-preview="be_schema_twitter_default_image_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_twitter_default_image"
                                    data-target-preview="be_schema_twitter_default_image_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php esc_html_e(
                                'Used for twitter:image when there is no featured image on a page. If empty, Twitter falls back to the Global default image (if set).',
                                'beseo'
                            ); ?>
                        </p>
                        <div id="be_schema_twitter_default_image_preview"
                             class="be-schema-image-preview">
                            <?php if ( $twitter_default_image ) : ?>
                                <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                    </th>
                    <td>
                        <div class="be-schema-image-field">
                            <input type="text"
                                   id="be_schema_twitter_default_image_alt"
                                   name="be_schema_twitter_default_image_alt"
                                   value="<?php echo esc_url( $twitter_default_image_alt ); ?>"
                                   class="regular-text" />
                            <button type="button"
                                    class="button be-schema-image-select"
                                    data-target-input="be_schema_twitter_default_image_alt"
                                    data-target-preview="be_schema_twitter_default_image_alt_preview">
                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                            </button>
                            <button type="button"
                                    class="button be-schema-image-clear"
                                    data-target-input="be_schema_twitter_default_image_alt"
                                    data-target-preview="be_schema_twitter_default_image_alt_preview">
                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                            </button>
                        </div>
                        <p class="description be-schema-social-description">
                            <?php echo esc_html( $twitter_summary_description ); ?>
                        </p>
                        <div id="be_schema_twitter_default_image_alt_preview"
                             class="be-schema-image-preview">
                            <?php if ( $twitter_default_image_alt ) : ?>
                                <img src="<?php echo esc_url( $twitter_default_image_alt ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
<?php endif; ?>
