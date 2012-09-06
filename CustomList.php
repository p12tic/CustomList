<?php
/*
    Copyright 2012 p12 <tir5c3@yahoo.co.uk>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


$wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'CustomList',
    'author'         => 'p12',
    'descriptionmsg' => 'Emulates ordered lists with custom labels ',
//  'url'            => '',
);


$CustomListLabelPrefix = '';
$CustomListLabelSuffix = ')';

$wgHooks['InternalParseBeforeLinks'][] = 'CustomList::parse';

class CustomList {

    static function parse(Parser &$parser, &$text)
    {
        $lines = explode("\n", $text);
        if (count($lines) < 1) {
            return true;
        }

        $tag_history = array();
        $list_history = array();
        $tag = '84hm40qst'; //tag that should never appear in text
        $in_list = false;
        $in_nowiki = false;
        
        for ($i = 0; $i < count($lines); $i++) {

            if (!$in_nowiki) {
                if ($in_list) {
                    if (preg_match('/^\s*=+.*?=+\s*$/', $lines[$i]) > 0) {
                        //terminate on heading
                        self::terminate($lines[$i-1]);
                        $in_list = false;
                    } else if (preg_match('/^\s*$/', $lines[$i]) > 0) {
                        //terminate on empty line
                        self::terminate($lines[$i-1]);
                        $in_list = false;
                    }
                }

                if (preg_match('/^(:*)@(.{0,20})@/', $lines[$i], $matches) > 0) {
                    $lines[$i] = preg_replace('/^:*@(.{0,20})@\s*/', '', $lines[$i]);
                    // new list entry
                    if ($in_list) {
                        self::terminate($lines[$i-1]);
                    }
                    $indent = strlen($matches[1]);
                    $label = $matches[2];

                    self::begin($lines[$i], $indent, $label);
                    $in_list = true;
                }

                // process wikitable nesting here
                if (preg_match('/^(:*)\{\|/', $lines[$i])) {
                    array_push($tag_history, $tag);
                    array_push($list_history, $in_list);
                    $tag = '{|';
                    $in_list = false;
                }
                if (substr($lines[$i], 0, 2) === '|}') {
                    if ($tag === '{|') {
                        if ($in_list) {
                            self::terminate($lines[$i-1]);
                        }
                        $tag = array_pop($tag_history);
                        $in_list = array_pop($list_history);
                    }
                    // else ignore
                }
            }

            // find all html tags within the line
            preg_match('/<(\/?\w*(:? [^>]?))>/', $lines[$i], $matches, PREG_OFFSET_CAPTURE);

            // if we terminate lists inline, we need to take into account that the remaining part
            // of the line is shifted
            $off_shift = 0; 

            for ($j = 1; $j < count($matches); $j++) {
                $match_str = $matches[$j][0];
                $match_off = $matches[$j][1];

                // special treatment for nowiki (we should not do anything within those tags)
                if ($in_nowiki) {
                    if ($match_str === "\/nowiki") {
                        $in_nowiki = false;
                    }
                    continue;
                }
                if ($match_str === "nowiki") {
                    $in_nowiki = true;
                    continue;
                }

                if ($match_str[strlen($match_str)-1] === "\/") {
                    //self-closing tag -> ignore
                    continue;
                }
                
                // get the tag
                preg_match('/^\/?(\w*)/', $match_str, $mm);
                $this_tag = $mm[1];

                // handle remaining tags
                if ($match_str[0] === "\/") {
                
                    //end tag
                    if (strcasecmp($this_tag, $tag) === 0) {
                        if ($in_list) {
                            self::terminate_inline($lines[$i], $match_off - 1, $off_shift);
                        }
                        $tag = array_pop($tag_history);
                        $in_list = array_pop($list_history);
                    }
                    continue;
                }

                // new tag
                array_push($tag_history, $tag);
                array_push($list_history, $in_list);
                $tag = $this_tag;
                $in_list = false;
            }
        }

        if ($in_list) {
            self::terminate($lines[count($lines)-1]);
        }
        $text = implode("\n", $lines);
        return true;

    }

    static function terminate(&$line)
    {
        $line = $line . '</div>';
    }

    static function terminate_inline(&$line, $pos, &$off)
    {
        $line = substr_replace($line, '</div>', $pos + $off, 0);
        // update off to reflect that the string became longer
        // any subsequent insertions will need it
        $off += 6; 
    }

    static function begin(&$line, $indent, $label)
    {
        global $CustomListLabelPrefix;
        global $CustomListLabelSuffix;
        $new_line = '<div class="t-li' . ($indent + 1) . '"><span class="t-li">';
        if (!empty($label)) {
            $new_line .= $CustomListLabelPrefix . $label . $CustomListLabelSuffix;
        }
        $new_line .= '</span> ' . $line;
        $line = $new_line;
    }

}
