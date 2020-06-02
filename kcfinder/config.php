<?php

/** This file is part of KCFinder project
 *
 *      @desc Base configuration file
 *   @package KCFinder
 *   @version 2.51
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010, 2011 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @link http://kcfinder.sunhater.com
 */

// IMPORTANT!!! Do not remove uncommented settings in this file even if
// you are using session configuration.
// See http://kcfinder.sunhater.com/install for setting descriptions

//solving: Maximum execution time of 30 seconds exceeded
@set_time_limit(0);

$_CONFIG = array(

    'disabled' => false,
    'denyZipDownload' => true,
    'denyUpdateCheck' => false,
    'denyExtensionRename' => false,

    'theme' => "oxygen",

    'uploadURL' => "upload",
    'uploadDir' => "",

    'dirPerms' => 0755,
    'filePerms' => 0644,

    'access' => array(

        'files' => array(
            'upload' => true,
            'delete' => true,
            'copy' => false,
            'move' => false,
            'rename' => false,
        ),

        'dirs' => array(
            'create' => true,
            'delete' => true,
            'rename' => false,
        ),
    ),

    'deniedExts' => "exe com msi bat php phps phtml php3 php4 cgi pl",

    'types' => array(

        // CKEditor & FCKEditor types
        'files' => ((defined('KCFINDER_FILE_FORMAT_WHITE') && !empty(KCFINDER_FILE_FORMAT_WHITE)) ? KCFINDER_FILE_FORMAT_WHITE : ""),
        'flash' => "swf",
        'images' => ((defined('KCFINDER_IMG_FORMAT_WHITE') && !empty(KCFINDER_IMG_FORMAT_WHITE)) ? KCFINDER_IMG_FORMAT_WHITE : "*img"),

        // TinyMCE types
        'file' => ((defined('KCFINDER_FILE_FORMAT_WHITE') && !empty(KCFINDER_FILE_FORMAT_WHITE)) ? KCFINDER_FILE_FORMAT_WHITE : ""),
        'media' => "swf flv avi mpg mpeg qt mov wmv asf rm",
        'image' => ((defined('KCFINDER_IMG_FORMAT_WHITE') && !empty(KCFINDER_IMG_FORMAT_WHITE)) ? KCFINDER_IMG_FORMAT_WHITE : "*img"),
        '图片' => ((defined('KCFINDER_IMG_FORMAT_WHITE') && !empty(KCFINDER_IMG_FORMAT_WHITE)) ? KCFINDER_IMG_FORMAT_WHITE : "*img"),
        '音频' => "mp3",
        '文件' => ((defined('KCFINDER_FILE_FORMAT_WHITE') && !empty(KCFINDER_FILE_FORMAT_WHITE)) ? KCFINDER_FILE_FORMAT_WHITE : ""),
    ),
    // 上传的大小  单位 M
    'sizeByType' => array(
        // TinyMCE types
        'file' => ((defined('KCFINDER_FILE_MAXSIZE') && !empty(KCFINDER_FILE_MAXSIZE)) ? KCFINDER_FILE_MAXSIZE : 0),
        'media' => 0,
        'image' => ((defined('KCFINDER_IMG_MAXSIZE') && !empty(KCFINDER_IMG_MAXSIZE)) ? KCFINDER_IMG_MAXSIZE : 0),
        '图片' => ((defined('KCFINDER_IMG_MAXSIZE') && !empty(KCFINDER_IMG_MAXSIZE)) ? KCFINDER_IMG_MAXSIZE : 0),
        '音频' => 0,
        '文件' => ((defined('KCFINDER_FILE_MAXSIZE') && !empty(KCFINDER_FILE_MAXSIZE)) ? KCFINDER_FILE_MAXSIZE : 0),
    ),

    'filenameChangeChars' => array( /*
' ' => "_",
':' => "."
 */),

    'dirnameChangeChars' => array( /*
' ' => "_",
':' => "."
 */),

    'mime_magic' => "",

    'maxImageWidth' => 0,
    'maxImageHeight' => 0,

    'thumbWidth' => 100,
    'thumbHeight' => 100,

    'thumbsDir' => "_thumbs",

    'jpegQuality' => 90,

    'cookieDomain' => "",
    'cookiePath' => "",
    'cookiePrefix' => 'KCFINDER_',

    // THE FOLLOWING SETTINGS CANNOT BE OVERRIDED WITH SESSION CONFIGURATION
    '_check4htaccess' => true,
    //'_tinyMCEPath' => "/tiny_mce",

    '_sessionVar' => &$_SESSION['KCFINDER'],
    //'_sessionLifetime' => 30,
    //'_sessionDir' => "/full/directory/path",

    //'_sessionDomain' => ".mysite.com",
    //'_sessionPath' => "/my/path",
);
