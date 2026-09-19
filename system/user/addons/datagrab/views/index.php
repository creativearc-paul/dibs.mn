<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3>Create a new import</h3>
        </div>
    </div>
    <div class="panel-body">
        <?php
        echo form_open($form_action);
        echo form_hidden("datagrab_step", "index");
        ?>

        <p>
            <select name="type">
                <?php foreach ($types as $type => $type_label): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type_label ?></option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Create new import" class="btn action"/>
        </p>

        <?php echo form_close(); ?>
    </div>
    <div class="panel-footer">
        <a href="<?php echo $license_url ?>">Manage DataGrab license</a>
    </div>
</div>

<?php if (count($saved_imports)): ?>
    <?php echo form_open($form_action); ?>

    <div class="panel">
        <div class="panel-heading">
            <div class="title-bar">
                <h3>Use a saved import</h3>
            </div>
        </div>
        <div class="table-responsive table-responsive--collapsible">
            <?php
            $this->table->set_template($cp_table_template);
            $this->table->set_heading('ID', 'Name', 'Manage Import', 'Queue Size', 'Status', 'Last run', 'Import Config');

            echo $this->table->generate($saved_imports);
            ?>
        </div>
    </div>

    <div class="app-notice app-notice--inline app-notice---attention">
        <div class="app-notice__tag">
            <span class="app-notice__icon"></span>
        </div>
        <div class="app-notice__content">
            <p>
                <strong>Saved imports</strong> can be run from outside the
                Control Panel <a href="https://docs.boldminded.com/datagrab/docs/automatic-imports/importing-with-cron">using the CLI Commands</a> or with the <i>Import URL</i>.
            </p>
        </div>
    </div>

    <?php echo form_close(); ?>
<?php endif; ?>
