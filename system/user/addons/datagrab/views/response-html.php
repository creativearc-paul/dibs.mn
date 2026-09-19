<?php
use BoldMinded\DataGrab\Model\ImportStatus;
?>
<table>
    <tr>
        <td>Import ID</td>
        <td><?php echo $id ?></td>
    </tr>
    <tr>
        <td>Import Count</td>
        <td><?php echo $last_record ?></td>
    </tr>
    <tr>
        <td>Total entries to import</td>
        <td><?php echo $total_records ?></td>
    </tr>
    <tr>
        <td>Status</td>
        <td><?php echo $status ?></td>
    </tr>
</table>

<?php if ($status !== ImportStatus::COMPLETED): ?>
<p><a href="<?php echo str_replace('&consume=yes', '', $_SERVER['REQUEST_URI']) ?>&consume=yes" title="This will start another consumer to import another batch of entries from the queue">Continue Importing</a></p>
<?php endif; ?>
