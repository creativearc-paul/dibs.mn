<?php // All this crap needs to go. Need to refactor to use EE's newer layout features ?>
<style>
    td.box {
        white-space: normal;
        color: var(--ee-text-tertiary);
        background-color: var(--ee-bg-10);
    }

    .subtext {
        color: var(--ee-text-secondary);
    }

    .datagrab_error {
        color: var(--ee-error);
    }

    .datagrab_required {
        color: var(--ee-error);
        font-weight: bold;
    }

    .datagrab_subtext {
        font-size: 12px;
        color: var(--ee-text-secondary);
    }
</style>

<?php

echo ee('CP/Alert')->get('datagrab-form');
$this->view($content);
