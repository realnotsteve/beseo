<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="be-schema-social-section">
    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Content', 'beseo' ); ?></h4>
    <table class="form-table">
        <tbody>
            <tr class="be-schema-optional-row">
                <th scope="row">
                    <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                </th>
                <td>
                    <div class="be-schema-optional-controls"
                         data-optional-scope="facebook"
                         data-optional-hidden="be_schema_facebook_optional"
                         data-optional-singleton="facebook_page_url,facebook_app_id,facebook_notes">
                        <label class="screen-reader-text" for="be-schema-facebook-optional"><?php esc_html_e( 'Add optional Facebook property', 'beseo' ); ?></label>
                        <select id="be-schema-facebook-optional" aria-label="<?php esc_attr_e( 'Add optional Facebook property', 'beseo' ); ?>">
                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                            <option value="facebook_page_url"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></option>
                            <option value="facebook_app_id"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></option>
                            <option value="facebook_notes"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></option>
                        </select>
                        <button type="button"
                                class="button be-schema-optional-add"
                                data-optional-add="facebook"
                                disabled>
                            +
                        </button>
                        <input type="hidden" name="be_schema_facebook_optional" id="be_schema_facebook_optional" value="<?php echo esc_attr( $facebook_optional_serialized ); ?>" />
                    </div>

                    <div class="be-schema-optional-fields" id="be-schema-facebook-optional-fields">
                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_page_url', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_page_url">
                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_page_url">−</button>
                            <label for="be_schema_facebook_page_url" class="screen-reader-text"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></label>
                            <input type="text"
                                   name="be_schema_facebook_page_url"
                                   id="be_schema_facebook_page_url"
                                   value="<?php echo esc_url( $facebook_page_url ); ?>"
                                   class="regular-text" />
                            <p class="description be-schema-social-description">
                                <?php esc_html_e(
                                    'A public Facebook Page URL for your site or organisation.',
                                    'beseo'
                                ); ?>
                            </p>
                        </div>

                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_app_id', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_app_id">
                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_app_id">−</button>
                            <label for="be_schema_facebook_app_id" class="screen-reader-text"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></label>
                            <input type="text"
                                   name="be_schema_facebook_app_id"
                                   id="be_schema_facebook_app_id"
                                   value="<?php echo esc_attr( $facebook_app_id ); ?>"
                                   class="regular-text" />
                            <p class="description be-schema-social-description">
                                <?php esc_html_e(
                                    'When set, the plugin outputs fb:app_id for Facebook debugging and analytics.',
                                    'beseo'
                                ); ?>
                            </p>
                        </div>

                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_notes', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_notes">
                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_notes">−</button>
                            <label for="be_schema_facebook_notes" class="screen-reader-text"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></label>
                            <textarea
                                name="be_schema_facebook_notes"
                                id="be_schema_facebook_notes"
                                rows="4"
                                class="large-text code"><?php echo esc_textarea( $facebook_notes ); ?></textarea>
                            <p class="description be-schema-social-description">
                                <?php esc_html_e(
                                    'Free-form notes for your own reference. This is never output on the front end.',
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
