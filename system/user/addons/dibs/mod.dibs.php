<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dibs Module
 *
 * @package     Dibs
 * @author      CreativeArc
 * @link        https://creativearc.com
 * @version     1.0.0
 *
 * Usage:
 * {exp:dibs:get_author url_title="{segment_2}" channel_id="4"}
 *
 * As a tag pair:
 * {exp:dibs:get_author url_title="{segment_2}" channel_id="4"}
 *     {shift_author_id}
 * {/exp:dibs:get_author}
 */

class Dibs
{
    public function get_author()
    {
        $url_title  = ee()->TMPL->fetch_param('url_title', '');
        $channel_id = (int) ee()->TMPL->fetch_param('channel_id', 4);

        if ($url_title === '') {
            return '';
        }

        $row = ee()->db->select('author_id AS shift_author_id')
            ->from('exp_channel_titles')
            ->where('channel_id', $channel_id)
            ->where('url_title', $url_title)
            ->limit(1)
            ->get()
            ->row_array();

        $shift_author_id = $row ? $row['shift_author_id'] : '';

        // Tag pair usage
        $tagdata = ee()->TMPL->tagdata;
        if ($tagdata) {
            return ee()->TMPL->parse_variables($tagdata, [[
                'shift_author_id' => $shift_author_id
            ]]);
        }

        // Single tag usage
        return $shift_author_id;
    }
}
