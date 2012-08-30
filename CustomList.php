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

$wgHooks['ParserBeforeStrip'][] = 'CustomList::parse';

class CustomList {

    static function parse(Parser &$parser, &$text, &$strip_state)
    {

        $lines = explode("\n", $text);
        if (count($lines) < 1) {
            return true;
        }

        $in_list = false;

        for ($i = 0; $i < count($lines); $i++) {

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

            $matches = array();

            if (preg_match('/^(:*)@([0-9,\-]*?)@/', $lines[$i], $matches) > 0) {
                $lines[$i] = preg_replace('/^:*@[0-9,\-]*?@\s*/', '', $lines[$i]);
                // new list entry
                if ($in_list) {
                    self::terminate($lines[$i-1]);
                }
                $indent = strlen($matches[1]);
                $label = $matches[2];

                self::begin($lines[$i], $indent, $label);
                $in_list = true;
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

    static function begin(&$line, $indent, $label)
    {
        global $CustomListLabelPrefix;
        global $CustomListLabelSuffix;
        $new_line = '<div class="t-li' . ($indent + 1) . '"><span class="t-li">';
        $new_line .= $CustomListLabelPrefix . $label . $CustomListLabelSuffix;
        $new_line .= '</span> ' . $line;
        $line = $new_line;
    }

}
