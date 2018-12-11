<?php
/****************************************************************

srrFileManager - Sergi Rodrigues Rius (October 2009, until now)
sergio.cero@gmail.com
http://www.crear-paginas-web.blogspot.com
http://www.imasdeweb.com
- - - - - - - - - - - - - - - - - - -

This script will perform basic functions on files
Functions include, List, Open, View, Edit, Create, Upload, Rename, Move and Search.

This project has built over the "osfm Static" project (Devin Smith, July 1st 2003)
http://www.osfilemanager.com

We added: multilingual interface, multiuser login, search functionality, multi-css-theme

We have conserved the original idea of having a UNIQUE PHP FILE
because we don't pretend to have the best file manager, but one simple and PORTABLE
 ****************************************************************/

/***************************************************************

Copyright (C) 2012  Sergi Rodrigues Rius
LICENSE: GPL v2

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ****************************************************************/

ini_set('display_errors', 'On');
error_reporting(E_ALL);

// users list:
$config                     = array();
$config['version']          = '1.5';
$config['a_users']          = array();
$config['a_users']['admin'] = array('user'=> 'admin', 'pass'=> '1234', 'filefolder'=> '../');

$config['adminfile'] = $_SERVER['SCRIPT_NAME'];
$config['SEARCH']    = array('scanned_files'=> 0, 'a_found'=> array());

/* ************************************************************** */
/*								SKINS & STYLES
/* ************************************************************** */

$skins_dir = dirname(__FILE__) . '/skins';

// == list of templates
if (isset($_COOKIE['a_skins'])) {
    $config['a_skins'] = unserialize(stripslashes($_COOKIE['a_skins']));
} else {
    // reset list
    $config['a_skins'] = array();
    // scan folder with skins (.css files)
    $dh = opendir($skins_dir);
    while (($file = readdir($dh)) != false) {
        if ($file != '.' && $file != '..') {
            $skin                     = substr($file, 0, -4);
            $config['a_skins'][$skin] = $skin;
        }
    }
    // store at cookie
    setcookie('a_skins', serialize($config['a_skins']), time() + 3600 * 24 * 365);
}

// == decide the language for this thread
$config['skin'] = (isset($_COOKIE['skin']) && isset($config['a_skins'][$_COOKIE['skin']])) ? $_COOKIE['skin'] : 'mint';
if (isset($_GET['skin']) && isset($config['a_skins'][trim($_GET['skin'])])) {
    $config['skin'] = trim($_GET['skin']);
    setcookie('skin', $config['skin'], time() + 100000000000);
}

/* ************************************************************** */
/*								LANGUAGES 
/* ************************************************************** */
$l        = array(); // for store translations
$lang_dir = dirname(__FILE__) . '/languages';

// == list of languages
    if (isset($_COOKIE['a_lang']) && !isset($_GET['lang'])) {
        $config['a_lang'] = unserialize(stripslashes($_COOKIE['a_lang']));
    } else {
        // reset list
        $config['a_lang'] = array();
        // scan folder with translations
        $dh = opendir($lang_dir);
        while (($file = readdir($dh)) != false) {
            if ($file != '.' && $file != '..') {
                include_once($lang_dir . '/' . $file);
            }
        }
        // store at cookie
        setcookie('a_lang', serialize($config['a_lang']), time() + 3600 * 24 * 365);
    }
    asort($config['a_lang']);

// == decide the language for this thread
    if (isset($_GET['lang']) && isset($config['a_lang'][$_GET['lang']]))
        $lang = $_GET['lang'];
    else if (isset($_COOKIE['lang']) && isset($config['a_lang'][$_COOKIE['lang']]))
        $lang =  $_COOKIE['lang'];
    else
        $lang =  'en';
    
    // == save the lang at a cookie
        if (!isset($_COOKIE['lang']) || $_COOKIE['lang']!=$lang) {
            setcookie('lang', $lang, time() + 100000000000);
        }

// == load language translations
    include($lang_dir . '/' . $lang . '.php');

/****************************************************************/
/* User identification                                          */
/*                                                              */
/* Looks for cookies. Yum.                                      */
/****************************************************************/
if (isset($_POST['user'])) {
    // try login
    if (isset($config['a_users'][$_REQUEST['user']])
        && $config['a_users'][$_REQUEST['user']]['pass'] == $_REQUEST['pass']
    ) {
        setcookie('user', $_REQUEST['user'], time() + 60 * 60 * 24 * 1);
        setcookie('pass', md5($config['a_users'][$_REQUEST['user']]['pass']), time() + 60 * 60 * 24 * 1);
        $config['user'] = $_REQUEST['user'];
    } else {
        login(true);
    }

} else if (isset($_COOKIE['user'])) {
    // once inside
    if (md5($config['a_users'][$_COOKIE['user']]['pass']) != $_COOKIE['pass']) {
        login(true);
    }
} else {
    // first visit
    login();
}

/* ************************************************************** */

$op     = (isset($_POST['op'])) ? $_POST['op'] : ((isset($_GET['op'])) ? $_GET['op'] : ((isset($_REQUEST['op'])) ? $_REQUEST['op'] : ""));
$folder = (isset($_POST['folder'])) ? $_POST['folder'] : ((isset($_GET['folder'])) ? $_GET['folder'] : ((isset($_REQUEST['folder'])) ? $_REQUEST['folder'] : ""));
$user   = (isset($_COOKIE['user'])) ? $_COOKIE['user'] : ((isset($_POST['user'])) ? $_POST['user'] : "");
//while (preg_match('/\.\.\//',$folder)) $folder = preg_replace('/\.\.\//','/',$folder);
while (preg_match('/\/\//', $folder)) $folder = preg_replace('/\/\//', '/', $folder);

$fileFolder = $config['a_users'][$user]['filefolder'];
if ($folder == '') {
    $folder = $fileFolder;
} elseif ($fileFolder != '') {
    // == check that the path of the folder requested CONTAIN the user filefolder
    if (!preg_match(_2preg($fileFolder), $folder)) {
        $folder = $fileFolder;
    }
    // == check that the path of the folder requested doesn't CONTAIN more '../' than the user filefolder
    preg_match_all('/\.\.\//',$folder,$matches1);
        $n_backdots_folder = count($matches1[0]);
    preg_match_all('/\.\.\//',$fileFolder,$matches2);
        $n_backdots_filefolder = count($matches2[0]);
    if ($n_backdots_folder > $n_backdots_filefolder){
        $folder = $fileFolder;
    }
}


/****************************************************************/
/* function maintop()                                           */
/*                                                              */
/* Controls the style and look of the site.                     */
/* Recieves $title and displayes it in the title and top.       */
/****************************************************************/
function maintop($title, $showtop = true)
{
    global $config, $l;
    echo "<html>\n<head>\n"
        . "\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />"
        . "<title>" . $l['SITETITLE'] . " :: $title</title>\n"
        . "<link rel='stylesheet' type='text/css' href='skins/" . $config['skin'] . ".css' />\n"
        . "</head>\n"
        . "<body>\n";

    echo "<div id='wrapper'>";
    echo "<div id='languages'>\n";
    foreach ($config['a_lang'] as $cod=> $tit)
        echo "<a href=\"" . $config['adminfile'] . "?op=home&lang=$cod\">$tit</a>\n";
    echo "</div>\n";
    echo "<h1>" . $l['SITETITLE'] . " :: $title</h1>\n";

    if ($showtop) {
        $var_folder = (isset($_GET['folder'])) ? '&folder=' . $_GET['folder'] : '';
        echo "<div id='top_menu'>\n"
            . "<a href='" . $config['adminfile'] . "?op=home'>" . $l['BT_HOME'] . "</a>\n"
            . "<a href='" . $config['adminfile'] . "?op=up$var_folder'>" . $l['BT_UPLOAD'] . "</a>\n"
            . "<a href='" . $config['adminfile'] . "?op=cr$var_folder'>" . $l['BT_CREATE'] . "</a>\n"
            . "<a href='" . $config['adminfile'] . "?op=search$var_folder'>" . $l['BT_SEARCH'] . "</a>\n"
            . "<a href='" . $config['adminfile'] . "?op=logout'>" . $l['BT_LOGOUT'] . "</a>\n"
            . "</div>";
    }
}


/****************************************************************/
/* function login()                                             */
/*                                                              */
/* Sets the cookies and alows user to log in.                   */
/* Recieves $pass as the user entered password.                 */
/****************************************************************/
function login($er = false)
{
    global $op, $config, $l;
    setcookie("user", "", time() - 60 * 60 * 24 * 1);
    setcookie("pass", "", time() - 60 * 60 * 24 * 1);
    maintop($l['TOP_LOGIN'], false);

    if ($er) {
        echo "<span class='error'>" . $l['LOGIN_ERR'] . "</span><br /><br />\n";
    }

    echo "<hr /><form id='login_form' action=\"" . $config['adminfile'] . "?op=" . $op . "\" method=\"post\">\n"
        . "<table id='tb_login'>\n"
        . "<tr><td class='a_r' style='width:40%;'>" . $l['LOGIN_USERNAME'] . ":</td>"
        . "	<td class='a_l'><input type='text' name='user' value=\"" . ((isset($_REQUEST['user'])) ? htmlspecialchars($_REQUEST['user']) : "") . "\"></td></tr>\n"
        . "<tr><td class='a_r'>" . $l['LOGIN_PASSW'] . ": </td>\n"
        . "	<td class='a_l'><input type='password' name='pass' value=\"" . ((isset($_REQUEST['pass'])) ? htmlspecialchars($_REQUEST['pass']) : "") . "\"></td></tr>\n"
        . "<tr><td>&nbsp;</td><td class='a_l'><br /><a href='#' class='button' onclick=\"document.getElementById('login_form').submit();return false;\">" . htmlspecialchars($l['LOGIN_BT']) . "</a></td></tr>\n"
        . "</table>\n"
        . "</form>\n";
    mainbottom();

}


/****************************************************************/
/* function home()                                              */
/*                                                              */
/* Main function that displays contents of folders.             */
/****************************************************************/
function home()
{
    global $folder, $config, $l;
    maintop($l['TOP_HOME']);

    $content1 = array();
    $content2 = array();

    $count = "0";
    $hf    = opendir($folder);
    $a     = 1;
    $b     = 1;

    while ($file = readdir($hf)) {
        if (strlen($file) > 40) {
            $sfile = substr($file, 0, 40) . "...";
        } else {
            $sfile = $file;
        }
        if ($file != "." && $file != "..") {
            $perm = substr(sprintf('%o', fileperms($folder . $file)), -3);
            if (is_dir($folder . $file)) {
                if (!is_readable($folder . $file) || !is_writable($folder . $file)) {
                    $content1[strtolower($file)] = "<td><a href=\"" . $config['adminfile'] . "?op=home&folder=" . $folder . $file . "/\" class='a_folder'>" . $sfile . "</a></td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap></td>\n"
                        . "<td align=\"left\" colspan='6'>" . $l['HOME_MSG1'] . "</td></tr>\n"
                        . "<tr height=\"2\"><td height=\"2\" colspan=\"9\">\n";
                } else {
                    $n_size                      = filesize($folder . $file); //_get_dir_size($folder.$file);
                    $content1[strtolower($file)] = "<td><a href=\"" . $config['adminfile'] . "?op=home&folder=" . $folder . $file . "/\" class='a_folder'>" . $sfile . "</a></td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap>" . _size_format($n_size) . "</td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=home&folder=" . $folder . $file . "/\">" . $l['HOME_BT_OPEN'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=ren&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_RENAME'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=del&dename=" . $file . "&folder=$folder\">" . $l['HOME_BT_DELETE'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=mov&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_MOVE'] . "</a></td>\n"
                        . "<td align=\"center\">&nbsp;</td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=zip&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_COMPRESS'] . "</a></td>\n"
                        . "</tr><tr height=\"2\"><td height=\"2\" colspan=\"9\">\n";
                }
                $a++;

            } elseif (!is_dir($folder . $file)) {
                if (!is_readable($folder . $file) || !is_writable($folder . $file)) {
                    $content2[strtolower($file)] = "<td>" . $sfile . "</td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap></td>\n"
                        . "<td align=\"left\" colspan='6'>" . $l['HOME_MSG2'] . "</td></tr>\n"
                        . "<tr height=\"2\"><td height=\"2\" colspan=\"9\">\n";
                } else {
                    $n_size                      = filesize($folder . $file);
                    $content2[strtolower($file)] = "<td>" . $sfile . "</td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap>" . _size_format($n_size) . "</td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=edit&fename=" . $file . "&folder=$folder\">" . $l['HOME_BT_EDIT'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=ren&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_RENAME'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=del&dename=" . $file . "&folder=$folder\">" . $l['HOME_BT_DELETE'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=mov&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_MOVE'] . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $folder . $file . "\" target='_blank'>" . $l['HOME_BT_VIEW'] . "</a></td>\n"
                        .(strtolower(substr($file,-4))=='.zip' ?
                        "<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=unzip&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_DECOMPRESS'] . "</a></td>\n"
                        :"<td align=\"center\"><a href=\"" . $config['adminfile'] . "?op=zip&file=" . $file . "&folder=$folder\">" . $l['HOME_BT_COMPRESS'] . "</a></td>\n")
                        . "</tr><tr height=\"2\"><td height=\"2\" colspan=\"9\">\n";
                }
                $b++;

            }
            $count++;
        }
    }
    closedir($hf);
    if (is_array($content1))
        ksort($content1);
    if (is_array($content2))
        ksort($content2);

    // = message to user
        if (!empty($config['msg'])){
            echo $config['msg']."<hr />";
        }
        
    // = breadcrumb & number of files
    echo "<div class='breadcrumb'>\n"
        . $l['HOME_LAB_BROWS'] . ": <span>" . breadcrumb($folder) . "</span>\n"
        . "<br />" . $l['HOME_LAB_NUM'] . ": <em>" . $count . "</em>\n"
        . "</div>";
    
    // = file list
    echo "<table id='tb_list' cellspacing='0'><thead><tr>"
        . "<th>" . $l['HOME_LAB_FILE'] . "\n"
        . "<th style='text-align:center;'>" . $l['HOME_LAB_PERM'] . "</th>\n"
        . "<th style='width:65px;text-align:right;'>" . $l['HOME_LAB_SIZE'] . "</th>\n"
        . "<th align=\"center\" style='width:44px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:58px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:57px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:40px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:44px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:57px;'>&nbsp;</th>\n"
        . "</tr></thead><tbody>\n";

    $j = 1;
    if (is_array($content1) && count($content1) > 0) {
        foreach ($content1 as $cont) {
            $class = ($j % 2) ? 'tr_par' : 'tr_impar';
            $j++;
            echo "<tr class='" . $class . "'>\n";
            echo $cont;
        }
    }
    if (is_array($content2) && count($content2) > 0) {
        foreach ($content2 as $cont) {
            $class = ($j % 2) ? 'tr_par' : 'tr_impar';
            $j++;
            echo "<tr class='" . $class . "'>\n";
            echo $cont;
        }
    }

    echo"</tbody></table>\n";
    mainbottom();
}

/****************************************************************/
/* function breadcrumb()                                        */
/*                                                              */
/* Generate a clickable breadcrumb.                             */
/****************************************************************/
function breadcrumb($path)
{
    global $config;
    $ex = explode('/', $path);
    if (count($ex) > 0) {
        $breadcrumb = '';
        $subpath    = '';
        foreach ($ex as $subfolder) {
            if ($subfolder == '') continue;
            if ($subpath != '') {
                $subpath .= '/';
                $breadcrumb .= ' / ';
            }
            $subpath .= $subfolder;
            $breadcrumb .= " <a href=\"" . $config['adminfile'] . "?op=home&folder=" . $subpath . "/\">" . $subfolder . "</a>";
        }
    } else {
        $breadcrumb = $path;
    }
    return $breadcrumb;
}

/****************************************************************/
/* function up()                                                */
/*                                                              */
/* First step to Upload.                                        */
/* User enters a file and the submits it to upload()            */
/****************************************************************/
function up()
{
    global $fileFolder, $config, $l;
    maintop($l['TOP_UPLOAD']);
    $perm = substr(sprintf('%o', fileperms($fileFolder)), -3);
    echo "<FORM id='upload_form' ENCTYPE=\"multipart/form-data\" ACTION=\"" . $config['adminfile'] . "?op=upload\" METHOD=\"POST\">\n"
        . "<font face='tahoma' size='2'><b>" . $l['CR_FILE'] . ":</b></font><br /><input type='File' name='upfile' class='text' style='width:99%;' />\n"
		. "<br /><br /><font face='tahoma' size='2'><b>" . $l['UP_REMOTE_URL'] . ":</b></font><br /><input type='text' name='upurl' placeholder='https://' class='text' style='width:99%;'>\n"
        . "<br /><br />" . $l['UP_DESTINATION'] . ":<br /><select name='ndir' style='width:400px;'>\n"
        . "<option value=\"" . $fileFolder . "\">[$perm] " . $fileFolder . "</option>";
    listdir($fileFolder);
    echo "</select><br /><br />"
        . "<a href='#' class='button' onclick=\"document.getElementById('upload_form').submit();\">" . $l['UP_BT'] . "</a>\n"
        . "</form>\n";

    mainbottom();
}


/****************************************************************/
/* function upload()                                            */
/*                                                              */
/* Sencond step in upload.                                      */
/* Saves the file to the disk.                                  */
/* Recieves $upfile from up() as the uploaded file.             */
/****************************************************************/
function upload($upfile, $ndir)
{
    global $l;
    if (!empty($_POST['upurl'])){
		$remote_url = trim($_POST['upurl']);
		if (!preg_match('/^(http:\/\/|https:\/\/)/',$remote_url)){
			printerror($l['UP_MSG5']);
		}else{
			$ex = explode('/',$remote_url);
			$ex2 = explode('?',$ex[(count($ex)-1)]);
			$filename = trim($ex2[0]);
			if ($filename==''){
				printerror($l['UP_MSG5']);
			}else if (file_exists($ndir.$filename)){
				printerror(str_replace(array('%1','%2'),array("[ <code>$filename</code> ]","[ ".breadcrumb($ndir)." ]"),$l['UP_MSG7']));
			}else{
				$response_info = _get_remote_file($remote_url,$ndir.$filename);
				if (!empty($response_info['error'])){
					printerror($response_info['error']);
				}else if (isset($response_info['http_code']) && $response_info['http_code']=='404'){
					@unlink($ndir.$filename);
					printerror($l['UP_MSG6']."<br /><br /><a href='$remote_url' target='_blank'>$remote_url</a>");
				}else{
					@chmod($ndir.$filename, 0777);
					maintop($l['TOP_UPLOAD']);
					echo "<div class='breadcrumb'>" . str_replace('%1', "[ <span style='color:#c00;'>" . breadcrumb($ndir) . " / " . $filename . "</span> ]", $l['UP_MSG2'])
						." (<b>"._size_format(filesize($ndir.$filename))."</b>)."."</div>\n";
					mainbottom();
				}
			}
		}
    }else if (!$upfile) {
        printerror($l['UP_MSG1']);
    } elseif ($upfile['name']) {
        if (@copy($upfile['tmp_name'], $ndir . $upfile['name'])) {
            maintop($l['TOP_UPLOAD']);
            echo "<div class='breadcrumb'>" . str_replace('%1', "[ <span style='color:#c00;'>" . breadcrumb($ndir) . " / " . $upfile['name'] . "</span> ]", $l['UP_MSG2']) . "</div>\n";
            mainbottom();
        } else {
            printerror(str_replace('%1', "[ <span style='color:#c00;'>" . $upfile['name'] . "</span> ]", $l['UP_MSG3']));
        }
    } else {
        printerror($l['UP_MSG4']);
    }
}

function _get_remote_file( $url, $filename, $vars = '') {
        $url = str_replace( "&amp;", "&", urldecode(trim($url)) );
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        //curl_setopt( $ch, CURLOPT_COOKIEJAR, tempnam ("/tmp", "CURLCOOKIE") );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );

        if (is_array($vars) && !empty($vars['HTTPHEADER']))
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $vars['HTTPHEADER']);

        if (is_array($vars) && !empty($vars['USERAGENT']))
            curl_setopt( $ch, CURLOPT_USERAGENT, $vars['USERAGENT']);
        else
            curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );

        if (is_array($vars) && !empty($vars['TIMEOUT'])){
            curl_setopt( $ch, CURLOPT_TIMEOUT, intval($vars['TIMEOUT']) );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, intval($vars['TIMEOUT']) );
        }else{
            curl_setopt( $ch, CURLOPT_TIMEOUT, 600 );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 600 );
        }

		if (($fp = fopen($filename, "wb")) === false) return array('error'=>"fopen error for filename $filename.");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        if (curl_exec($ch) === false) {
            fclose($fp);
            unlink($filename);
            return array('error'=>"curl_exec error for url $url.");
        }
		fclose($fp);
		
        $response = curl_getinfo( $ch );
        curl_close ( $ch );

        return $response;
}

/****************************************************************/
/* function del()                                               */
/*                                                              */
/* First step in delete.                                        */
/* Prompts the user for confirmation.                           */
/* Recieves $dename and ask for deletion confirmation.          */
/****************************************************************/
function del($dename)
{
    global $folder, $config, $l;
    if (!$dename == "") {
        maintop($l['TOP_DELETE']);
        echo ""
            . "<p class='error'>\n"
            . $l['DEL_MSG1'] . "</p>\n"
            . "<p>" . str_replace('%1', "[ <span style='color:#c00;'>" . $folder . $dename . "</span> ]", $l['DEL_MSG2']) . "</p>\n"
            . "<a href='" . $config['adminfile'] . "?op=delete&dename=" . urlencode($dename) . "&folder=$folder' class='button'>" . $l['YES'] . "</a>\n"
            . "<a href='" . $config['adminfile'] . "?op=home&folder=$folder' class='button'>" . $l['NO'] . "</a>\n";
        mainbottom();
    } else {
        home();
    }
}

/****************************************************************/
/* function search()                                               */
/*                                                              */
/* Recieves $folder and optionally $query and other filter variables */
/****************************************************************/
function search()
{
    global $fileFolder, $config, $l;
    maintop($l['BT_SEARCH']);
    // == load data
    if (!isset($_REQUEST['folder']) || trim($_REQUEST['folder']) == '')
        $folder = $fileFolder;
    else
        $folder = trim($_REQUEST['folder']);
    $query      = (isset($_REQUEST['query'])) ? trim(stripslashes($_REQUEST['query'])) : '';
    $where      = (isset($_REQUEST['where'])) ? trim(stripslashes($_REQUEST['where'])) : '';
    $extensions = (isset($_REQUEST['extensions'])) ? stripslashes($_REQUEST['extensions']) : 'php,html,js,css,txt';
    $extensions = trim(str_replace(array('.', ' ', ';', '|'), ',', $extensions));
    if ($extensions == '') $extensions = '*';
    $sensitive = (isset($_REQUEST['sensitive'])) ? trim(stripslashes($_REQUEST['sensitive'])) : '';
    // == search options
    echo "<h3>" . $l['SCH_TIT1'] . "</h3>\n";
    echo "<div class='list_dir'>\n";
    echo "<form action='" . $config['adminfile'] . "?op=search&folder=" . $folder . "' method='post'>\n";
    echo "<p>" . $l['SCH_DIR'] . " <span style='color:#c00;'>" . $folder . "</span></p>\n";
    echo "<div>" . $l['SCH_QRY'] . " <input type='text' name='query' id='query' value=\"" . htmlspecialchars($query) . "\"  style='width:300px;' /> &nbsp; "
        . $l['SCH_CASE'] . " <input type='checkbox' name='sensitive' " . (($sensitive == 'on') ? "checked='checked'" : "") . " /></div>";
    echo "<div>" . $l['SCH_WHERE']
        . " <p style='margin-left:50px;display:none;'><input type='radio' name='where' id='where_1' value='filename' " . (($where == 'filename' or $where == '') ? "checked='true'" : '') . "  />" . $l['SCH_W1'] . "</p>\n"
        . " <p style='margin-left:50px;'><input type='radio' name='where' id='where_2' value='filecontent' checked='true' />" . $l['SCH_W2']
        . " <input type='text' name='extensions' id='extensions' value=\"" . htmlspecialchars($extensions) . "\"  style='width:200px;' /></p>\n"
        . "</div>";
    echo "<div><br /><input type='submit' value=\"" . htmlspecialchars($l['SCH_BT']) . "\" onclick=\"if (document.getElementById('query').value=='') return false;\" /></div>";
    echo "</form></div>\n";
    // == search results
    if ($query != '') {
        $a_ext       = ($extensions != '*') ? explode(',', $extensions) : $extensions;
        $b_sensitive = ($sensitive == 'on') ? '' : 'i';
        if (substr($folder, -1) == '/') $folder = substr($folder, 0, -1);
        _scanDirectory(_2preg($query, $b_sensitive, $where), $a_ext, $where, opendir($folder), $folder, basename($folder), '');
        $n_found = count($config['SEARCH']['a_found']);
        echo "<h3>" . $l['SCH_TIT2'] . " &nbsp; &rarr; <span style='color:#c00;'>" . $n_found . "</span> / " . $config['SEARCH']['scanned_files'] . "</h3>\n";
        echo "<div class='list_dir'>\n";
        if ($n_found > 0) {
            ksort($config['SEARCH']['a_found']);
            echo "<ul>";
            foreach ($config['SEARCH']['a_found'] as $path=> $arr) {
                echo '<li>' . $path;
                if (is_array($arr) && count($arr) > 0) {
                    echo "\n<ul>";
                    foreach ($arr as $str)
                        echo "\n<li>" . (($where == 'filecontent') ? str_replace($query, '<em>' . $query . '</em>', htmlentities($str)) : $str) . '</li>';
                    echo "\n</ul>";
                }
                echo "</li>\n";
            }
            echo "</ul>";
        }
        echo "</div>\n";
    }
    mainbottom();
}


/****************************************************************/
/* function delete()                                            */
/*                                                              */
/* Second step in delete.                                       */
/* Deletes the actual file from disk.                           */
/* Recieves $upfile from up() as the uploaded file.             */
/****************************************************************/
function delete($dename)
{
    global $folder, $l, $config;
    if (!$dename == "") {
        if (is_dir($folder . $dename)) {
            if (_rmdir($folder . $dename))
                $msg = $l['DEL_MSG3'];
            else
                $msg = $l['DEL_MSG4'];
        } else {
            if (unlink($folder . $dename))
                $msg = $l['DEL_MSG3'];
            else
                $msg = $l['DEL_MSG5'];
        }
        $config['msg'] = "<div class='breadcrumb'>" . str_replace('%1', "[ <span style='color:#c00;'>" . breadcrumb($folder) . " / " . $dename . "</span> ]", $msg) . "</div>";
    }
    home();
}

/****************************************************************/
/* function zip()                                               */
/*                                                              */
/* Compress a file/directory in a unique step.                  */
/*                                                              */
/****************************************************************/
function zip($filename)
{
    global $folder, $l, $config;
    if (!$filename == "") {
        // = path to zip
            $path_to_zip = $folder.$filename;
        // = destination file
            $zip_filename = $folder.$filename.'.zip';
            if (file_exists($zip_filename))
                $zip_filename = $folder.$filename.'.'.time().'.zip';
        // = zip it
            $res = _zip($path_to_zip,$zip_filename);
            if ($res === false){
               $msg = $l['ZIP_MSG3']; 
            }else if (is_dir($path_to_zip)){
               $msg = $l['ZIP_MSG2']; 
            }else{
               $msg = $l['ZIP_MSG1']; 
            }
        // = render answer
            $config['msg'] = "<div class='breadcrumb'>" . str_replace(
                    array('%1','%2'),
                    array("[ <span style='color:#c00;'>" . breadcrumb($folder) . " / " . $filename . "</span> ]"," <a href='".$zip_filename."' target='_blank'>".str_replace($folder,'',$zip_filename)."</a>"),
                    $msg) . "</div>";
            home();
        return;
    }
}

/****************************************************************/
/* function unzip()                                             */
/*                                                              */
/* Decompress a ZIP file.                                       */
/*                                                              */
/****************************************************************/
function unzip($filezip)
{
    global $folder, $l, $config;
    if (!$filezip == "") {
        // = zip it
            $res = _unzip($folder.$filezip,$folder);
            if ($res !== true){
               $msg = $l['UNZIP_MSG2']. "<p>".$res."</p>"; 
            }else{
               $msg = $l['UNZIP_MSG1']; 
            }
        // = render answer
            $config['msg'] = "<div class='breadcrumb'>" . str_replace('%1',"[ <span style='color:#c00;'>" . breadcrumb($folder) . " / " . $filezip . "</span> ]",$msg) . "</div>";
            home();
        return;
    }
}

/****************************************************************/
/* function edit()                                              */
/*                                                              */
/* First step in edit.                                          */
/* Reads the file from disk and displays it to be edited.       */
/* Recieves $upfile from up() as the uploaded file.             */
/****************************************************************/
function edit($fename)
{
    global $folder, $config, $l;
    if (!$fename == "") {
        maintop($l['TOP_EDIT']);

        $handle = @fopen($folder . $fename, "r");
        if (!$handle) {
            echo "<p style='color:#c00;'>" . $l['HOME_MSG2'] . "</p>";
            return;
        }

        echo $folder . $fename;

        echo "<form action=\"" . $config['adminfile'] . "?op=save\" method='post'>\n"
            . "<textarea rows='40' name='ncontent' style='width:100%;'>\n";

        $contents = "";
        while ($data = @fread($handle, filesize($folder . $fename))) {
            if (strlen($data) == 0) {
                break;
            }
            $contents .= $data;
        }
        fclose($handle);

        $replace1 = "</text";
        $replace2 = "area>";
        $replace3 = "< / text";
        $replace4 = "area>";
        $replacea = $replace1 . $replace2;
        $replaceb = $replace3 . $replace4;
        $contents = preg_replace(_2preg($replacea), $replaceb, $contents);

        echo $contents;

        echo "</textarea>\n"
            . "<br /><br />\n"
            . "<input type=\"hidden\" name=\"folder\" value=\"" . $folder . "\">\n"
            . "<input type=\"hidden\" name=\"fename\" value=\"" . $fename . "\">\n"
            . "<input type=\"submit\" value=\"" . $l['EDIT_BT'] . "\" >\n"
            . "</form>\n";
        mainbottom();
    } else {
        home();
    }
}


/****************************************************************/
/* function save()                                              */
/*                                                              */
/* Second step in edit.                                         */
/* Recieves $ncontent from edit() as the file content.          */
/* Recieves $fename from edit() as the file name to modify.     */
/****************************************************************/
function save($ncontent, $fename)
{
    global $folder, $l;
    if (!$fename == "") {
        maintop($l['TOP_EDIT']);
        $loc = $folder . $fename;
        if (!@$fp = fopen($loc, "w")) {
            echo $l['EDIT_MSG2'] . "\n";
            mainbottom();
            return;
        }

        $replace1 = "</text";
        $replace2 = "area>";
        $replace3 = "< / text";
        $replace4 = "area>";
        $replacea = $replace1 . $replace2;
        $replaceb = $replace3 . $replace4;
        $ncontent = preg_replace(_2preg($replaceb), $replacea, $ncontent);

        $ydata = stripslashes($ncontent);

        if (@fwrite($fp, $ydata)) {
            echo str_replace('%1', "[ <span style='color:#c00;'><a href=\"" . $folder . $fename . "\" target='_blank'>" . $folder . $fename . "</a></span> ]", $l['EDIT_MSG1']);
            $fp = null;
        } else {
            echo $l['EDIT_MSG2'] . "\n";
        }
        mainbottom();
    } else {
        home();
    }
}


/****************************************************************/
/* function cr()                                                */
/*                                                              */
/* First step in create.                                        */
/* Promts the user to a filename and file/directory switch.     */
/****************************************************************/
function cr()
{
    global $folder, $content, $fileFolder, $config, $l;
    maintop($l['TOP_CREATE']);
    if (!$content == "") {
        echo "<br /><br />" . $l['CR_MSG1'] . ".\n";
    }
    $perm = substr(sprintf('%o', fileperms($fileFolder)), -3);
    echo "<form action=\"" . $config['adminfile'] . "?op=create\" method=\"post\">\n";
    echo "<input type=\"radio\" size=\"20\" name=\"isfolder\" value=\"0\" checked> " . $l['CR_FILE'] . "<br />\n"
        . "<input type=\"radio\" size=\"20\" name=\"isfolder\" value=\"1\"> " . $l['CR_DIRECTORY'] . "<br /><br />\n";
    echo $l['CR_FILENAME'] . ": <br /><input type=\"text\" size=\"20\" name=\"nfname\" class=\"text\"><br /><br />\n"
        . $l['CR_DESTINATION'] . ":<br /><select name='ndir' style='width:400px;'>\n"
        . "<option value=\"" . $fileFolder . "\">[$perm] " . $fileFolder . "</option>";
    listdir($fileFolder);
    echo "</select><br /><br />\n"
        . "<input type=\"hidden\" name=\"folder\" value=\"$folder\">\n"
        . "<input type=\"submit\" value=\"" . $l['CR_BT'] . "\" >\n"
        . "</form>\n";
    mainbottom();
}


/****************************************************************/
/* function create()                                            */
/*                                                              */
/* Second step in create.                                       */
/* Creates the file/directoy on disk.                           */
/* Recieves $nfname from cr() as the filename.                  */
/* Recieves $infolder from cr() to determine file trpe.         */
/****************************************************************/
function create($nfname, $isfolder, $ndir)
{
    global $l;
    if (!$nfname == "") {
        maintop($l['TOP_CREATE']);
        if (substr($ndir, -1) != '/') $ndir .= '/';
        if ($isfolder == 1) {
            if (@mkdir($ndir . $nfname, 0777)) {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir . $nfname) . "</span> ]", $l['CRT_MSG1']) . "\n";
            } else {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir . $nfname) . "</span> ]", $l['CRT_MSG2']) . "\n";
            }
        } else {
            if (@fopen($ndir . $nfname, "w")) {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir) . " / " . $nfname . "</span> ]", $l['CRT_MSG3']) . "\n";
            } else {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir) . " / " . $nfname . "</span> ]", $l['CRT_MSG4']) . "\n";
            }
        }
        mainbottom();
    } else {
        cr();
    }
}

/****************************************************************/
/* function ren()                                               */
/*                                                              */
/* First step in rename.                                        */
/* Promts the user for new filename.                            */
/* Globals $file and $folder for filename.                      */
/****************************************************************/
function ren($file)
{
    global $folder, $config, $l;
    if (!$file == "") {
        maintop($l['TOP_RENAME']);
        echo "<form action=\"" . $config['adminfile'] . "?op=rename\" method=\"post\">\n"
            . "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n"
            . $l['REN_RENAMING'] . " [ <span style='color:#c00;'>" . $folder . $file . "</span> ]";

        echo "</table><br />\n"
            . "<input type=\"hidden\" name=\"rename\" value=\"" . $file . "\">\n"
            . "<input type=\"hidden\" name=\"folder\" value=\"" . $folder . "\">\n"
            . $l['REN_NEW'] . ":<br /><input class=\"text\" type=\"text\" size=\"20\" name=\"nrename\" value=\"" . $file . "\">\n"
            . "<input type=\"Submit\" value=\"" . $l['REN_BT'] . "\" >\n";
        mainbottom();
    } else {
        home();
    }
}

/****************************************************************/
/* function renam()                                             */
/*                                                              */
/* Second step in rename.                                       */
/* Rename the specified file.                                   */
/* Recieves $rename from ren() as the old  filename.            */
/* Recieves $nrename from ren() as the new filename.            */
/****************************************************************/
function renam($rename, $nrename, $folder)
{
    global $folder, $l;
    if (!$rename == "") {
        maintop($l['TOP_RENAME']);
        $loc1 = "$folder" . $rename;
        $loc2 = "$folder" . $nrename;

        if (@rename($loc1, $loc2)) {
            $txt = str_replace('%1', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $rename . "</div>", $l['REN_MSG1']);
            $txt = str_replace('%2', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $nrename . "</div>", $txt);
            echo $txt;
        } else {
            if (is_dir($loc1))
                echo $l['REN_MSG3'] . "\n";
            else
                echo $l['REN_MSG2'] . "\n";
        }
        mainbottom();
    } else {
        home();
    }
}

/****************************************************************/
/* function listdir()                                           */
/*                                                              */
/* Recursivly lists directories and sub-directories.            */
/* Recieves $dir as the directory to scan through.              */
/****************************************************************/
function listdir($dir, $level_count = 0)
{
    if (!@($thisdir = opendir($dir))) {
        return;
    }
    while ($item = readdir($thisdir)) {
        if (is_dir("$dir/$item") && (substr("$item", 0, 1) != '.')) {
            listdir("$dir/$item", $level_count + 1);
        }
    }
    if ($level_count > 0) {
        $dir      = preg_replace("/\/\//", "/", $dir);
        $selected = (isset($_GET['folder']) && $_GET['folder'] == $dir . '/') ? "selected='selected'" : "";
        $perm     = substr(sprintf('%o', fileperms($dir)), -3);
        echo "<option value=\"" . $dir . "/\" $selected>[$perm] " . $dir . "/</option>\n";
    }
    return;
}

function a_listdir($dir, $onclick)
{
    global $fileFolder, $l;
    if (!@($thisdir = opendir($dir))) {
        return;
    }
    // == directories ABOVE this
    $dir_list = array();
    $dir2     = (substr($dir, -1) == '/') ? substr($dir, 0, -1) : $dir;
    $ex       = explode('/', $dir2);
    if (count($ex) > 0) {
        $current  = $ex[count($ex) - 1];
        $realpath = '';
        foreach ($ex as $p) {
            if ($p == $current || $p == '') continue;
            $realpath .= $p . '/';
            if (strpos($realpath, $fileFolder) !== false) // $filefolder is the folder which the user has permission to access
                $dir_list[] = "<a href='#' onclick=\"" . $onclick . "('" . $realpath . "')\">" . $realpath . "</a>\n";
        }
    }
    if (count($dir_list) > 0) {
        krsort($dir_list);
        echo "<p>" . $l['ABOVE'] . ":</p>\n" . implode('', $dir_list);
    }
    // == directories UNDER this
    $dir_list = array();
    while ($item = readdir($thisdir)) {
        if (is_dir("$dir/$item") && (substr("$item", 0, 1) != '.')) {
            $dir_list[strtolower($item)] = "<a href='#' onclick=\"" . $onclick . "('" . $dir . $item . "/')\">" . $dir . $item . "/</a>\n";
        }
    }
    if (count($dir_list) > 0) {
        ksort($dir_list);
        echo "<p>" . $l['BELOW'] . ":</p>\n" . implode('', $dir_list);
    }
    return;
}


/****************************************************************/
/* function mov()                                               */
/*                                                              */
/* First step in move.                                          */
/* Prompts the user for destination path.                       */
/* Recieves $file and sends to move().                          */
/****************************************************************/
function mov($file)
{
    global $folder, $config, $l;
    if (!$file == "") {
        maintop($l['TOP_MOVE']);
        echo "<form action=\"" . $config['adminfile'] . "?op=move\" method=\"post\">\n"
            . "<div>\n"
            . str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($folder) . " / " . $file . "</span> ]", $l['MOV_MSG1']) . ": \n"
            . " <span id='span_target' style='color:#c00;'></span>\n"
            . "<input type='hidden' id='ndir' name='ndir' style='width:400px;'>\n"
            . "<div class='list_dir'>\n";
        a_listdir($folder, 'js_change_dir');
        echo "</div>\n"
            . "<script>function js_change_dir(new_dir){document.getElementById('ndir').value=new_dir;document.getElementById('span_target').innerHTML=new_dir;}</script>\n"
            . "</div><br /><input type='hidden' name='file' value=\"" . $file . "\">\n"
            . "<input type='hidden' name='folder' value=\"" . $folder . "\" />\n"
            . "<input type='submit' value=\"" . htmlspecialchars($l['MOV_BT']) . "\"  />\n"
            . "</form>";
        mainbottom();
    } else {
        home();
    }
}

/****************************************************************/
/* function move()                                              */
/*                                                              */
/* Second step in move.                                         */
/* Moves the oldfile to the new one.                            */
/* Recieves $file and $ndir and creates $file.$ndir             */
/****************************************************************/
function move($file, $ndir, $folder)
{
    global $folder, $l;
    if (!$file == "") {
        maintop($l['TOP_MOVE']);
        if (@rename($folder . $file, $ndir . $file)) {
            $txt = str_replace('%1', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $file . "</div>", $l['MOV_MSG2']);
            $txt = str_replace('%2', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($ndir) . " / " . $file . "</div>", $txt);
            echo $txt;
        } else {
            echo "<p>" . str_replace('%1', " <span class='breadcrumb'>" . breadcrumb($folder) . " / " . $file . "</span> ", $l['MOV_MSG3']) . "</p>";
        }
        mainbottom();
    } else {
        home();
    }
}

/****************************************************************/
/* function logout()                                            */
/*                                                              */
/* Logs the user out and kills cookies                          */
/****************************************************************/
function logout()
{
    global $config, $l;
    setcookie("user", "", time() - 60 * 60 * 24 * 1);
    setcookie("pass", "", time() - 60 * 60 * 24 * 1);

    maintop($l['TOP_LOGOUT'], false);
    echo $l['LOGOUT_MSG1']
        . "<hr /><br /><br />"
        . "<a href=" . $config['adminfile'] . "?op=home>" . $l['LOGOUT_MSG2'] . "</a>";
    mainbottom();
}

/****************************************************************/
/* function mainbottom()                                        */
/*                                                              */
/* Controls the bottom copyright.                               */
/****************************************************************/
function mainbottom()
{
    global $config, $l;
    $a_skin_html = array();
    foreach ($config['a_skins'] as $t) {
        if ($config['skin'] != $t)
            $a_skin_html[] = "<a href=\"" . $config['adminfile'] . "?op=home&skin=" . urlencode($t) . "\">" . $t . "</a>";
        else
            $a_skin_html[] = $t;
    }
    echo "<div id='main_bottom'>"
        . "<div style='text-align:right;'>version " . $config['version'] . " | GPL 2009 - " . date('Y') . " <a href='https://github.com/caos30/php_srrFileManager' target='_blank'>srrFileManager at GitHub</a></div>\n"
        . "<div style='text-align:right;'>" . $l['AVAILABLE_SKINS'] . " " . implode(' | ', $a_skin_html) . "</div>\n"
        . "</div>\n"
        . "</body></html>\n";
    exit;
}

/****************************************************************/
/* function printerror()                                        */
/*                                                              */
/* Prints error onto screen                                     */
/* Recieves $error and prints it.                               */
/****************************************************************/
function printerror($error)
{
    global $l;
    maintop($l['TOP_ERROR']);
    echo "<font class=error>\n" . $error . "\n</font>";
    mainbottom();
}


/****************************************************************/
/* function switch()                                            */
/*                                                              */
/* Switches functions.                                          */
/* Recieves $op() and switches to it                            *.
/****************************************************************/
switch ($op) {

    case "home":
        home();
        break;

    case "search":
        search();
        break;

    case "up":
        up();
        break;

    case "upload":
        upload($_FILES['upfile'], $_REQUEST['ndir']);
        break;

    case "del":
        del($_REQUEST['dename']);
        break;

    case "delete":
        delete($_REQUEST['dename']);
        break;

    case "zip":
        zip($_REQUEST['file']);
        break;

    case "unzip":
        unzip($_REQUEST['file']);
        break;

    case "edit":
        edit($_REQUEST['fename']);
        break;

    case "save":
        save($_REQUEST['ncontent'], $_REQUEST['fename']);
        break;

    case "cr":
        cr();
        break;

    case "create":
        create($_REQUEST['nfname'], $_REQUEST['isfolder'], $_REQUEST['ndir']);
        break;

    case "ren":
        ren($_REQUEST['file']);
        break;

    case "rename":
        renam($_REQUEST['rename'], $_REQUEST['nrename'], $folder);
        break;

    case "mov":
        mov($_REQUEST['file']);
        break;

    case "move":
        move($_REQUEST['file'], $_REQUEST['ndir'], $folder);
        break;

    case "printerror":
        printerror($error);
        break;

    case "logout":
        logout();
        break;

    default:
        home();
        break;
}

/****************************************************************/
/* 								MISCELLANEAUS                        */
/*                                                              */
/****************************************************************/
function _2preg($string, $insensitive = 'i', $where = '')
{
    // /home/dir.1 ----> /\/home\/dir\.1/
    if ($where == 'filecontent')
        return '/(.*)' . str_replace(array('/', '.'), array('\/', '\.'), $string) . '(.*)/' . $insensitive;
    else
        return '/' . str_replace(array('/', '.'), array('\/', '\.'), $string) . '/' . $insensitive;
}

function _get_dir_size($dir_name)
{
    $dir_size = 0;
    if (is_dir($dir_name)) {
        if ($dh = opendir($dir_name)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (is_file($dir_name . '/' . $file)) {
                        $dir_size += filesize($dir_name . '/' . $file);
                    }
                    /* check for any new directory inside this directory */
                    if (is_dir($dir_name . '/' . $file)) {
                        $dir_size += _get_dir_size($dir_name . '/' . $file);
                    }
                }
            }
            closedir($dh);
        }
    }
    return $dir_size;
}

function _size_format($n)
{
    if ($n < 1024) return "<span class='size_b'>" . $n . ' b</span>';
    else if ($n < 1048576) return "<span class='size_kb'>" . number_format($n / 1024, 1, '.', ',') . ' kb</span>';
    else if ($n < 1073741824) return "<span class='size_mb'>" . number_format($n / 1048576, 1, '.', ',') . ' Mb</span>';
    else return "<span class='size_gb'>" . number_format($n / 1073741824, 1, '.', ',') . ' Gb</span>';

}

function _rmdir($dir)
{
    $ret = true;
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") $ret = _rmdir($dir . "/" . $object); else $ret = unlink($dir . "/" . $object);
                if (!$ret) return false;
            }
        }
        if (!$ret) return false;
        reset($objects);
        rmdir($dir);
    }
    return $ret;
}

function _scanDirectory($needle, $a_ext = '*', $where = 'filename', $dirid, $dirname, $path, $spaces)
{
    global $config;
    while (($file = readdir($dirid)) != false) {
        if (($file != '.') && ($file != '..')) {
            $dirname_full = $dirname . '/' . $file;
            if (is_dir($dirname_full) && ($file != 'cgi-bin')) {
                if ($where == 'filename' && preg_match($needle, $file))
                    $config['SEARCH']['a_found'][$dirname . '/<b>' . $file . '</b>'] = $dirname . '/<b>' . $file . '</b>';

                $dirid_next = opendir($dirname_full); // was just $file
                $newpath    = $path . '/' . $file;
                _scanDirectory($needle, $a_ext, $where, $dirid_next, $dirname_full, $newpath, $spaces);
                closedir($dirid_next);
            } else {
                if ($where == 'filecontent') {
                    $b_analyze = false;
                    if (!is_array($a_ext) && $a_ext == '*') {
                        $b_analyze = true;
                    } else {
                        $break              = explode('.', $file);
                        $file_ext           = $break[(count($break) - 1)];
                        $file_ext_lowercase = strtolower($file_ext);
                        if ((in_array($file_ext_lowercase, $a_ext)))
                            $b_analyze = true;
                    }

                    if ($b_analyze) {
                        $code = file_get_contents($dirname . '/' . $file);
                        if (preg_match_all($needle, $code, $a_matches)) {
                            $config['SEARCH']['a_found'][$dirname . '/<b>' . $file . '</b>'] = $a_matches[0];
                        }
                        $config['SEARCH']['scanned_files']++;

                    }
                } else {
                    // == filename
                    $config['SEARCH']['scanned_files']++;
                    if (preg_match($needle, $file)) {
                        if (!isset($config['SEARCH']['a_found'][$dirname])) $config['SEARCH']['a_found'][$dirname] = array();
                        $config['SEARCH']['a_found'][$dirname][] = $dirname . '/<b>' . $file . '</b>';
                    }
                }
            }
        }
    }
    return;
}

/*
 * high level function for zip (taken from CMS Barllo: f_zip() )
 */
function _zip($path_to_zip,$zip_filename){
    if (!extension_loaded('zip') || (!is_array($path_to_zip) && !file_exists($path_to_zip))) return false;

    $zip = new ZipArchive();
    if (!$zip->open($zip_filename, ZIPARCHIVE::CREATE)) return false;

    if (!is_array($path_to_zip)){
        $array_of_paths = array($path_to_zip);
    }else if (count($path_to_zip)==0){
        return false;
    }else{
        $array_of_paths = $path_to_zip;
    }
    
    foreach($array_of_paths as $path_to_zip){
        $path_to_zip = str_replace('\\', '/', realpath($path_to_zip));
        if (is_dir($path_to_zip) === true){
            $ex = explode('/',$path_to_zip); // $path_to_zip = /foo/bar 
            $first_folder = $ex[(count($ex)-1)]; // $ex[(count($ex)-1)] = bar
            $zip->addEmptyDir($first_folder); 
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path_to_zip), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file){
                $file = str_replace('\\', '/', $file);

                // == Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) continue;

                $file = realpath($file);

                if (is_dir($file) === true){
                    $zip->addEmptyDir($first_folder.'/'.str_replace($path_to_zip . '/', '', $file . '/'));
                }else if (is_file($file) === true){
                    $zip->addFromString($first_folder.'/'.str_replace($path_to_zip . '/', '', $file), file_get_contents($file));
                }
            }
        }else if (is_file($path_to_zip) === true){
            $zip->addFromString(basename($path_to_zip), file_get_contents($path_to_zip));
        }
    }
    return $zip->close();
}

/*
 * high level function for zip (taken from CMS Barllo: update_repository.php)
 */
function _unzip($zipfile,$destination_folder){
    if (!extension_loaded('zip') || !file_exists($zipfile)) return false;

    $zip_errors = array(
        '10' => 'ZIPARCHIVE::ER_EXISTS (File already exists)',
        '21' => 'ZIPARCHIVE::ER_INCONS (Zip archive inconsistent)',
        '18' => 'ZIPARCHIVE::ER_INVAL',
        '14' => 'ZIPARCHIVE::ER_MEMORY (Malloc failure)',
        '9' => 'ZIPARCHIVE::ER_NOENT (No such file)',
        '19' => 'ZIPARCHIVE::ER_NOZIP (Not a zip archive)',
        '11' => 'ZIPARCHIVE::ER_OPEN (Cannot open file)',
        '5' => 'ZIPARCHIVE::ER_READ (Read error)',
        '4' => 'ZIPARCHIVE::ER_SEEK (Seek error)',
        );

    $zip = new ZipArchive;
    $res = $zip->open($zipfile);
    if ($res !== true) return "Impossible to open the ZIP file: [ ".$zipfile." ]. <br /><br />ERROR: ".$zip_errors[$res].".";

    $res = $zip->extractTo($destination_folder);
    if ($res !== true) return "Impossible to extract the content of the ZIP file: [ ".$zipfile." ] to [ ".$destination_folder." ]. <br /><br />ERROR: ".$zip_errors[$res].".";

    $zip->close();
    return true;
}
