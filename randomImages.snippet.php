<?php
/**
 *  File    randomImages.snippet.php (MODX snippet)
 * Created on  Nov 12, 2009
 * Project    shawn_wilkerson
 * @package    MODX Revolution Scripts
 * @version    1.3
 * @category  randomization
 * @author    W. Shawn Wilkerson
 * @link    http://www.shawnwilkerson.com
 * @copyright  Copyright (c) 2009, W. Shawn Wilkerson.  All rights reserved.
 * @license
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *

/**
 * define allowed template placeholders
 */
$placeHolders = array(
    '[[+ri.name]]',
    '[[+ri.size]]',
    '[[+ri.alttext]]',
    '[[+ri.titletext]]',
    '[[+ri.position]]'
);

/**
 * set output mode
 */
if (empty ($template)) {
    switch (strtolower($mode)) {
        case 'background' :
            $mode = 'bg';
            break;
        default :
            $mode = 'img';
            break;
    }
} else {
    $mode = 'template';
}

if ($folder = !empty ($folder) ? $folder : false) {
    /**
     * moves the selected picture to a random position providing the appearance
     * of multiple images being used instead of simply using one
     * reduces bandwidth
     */
    if ($bgPosition == 'random') {
        $possBgPos = array(
            "top left",
            "top center",
            "top right",
            "center left",
            "center center",
            "center right",
            "bottom left",
            "bottom center",
            "bottom right"
        );
        $bgPosition = $possBgPos[array_rand($possBgPos)];
    }
    unset ($possBgPos);
    /**
     * Establish a file array to hold all the individual matching file names
     * with their respective paths
     */
    $files = array();
    $location = preg_split("/[\s,]+/", $folder);
    reset($location);
    foreach ($location as $value) {
        $dir = new DirectoryIterator($value);
        foreach ($dir as $file) {
            if (!$file->isDot() && !$file->isDir() && preg_match("/\.(gif|png|jpg)$/", $file->getFilename())) {
                $files[] = $value . '/' . $file->getFilename();
            }
        }
    }
    $cnt = count($files);
    if (cnt > 0) {
        /* there are no images in this location */
        $output = false;
    } else {
        /**
         * we have a list of images to pick from
         */
        $image_name = $files[array_rand($files)];
        $imgSize = getImageSize($image_name);
        /**
         * Alt text is always supposed to be present in any img link
         * This will always return alt="" at the minimum
         * Can be silently dropped for background applications
         */
        $altText = ' alt="' . (!empty ($altText) ? '"' . $altText . '"' : '"');
        /**
         * title is always optional for images and can return an empty string
         */
        $titleText = !empty ($titleText) ? ' title="' . $titleText . '"' : '';
        switch ($mode) {
            case 'template' :
                /**
                 * Grab a MODX chunk from the database and replace placeholders
                 * with image content
                 */
                $useChunk = $modx->getChunk($template);
                /**
                 * create an array of image properties provided from the snippet call,
                 * as well as, from the actual selected image
                 */
                $phArray = array(
                    $image_name,
                    $imgSize[3],
                    $altText,
                    $titleText,
                    $bgPosition
                );
                /**
                 * perform string replace of image details into the template based on
                 * allowed placeholders listed at the top of this snippet
                 */
                $output .= str_replace($placeHolders, $phArray, $useChunk);
                break;
            case 'bg' :
                /**
                 * return only the image name and a position
                 */
                $output = 'style="background: #fff url(' . $image_name . ') no-repeat ';
                $output .= (!empty ($bgPosition)) ? $bgPosition . ';"' : 'top left;"';
                break;
            case 'img' :
            default :
                /**
                 * format a xhtml strict valid img tag
                 */
                $output = '<img src="' . $image_name . '"' . $imgSize[3] . $altText . $titleText . ' />\n"';
                break;
        }
    }
    return $output;
}

