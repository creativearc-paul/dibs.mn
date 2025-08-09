<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Dibs_upd
{
    public $version = '1.0.0';

    public function install()
    {
        // Register module in the DB
        ee()->db->insert('modules', [
            'module_name'        => 'Dibs',
            'module_version'     => $this->version,
            'has_cp_backend'     => 'n',
            'has_publish_fields' => 'n',
        ]);

        return true;
    }

    public function uninstall()
    {
        ee()->db->where('module_name', 'Dibs')->delete('modules');
        return true;
    }

    public function update($current = '')
    {
        return true;
    }
}
