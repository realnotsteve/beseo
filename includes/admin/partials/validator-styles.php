<style>
.be-schema-tools-panel {
                margin-top: 16px;
                display: none;
            }
            .be-schema-tools-panel.active {
                display: block;
            }
            .be-schema-validator-header {
                border: 1px solid #ccd0d4;
                background: #fff;
                border-radius: 6px;
                margin-top: 8px;
                overflow: hidden;
            }
            .be-schema-header-titles {
                display: grid;
                grid-template-columns: max-content max-content max-content 1fr;
                background: #e5e7ea;
                color: #444;
                font-weight: 600;
                font-size: 13px;
                text-transform: uppercase;
                padding: 8px 12px;
                gap: 0;
            }
            .be-schema-header-titles div {
                border-left: 1px solid #d1d4d8;
                padding-left: 10px;
            }
            .be-schema-header-titles div:first-child {
                border-left: none;
            }
            .be-schema-header-grid {
                display: grid;
                grid-template-columns: max-content max-content max-content 1fr;
                gap: 0;
                padding: 14px 12px 6px 12px;
            }
            .be-schema-header-section {
                padding: 4px 14px 10px;
                border-left: 1px solid #dfe2e6;
                min-height: 120px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                justify-content: flex-start;
            }
            .be-schema-header-section:first-child {
                border-left: none;
                padding-left: 0;
            }
            .be-schema-header-section:last-child {
                padding-right: 0;
            }
            .be-schema-validator-context {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                font-size: 12px;
                color: #444;
            }
            .be-schema-context-line {
                background: #eef2f5;
                border-radius: 999px;
                padding: 6px 10px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                width: fit-content;
            }
            .be-schema-context-line .label {
                font-weight: 700;
                color: #2c3e50;
            }
            .be-schema-validator-select-wrap {
                display: flex;
                flex-direction: column;
                gap: 6px;
                min-width: 260px;
                width: 100%;
            }
            .be-schema-validator-select-wrap select,
            .be-schema-validator-select-wrap input[type="text"] {
                min-width: 260px;
                width: 100%;
            }
            .be-schema-validator-search input[type="text"] {
                width: 100%;
                min-width: 260px;
            }
            .be-schema-validator-actions {
                display: flex;
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            .be-schema-validator-actions button {
                min-width: 130px;
            }
            .be-schema-validator-service {
                min-width: 200px;
                width: 100%;
            }
            .be-schema-mini-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 6px;
            }
            .be-schema-mini-badge {
                background: #eef2f5;
                border-radius: 999px;
                padding: 3px 8px;
                font-size: 11px;
                color: #2c3e50;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .be-schema-validator-platforms label {
                margin-right: 8px;
                margin-bottom: 2px;
            }
            .be-schema-engine-box {
                background: #eef2f5;
                border-radius: 8px;
                padding: 10px 12px;
                display: inline-flex;
                flex-wrap: wrap;
                gap: 10px 16px;
                align-items: center;
                width: auto;
            }
            .be-schema-engine-box.stacked {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .be-schema-engine-box .be-schema-validator-platforms {
                margin: 0;
            }
            .be-schema-engine-row {
                display: flex;
                gap: 16px;
                align-items: flex-start;
            }
            .be-schema-engine-col {
                flex: 0 1 auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .be-schema-validator-rowline {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
                white-space: nowrap;
            }
            .be-schema-validator-rowline label {
                margin-right: 8px;
                white-space: nowrap;
            }
            .be-schema-engine-box label {
                white-space: nowrap;
            }
            .be-schema-validator-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                margin-top: 12px;
            }
            .be-schema-fetch-log {
                margin-top: 8px;
                font-size: 12px;
            }
            .be-schema-fetch-log summary {
                cursor: pointer;
                font-weight: 600;
            }
            .be-schema-fetch-log table {
                margin-top: 4px;
                border-collapse: collapse;
                width: 100%;
            }
            .be-schema-fetch-log td {
                padding: 2px 4px;
                border-bottom: 1px solid #e5e5e5;
            }
            .be-schema-validator-right {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .be-schema-warning-legend {
                font-size: 12px;
                color: #444;
                margin-bottom: 6px;
            }
            .be-schema-validator-card {
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 12px;
                background: #fff;
            }
            .be-schema-validator-preview {
                border: 1px solid #dfe2e7;
                border-radius: 6px;
                padding: 12px;
                background: linear-gradient(135deg, #f7f9fb, #eef1f5);
            }
            .be-schema-preview-label {
                font-size: 12px;
                font-weight: 600;
                color: #3c434a;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .be-schema-preview-img {
                position: relative;
                width: 100%;
                padding-top: 52.3%;
                border-radius: 4px;
                background: #e2e5ea;
                background-size: cover;
                background-position: center;
                margin-bottom: 8px;
                overflow: hidden;
            }
            .be-schema-preview-img .be-schema-crop-overlay {
                position: absolute;
                top: 12%;
                left: 8%;
                right: 8%;
                bottom: 12%;
                border: 2px dashed rgba(0,0,0,0.35);
                border-radius: 4px;
                display: none;
            }
            .be-schema-preview-img.crops-on .be-schema-crop-overlay {
                display: block;
            }
            .be-schema-preview-meta {
                font-size: 12px;
                color: #555;
                margin-bottom: 4px;
            }
            .be-schema-preview-domain-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                background: #e6f4ff;
                color: #1d4b7a;
                font-size: 11px;
                margin-top: 4px;
            }
            .be-schema-preview-crop-flag {
                display: inline-block;
                margin-left: 8px;
                background: #fff3cd;
                color: #8a6d3b;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
            }
            .be-schema-preview-title {
                font-weight: 700;
                margin: 0 0 4px;
            }
            .be-schema-preview-desc {
                margin: 0;
                color: #444;
                font-size: 13px;
            }
            .be-schema-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
                margin-right: 6px;
                background: #f0f4f8;
                color: #22303a;
            }
            .be-schema-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 999px;
                margin-right: 6px;
            }
            .be-schema-dot.green { background: #2ecc71; }
            .be-schema-dot.yellow { background: #f1c40f; }
            .be-schema-dot.red { background: #e74c3c; }
            .be-schema-validator-table {
                width: 100%;
                border-collapse: collapse;
            }
            .be-schema-validator-table th,
            .be-schema-validator-table td {
                border-bottom: 1px solid #e5e5e5;
                padding: 6px;
                vertical-align: top;
                font-size: 13px;
            }
            .be-schema-validator-table th {
                width: 25%;
                font-weight: 600;
            }
            .be-schema-source-value {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .be-schema-source-value .truncate {
                max-width: 320px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .be-schema-copy-btn {
                display: inline-block;
                padding: 2px 6px;
                font-size: 11px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                background: #f6f7f7;
                cursor: pointer;
            }
            .be-schema-validator-legend {
                margin-top: 8px;
                font-size: 12px;
                color: #444;
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }
            .be-schema-warning-list {
                list-style: none;
                padding-left: 0;
                margin: 8px 0 0;
            }
            .be-schema-warning-list li {
                margin-bottom: 6px;
            }
            .be-schema-warning-list .status {
                font-weight: 700;
                margin-right: 6px;
            }
            .be-schema-warning-list .platform {
                display: inline-block;
                margin-left: 6px;
                font-size: 11px;
                background: #f0f4f8;
                border-radius: 999px;
                padding: 2px 8px;
            }
            .be-schema-warning-empty {
                color: #666;
            }
            .be-schema-validator-preview:not(:last-child) {
                margin-bottom: 12px;
            }
            .nav-tab-wrapper {
                margin-top: 12px;
            }
            /* Fallback styling in case admin nav-tab CSS is not present */
            .nav-tab {
                display: inline-block;
                padding: 8px 14px;
                border: 1px solid #c3c4c7;
                border-bottom: none;
                background: #f6f7f7;
                color: #50575e;
                text-decoration: none;
                margin-right: 4px;
                border-radius: 3px 3px 0 0;
            }
            .nav-tab-active {
                background: #fff;
                color: #1d2327;
                border-bottom: 1px solid #fff;
            }
</style>
