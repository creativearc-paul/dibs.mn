<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dibs Module
 *
 * Usage:
 * {exp:dibs:get_author url_title="{segment_2}" channel_id="4"}
 * {exp:dibs:get_author author_id="123"}  // echoes 123
 *
 * {exp:dibs:shifts_claimed author_id="12|34|56"}
 * {exp:dibs:shifts_claimed url_title="{segment_2}" channel_id="4"}
 *
 * Tag pair examples:
 * {exp:dibs:get_author url_title="{segment_2}"}
 *   {shift_author_id}
 * {/exp:dibs:get_author}
 *
 * {exp:dibs:shifts_claimed author_id="12|34|56"}
 *   {shifts_claimed}
 * {/exp:dibs:shifts_claimed}
 */

class Dibs
{
    /**
     * get_author
     * - Returns the author_id for a given url_title/channel_id; or just echoes author_id if provided
     * - Tag pair exposes {shift_author_id}
     */
    public function get_author()
    {
        // Prefer explicit author_id if provided
        $explicit_author = ee()->TMPL->fetch_param('author_id', '');
        $tagdata         = ee()->TMPL->tagdata;

        if ($explicit_author !== '') {
            $author_id = (int) $explicit_author;
        } else {
            $author_id = $this->resolve_author_id();
        }

        $author_id_str = (string) $author_id;

        if ($tagdata) {
            return ee()->TMPL->parse_variables($tagdata, [[
                'shift_author_id' => $author_id_str
            ]]);
        }

        return $author_id_str;
    }

    /**
     * shifts_claimed
     * - Sums exp_channel_data_field_17.field_id_17 for all entries authored by one or more authors
     * - Accepts author_id="12|34|56" (pipe-delimited)
     * - Or resolves via url_title (+ optional channel_id, default 4)
     * - Tag pair exposes {shifts_claimed}
     */
    public function shifts_claimed()
    {
        $author_ids = $this->resolve_author_ids(); // array of ints

        if (empty($author_ids)) {
            return $this->render_value('0', 'shifts_claimed');
        }

        $row = ee()->db
            ->select('SUM(d.field_id_17) AS shifts_claimed', false)
            ->from('exp_channel_data_field_17 d')
            ->join('exp_channel_titles t', 't.entry_id = d.entry_id', 'inner')
            ->where_in('t.author_id', $author_ids)
            ->get()
            ->row_array();

        $total = ($row && $row['shifts_claimed'] !== null)
            ? (string) (int) $row['shifts_claimed']
            : '0';

        return $this->render_value($total, 'shifts_claimed');
    }

    /* ===========================
       Helpers
       =========================== */

    /**
     * Resolve exactly one author_id (kept for backward-compat).
     * Uses resolve_author_ids() and returns the first or 0.
     */
    private function resolve_author_id()
    {
        $ids = $this->resolve_author_ids();
        return $ids ? (int) $ids[0] : 0;
    }

    /**
     * Resolve one or more author IDs based on params:
     * - If author_id param is provided, supports pipe-delimited list ("12|34|56")
     * - Else if url_title (and optional channel_id) provided, look up a single author_id
     * Returns: array<int>
     */
    private function resolve_author_ids()
    {
        $author_param = trim(ee()->TMPL->fetch_param('author_id', ''));

        if ($author_param !== '') {
            // Pipe-delimited list to array<int>
            $ids = array_filter(array_map('intval', explode('|', $author_param)));
            // Remove zeros and duplicates
            $ids = array_values(array_unique(array_filter($ids, function ($v) { return $v > 0; })));
            return $ids;
        }

        $url_title  = ee()->TMPL->fetch_param('url_title', '');
        $channel_id = (int) ee()->TMPL->fetch_param('channel_id', 4);

        if ($url_title === '') {
            return [];
        }

        $row = ee()->db->select('author_id')
            ->from('exp_channel_titles')
            ->where('channel_id', $channel_id)
            ->where('url_title', $url_title)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? [(int) $row['author_id']] : [];
    }

    /**
     * Render a value either as single tag or tag pair variable
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
