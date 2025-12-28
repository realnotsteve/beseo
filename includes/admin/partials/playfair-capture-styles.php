<?php
/**
 * Playfair capture styles (shared).
 */
?>
<style>
    .be-schema-playfair-form {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .be-schema-playfair-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .be-schema-playfair-row label {
        font-size: 12px;
        font-weight: 600;
        color: #3c434a;
    }
    .be-schema-playfair-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .be-schema-playfair-status {
        margin-top: 10px;
        padding: 8px 10px;
        border-radius: 6px;
        background: #f6f7f7;
        font-size: 12px;
        color: #3c434a;
        display: none;
    }
    .be-schema-playfair-status.is-active {
        display: block;
    }
    .be-schema-playfair-status.is-error {
        background: #fbeaea;
        color: #8a1c1c;
    }
    .be-schema-playfair-status.is-warning {
        background: #fff8e5;
        color: #7a4f01;
    }
    .be-schema-playfair-results {
        margin-top: 16px;
        display: none;
    }
    .be-schema-playfair-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    .be-schema-playfair-panel {
        background: #fff;
        border: 1px solid #e2e4e7;
        border-radius: 6px;
        padding: 10px;
    }
    .be-schema-playfair-panel h4 {
        margin: 0 0 8px;
        font-size: 13px;
        color: #1d2327;
    }
    .be-schema-playfair-pre {
        margin: 0;
        max-height: 260px;
        overflow: auto;
        background: #fff;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        padding: 8px;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        font-size: 12px;
        white-space: pre-wrap;
    }
    .be-schema-playfair-meta {
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 8px;
        font-size: 12px;
        color: #444;
    }
    .be-schema-playfair-target {
        margin-top: 8px;
        font-size: 12px;
        color: #3c434a;
    }
</style>
