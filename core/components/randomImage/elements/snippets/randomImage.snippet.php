<?php
/**
 *  File    randomImages.php (MODx Revolution Snippet)
 * Created on  Nov 12, 2009
 * Project    shawn_wilkerson
 * @package    MODx Revolution Scripts
 * @version    1.4
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
 ************************************************
 * Purpose: to access a user declared folder, select an image and display it
 *
 * Requirements: user must supply folder name in snippet call
 *         use an image as a random img placed into the page located in the root of the site might be accessed via:
 *         [[randomImages?folder=`img/blue/random`]] or [[randomImages?folder=`img/blue/random`&mode=`img`]]
 *
 *         use image as the background of an html object
 *         <div id="header" [[randomImages?folder=`img/blue/random`&mode=`background`&bgPosition=`bottom left`]]>
 *
 *         use a modX chunk as a template:
 *         [[randomImages?folder=`img/blue/random`&template=`imageTest`&altText=`[[++site_name]] picture`&titleText=`Guess what?`]]
 *
 * Dependencies: MODx Revolution 2.x +
 *
 *
 * Variable lexicon
 * Via Snippet call:
 *     $altText (string)  text to be placed in the image alt tag
 *     $bgPosition (string) can be one of: top left, top center, top right, center left,
 *                       center center, center right, bottom left,
 *                       bottom center, bottom right, random
 *
 *           `random` will change the background position to one of the allowed positions
 *
 *     $folder (string)   location on server of images to select from
 *         (comma delimeted string) [[randomImages?folder=`img/blue/random, img/christian/random`&mode=`background`]]
 *
 *     $mode  (string)   image (default)
 *               background of parent tag
 *     $template (string)  uses a user created MODx Revolution Chunk
 *     $titleText (string)  text to be placed in the image title tag
 *
 * Snippet internal vars:
 *     $i       (integer) simple counter -- holds current number of images found in directory
 *     $imgDir   (directory handle resource)
 *     $file_type  (string) returns every after the last occurance of the provided seed: "."
 *     $is_image   (string) returns an array with 7 elements
 *     $output   (string  | bool) returns the output content or false
 ************************************************/

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
    $locations = preg_split("/[\s,]+/", $folder);
    reset($locations);
    foreach ($locations as $location) {
        try
        {
            $iterator = new DirectoryIterator($location);
            while ($iterator->valid()) {
                if (!$iterator->isDot() && !$iterator->isDir() && preg_match("/\.(gif|png|jpg)$/", $iterator->getFilename())) {
                    $files[] = $location . '/' . $iterator->getFilename();
                }
                $iterator->next();
            }
        }
        catch (Exception $ex)
        {
            return $ex;
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
                $output = '<img src="' . $image_name . '" ' . $imgSize[3] . $altText . $titleText . ' />';
                break;
        }
    }
    return $output;
}