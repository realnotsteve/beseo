<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!-- WEBSITE TAB -->
                <div id="be-schema-tab-website" class="be-schema-tab-panel">
                    <h2><?php esc_html_e( 'Website Entities', 'beseo' ); ?></h2>
                    <p class="description be-schema-description">
                        <?php esc_html_e(
                            'Configure site identity mode plus the Person, Organisation, Publisher, and shared logo/images used by the site-level JSON-LD graph.',
                            'beseo'
                        ); ?>
                    </p>

                    <div class="be-schema-website-layout">
                        <div class="be-schema-website-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-website-global"
                                       class="be-schema-website-tab-link be-schema-website-tab-active"
                                       data-website-tab="global">
                                        <?php esc_html_e( 'Global', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-person"
                                       class="be-schema-website-tab-link<?php echo $identity_person_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="person"
                                       <?php echo $identity_person_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Person', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-organization"
                                       class="be-schema-website-tab-link<?php echo $identity_organisation_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="organization"
                                       <?php echo $identity_organisation_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Organisation', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-publisher"
                                       class="be-schema-website-tab-link<?php echo $identity_publisher_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="publisher"
                                       <?php echo $identity_publisher_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Publisher', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                            <div class="be-schema-website-panels">

                                <!-- GLOBAL PANEL -->
                                <div id="be-schema-website-global"
                                     class="be-schema-website-panel be-schema-website-panel-active">
                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Site Identity Mode', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Identity', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-identity-options">
                                                        <div class="be-schema-identity-option">
                                                            <div class="be-schema-identity-toggle">
                                                                <label for="be_schema_identity_person_checkbox">
                                                                    <input type="checkbox"
                                                                           class="be-schema-identity-checkbox"
                                                                           id="be_schema_identity_person_checkbox"
                                                                           name="be_schema_identity_person_enabled"
                                                                           data-target-radio="be_schema_identity_person_radio"
                                                                           data-target-tab="person"
                                                                           <?php checked( $identity_person_enabled ); ?> />
                                                                    <?php esc_html_e( 'Person-First (Personal Brand)', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                            <div class="be-schema-identity-priority">
                                                                <label for="be_schema_identity_person_radio">
                                                                    <input type="radio"
                                                                           id="be_schema_identity_person_radio"
                                                                           class="be-schema-identity-radio"
                                                                           name="be_schema_site_identity_mode"
                                                                           value="person"
                                                                           <?php checked( 'person', $site_identity_mode ); ?>
                                                                           <?php disabled( ! $identity_person_enabled ); ?> />
                                                                    <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-identity-option">
                                                            <div class="be-schema-identity-toggle">
                                                                <label for="be_schema_identity_org_checkbox">
                                                                    <input type="checkbox"
                                                                           class="be-schema-identity-checkbox"
                                                                           id="be_schema_identity_org_checkbox"
                                                                           name="be_schema_identity_org_enabled"
                                                                           data-target-radio="be_schema_identity_org_radio"
                                                                           data-target-tab="organization"
                                                                           <?php checked( $identity_organisation_enabled ); ?> />
                                                                    <?php esc_html_e( 'Organisation-First (Company / Organisation)', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                            <div class="be-schema-identity-priority">
                                                                <label for="be_schema_identity_org_radio">
                                                                    <input type="radio"
                                                                           id="be_schema_identity_org_radio"
                                                                           class="be-schema-identity-radio"
                                                                           name="be_schema_site_identity_mode"
                                                                           value="organisation"
                                                                           <?php checked( 'organisation', $site_identity_mode ); ?>
                                                                           <?php disabled( ! $identity_organisation_enabled ); ?> />
                                                                    <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                        </div>

                                                    <div class="be-schema-identity-option">
                                                        <div class="be-schema-identity-toggle">
                                                            <label for="be_schema_identity_publisher_checkbox">
                                                                <input type="checkbox"
                                                                       class="be-schema-identity-checkbox"
                                                                       id="be_schema_identity_publisher_checkbox"
                                                                       name="be_schema_identity_publisher_enabled"
                                                                       data-target-radio="be_schema_identity_publisher_radio"
                                                                       data-target-tab="publisher"
                                                                       <?php checked( $identity_publisher_enabled ); ?>
                                                                       <?php disabled( ! $publisher_enabled ); ?> />
                                                                <?php esc_html_e( 'Publisher (Use Publisher Entity When Available)', 'beseo' ); ?>
                                                            </label>
                                                        </div>
                                                        <div class="be-schema-identity-priority">
                                                            <label for="be_schema_identity_publisher_radio">
                                                                <input type="radio"
                                                                       id="be_schema_identity_publisher_radio"
                                                                       class="be-schema-identity-radio"
                                                                       name="be_schema_site_identity_mode"
                                                                       value="publisher"
                                                                       <?php checked( 'publisher', $site_identity_mode ); ?>
                                                                       <?php disabled( ! $identity_publisher_enabled || ! $publisher_enabled ); ?> />
                                                                <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Controls which identity is prioritised for WebSite.about / WebSite.publisher. Other enabled entities remain in the graph as fallbacks.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Publisher identity can only be selected when WebSite.publisher is enabled on the Publisher tab; dedicated publisher is optional.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Site Logo (Shared)', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="org-logo"
                                                         data-optional-hidden="be_schema_org_logo_optional"
                                                         data-optional-singleton="image_16_9,image_4_3,image_1_1,image_3_4,image_9_16">
                                                        <label class="screen-reader-text" for="be-schema-org-logo-optional"><?php esc_html_e( 'Add site logo image', 'beseo' ); ?></label>
                                                        <select id="be-schema-org-logo-optional" aria-label="<?php esc_attr_e( 'Add site logo image', 'beseo' ); ?>">
                                                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                            <option value="image_16_9"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></option>
                                                            <option value="image_4_3"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></option>
                                                            <option value="image_1_1"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></option>
                                                            <option value="image_3_4"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></option>
                                                            <option value="image_9_16"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></option>
                                                        </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="org-logo"
                                                                    disabled>
                                                            +
                                                        </button>
                                                        <input type="hidden" name="be_schema_org_logo_optional" id="be_schema_org_logo_optional" value="<?php echo esc_attr( $org_logo_optional_serialized ); ?>" />
                                                    </div>

                                                    <div class="be-schema-optional-fields" id="be-schema-org-logo-optional-fields">
                                                        <div class="be-schema-optional-field<?php echo in_array( 'image_16_9', $org_logo_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                                            <label for="be_schema_org_logo_image_16_9" class="screen-reader-text"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <label class="be-schema-image-enable-label">
                                                                    <input type="checkbox"
                                                                           class="be-schema-image-enable"
                                                                           data-target-input="be_schema_org_logo_image_16_9"
                                                                           data-target-select="be_schema_org_logo_image_16_9_select"
                                                                           data-target-clear="be_schema_org_logo_image_16_9_clear"
                                                                           name="be_schema_org_logo_image_16_9_enabled"
                                                                           <?php checked( $org_logo_image_16_9_enabled ); ?> />
                                                                    <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                </label>
                                                                <input type="text"
                                                                       id="be_schema_org_logo_image_16_9"
                                                                       name="be_schema_org_logo_image_16_9"
                                                                       value="<?php echo esc_url( $org_logo_image_16_9 ); ?>"
                                                                       class="regular-text"
                                                                       <?php disabled( ! $org_logo_image_16_9_enabled ); ?> />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        id="be_schema_org_logo_image_16_9_select"
                                                                        data-target-input="be_schema_org_logo_image_16_9"
                                                                        data-target-preview="be_schema_org_logo_image_16_9_preview"
                                                                        <?php disabled( ! $org_logo_image_16_9_enabled ); ?>>
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        id="be_schema_org_logo_image_16_9_clear"
                                                                        data-target-input="be_schema_org_logo_image_16_9"
                                                                        data-target-preview="be_schema_org_logo_image_16_9_preview"
                                                                        <?php disabled( ! $org_logo_image_16_9_enabled ); ?>>
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                                <span id="be_schema_org_logo_image_16_9_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                            </div>
                                                            <p class="description be-schema-description">
                                                                <?php esc_html_e( 'Recommended dimensions: 1920x1080.', 'beseo' ); ?>
                                                            </p>
                                                            <div id="be_schema_org_logo_image_16_9_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $org_logo_image_16_9 ) : ?>
                                                                    <img src="<?php echo esc_url( $org_logo_image_16_9 ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'image_4_3', $org_logo_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_3">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_3">−</button>
                                                            <label for="be_schema_org_logo_image_4_3" class="screen-reader-text"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <label class="be-schema-image-enable-label">
                                                                    <input type="checkbox"
                                                                           class="be-schema-image-enable"
                                                                           data-target-input="be_schema_org_logo_image_4_3"
                                                                           data-target-select="be_schema_org_logo_image_4_3_select"
                                                                           data-target-clear="be_schema_org_logo_image_4_3_clear"
                                                                           name="be_schema_org_logo_image_4_3_enabled"
                                                                           <?php checked( $org_logo_image_4_3_enabled ); ?> />
                                                                    <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                </label>
                                                                <input type="text"
                                                                       id="be_schema_org_logo_image_4_3"
                                                                       name="be_schema_org_logo_image_4_3"
                                                                       value="<?php echo esc_url( $org_logo_image_4_3 ); ?>"
                                                                       class="regular-text"
                                                                       <?php disabled( ! $org_logo_image_4_3_enabled ); ?> />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        id="be_schema_org_logo_image_4_3_select"
                                                                        data-target-input="be_schema_org_logo_image_4_3"
                                                                        data-target-preview="be_schema_org_logo_image_4_3_preview"
                                                                        <?php disabled( ! $org_logo_image_4_3_enabled ); ?>>
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        id="be_schema_org_logo_image_4_3_clear"
                                                                        data-target-input="be_schema_org_logo_image_4_3"
                                                                        data-target-preview="be_schema_org_logo_image_4_3_preview"
                                                                        <?php disabled( ! $org_logo_image_4_3_enabled ); ?>>
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                                <span id="be_schema_org_logo_image_4_3_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                            </div>
                                                            <p class="description be-schema-description">
                                                                <?php esc_html_e( 'Recommended dimensions: 1600x1200.', 'beseo' ); ?>
                                                            </p>
                                                            <div id="be_schema_org_logo_image_4_3_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $org_logo_image_4_3 ) : ?>
                                                                    <img src="<?php echo esc_url( $org_logo_image_4_3 ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'image_1_1', $org_logo_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1">−</button>
                                                            <label for="be_schema_org_logo_image_1_1" class="screen-reader-text"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <label class="be-schema-image-enable-label">
                                                                    <input type="checkbox"
                                                                           class="be-schema-image-enable"
                                                                           data-target-input="be_schema_org_logo_image_1_1"
                                                                           data-target-select="be_schema_org_logo_image_1_1_select"
                                                                           data-target-clear="be_schema_org_logo_image_1_1_clear"
                                                                           name="be_schema_org_logo_image_1_1_enabled"
                                                                           <?php checked( $org_logo_image_1_1_enabled ); ?> />
                                                                    <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                </label>
                                                                <input type="text"
                                                                       id="be_schema_org_logo_image_1_1"
                                                                       name="be_schema_org_logo_image_1_1"
                                                                       value="<?php echo esc_url( $org_logo_image_1_1 ); ?>"
                                                                       class="regular-text"
                                                                       <?php disabled( ! $org_logo_image_1_1_enabled ); ?> />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        id="be_schema_org_logo_image_1_1_select"
                                                                        data-target-input="be_schema_org_logo_image_1_1"
                                                                        data-target-preview="be_schema_org_logo_image_1_1_preview"
                                                                        <?php disabled( ! $org_logo_image_1_1_enabled ); ?>>
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        id="be_schema_org_logo_image_1_1_clear"
                                                                        data-target-input="be_schema_org_logo_image_1_1"
                                                                        data-target-preview="be_schema_org_logo_image_1_1_preview"
                                                                        <?php disabled( ! $org_logo_image_1_1_enabled ); ?>>
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                                <span id="be_schema_org_logo_image_1_1_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                            </div>
                                                            <p class="description be-schema-description">
                                                                <?php esc_html_e( 'Recommended dimensions: 1200x1200.', 'beseo' ); ?>
                                                            </p>
                                                            <div id="be_schema_org_logo_image_1_1_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $org_logo_image_1_1 ) : ?>
                                                                    <img src="<?php echo esc_url( $org_logo_image_1_1 ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'image_3_4', $org_logo_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_3_4">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_3_4">−</button>
                                                            <label for="be_schema_org_logo_image_3_4" class="screen-reader-text"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <label class="be-schema-image-enable-label">
                                                                    <input type="checkbox"
                                                                           class="be-schema-image-enable"
                                                                           data-target-input="be_schema_org_logo_image_3_4"
                                                                           data-target-select="be_schema_org_logo_image_3_4_select"
                                                                           data-target-clear="be_schema_org_logo_image_3_4_clear"
                                                                           name="be_schema_org_logo_image_3_4_enabled"
                                                                           <?php checked( $org_logo_image_3_4_enabled ); ?> />
                                                                    <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                </label>
                                                                <input type="text"
                                                                       id="be_schema_org_logo_image_3_4"
                                                                       name="be_schema_org_logo_image_3_4"
                                                                       value="<?php echo esc_url( $org_logo_image_3_4 ); ?>"
                                                                       class="regular-text"
                                                                       <?php disabled( ! $org_logo_image_3_4_enabled ); ?> />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        id="be_schema_org_logo_image_3_4_select"
                                                                        data-target-input="be_schema_org_logo_image_3_4"
                                                                        data-target-preview="be_schema_org_logo_image_3_4_preview"
                                                                        <?php disabled( ! $org_logo_image_3_4_enabled ); ?>>
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        id="be_schema_org_logo_image_3_4_clear"
                                                                        data-target-input="be_schema_org_logo_image_3_4"
                                                                        data-target-preview="be_schema_org_logo_image_3_4_preview"
                                                                        <?php disabled( ! $org_logo_image_3_4_enabled ); ?>>
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                                <span id="be_schema_org_logo_image_3_4_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                            </div>
                                                            <p class="description be-schema-description">
                                                                <?php esc_html_e( 'Recommended dimensions: 1200x1600.', 'beseo' ); ?>
                                                            </p>
                                                            <div id="be_schema_org_logo_image_3_4_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $org_logo_image_3_4 ) : ?>
                                                                    <img src="<?php echo esc_url( $org_logo_image_3_4 ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'image_9_16', $org_logo_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                                            <label for="be_schema_org_logo_image_9_16" class="screen-reader-text"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <label class="be-schema-image-enable-label">
                                                                    <input type="checkbox"
                                                                           class="be-schema-image-enable"
                                                                           data-target-input="be_schema_org_logo_image_9_16"
                                                                           data-target-select="be_schema_org_logo_image_9_16_select"
                                                                           data-target-clear="be_schema_org_logo_image_9_16_clear"
                                                                           name="be_schema_org_logo_image_9_16_enabled"
                                                                           <?php checked( $org_logo_image_9_16_enabled ); ?> />
                                                                    <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                </label>
                                                                <input type="text"
                                                                       id="be_schema_org_logo_image_9_16"
                                                                       name="be_schema_org_logo_image_9_16"
                                                                       value="<?php echo esc_url( $org_logo_image_9_16 ); ?>"
                                                                       class="regular-text"
                                                                       <?php disabled( ! $org_logo_image_9_16_enabled ); ?> />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        id="be_schema_org_logo_image_9_16_select"
                                                                        data-target-input="be_schema_org_logo_image_9_16"
                                                                        data-target-preview="be_schema_org_logo_image_9_16_preview"
                                                                        <?php disabled( ! $org_logo_image_9_16_enabled ); ?>>
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        id="be_schema_org_logo_image_9_16_clear"
                                                                        data-target-input="be_schema_org_logo_image_9_16"
                                                                        data-target-preview="be_schema_org_logo_image_9_16_preview"
                                                                        <?php disabled( ! $org_logo_image_9_16_enabled ); ?>>
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                                <span id="be_schema_org_logo_image_9_16_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                            </div>
                                                            <p class="description be-schema-description">
                                                                <?php esc_html_e( 'Recommended dimensions: 1080x1920.', 'beseo' ); ?>
                                                            </p>
                                                            <div id="be_schema_org_logo_image_9_16_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $org_logo_image_9_16 ) : ?>
                                                                    <img src="<?php echo esc_url( $org_logo_image_9_16 ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td colspan="2">
                                                    <hr class="be-schema-global-divider" />
                                                </td>
                                            </tr>

                                            <tr class="be-schema-optional-row">
                                                <th scope="row">
                                                    <?php esc_html_e( 'WebSite Featured Image(s)', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e( 'Used by the WebSite or WebPage schema when a featured image is needed.', 'beseo' ); ?>
                                                    </p>
                                                        <div class="be-schema-optional-controls"
                                                             data-optional-scope="website-images"
                                                             data-optional-hidden="be_schema_website_images_optional"
                                                             data-optional-singleton="image_16_9,image_4_3,image_1_1,image_3_4,image_9_16">
                                                            <label class="screen-reader-text" for="be-schema-website-images-optional"><?php esc_html_e( 'Add optional WebSite image', 'beseo' ); ?></label>
                                                            <select id="be-schema-website-images-optional" aria-label="<?php esc_attr_e( 'Add optional WebSite image', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional image…', 'beseo' ); ?></option>
                                                                <option value="image_16_9"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></option>
                                                                <option value="image_4_3"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></option>
                                                                <option value="image_1_1"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></option>
                                                                <option value="image_3_4"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></option>
                                                                <option value="image_9_16"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="website-images"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_website_images_optional" id="be_schema_website_images_optional" value="<?php echo esc_attr( $website_images_optional_serialized ); ?>" />
                                                        </div>
                                                    <table class="form-table be-schema-optional-fields" id="be-schema-website-images-optional-fields">
                                                        <tbody>
                                                            <tr class="be-schema-optional-field<?php echo in_array( 'image_16_9', $website_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                                                <th scope="row">
                                                                    <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                                                    <?php esc_html_e( '16x9 (Widescreen/Panoramic)', 'beseo' ); ?>
                                                                </th>
                                                                <td>
                                                                    <div class="be-schema-image-field">
                                                                        <label class="be-schema-image-enable-label">
                                                                            <input type="checkbox"
                                                                                   class="be-schema-image-enable"
                                                                                   data-target-input="be_schema_website_image_16_9"
                                                                                   data-target-select="be_schema_website_image_16_9_select"
                                                                                   data-target-clear="be_schema_website_image_16_9_clear"
                                                                                   name="be_schema_website_image_16_9_enabled"
                                                                                   <?php checked( $website_image_16_9_enabled ); ?> />
                                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                        </label>
                                                                        <input type="text"
                                                                               id="be_schema_website_image_16_9"
                                                                               name="be_schema_website_image_16_9"
                                                                               value="<?php echo esc_url( $website_image_16_9 ); ?>"
                                                                               class="regular-text"
                                                                               <?php disabled( ! $website_image_16_9_enabled ); ?> />
                                                                        <button type="button"
                                                                                class="button be-schema-image-select"
                                                                                id="be_schema_website_image_16_9_select"
                                                                                data-target-input="be_schema_website_image_16_9"
                                                                                data-target-preview="be_schema_website_image_16_9_preview"
                                                                                <?php disabled( ! $website_image_16_9_enabled ); ?>>
                                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="button be-schema-image-clear"
                                                                                id="be_schema_website_image_16_9_clear"
                                                                                data-target-input="be_schema_website_image_16_9"
                                                                                data-target-preview="be_schema_website_image_16_9_preview"
                                                                                <?php disabled( ! $website_image_16_9_enabled ); ?>>
                                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                        </button>
                                                                        <span id="be_schema_website_image_16_9_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                    </div>
                                                                    <p class="description be-schema-description">
                                                                        <?php esc_html_e( 'Dimensions: 1920x1080.', 'beseo' ); ?>
                                                                    </p>
                                                                    <div id="be_schema_website_image_16_9_preview"
                                                                         class="be-schema-image-preview">
                                                                        <?php if ( $website_image_16_9 ) : ?>
                                                                            <img src="<?php echo esc_url( $website_image_16_9 ); ?>" alt="" />
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr class="be-schema-optional-field<?php echo in_array( 'image_4_3', $website_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_3">
                                                                <th scope="row">
                                                                    <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_3">−</button>
                                                                    <?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?>
                                                                </th>
                                                                <td>
                                                                    <div class="be-schema-image-field">
                                                                        <label class="be-schema-image-enable-label">
                                                                            <input type="checkbox"
                                                                                   class="be-schema-image-enable"
                                                                                   data-target-input="be_schema_website_image_4_3"
                                                                                   data-target-select="be_schema_website_image_4_3_select"
                                                                                   data-target-clear="be_schema_website_image_4_3_clear"
                                                                                   name="be_schema_website_image_4_3_enabled"
                                                                                   <?php checked( $website_image_4_3_enabled ); ?> />
                                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                        </label>
                                                                        <input type="text"
                                                                               id="be_schema_website_image_4_3"
                                                                               name="be_schema_website_image_4_3"
                                                                               value="<?php echo esc_url( $website_image_4_3 ); ?>"
                                                                               class="regular-text"
                                                                               <?php disabled( ! $website_image_4_3_enabled ); ?> />
                                                                        <button type="button"
                                                                                class="button be-schema-image-select"
                                                                                id="be_schema_website_image_4_3_select"
                                                                                data-target-input="be_schema_website_image_4_3"
                                                                                data-target-preview="be_schema_website_image_4_3_preview"
                                                                                <?php disabled( ! $website_image_4_3_enabled ); ?>>
                                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="button be-schema-image-clear"
                                                                                id="be_schema_website_image_4_3_clear"
                                                                                data-target-input="be_schema_website_image_4_3"
                                                                                data-target-preview="be_schema_website_image_4_3_preview"
                                                                                <?php disabled( ! $website_image_4_3_enabled ); ?>>
                                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                        </button>
                                                                        <span id="be_schema_website_image_4_3_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                    </div>
                                                                    <p class="description be-schema-description">
                                                                        <?php esc_html_e( 'Dimensions: 1600x1200.', 'beseo' ); ?>
                                                                    </p>
                                                                    <div id="be_schema_website_image_4_3_preview"
                                                                         class="be-schema-image-preview">
                                                                        <?php if ( $website_image_4_3 ) : ?>
                                                                            <img src="<?php echo esc_url( $website_image_4_3 ); ?>" alt="" />
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr class="be-schema-optional-field<?php echo in_array( 'image_1_1', $website_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1">
                                                                <th scope="row">
                                                                    <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1">−</button>
                                                                    <?php esc_html_e( '1:1 (Square)', 'beseo' ); ?>
                                                                </th>
                                                                <td>
                                                                    <div class="be-schema-image-field">
                                                                        <label class="be-schema-image-enable-label">
                                                                            <input type="checkbox"
                                                                                   class="be-schema-image-enable"
                                                                                   data-target-input="be_schema_website_image_1_1"
                                                                                   data-target-select="be_schema_website_image_1_1_select"
                                                                                   data-target-clear="be_schema_website_image_1_1_clear"
                                                                                   name="be_schema_website_image_1_1_enabled"
                                                                                   <?php checked( $website_image_1_1_enabled ); ?> />
                                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                        </label>
                                                                        <input type="text"
                                                                               id="be_schema_website_image_1_1"
                                                                               name="be_schema_website_image_1_1"
                                                                               value="<?php echo esc_url( $website_image_1_1 ); ?>"
                                                                               class="regular-text"
                                                                               <?php disabled( ! $website_image_1_1_enabled ); ?> />
                                                                        <button type="button"
                                                                                class="button be-schema-image-select"
                                                                                id="be_schema_website_image_1_1_select"
                                                                                data-target-input="be_schema_website_image_1_1"
                                                                                data-target-preview="be_schema_website_image_1_1_preview"
                                                                                <?php disabled( ! $website_image_1_1_enabled ); ?>>
                                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="button be-schema-image-clear"
                                                                                id="be_schema_website_image_1_1_clear"
                                                                                data-target-input="be_schema_website_image_1_1"
                                                                                data-target-preview="be_schema_website_image_1_1_preview"
                                                                                <?php disabled( ! $website_image_1_1_enabled ); ?>>
                                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                        </button>
                                                                        <span id="be_schema_website_image_1_1_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                    </div>
                                                                    <p class="description be-schema-description">
                                                                        <?php esc_html_e( 'Dimensions: 1200x1200.', 'beseo' ); ?>
                                                                    </p>
                                                                    <div id="be_schema_website_image_1_1_preview"
                                                                         class="be-schema-image-preview">
                                                                        <?php if ( $website_image_1_1 ) : ?>
                                                                            <img src="<?php echo esc_url( $website_image_1_1 ); ?>" alt="" />
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr class="be-schema-optional-field<?php echo in_array( 'image_3_4', $website_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_3_4">
                                                                <th scope="row">
                                                                    <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_3_4">−</button>
                                                                    <?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?>
                                                                </th>
                                                                <td>
                                                                    <div class="be-schema-image-field">
                                                                        <label class="be-schema-image-enable-label">
                                                                            <input type="checkbox"
                                                                                   class="be-schema-image-enable"
                                                                                   data-target-input="be_schema_website_image_3_4"
                                                                                   data-target-select="be_schema_website_image_3_4_select"
                                                                                   data-target-clear="be_schema_website_image_3_4_clear"
                                                                                   name="be_schema_website_image_3_4_enabled"
                                                                                   <?php checked( $website_image_3_4_enabled ); ?> />
                                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                        </label>
                                                                        <input type="text"
                                                                               id="be_schema_website_image_3_4"
                                                                               name="be_schema_website_image_3_4"
                                                                               value="<?php echo esc_url( $website_image_3_4 ); ?>"
                                                                               class="regular-text"
                                                                               <?php disabled( ! $website_image_3_4_enabled ); ?> />
                                                                        <button type="button"
                                                                                class="button be-schema-image-select"
                                                                                id="be_schema_website_image_3_4_select"
                                                                                data-target-input="be_schema_website_image_3_4"
                                                                                data-target-preview="be_schema_website_image_3_4_preview"
                                                                                <?php disabled( ! $website_image_3_4_enabled ); ?>>
                                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="button be-schema-image-clear"
                                                                                id="be_schema_website_image_3_4_clear"
                                                                                data-target-input="be_schema_website_image_3_4"
                                                                                data-target-preview="be_schema_website_image_3_4_preview"
                                                                                <?php disabled( ! $website_image_3_4_enabled ); ?>>
                                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                        </button>
                                                                        <span id="be_schema_website_image_3_4_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                    </div>
                                                                    <p class="description be-schema-description">
                                                                        <?php esc_html_e( 'Dimensions: 1200x1600.', 'beseo' ); ?>
                                                                    </p>
                                                                    <div id="be_schema_website_image_3_4_preview"
                                                                         class="be-schema-image-preview">
                                                                        <?php if ( $website_image_3_4 ) : ?>
                                                                            <img src="<?php echo esc_url( $website_image_3_4 ); ?>" alt="" />
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr class="be-schema-optional-field<?php echo in_array( 'image_9_16', $website_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                                                <th scope="row">
                                                                    <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                                                    <?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?>
                                                                </th>
                                                                <td>
                                                                    <div class="be-schema-image-field">
                                                                        <label class="be-schema-image-enable-label">
                                                                            <input type="checkbox"
                                                                                   class="be-schema-image-enable"
                                                                                   data-target-input="be_schema_website_image_9_16"
                                                                                   data-target-select="be_schema_website_image_9_16_select"
                                                                                   data-target-clear="be_schema_website_image_9_16_clear"
                                                                                   name="be_schema_website_image_9_16_enabled"
                                                                                   <?php checked( $website_image_9_16_enabled ); ?> />
                                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                        </label>
                                                                        <input type="text"
                                                                               id="be_schema_website_image_9_16"
                                                                               name="be_schema_website_image_9_16"
                                                                               value="<?php echo esc_url( $website_image_9_16 ); ?>"
                                                                               class="regular-text"
                                                                               <?php disabled( ! $website_image_9_16_enabled ); ?> />
                                                                        <button type="button"
                                                                                class="button be-schema-image-select"
                                                                                id="be_schema_website_image_9_16_select"
                                                                                data-target-input="be_schema_website_image_9_16"
                                                                                data-target-preview="be_schema_website_image_9_16_preview"
                                                                                <?php disabled( ! $website_image_9_16_enabled ); ?>>
                                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                        </button>
                                                                        <button type="button"
                                                                                class="button be-schema-image-clear"
                                                                                id="be_schema_website_image_9_16_clear"
                                                                                data-target-input="be_schema_website_image_9_16"
                                                                                data-target-preview="be_schema_website_image_9_16_preview"
                                                                                <?php disabled( ! $website_image_9_16_enabled ); ?>>
                                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                        </button>
                                                                        <span id="be_schema_website_image_9_16_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                    </div>
                                                                    <p class="description be-schema-description">
                                                                        <?php esc_html_e( 'Dimensions: 1080x1920.', 'beseo' ); ?>
                                                                    </p>
                                                                    <div id="be_schema_website_image_9_16_preview"
                                                                         class="be-schema-image-preview">
                                                                        <?php if ( $website_image_9_16 ) : ?>
                                                                            <img src="<?php echo esc_url( $website_image_9_16 ); ?>" alt="" />
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- PERSON PANEL -->
                            <div id="be-schema-website-person" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Person Entity', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                   <input type="checkbox"
                                                           name="be_schema_person_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-person-block be-schema-person-images-block"
                                                           <?php checked( $person_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Include a Person node in the site-level schema.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include a Person entity (usually the primary individual behind the site). The name itself is derived from other context, such as the site name or additional configuration.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Person Details', 'beseo' ); ?></h4>
                                    <p>
                                        <span class="be-schema-status-pill <?php echo $person_enabled ? '' : 'off'; ?>">
                                            <?php echo $person_enabled ? esc_html__( 'Person: ON', 'beseo' ) : esc_html__( 'Person: OFF', 'beseo' ); ?>
                                        </span>
                                    </p>
                                    <div id="be-schema-person-block"
                                         class="be-schema-conditional-block <?php echo $person_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Person Name', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <input type="text"
                                                               name="be_schema_person_name"
                                                               value="<?php echo esc_attr( $person_name ); ?>"
                                                               class="regular-text be-schema-person-name"
                                                               placeholder="<?php echo esc_attr( $person_name_effective ); ?>"
                                                               data-fallback="<?php echo esc_attr( $person_name_effective ); ?>" />
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'If empty, defaults to the Site Title.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"></td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls"
                                                             data-optional-scope="person"
                                                             data-optional-hidden="be_schema_person_optional"
                                                             data-optional-singleton="description,person_url">
                                                            <label class="screen-reader-text" for="be-schema-person-optional"><?php esc_html_e( 'Add optional Person property', 'beseo' ); ?></label>
                                                            <select id="be-schema-person-optional" aria-label="<?php esc_attr_e( 'Add optional Person property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="description"><?php esc_html_e( 'Description', 'beseo' ); ?></option>
                                                                <option value="honorific_prefix"><?php esc_html_e( 'Honorific Prefix', 'beseo' ); ?></option>
                                                                <option value="honorific_suffix"><?php esc_html_e( 'Honorific Suffix', 'beseo' ); ?></option>
                                                                <option value="person_url"><?php esc_html_e( 'Person URL', 'beseo' ); ?></option>
                                                                <option value="alumni_of"><?php esc_html_e( 'Alumni Of', 'beseo' ); ?></option>
                                                                <option value="job_title"><?php esc_html_e( 'Job Title', 'beseo' ); ?></option>
                                                                <option value="affiliation"><?php esc_html_e( 'Affiliation', 'beseo' ); ?></option>
                                                                <option value="sameas"><?php esc_html_e( 'SameAs URLs', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="person"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_person_optional" id="be_schema_person_optional" value="<?php echo esc_attr( $person_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-person-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'description', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="description">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="description">−</button>
                                                                <label for="be_schema_person_description" class="screen-reader-text"><?php esc_html_e( 'Description', 'beseo' ); ?></label>
                                                                <textarea
                                                                    name="be_schema_person_description"
                                                                    id="be_schema_person_description"
                                                                    rows="3"
                                                                    class="large-text code"><?php echo esc_textarea( $person_description ); ?></textarea>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A short bio or summary for the Person entity.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'honorific_prefix', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="honorific_prefix">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="honorific_prefix">−</button>
                                                                <label for="be_schema_person_honorific_prefix_0" class="screen-reader-text"><?php esc_html_e( 'Honorific Prefix', 'beseo' ); ?></label>
                                                                <div class="be-schema-repeatable" data-repeatable-prop="honorific_prefix" data-repeatable-name="be_schema_person_honorific_prefix[]">
                                                                    <div class="be-schema-repeatable-items">
                                                                        <?php
                                                                        $honorific_prefix_values = be_schema_admin_ensure_list( $person_honorific_prefix );
                                                                        foreach ( $honorific_prefix_values as $idx => $value ) :
                                                                        ?>
                                                                        <div class="be-schema-repeatable-item">
                                                                            <input type="text"
                                                                                   name="be_schema_person_honorific_prefix[]"
                                                                                   <?php echo 0 === $idx ? 'id="be_schema_person_honorific_prefix_0"' : ''; ?>
                                                                                   value="<?php echo esc_attr( $value ); ?>"
                                                                                   class="regular-text" />
                                                                            <button type="button" class="button be-schema-repeatable-remove">−</button>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="button be-schema-repeatable-add" data-repeatable-prop="honorific_prefix">+</button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Examples: Dr, Prof, Mr, Ms.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'honorific_suffix', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="honorific_suffix">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="honorific_suffix">−</button>
                                                                <label for="be_schema_person_honorific_suffix_0" class="screen-reader-text"><?php esc_html_e( 'Honorific Suffix', 'beseo' ); ?></label>
                                                                <div class="be-schema-repeatable" data-repeatable-prop="honorific_suffix" data-repeatable-name="be_schema_person_honorific_suffix[]">
                                                                    <div class="be-schema-repeatable-items">
                                                                        <?php
                                                                        $honorific_suffix_values = be_schema_admin_ensure_list( $person_honorific_suffix );
                                                                        foreach ( $honorific_suffix_values as $idx => $value ) :
                                                                        ?>
                                                                        <div class="be-schema-repeatable-item">
                                                                            <input type="text"
                                                                                   name="be_schema_person_honorific_suffix[]"
                                                                                   <?php echo 0 === $idx ? 'id="be_schema_person_honorific_suffix_0"' : ''; ?>
                                                                                   value="<?php echo esc_attr( $value ); ?>"
                                                                                   class="regular-text" />
                                                                            <button type="button" class="button be-schema-repeatable-remove">−</button>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="button be-schema-repeatable-add" data-repeatable-prop="honorific_suffix">+</button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Examples: PhD, MD, CPA.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'person_url', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="person_url">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="person_url">−</button>
                                                                <label for="be_schema_person_url" class="screen-reader-text"><?php esc_html_e( 'Person URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_person_url"
                                                                       id="be_schema_person_url"
                                                                       value="<?php echo esc_url( $person_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A canonical URL for this person (for example, personal site or primary profile).',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'alumni_of', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="alumni_of">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="alumni_of">−</button>
                                                                <label for="be_schema_person_alumni_of_0" class="screen-reader-text"><?php esc_html_e( 'Alumni Of', 'beseo' ); ?></label>
                                                                <div class="be-schema-repeatable" data-repeatable-prop="alumni_of" data-repeatable-name="be_schema_person_alumni_of[]">
                                                                    <div class="be-schema-repeatable-items">
                                                                        <?php
                                                                        $alumni_values = be_schema_admin_ensure_list( $person_alumni_of );
                                                                        foreach ( $alumni_values as $idx => $value ) :
                                                                        ?>
                                                                        <div class="be-schema-repeatable-item">
                                                                            <input type="text"
                                                                                   name="be_schema_person_alumni_of[]"
                                                                                   <?php echo 0 === $idx ? 'id="be_schema_person_alumni_of_0"' : ''; ?>
                                                                                   value="<?php echo esc_attr( $value ); ?>"
                                                                                   class="regular-text" />
                                                                            <button type="button" class="button be-schema-repeatable-remove">−</button>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="button be-schema-repeatable-add" data-repeatable-prop="alumni_of">+</button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'School or institution the person graduated from (text).',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'job_title', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="job_title">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="job_title">−</button>
                                                                <label for="be_schema_person_job_title_0" class="screen-reader-text"><?php esc_html_e( 'Job Title', 'beseo' ); ?></label>
                                                                <div class="be-schema-repeatable" data-repeatable-prop="job_title" data-repeatable-name="be_schema_person_job_title[]">
                                                                    <div class="be-schema-repeatable-items">
                                                                        <?php
                                                                        $job_values = be_schema_admin_ensure_list( $person_job_title );
                                                                        foreach ( $job_values as $idx => $value ) :
                                                                        ?>
                                                                        <div class="be-schema-repeatable-item">
                                                                            <input type="text"
                                                                                   name="be_schema_person_job_title[]"
                                                                                   <?php echo 0 === $idx ? 'id="be_schema_person_job_title_0"' : ''; ?>
                                                                                   value="<?php echo esc_attr( $value ); ?>"
                                                                                   class="regular-text" />
                                                                            <button type="button" class="button be-schema-repeatable-remove">−</button>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="button be-schema-repeatable-add" data-repeatable-prop="job_title">+</button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Primary role or position for this person.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'affiliation', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="affiliation">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="affiliation">−</button>
                                                                <label for="be_schema_person_affiliation_0" class="screen-reader-text"><?php esc_html_e( 'Affiliation', 'beseo' ); ?></label>
                                                                <div class="be-schema-repeatable" data-repeatable-prop="affiliation" data-repeatable-name="be_schema_person_affiliation[]">
                                                                    <div class="be-schema-repeatable-items">
                                                                        <?php
                                                                        $affiliation_values = be_schema_admin_ensure_list( $person_affiliation );
                                                                        foreach ( $affiliation_values as $idx => $value ) :
                                                                        ?>
                                                                        <div class="be-schema-repeatable-item">
                                                                            <input type="text"
                                                                                   name="be_schema_person_affiliation[]"
                                                                                   <?php echo 0 === $idx ? 'id="be_schema_person_affiliation_0"' : ''; ?>
                                                                                   value="<?php echo esc_attr( $value ); ?>"
                                                                                   class="regular-text" />
                                                                            <button type="button" class="button be-schema-repeatable-remove">−</button>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                    <button type="button" class="button be-schema-repeatable-add" data-repeatable-prop="affiliation">+</button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Organisation this person is affiliated with (text).',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'sameas', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="sameas">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="sameas">−</button>
                                                                <label for="be_schema_person_sameas_raw" class="screen-reader-text"><?php esc_html_e( 'SameAs URLs', 'beseo' ); ?></label>
                                                                <textarea
                                                                    name="be_schema_person_sameas_raw"
                                                                    id="be_schema_person_sameas_raw"
                                                                    rows="5"
                                                                    class="large-text code"><?php echo esc_textarea( $person_sameas_raw ); ?></textarea>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'One URL per line, pointing to authoritative profiles for this person (for example, knowledge panels or professional profiles). These are used as Person.sameAs and are separate from social sharing settings.',
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
                                </div>

                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Image(s)', 'beseo' ); ?></h4>
                                    <div id="be-schema-person-images-block"
                                         class="be-schema-conditional-block <?php echo $person_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Profile (Optional)', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls"
                                                             data-optional-scope="person-images"
                                                             data-optional-hidden="be_schema_person_images_optional"
                                                             data-optional-singleton="image_16_9,image_4_3,image_1_1,image_3_4,image_9_16">
                                                            <label class="screen-reader-text" for="be-schema-person-images-optional"><?php esc_html_e( 'Add optional Person image', 'beseo' ); ?></label>
                                                            <select id="be-schema-person-images-optional" aria-label="<?php esc_attr_e( 'Add optional Person image', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="image_16_9"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></option>
                                                                <option value="image_4_3"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></option>
                                                                <option value="image_1_1"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></option>
                                                                <option value="image_3_4"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></option>
                                                                <option value="image_9_16"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="person-images"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_person_images_optional" id="be_schema_person_images_optional" value="<?php echo esc_attr( $person_images_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-person-images-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_16_9', $person_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                                                <label for="be_schema_person_image_16_9" class="screen-reader-text"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <label class="be-schema-image-enable-label">
                                                                        <input type="checkbox"
                                                                               class="be-schema-image-enable"
                                                                               data-target-input="be_schema_person_image_16_9"
                                                                               data-target-select="be_schema_person_image_16_9_select"
                                                                               data-target-clear="be_schema_person_image_16_9_clear"
                                                                               name="be_schema_person_image_16_9_enabled"
                                                                               <?php checked( $person_image_16_9_enabled ); ?> />
                                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                    </label>
                                                                    <input type="text"
                                                                           id="be_schema_person_image_16_9"
                                                                           name="be_schema_person_image_16_9"
                                                                           value="<?php echo esc_url( $person_image_16_9 ); ?>"
                                                                           class="regular-text"
                                                                           <?php disabled( ! $person_image_16_9_enabled ); ?> />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            id="be_schema_person_image_16_9_select"
                                                                            data-target-input="be_schema_person_image_16_9"
                                                                            data-target-preview="be_schema_person_image_16_9_preview"
                                                                            <?php disabled( ! $person_image_16_9_enabled ); ?>>
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            id="be_schema_person_image_16_9_clear"
                                                                            data-target-input="be_schema_person_image_16_9"
                                                                            data-target-preview="be_schema_person_image_16_9_preview"
                                                                            <?php disabled( ! $person_image_16_9_enabled ); ?>>
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                    <span id="be_schema_person_image_16_9_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1920x1080.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_16_9_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_16_9 ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_16_9 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_4_3', $person_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_3">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_3">−</button>
                                                                <label for="be_schema_person_image_4_3" class="screen-reader-text"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <label class="be-schema-image-enable-label">
                                                                        <input type="checkbox"
                                                                               class="be-schema-image-enable"
                                                                               data-target-input="be_schema_person_image_4_3"
                                                                               data-target-select="be_schema_person_image_4_3_select"
                                                                               data-target-clear="be_schema_person_image_4_3_clear"
                                                                               name="be_schema_person_image_4_3_enabled"
                                                                               <?php checked( $person_image_4_3_enabled ); ?> />
                                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                    </label>
                                                                    <input type="text"
                                                                           id="be_schema_person_image_4_3"
                                                                           name="be_schema_person_image_4_3"
                                                                           value="<?php echo esc_url( $person_image_4_3 ); ?>"
                                                                           class="regular-text"
                                                                           <?php disabled( ! $person_image_4_3_enabled ); ?> />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            id="be_schema_person_image_4_3_select"
                                                                            data-target-input="be_schema_person_image_4_3"
                                                                            data-target-preview="be_schema_person_image_4_3_preview"
                                                                            <?php disabled( ! $person_image_4_3_enabled ); ?>>
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            id="be_schema_person_image_4_3_clear"
                                                                            data-target-input="be_schema_person_image_4_3"
                                                                            data-target-preview="be_schema_person_image_4_3_preview"
                                                                            <?php disabled( ! $person_image_4_3_enabled ); ?>>
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                    <span id="be_schema_person_image_4_3_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1600x1200.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_4_3_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_4_3 ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_4_3 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_1_1', $person_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1">−</button>
                                                                <label for="be_schema_person_image_1_1" class="screen-reader-text"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <label class="be-schema-image-enable-label">
                                                                        <input type="checkbox"
                                                                               class="be-schema-image-enable"
                                                                               data-target-input="be_schema_person_image_1_1"
                                                                               data-target-select="be_schema_person_image_1_1_select"
                                                                               data-target-clear="be_schema_person_image_1_1_clear"
                                                                               name="be_schema_person_image_1_1_enabled"
                                                                               <?php checked( $person_image_1_1_enabled ); ?> />
                                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                    </label>
                                                                    <input type="text"
                                                                           id="be_schema_person_image_1_1"
                                                                           name="be_schema_person_image_1_1"
                                                                           value="<?php echo esc_url( $person_image_1_1 ); ?>"
                                                                           class="regular-text"
                                                                           <?php disabled( ! $person_image_1_1_enabled ); ?> />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            id="be_schema_person_image_1_1_select"
                                                                            data-target-input="be_schema_person_image_1_1"
                                                                            data-target-preview="be_schema_person_image_1_1_preview"
                                                                            <?php disabled( ! $person_image_1_1_enabled ); ?>>
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            id="be_schema_person_image_1_1_clear"
                                                                            data-target-input="be_schema_person_image_1_1"
                                                                            data-target-preview="be_schema_person_image_1_1_preview"
                                                                            <?php disabled( ! $person_image_1_1_enabled ); ?>>
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                    <span id="be_schema_person_image_1_1_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1200x1200.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_1_1_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_1_1 ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_1_1 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_3_4', $person_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_3_4">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_3_4">−</button>
                                                                <label for="be_schema_person_image_3_4" class="screen-reader-text"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <label class="be-schema-image-enable-label">
                                                                        <input type="checkbox"
                                                                               class="be-schema-image-enable"
                                                                               data-target-input="be_schema_person_image_3_4"
                                                                               data-target-select="be_schema_person_image_3_4_select"
                                                                               data-target-clear="be_schema_person_image_3_4_clear"
                                                                               name="be_schema_person_image_3_4_enabled"
                                                                               <?php checked( $person_image_3_4_enabled ); ?> />
                                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                    </label>
                                                                    <input type="text"
                                                                           id="be_schema_person_image_3_4"
                                                                           name="be_schema_person_image_3_4"
                                                                           value="<?php echo esc_url( $person_image_3_4 ); ?>"
                                                                           class="regular-text"
                                                                           <?php disabled( ! $person_image_3_4_enabled ); ?> />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            id="be_schema_person_image_3_4_select"
                                                                            data-target-input="be_schema_person_image_3_4"
                                                                            data-target-preview="be_schema_person_image_3_4_preview"
                                                                            <?php disabled( ! $person_image_3_4_enabled ); ?>>
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            id="be_schema_person_image_3_4_clear"
                                                                            data-target-input="be_schema_person_image_3_4"
                                                                            data-target-preview="be_schema_person_image_3_4_preview"
                                                                            <?php disabled( ! $person_image_3_4_enabled ); ?>>
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                    <span id="be_schema_person_image_3_4_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1200x1600.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_3_4_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_3_4 ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_3_4 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_9_16', $person_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                                                <label for="be_schema_person_image_9_16" class="screen-reader-text"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <label class="be-schema-image-enable-label">
                                                                        <input type="checkbox"
                                                                               class="be-schema-image-enable"
                                                                               data-target-input="be_schema_person_image_9_16"
                                                                               data-target-select="be_schema_person_image_9_16_select"
                                                                               data-target-clear="be_schema_person_image_9_16_clear"
                                                                               name="be_schema_person_image_9_16_enabled"
                                                                               <?php checked( $person_image_9_16_enabled ); ?> />
                                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                                    </label>
                                                                    <input type="text"
                                                                           id="be_schema_person_image_9_16"
                                                                           name="be_schema_person_image_9_16"
                                                                           value="<?php echo esc_url( $person_image_9_16 ); ?>"
                                                                           class="regular-text"
                                                                           <?php disabled( ! $person_image_9_16_enabled ); ?> />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            id="be_schema_person_image_9_16_select"
                                                                            data-target-input="be_schema_person_image_9_16"
                                                                            data-target-preview="be_schema_person_image_9_16_preview"
                                                                            <?php disabled( ! $person_image_9_16_enabled ); ?>>
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            id="be_schema_person_image_9_16_clear"
                                                                            data-target-input="be_schema_person_image_9_16"
                                                                            data-target-preview="be_schema_person_image_9_16_preview"
                                                                            <?php disabled( ! $person_image_9_16_enabled ); ?>>
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                    <span id="be_schema_person_image_9_16_status" class="be-schema-image-status"><?php esc_html_e( 'Undefined', 'beseo' ); ?></span>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1080x1920.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_9_16_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_9_16 ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_9_16 ); ?>" alt="" />
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

                            </div>

                            <!-- ORGANISATION PANEL -->
                            <div id="be-schema-website-organization" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Organisation Entity', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_organization_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-organization-block"
                                                           <?php checked( $organization_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Include an Organisation node for this site.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include an Organisation entity that can be used as the primary about/publisher for the WebSite, and as the default publisher for BlogPosting.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Organisation Details', 'beseo' ); ?></h4>
                                        <p>
                                            <span class="be-schema-status-pill <?php echo $organization_enabled ? '' : 'off'; ?>">
                                                <?php echo $organization_enabled ? esc_html__( 'Organisation: ON', 'beseo' ) : esc_html__( 'Organisation: OFF', 'beseo' ); ?>
                                        </span>
                                    </p>
                                    <div id="be-schema-organization-block"
                                         class="be-schema-conditional-block <?php echo $organization_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Organisation Name', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <input type="text"
                                                               name="be_schema_org_name"
                                                               value="<?php echo esc_attr( $org_name ); ?>"
                                                               class="regular-text" />
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'The public name of the organisation.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                    </td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls"
                                                             data-optional-scope="org"
                                                             data-optional-hidden="be_schema_org_optional"
                                                             data-optional-singleton="legal_name,org_url,org_sameas">
                                                            <label class="screen-reader-text" for="be-schema-org-optional"><?php esc_html_e( 'Add optional Organisation property', 'beseo' ); ?></label>
                                                            <select id="be-schema-org-optional" aria-label="<?php esc_attr_e( 'Add optional Organisation property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="legal_name"><?php esc_html_e( 'Legal Name', 'beseo' ); ?></option>
                                                                <option value="org_url"><?php esc_html_e( 'Organisation URL', 'beseo' ); ?></option>
                                                                <option value="org_sameas"><?php esc_html_e( 'sameAs URLs', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="org"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_org_optional" id="be_schema_org_optional" value="<?php echo esc_attr( $organization_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-org-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'legal_name', $organization_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="legal_name">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="legal_name">−</button>
                                                                <label for="be_schema_org_legal_name" class="screen-reader-text"><?php esc_html_e( 'Legal Name', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_org_legal_name"
                                                                       id="be_schema_org_legal_name"
                                                                       value="<?php echo esc_attr( $org_legal_name ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'The legal name of the organisation, if different from the public name.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'org_url', $organization_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="org_url">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="org_url">−</button>
                                                                <label for="be_schema_org_url" class="screen-reader-text"><?php esc_html_e( 'Organisation URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_org_url"
                                                                       id="be_schema_org_url"
                                                                       value="<?php echo esc_url( $org_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'If empty, the site URL is used.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'org_sameas', $organization_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="org_sameas">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="org_sameas">−</button>
                                                                <label for="be_schema_org_sameas_raw">
                                                                    <?php esc_html_e( 'sameAs (one URL per line)', 'beseo' ); ?>
                                                                </label>
                                                                <textarea name="be_schema_org_sameas_raw"
                                                                          id="be_schema_org_sameas_raw"
                                                                          rows="4"
                                                                          class="large-text code"
                                                                          placeholder="https://twitter.com/yourbrand&#10;https://www.linkedin.com/company/yourbrand"><?php echo esc_textarea( $org_sameas_raw ); ?></textarea>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'List official brand/profile URLs to attach as Organisation sameAs.', 'beseo' ); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Shared Logo (Read-Only)', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'The Organisation uses the shared site logo configured on the Global tab.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                        <div class="be-schema-image-preview">
                                                            <?php if ( $org_logo ) : ?>
                                                                <img src="<?php echo esc_url( $org_logo ); ?>" alt="" />
                                                            <?php else : ?>
                                                                <em><?php esc_html_e( 'No shared logo selected yet.', 'beseo' ); ?></em>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- PUBLISHER PANEL -->
                            <div id="be-schema-website-publisher" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable WebSite.publisher', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_publisher_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-publisher-block"
                                                           <?php checked( $publisher_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Attach a Publisher entity to the WebSite.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the WebSite.publisher property can reference either the site Person, the Organisation, or a dedicated custom publisher organisation, depending on your configuration.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <p>
                                    <span class="be-schema-status-pill <?php echo $publisher_enabled ? '' : 'off'; ?>">
                                        <?php echo $publisher_enabled ? esc_html__( 'Publisher: ON', 'beseo' ) : esc_html__( 'Publisher: OFF', 'beseo' ); ?>
                                    </span>
                                    <span id="be-schema-publisher-type-pill" class="be-schema-status-pill <?php echo esc_attr( $publisher_type_class ); ?>">
                                        <?php echo esc_html( $publisher_type_label ); ?>
                                    </span>
                                </p>
                                <p class="description be-schema-description">
                                    <?php esc_html_e(
                                        'Use this tab to enable WebSite.publisher; once enabled, you can select Publisher in Site Identity on the Global tab (Dedicated is optional).',
                                        'beseo'
                                    ); ?>
                                </p>

                                <div id="be-schema-publisher-block"
                                     class="be-schema-conditional-block <?php echo $publisher_enabled ? '' : 'is-disabled'; ?>">
                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Entity', 'beseo' ); ?></h4>
                                        <table class="form-table">
                                            <tbody>
                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="publisher-entity"
                                                         data-optional-hidden="be_schema_publisher_entity_optional"
                                                         data-optional-singleton="copyright_year,license_url,publishing_principles,corrections_policy,ownership_funding">
                                                            <label class="screen-reader-text" for="be-schema-publisher-entity-optional"><?php esc_html_e( 'Add optional Publisher property', 'beseo' ); ?></label>
                                                            <select id="be-schema-publisher-entity-optional" aria-label="<?php esc_attr_e( 'Add optional Publisher property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="copyright_year"><?php esc_html_e( 'Copyright Year', 'beseo' ); ?></option>
                                                                <option value="license_url"><?php esc_html_e( 'License URL', 'beseo' ); ?></option>
                                                                <option value="publishing_principles"><?php esc_html_e( 'Publishing Principles URL', 'beseo' ); ?></option>
                                                                <option value="corrections_policy"><?php esc_html_e( 'Corrections Policy URL', 'beseo' ); ?></option>
                                                                <option value="ownership_funding"><?php esc_html_e( 'Ownership / Funding Info URL', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="publisher-entity"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_publisher_entity_optional" id="be_schema_publisher_entity_optional" value="<?php echo esc_attr( $publisher_entity_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-publisher-entity-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'copyright_year', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="copyright_year">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="copyright_year">−</button>
                                                                <label for="be_schema_copyright_year" class="screen-reader-text"><?php esc_html_e( 'Copyright Year', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_copyright_year"
                                                                       id="be_schema_copyright_year"
                                                                       value="<?php echo esc_attr( $copyright_year ); ?>"
                                                                       class="regular-text"
                                                                       style="max-width: 120px;" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Used for descriptive publishing metadata; not all validators require this.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'license_url', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="license_url">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="license_url">−</button>
                                                                <label for="be_schema_license_url" class="screen-reader-text"><?php esc_html_e( 'License URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_license_url"
                                                                       id="be_schema_license_url"
                                                                       value="<?php echo esc_url( $license_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A URL describing the license under which the site content is published.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'publishing_principles', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="publishing_principles">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="publishing_principles">−</button>
                                                                <label for="be_schema_publishing_principles" class="screen-reader-text"><?php esc_html_e( 'Publishing Principles URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publishing_principles"
                                                                       id="be_schema_publishing_principles"
                                                                       value="<?php echo esc_url( $publishing_principles ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A page describing your editorial standards or publishing principles.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'corrections_policy', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="corrections_policy">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="corrections_policy">−</button>
                                                                <label for="be_schema_corrections_policy" class="screen-reader-text"><?php esc_html_e( 'Corrections Policy URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_corrections_policy"
                                                                       id="be_schema_corrections_policy"
                                                                       value="<?php echo esc_url( $corrections_policy ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A page explaining how corrections or updates are handled.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'ownership_funding', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="ownership_funding">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="ownership_funding">−</button>
                                                                <label for="be_schema_ownership_funding" class="screen-reader-text"><?php esc_html_e( 'Ownership / Funding Info URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_ownership_funding"
                                                                       id="be_schema_ownership_funding"
                                                                       value="<?php echo esc_url( $ownership_funding ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A page describing ownership or funding information for the publisher.',
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

                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Dedicated', 'beseo' ); ?></h4>
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Dedicated Publisher', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox"
                                                                   name="be_schema_publisher_dedicated_enabled"
                                                                   value="1"
                                                                   <?php checked( $publisher_dedicated_enabled ); ?>
                                                                   <?php disabled( ! $publisher_enabled ); ?> />
                                                            <?php esc_html_e( 'Use a dedicated publisher entity.', 'beseo' ); ?>
                                                        </label>
                                                    </td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="publisher-dedicated"
                                                         data-optional-hidden="be_schema_publisher_dedicated_optional"
                                                         data-optional-singleton="custom_name,custom_url">
                                                            <label class="screen-reader-text" for="be-schema-publisher-dedicated-optional"><?php esc_html_e( 'Add optional dedicated publisher property', 'beseo' ); ?></label>
                                                            <select id="be-schema-publisher-dedicated-optional" aria-label="<?php esc_attr_e( 'Add optional dedicated publisher property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="custom_name"><?php esc_html_e( 'Custom Publisher Organisation Name', 'beseo' ); ?></option>
                                                                <option value="custom_url"><?php esc_html_e( 'Custom Publisher URL', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="publisher-dedicated"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_publisher_dedicated_optional" id="be_schema_publisher_dedicated_optional" value="<?php echo esc_attr( $publisher_dedicated_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-publisher-dedicated-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_name', $publisher_dedicated_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_name">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="custom_name">−</button>
                                                                <label for="be_schema_publisher_custom_name" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher Organisation Name', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publisher_custom_name"
                                                                       id="be_schema_publisher_custom_name"
                                                                       value="<?php echo esc_attr( $publisher_custom_name ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'If set, the site can treat this as a dedicated publisher organisation instead of re-using the main Organisation.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_url', $publisher_dedicated_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_url">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="custom_url">−</button>
                                                                <label for="be_schema_publisher_custom_url" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publisher_custom_url"
                                                                       id="be_schema_publisher_custom_url"
                                                                       value="<?php echo esc_url( $publisher_custom_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'The URL for the custom publisher organisation.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                    <?php esc_html_e( 'Publisher Logo', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="publisher-images"
                                                         data-optional-hidden="be_schema_publisher_dedicated_images_optional"
                                                         data-optional-singleton="custom_logo,image_16_9,image_4_3,image_1_1,image_3_4,image_9_16">
                                                            <label class="screen-reader-text" for="be-schema-publisher-dedicated-images-optional"><?php esc_html_e( 'Add optional dedicated publisher image', 'beseo' ); ?></label>
                                                            <select id="be-schema-publisher-dedicated-images-optional" aria-label="<?php esc_attr_e( 'Add optional dedicated publisher image', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="custom_logo"><?php esc_html_e( 'Custom Publisher Logo', 'beseo' ); ?></option>
                                                                <option value="image_16_9"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></option>
                                                                <option value="image_4_3"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></option>
                                                                <option value="image_1_1"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></option>
                                                                <option value="image_3_4"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></option>
                                                                <option value="image_9_16"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="publisher-dedicated-images"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_publisher_dedicated_images_optional" id="be_schema_publisher_dedicated_images_optional" value="<?php echo esc_attr( $publisher_dedicated_images_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-publisher-dedicated-images-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_logo', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_logo">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="custom_logo">−</button>
                                                                <label for="be_schema_publisher_custom_logo" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher Logo', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_custom_logo"
                                                                           name="be_schema_publisher_custom_logo"
                                                                           value="<?php echo esc_url( $publisher_custom_logo ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_custom_logo"
                                                                            data-target-preview="be_schema_publisher_custom_logo_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_custom_logo"
                                                                            data-target-preview="be_schema_publisher_custom_logo_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'A dedicated logo for the custom publisher organisation. If empty, the shared site logo may still be used depending on the site-entity logic.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_custom_logo_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_custom_logo ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_custom_logo ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_16_9', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                                                <label for="be_schema_publisher_image_16_9" class="screen-reader-text"><?php esc_html_e( '16:9 (Widescreen/Panoramic)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_image_16_9"
                                                                           name="be_schema_publisher_image_16_9"
                                                                           value="<?php echo esc_url( $publisher_image_16_9 ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_image_16_9"
                                                                            data-target-preview="be_schema_publisher_image_16_9_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_image_16_9"
                                                                            data-target-preview="be_schema_publisher_image_16_9_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1920x1080.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_image_16_9_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_image_16_9 ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_image_16_9 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_4_3', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_3">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_3">−</button>
                                                                <label for="be_schema_publisher_image_4_3" class="screen-reader-text"><?php esc_html_e( '4:3 (Standard)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_image_4_3"
                                                                           name="be_schema_publisher_image_4_3"
                                                                           value="<?php echo esc_url( $publisher_image_4_3 ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_image_4_3"
                                                                            data-target-preview="be_schema_publisher_image_4_3_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_image_4_3"
                                                                            data-target-preview="be_schema_publisher_image_4_3_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1600x1200.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_image_4_3_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_image_4_3 ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_image_4_3 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_1_1', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1">−</button>
                                                                <label for="be_schema_publisher_image_1_1" class="screen-reader-text"><?php esc_html_e( '1:1 (Square)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_image_1_1"
                                                                           name="be_schema_publisher_image_1_1"
                                                                           value="<?php echo esc_url( $publisher_image_1_1 ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_image_1_1"
                                                                            data-target-preview="be_schema_publisher_image_1_1_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_image_1_1"
                                                                            data-target-preview="be_schema_publisher_image_1_1_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1200x1200.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_image_1_1_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_image_1_1 ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_image_1_1 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_3_4', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_3_4">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_3_4">−</button>
                                                                <label for="be_schema_publisher_image_3_4" class="screen-reader-text"><?php esc_html_e( '3:4 (Portrait)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_image_3_4"
                                                                           name="be_schema_publisher_image_3_4"
                                                                           value="<?php echo esc_url( $publisher_image_3_4 ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_image_3_4"
                                                                            data-target-preview="be_schema_publisher_image_3_4_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_image_3_4"
                                                                            data-target-preview="be_schema_publisher_image_3_4_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1200x1600.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_image_3_4_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_image_3_4 ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_image_3_4 ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="be-schema-optional-field<?php echo in_array( 'image_9_16', $publisher_dedicated_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                                                <label for="be_schema_publisher_image_9_16" class="screen-reader-text"><?php esc_html_e( '9:16 (Portrait/Mobile)', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_image_9_16"
                                                                           name="be_schema_publisher_image_9_16"
                                                                           value="<?php echo esc_url( $publisher_image_9_16 ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_image_9_16"
                                                                            data-target-preview="be_schema_publisher_image_9_16_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_image_9_16"
                                                                            data-target-preview="be_schema_publisher_image_9_16_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e( 'Recommended dimensions: 1080x1920.', 'beseo' ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_image_9_16_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_image_9_16 ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_image_9_16 ); ?>" alt="" />
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
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            
