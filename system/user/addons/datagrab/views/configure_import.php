<?php
echo form_open($form_action);
echo form_hidden("datagrab_step", "configure_import");

$tableTemplate = [
    'table_open' => '<table class="grid-field__table">',
];
?>

<style>
    .add-on-layout .panel .table-responsive {
        border: 1px solid var(--ee-border);
    }
    .grid-field {
        margin-bottom: 1.5em;
    }
</style>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?php echo $title ?></h3>
            <div class="form-btns">
                <button class="button button--primary" type="submit" name="save" data-submit-text="Save" data-work-text="Saving...">Save</button>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <div class="grid-field">
            <div class="table-responsive">
<?php

ee()->table->set_template($tableTemplate);

$this->table->set_heading("Import settings", "Value");

$this->table->add_row(
    "<b>Channel</b>",
    $channel_title
);

$this->table->add_row(
    "<b>Import type</b>",
    $datatype_info["name"]
);

foreach ($datatype_settings as $key => $value) {
    $this->table->add_row(
        "<b>" . $key . "</b>",
        $value
    );
}

$this->table->add_row([
    'colspan' => 2,
    'data' => anchor($back_link, "Edit settings", ['class' => 'button button--secondary button--small'])
]);

echo $this->table->generate();
echo $this->table->clear();

?>
            </div>
        </div>
        <div class="grid-field">
            <div class="table-responsive">
<?php

$this->table->set_template($tableTemplate);
$this->table->set_heading("Default Fields", "Value");

$this->table->add_row(
    array(
        'colspan' => 2,
        'data' => 'Choose which values to use for the standard channel fields',
        'class' => 'sub-heading'
    )
);

/* Standard fields */

$this->table->add_row(
    form_label('Title', 'title') . ' <span class="datagrab_required">*</span>' .
    '<div class="datagrab_subtext">The entry\'s title.</div>',
    form_dropdown("title", $data_fields, $default_settings["config"]["title"] ?? '')
);
$this->table->add_row(
    form_label('Title Suffix', 'title_suffix') .
    '<div class="datagrab_subtext">If defined, the value of this field will be added to the end of the Title field.
    This can be used to combine values to create unique Titles.</div>',
    form_dropdown("title_suffix", $data_fields, $default_settings["config"]["title_suffix"] ?? '')
);
$this->table->add_row(
    form_label('URL', 'url_title') .
    '<div class="datagrab_subtext">The entry\'s URL title. If this is not set then the URL title will be derived from the entry\'s title.</div>',
    form_dropdown("url_title", $data_fields, $default_settings["config"]["url_title"] ?? '')
);
$this->table->add_row(
    form_label('URL Suffix', 'url_title_suffix') .
    '<div class="datagrab_subtext">If defined, the value of this field will be added to the end of the URL Title field.
    This can be used to combine values to create unique URL Titles.</div>',
    form_dropdown("url_title_suffix", $data_fields, $default_settings["config"]["url_title_suffix"] ?? '')
);
$this->table->add_row(
    form_label('Date', 'date') .
    '<div class="datagrab_subtext">Leave blank to set the entry\'s date to the time of import</div>',
    form_dropdown("date", $data_fields, $default_settings["config"]["date"] ?? '')
);
$this->table->add_row(
    form_label('Expiry date', 'expiry_date') .
    '<div class="datagrab_subtext">Leave blank if you do not want to set an expiry date</div>',
    form_dropdown("expiry_date", $data_fields, $default_settings["config"]["expiry_date"] ?? '')
);

echo $this->table->generate();
echo $this->table->clear();

?>
            </div>
        </div>
        <div class="grid-field">
            <div class="table-responsive">
<?php

/* Custom fields */

$this->table->set_template($tableTemplate);
$this->table->set_heading("Custom Fields", "Value");

$this->table->add_row(
    array(
        'colspan' => 2,
        'data' => 'Assign values to use for the channel\'s custom fields. You can leave values blank.',
        'class' => 'box'
    )
);

foreach ($cf_config as $cf) {
    $this->table->add_row($cf["label"], $cf["value"]);
}

echo $this->table->generate();
echo $this->table->clear();

?>
            </div>
        </div>

<?php if (!empty($cm_config)): ?>
<?php foreach ($cm_config as $displayName => $rows): ?>
        <div class="grid-field">
            <div class="table-responsive">
                <?php

                $this->table->set_template($tableTemplate);
                $this->table->set_heading($displayName . ' Settings', 'Value');

                foreach ($rows as $row) {
                    $this->table->add_row($row[0], $row[1]);
                }

                echo $this->table->generate();
                echo $this->table->clear();

                ?>
            </div>
        </div>
<?php endforeach; ?>
<?php endif; ?>

<?php

?>
        <div class="grid-field">
            <div class="table-responsive">
<?php

/* Categories */

$this->table->set_template($tableTemplate);
$this->table->set_heading("Categories", "Value");
$this->table->add_row(
    array(
        'colspan' => 2,
        'data' => 'Add categories to the entry',
        'class' => 'box'
    )
);

/*

$this->table->add_row(
	form_label("Default category value") .
	'<div class="datagrab_subtext">Assign this category to every entry</div>',
	form_input("category_value",
		isset($default_settings["config"]["category_value"]) ? $default_settings["config"]["category_value"] : '' )
);

$this->table->add_row(
	form_label("Category field") .
	'<div class="datagrab_subtext">Assign categories from this value to the entry</div>',
	form_dropdown("cat_field", $data_fields,
		isset($default_settings["config"]["cat_field"]) ? $default_settings["config"]["cat_field"] : '')
);

$this->table->add_row(
	form_label("Add new categories to this group")  .
	'<div class="datagrab_subtext">Choose which group new categories should be added to</div>',
	form_dropdown("cat_group", $category_groups,
		isset($default_settings["config"]["cat_group"]) ? $default_settings["config"]["cat_group"] : '' )
);

$this->table->add_row(
	form_label("Category delimiter") .
	'<div class="datagrab_subtext">eg, "One, Two, Three" will create 3 categories if the delimiter is a comma</div>',
	form_input("cat_delimiter",
		isset($default_settings["config"]["cat_delimiter"]) ? $default_settings["config"]["cat_delimiter"] : ',' )
);
*/

if (count($category_groups) == 0) {
    $this->table->add_row(
        array(
            'colspan' => 2,
            'data' => 'No category groups are assigned to this channel.'
        )
    );
}

$c_groups = array();
foreach ($category_groups as $group_id => $label) {
    $c_groups[] = $group_id;
    $this->table->add_row(
        array(
            'colspan' => 2,
            'data' => 'Add categories to the category group: <strong>' . $label . '</strong>'
        )
    );

    $this->table->add_row(
        form_label("Default category value") .
        '<div class="datagrab_subtext">Assign this category to every entry</div>',
        form_input("cat_default_" . $group_id, $default_settings["config"]["cat_default_" . $group_id] ?? '')
    );

    $this->table->add_row(
        form_label("Category group: " . $label) .
        '<div class="datagrab_subtext">Assign categories from this value to the entry</div>',
        form_dropdown("cat_field_" . $group_id, $data_fields, $default_settings["config"]["cat_field_" . $group_id] ?? '')
    );

    $this->table->add_row(
        form_label("Category delimiter") .
        '<div class="datagrab_subtext">eg, "One, Two, Three" will create 3 categories if the delimiter is a comma</div>',
        form_input("cat_delimiter_" . $group_id, $default_settings["config"]["cat_delimiter_" . $group_id] ?? ',', ' style="width: 50px"')
    );

    $this->table->add_row(
        form_label("Sub Category delimiter") .
        '<div class="datagrab_subtext">eg, "Parent/Child/Grand Child" will create a nested heirarchy of categories.</div>',
        form_input("cat_sub_delimiter_" . $group_id, $default_settings["config"]["cat_sub_delimiter_" . $group_id] ?? '/', ' style="width: 50px"')
    );

    $this->table->add_row(
        form_label("Allow Numeric Category Names?") .
        '<div class="datagrab_subtext">By default integers are assumed to be existing category IDs you want to assign to the entry. Enabling this setting will allow you to create new or assign existing categories with numeric values as the category name to an entry.</div>',
        form_dropdown("cat_allow_numeric_names_" . $group_id, [0 => 'No', 1 => 'Yes'], $default_settings["config"]["cat_allow_numeric_names_" . $group_id] ?? '0')
    );

}

echo $this->table->generate();
echo $this->table->clear();

?>
            </div>
        </div>
        <div class="grid-field">
            <div class="table-responsive">
<?php

echo form_hidden("c_groups", implode("|", $c_groups));

/* Duplicate entries/updates */

$this->table->set_template($tableTemplate);
$this->table->set_heading("Check for duplicate entries", "Value");

$this->table->add_row(
    array(
        'colspan' => 2,
        'data' => 'Determine what happens if the import is run again',
        'class' => 'box'
    )
);

// $this->table->add_row(
// 	form_label("Entry id") .
// 	'<div class="datagrab_subtext">Specify the entry\'s id.</div>',
// 	form_dropdown("aj_entry_id", $data_fields,
// 		isset($default_settings["config"]["entry_id"]) ? $default_settings["config"]["entry_id"] : '' )
// );

// Unique fields
if (isset($default_settings["config"]) &&
    isset($default_settings["config"]["unique"]) &&
    is_array($default_settings["config"]["unique"]) &&
    (count($default_settings["config"]["unique"]) > 0)) {

    // Import has multiple/array of unique fields

    $unique_form = "";
    foreach ($default_settings["config"]["unique"] as $unique_value) {
        if ($unique_value != "") {
            $unique_form .= form_dropdown("unique[]", $unique_fields, $unique_value) . BR . BR;
        }
    }

    // Make sure there is always atleast one
    if ($unique_form == "") {
        $unique_form .= form_dropdown("unique[]", $unique_fields, '');
    }

    $this->table->add_row(
        form_label("Use this field to check for duplicates") .
        '<div class="datagrab_subtext">If an entry with this field\'s value already exists, do not create a new entry.</div>',
        $unique_form
    );

} else {

    // Handle legacy imports with single unique field

    $this->table->add_row(
        form_label("Use this field to check for duplicates") .
        '<div class="datagrab_subtext">If an entry with this field\'s value already exists, do not create a new entry</div>',
        form_dropdown("unique[]", $unique_fields, $default_settings["config"]["unique"] ?? '')
    );
}
$this->table->add_row(
    form_label("Update existing entries") .
    '<div class="datagrab_subtext">If the unique field matches, then update the original entry, otherwise ignore it</div>',
    form_hidden("update", "n") .
    form_checkbox("update", "y", isset($default_settings["config"]["update"]) && $default_settings["config"]["update"] == "y")
);
$this->table->add_row(
    form_label("Delete old entries") .
    '<div class="datagrab_subtext">Delete entries from this channel that are not updated by this import.</div>',
    form_hidden("delete_old", "n") .
    form_checkbox("delete_old", "y", isset($default_settings["config"]["delete_old"]) && $default_settings["config"]["delete_old"] == "y")
);
$this->table->add_row(
    form_label("Soft delete old entries") .
    '<div class="datagrab_subtext">If deleting old entries from this channel, should the status be set to "Closed" instead of deleted from the database?</div>',
    form_hidden("soft_delete", "n") .
    form_checkbox("soft_delete", "y", isset($default_settings["config"]["soft_delete"]) && $default_settings["config"]["soft_delete"] == "y")
);
$this->table->add_row(
    form_label("Update Edit Date") .
    '<div class="datagrab_subtext">Set the entry\'s Edit Date field to the time of import?</div>',
    form_hidden("update_edit_date", "n") .
    form_checkbox("update_edit_date", "y", isset($default_settings["config"]["update_edit_date"]) && $default_settings["config"]["update_edit_date"] == "y")
);
$this->table->add_row(
    form_label("Add a timestamp to this field") .
    '<div class="datagrab_subtext">Add the time of the import to this custom field.</div>',
    form_dropdown("timestamp", $unique_fields, $default_settings["config"]["timestamp"] ?? '')
);
// $this->table->add_row(
// 	form_label("Delete old entries by timestamp") .
// 	'<div class="datagrab_subtext">Delete entries from this channel whose timestamp has not been updated</div>',
// 	form_hidden("delete_by_timestamp", "n") .
// 	form_checkbox("delete_by_timestamp", "y", (isset($default_settings["config"]["delete_by_timestamp"]) && $default_settings["config"]["delete_by_timestamp"] == "y" ? true : false) )
// );
// $this->table->add_row(
// 	form_label("Delete entries with old timestamp") .
// 	'<div class="datagrab_subtext">Set how old (in seconds) entries can be before being deleted</div>',
// 	form_input("delete_by_timestamp_duration",
// 	 	isset($default_settings["config"]["delete_by_timestamp_duration"]) ? $default_settings["config"]["delete_by_timestamp_duration"] : '86400' )
// );

echo $this->table->generate();

?>
            </div>
        </div>
<?php

/* Comments */

if ($allow_comments) {

    echo '<div class="grid-field">';
    echo '<div class="table-responsive">';

    $this->table->set_template($tableTemplate);
    $this->table->set_heading("Comments", "Value");

    $this->table->add_row(
        array(
            'colspan' => 2,
            'data' => 'Import comments. NOTE: comments are only added when an entry in imported for the first time. Running a subsequent import will update the entry, but not the comments. Please delete the entry to force new comments to be added.',
            'class' => 'box'
        )
    );

    $this->table->add_row(
        form_label("Import comments?") .
        '<div class="datagrab_subtext">Add comments for this entry</div>',
        form_hidden("import_comments", "n") .
        form_checkbox("import_comments", "y", isset($default_settings["config"]["import_comments"]) && $default_settings["config"]["import_comments"] == "y")
    );

    $this->table->add_row(
        form_label("Comment Author"),
        form_dropdown('comment_author', $data_fields, $default_settings["config"]['comment_author'] ?? '')
    );

    $this->table->add_row(
        form_label("Comment Author Email"),
        form_dropdown('comment_email', $data_fields, $default_settings["config"]['comment_email'] ?? '')
    );

    $this->table->add_row(
        form_label("Comment Author URL"),
        form_dropdown('comment_url', $data_fields, $default_settings["config"]['comment_url'] ?? '')
    );

    $this->table->add_row(
        form_label("Comment Date"),
        form_dropdown('comment_date', $data_fields, $default_settings["config"]['comment_date'] ?? '')
    );

    $this->table->add_row(
        form_label("Comment Body"),
        form_dropdown('comment_body', $data_fields, $default_settings["config"]['comment_body'] ?? '')
    );


    echo $this->table->generate();
    echo $this->table->clear();

    echo '</div>';
    echo '</div>';
}

?>
        <div class="grid-field">
            <div class="table-responsive">
<?php

/* Other parameters */

$this->table->set_template($tableTemplate);
$this->table->set_heading("Other settings", "Value");

$this->table->add_row(
    array(
        'colspan' => 2,
        'data' => 'Some additional options',
        'class' => 'box'
    )
);
$this->table->add_row(
    form_label("Default Author") .
    '<div class="datagrab_subtext">By default, assign entries to this author</div>',
    form_dropdown("author", $authors, $default_settings["config"]["author"] ?? '1')
);
$this->table->add_row(
    form_label("Author") .
    NBS . anchor("http://brandnewbox.co.uk/support/details/assigning_authors_to_entries_with_datagrab", "More details", 'class="datagrab_help" title="Help"') .
    '<div class="datagrab_subtext">Assign the entry to the member in this field.<br/>Note: members will not be created. If the member does not exist the default author will be used.</div>'
    ,
    form_dropdown("author_field", $data_fields, $default_settings["config"]["author_field"] ?? '')
);
$this->table->add_row(
    form_label("Author Field Value") .
    '<div class="datagrab_subtext">Select the type of member data that the author field contains</div>',
    form_dropdown("author_check", $author_fields, $default_settings["config"]["author_check"] ?? 'screen_name')
);

$this->table->add_row(
    form_label("Status") .
    '<div class="datagrab_subtext">Choose the entry\'s status</div>',
    form_dropdown("status", $status_fields, $default_settings["config"]["status"] ?? 'default')
);

$this->table->add_row(
    form_label("Update Status?") .
    '<div class="datagrab_subtext">When should the entry status be set or updated? If set to "Create or Update" (the default value), 
    the entry\'s status will always be updated to the defined status, or the channel default each time it is imported.
    If set to "Create" it will only be set the first time an entry is imported. Subsequent imports will not change the
    entry\'s current status, even if it differs from the setting value above.</div>',
    form_dropdown("update_status", [
        '' => 'Create or Update',
        'create' => 'Create Only',
        'update' => 'Update Only'
    ], $default_settings["config"]["update_status"] ?? '')
);

$this->table->add_row(
    form_label("Publish date offset (in seconds)") .
    '<div class="datagrab_subtext">Apply an offset to the publish date</div>',
    form_input("offset", $default_settings["config"]["offset"] ?? '0')
);

$this->table->add_row(
    form_label("Import in batches") .
    '<div class="datagrab_subtext">Set the number of entries the consumer will import at a time. If you have issues with a server timing out, try reducing the number.</div>',
    form_input("limit", $default_settings["import"]["limit"] ?? '50')
);


echo $this->table->generate();
echo $this->table->clear();


if (isset($id) && $id != "") {
    echo form_hidden("id", $id);
}

?>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <a href="<?php echo $back_link ?>" style="float: left;" class="button button--secondary">Back to Settings</a>
        <div class="form-btns">
            <button class="button button--primary" type="submit" name="save" data-submit-text="Save" data-work-text="Saving...">Save</button>
        </div>
    </div>
</div>

<?php echo form_close(); ?>
