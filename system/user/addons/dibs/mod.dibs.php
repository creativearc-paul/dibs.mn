<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Dibs
{
    /**
     * Usage:
     * {exp:dibs:get_author url_title="{segment_2}" channel_id="4"}
     * {exp:dibs:get_author author_id="123"} (will just echo 123)
     *
     * Tag pair:
     * {exp:dibs:get_author url_title="{segment_2}" channel_id="4"}
     *   {shift_author_id}
     * {/exp:dibs:get_author}
     */
    public function get_author()
    {
        $author_id = $this->resolve_author_id();
        $tagdata   = ee()->TMPL->tagdata;

        if ($tagdata) {
            return ee()->TMPL->parse_variables($tagdata, [[
                'shift_author_id' => (string) $author_id
            ]]);
        }

        return (string) $author_id;
    }

    /**
     * Usage (single tag):
     * {exp:dibs:shifts_claimed author_id="123"}
     * {exp:dibs:shifts_claimed url_title="{segment_2}" channel_id="4"}
     *
     * Tag pair:
     * {exp:dibs:shifts_claimed url_title="{segment_2}" channel_id="4"}
     *   {shifts_claimed}
     * {/exp:dibs:shifts_claimed}
     *
     * Returns the SUM of exp_channel_data_field_17.field_id_17 for all entries
     * where exp_channel_titles.author_id = resolved author_id and titles.entry_id = data.entry_id.
     */
    public function shifts_claimed()
    {
        $author_id = $this->resolve_author_id();
        if (! $author_id) {
            // Nothing to calculate
            return $this->render_value('0');
        }

        $row = ee()->db
            ->select('SUM(d.field_id_17) AS shifts_claimed', false)
            ->from('exp_channel_data_field_17 d')
            ->join('exp_channel_titles t', 't.entry_id = d.entry_id', 'inner')
            ->where('t.author_id', (int) $author_id)
            ->get()
            ->row_array();

        $total = $row && $row['shifts_claimed'] !== null ? (string) (int) $row['shifts_claimed'] : '0';

        return $this->render_value($total, 'shifts_claimed');
    }

    /**
     * Resolve author_id from params:
     * - If author_id param provided, use it
     * - Else if url_title (and optional channel_id) provided, look up author_id from titles
     */
    private function resolve_author_id()
    {
        $author_id  = ee()->TMPL->fetch_param('author_id', '');
        if ($author_id !== '') {
            return (int) $author_id;
        }

        $url_title  = ee()->TMPL->fetch_param('url_title', '');
        $channel_id = (int) ee()->TMPL->fetch_param('channel_id', 4);

        if ($url_title === '') {
            return 0;
        }

        $row = ee()->db->select('author_id')
            ->from('exp_channel_titles')
            ->where('channel_id', $channel_id)
            ->where('url_title', $url_title)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? (int) $row['author_id'] : 0;
    }

    /**
     * Helper: render single or tagpair value
     */
    private function render_value($value, $var_name = null)
    {
        $tagdata = ee()->TMPL->tagdata;
        if ($tagdata && $var_name) {
            return ee()->TMPL->parse_variables($tagdata, [[ $var_name => $value ]]);
        }
        return $value;
    }
}
