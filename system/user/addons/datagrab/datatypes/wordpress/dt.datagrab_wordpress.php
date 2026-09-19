<?php

/**
 * DataGrab WordPress import class
 *
 * Allows WordPress imports
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_wordpress extends AbstractDataType
{
    public $datatype_info = [
        'name' => 'WordPress (Beta)',
        'version' => '0.1',
        'description' => 'Import data from a WordPress export file <span class="st-warning">BETA</span>',
        'allow_comments' => true
    ];

    public $settings = [
        "filename" => "",
        "just_posts" => "y"
    ];

    public $config_defaults = [
        "title" => "title",
        "date" => "pubDate",
        "import_comments" => "y",
        "comment_author" => "wp:comment/wp:comment_author",
        "comment_email" => "wp:comment/wp:comment_author_email",
        "comment_url" => "wp:comment/wp:comment_author_url",
        "comment_date" => "wp:comment/wp:comment_date",
        "comment_body" => "wp:comment/wp:comment_content",
        "cat_field" => "categories",
        "author_field" => "dc:creator",
        "status" => "wp:status"
    ];

    /*
       Map WP statuses to EE ones. This is a temporary fix until I can
       add a more friendly user interface

       Alter this array to suit your requirements - you can use your own
       custom statuses here
    */
    public $statuses = [
        "publish" => "Open",
        "draft" => "Closed",
        "auto-draft" => "Closed",
        "pending" => "Closed",
        "inherit" => "Closed"
    ];

    public $path = "/rss/channel/item";

    public $items;

    /**
     * @param array $values
     * @return array[]
     */
    public function settings_form(array $values = []): array
    {
        return [
            [
                'title' => 'Filename or URL',
                'desc' => lang('datagrab_filename_instructions'),
                'fields' => [
                    'filename' => [
                        'required' => true,
                        'type' => 'text',
                        'value' => $this->get_value($values, 'filename') ?: '{base_url}/my-file.xml',
                    ]
                ]
            ],
            [
                'title' => 'Just import posts?',
                'desc' => 'Only import posts, or include other post types (eg, attachments)',
                'fields' => [
                    'just_posts' => [
                        'required' => true,
                        'type' => 'toggle',
                        'value' => $this->get_value($values, 'just_posts') ?: 1,
                    ]
                ]
            ]
        ];
    }

    public function fetch()
    {
        try {
            ini_set('pcre.backtrack_limit','250000');
            $xml = file_get_contents($this->getFilename());
        } catch (Exception $exception) {
            return -1;
        }

        if($xml === false) {
            $this->addError('Cannot open file/url: ' . $this->getFilename());
            return -1;
        }

        $this->items = array();
        $this->items = $this->_fetch_xml( $xml );

        if ( $this->items == "" ) {
            $this->addError('Please check the path is correct: ' . $this->path);
            return -1;
        }
    }

    public function next()
    {
        // PHP 8.1 change
        if (!is_array($this->items)) {
            $this->items = (array) $this->items;
        }

        $item = current( $this->items );
        next( $this->items );

        return $item;
    }

    public function fetch_columns(): array
    {
        try {
            $this->fetch();
            $columns = $this->next();

            $titles = [
                "title" => "Title",
                "pubDate" => "Published Date",
                "link" => "Link",
                "dc:creator" => "Creator",
                "guid" => "Guid",
                "description" => "Description",
                "content:encoded" => "Content",
                "wp:status" => "Post Status",

                "wp:comment/wp:comment_author" => "Comment Author",
                "wp:comment/wp:comment_author_email" => "Comment Author Email",
                "wp:comment/wp:comment_author_url" => "Comment Author URL",
                "wp:comment/wp:comment_date" => "Comment Date",
                "wp:comment/wp:comment_content" => "Comment Content",

                "categories" => "Categories",
                "tags" => "Tags",

                //'wp:term' => 'Term',

                "wp:post_type" => "Post type"
            ];

            foreach( $titles as $idx => $title ) {
                if( isset( $columns[ $idx ] ) ) {
                    $eg = $columns[ $idx ];
                    if ( strlen( $eg ) > 32 ) {
                        $eg = substr( htmlspecialchars( $eg ), 0, 32 ) . "...";
                    }
                    if( $eg != "" ) {
                        $titles[ $idx ] .= " - eg, " . $eg;
                    }
                }
            }

            return $titles;
        } catch (Error $error) {
            $this->addError($error->getMessage());
        }

        return [];
    }

    /* Private functions */

    private function _fetch_xml( $xml ) {

        $items = array();

        $item_array = $this->_fetch_tags( $xml, "item" );

        if (preg_last_error() !== PREG_NO_ERROR) {
            $this->addError(sprintf('XML Parsing Error: %s', preg_last_error_msg()));
        }

        foreach( $item_array as $i ) {

            $item = array();

            // Fetch post type
            $item["wp:post_type"] = $this->_fetch_tags( $i, "wp:post_type" );

            // We could filter the results by post type, or just add it as
            // an available field...

            if( isset( $this->settings["just_posts"] ) && $this->settings["just_posts"] == "1" && $item["wp:post_type"] != "post" ) {
                continue;
            }

            // Fetch basic data
            $item["title"] = $this->_fetch_tags( $i, "title" );
            $item["pubDate"] = $this->_fetch_tags( $i, "pubDate" );
            $item["dc:creator"] = $this->_fetch_tags( $i, "dc:creator" );
            $item["link"] = $this->_fetch_tags( $i, "link" );
            $item["guid"] = $this->_fetch_tags( $i, "guid" );
            $item["description"] = $this->_fetch_tags( $i, "description" );
            $item["content:encoded"] = $this->_fetch_tags( $i, "content:encoded" );

            // Fetch and convert status
            $item["wp:status"] = $this->_fetch_tags( $i, "wp:status" );
            if( isset( $this->statuses[ $item["wp:status"] ] )) {
                $item["wp:status"] = $this->statuses[ $item["wp:status"] ];
            }


            // Fetch comments
            $comments = $this->_fetch_comments( $i );
            $item["wp:comment#"] = count( $comments );
            $item["wp:comment/wp:comment_content#"] = count( $comments );
            $count = 0;
            foreach( $comments as $comment ) {
                $count++;
                $prefix = "";
                if( $count > 1 ) {
                    $prefix = "#" . $count;
                }
                $item["wp:comment/wp:comment_author{$prefix}"] = $this->_fetch_tags( $comment, "wp:comment_author" );
                $item["wp:comment/wp:comment_author_email{$prefix}"] = $this->_fetch_tags( $comment, "wp:comment_author_email" );
                $item["wp:comment/wp:comment_author_url{$prefix}"] = $this->_fetch_tags( $comment, "wp:comment_author_url" );
                $item["wp:comment/wp:comment_date{$prefix}"] = $this->_fetch_tags( $comment, "wp:comment_date" );
                $item["wp:comment/wp:comment_content{$prefix}"] = $this->_fetch_tags( $comment, "wp:comment_content" );
            }

            // Fetch categories
            $categories = $this->_fetch_tags( $i, "category", 'domain="category"' );
            if( is_array( $categories ) ) {
                $categories = array_unique( $categories );
                foreach( $categories as $idx => $category ) {
                    $categories[$idx] = $this->_remove_cdata( $category );
                }
                $item["categories"] = implode( ", ", $categories );
            }
            //tim addes to include post with single categories
            else {
                $item["categories"] = $this->_remove_cdata( $categories );
            }

            // Fetch tags
            $categories = $this->_fetch_tags( $i, "category", 'domain="tag"' );
            if( is_array( $categories ) ) {
                $categories = array_unique( $categories );
                foreach( $categories as $idx => $category ) {
                    $categories[$idx] = $this->_remove_cdata( $category );
                }
                $item["tags"] = implode( ", ", $categories );
            }
            //tim addes to include post with single tags
            else {
                $item["tags"] = $this->_remove_cdata( $categories );
            }

            $items[] = $item;

        }

        return $items;
    }

    function _fetch_tags( $xml, $tag, $attr="" ) {
        $reg = "|<$tag.*?>(.*?)</$tag>|is";
        if( $attr != "" ) {
            $reg = "|<$tag $attr.*?>(.*?)</$tag>|is";
        }
        $count = preg_match_all( $reg, $xml, $matches );
        if( $count == 0 ) {
            return array();
        }
        if( $count == 1 ) {
            $str = $matches[1][0];
            $str = $this->_remove_cdata( $str );
            return( $str );
        }
        return $matches[1];
    }

    function _remove_cdata( $str ) {
        $str = preg_replace( '#^<!\[CDATA\[(.*)\]\]>$#s', '$1', $str );
        return $str;
    }

    function _fetch_comments( $xml ) {
        $count = preg_match_all( '#<wp:comment>(.*?)</wp:comment>#is', $xml, $matches );
        if( $count == 0 ) {
            return array();
        }
        return $matches[1];
    }

    function _curl_fetch($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

    function _fsockopen_fetch($url)
    {
        $target = parse_url($url);

        $data = '';

        $fp = fsockopen($target['host'], 80, $error_num, $error_str, 8);

        if (is_resource($fp))
        {
            fputs($fp, "GET {$url} HTTP/1.0\r\n");
            fputs($fp, "Host: {$target['host']}\r\n");
            fputs($fp, "User-Agent: EE/xmlgrab PHP/" . phpversion() . "\r\n\r\n");

            $headers = TRUE;

            while( ! feof($fp))
            {
                $line = fgets($fp, 4096);

                if ($headers === FALSE)
                {
                    $data .= $line;
                }
                elseif (trim($line) == '')
                {
                    $headers = FALSE;
                }
            }

            fclose($fp);
        }

        return $data;
    }
}
