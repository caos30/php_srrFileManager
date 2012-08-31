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


// users list:
$config                     = array();
$config['version']          = '1.0';
$config['a_users']          = array();
$config['a_users']['admin'] = array('user'=> 'admin', 'pass'=> '1234', 'filefolder'=> './');

$adminfile        = $_SERVER['SCRIPT_NAME'];
$config['SEARCH'] = array('scanned_files'=> 0, 'a_found'=> array());

/* ************************************************************** */
/*								SKINS & STYLES
/* ************************************************************** */

$a_skin = array("Mint", "Night");

$skin = (isset($_COOKIE['skin']) && in_array($_COOKIE['skin'], $a_skin)) ? $_COOKIE['skin'] : 'Mint';
if (isset($_GET['skin']) && in_array(urldecode(stripslashes(trim($_GET['skin']))), $a_skin)) {
    $skin = urldecode(stripslashes(trim($_GET['skin'])));
    setcookie('skin', $skin, time() + 100000000000);
}

/* ************************************************************** */
/*								STYLES 
/* ************************************************************** */

switch ($skin) {
    case 'Mint': // by caos30, 2012
        $styles = "
	body {color: #444; font-size:11px; font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; scrollbar-base-color: #a6a6a6; MARGIN: 0px 0px 10px; BACKGROUND-COLOR: #ffffff}
	p, td, a, span {font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;}
	a {color:#8b8;text-decoration:none;}
	a:hover {color:#333;}
	hr{color:#ccd;height:1px;border:0px;border-bottom:2px #ccd solid;margin:17px 0px;}

	.copyright {font-size:10px; color: #000000; text-align: left;}
	.error {font-size:10px; color: #AA2222;}

	#wrapper{position:relative;width:750px;margin:11px auto;border:2px #eee solid;padding:15px;-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	h1{margin:0px 0px 7px 0px;font-size:18px;color:#555;}
	#languages{text-align:right;}
	#languages a, .button{display:inline-block;padding:2px 4px;border:1px #ddd solid;background-color:#eee;margin:3px;}
	#tb_login{width:100%;}
	#top_menu{border-top:2px #cdc solid;border-bottom:2px #cdc solid;margin:15px 0px;}
	#top_menu a{display:inline-block;padding:2px 6px;margin:3px;background-color:#cdc;color:#222;border:1px #aca solid;font-weight:bold;
					letter-spacing:1px;
					-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	-moz-border-radius-topleft: 4px;
	border-top-left-radius: 4px;
	-moz-border-radius-topright: 50px;		
	border-top-right-radius: 50px;

	#top_menu a:hover{background-color:#ded;}

	td { font-size : 80%;font-family : Tahoma, Verdana, Arial, Helvetica, sans-serif;color: #000000;font-weight: 700;}
	.tr_impar{background-color:#ded;}
	.tr_par{background-color:#c5d5c5;}

	.f_l {float:left;}
	.a_l{text-align:left;}
	.a_r{text-align:right;}
	.a_c{text-align:center;}
	
	.breadcrumb{margin:11px 0px;}
	.breadcrumb span{color:#333;}
	.breadcrumb em{color:#c00;font-style:normal;}
	.breadcrumb a{display:inline-block;padding:2px 4px;border:1px #ddd solid;background-color:#eee;}

	#tb_list{width:100%;}
	#tb_list th{background-color:#eee;color:#555;font-weight:bold;font-size:12px;padding:5px;}
	#tb_list td{padding:1px 3px;font-weight:normal;}
	#tb_list td a{display:inline-block;padding:2px 6px;margin:3px;background-color:#efe;color:#222;border:1px #ded solid;font-size:12px;
						font-weight:normal;
					-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	#tb_list td a:hover{background-color:#ded;}
	#tb_list td a.a_folder{background-color:#fed;width:98%;border:1px #edc solid;
					-moz-border-radius-topleft: 10px;border-top-left-radius: 10px;-moz-border-radius-topright: 10px;border-top-right-radius: 10px;}
	#tb_list td a.a_folder:hover{background-color:#ffe;}
	
	
	.size_b{color:#666;}
	.size_kb{color:#944;}
	.size_mb{color:#c22;}
	.size_gb{color:#f00;}
	
	div.list_dir{background:#eee;padding:11px;margin:11px;-webkit-border-radius: 5px;-moz-border-radius: 5px;}
	div.list_dir em{background:#ddd;font-style:normal;}
	div.list_dir a{display:block;padding:2px;margin:2px;background:#e5e5e5;}
	div.list_dir a:hover{background:#f5f5f5;}
	
	#main_bottom{margin:11px 0px;margin-top:51px;}
	";
        break;

    case 'Night': // by caos30, 2012
        $styles = "
	body {color: #ccc; font-size:11px; font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; scrollbar-base-color: #a6a6a6; MARGIN: 0px 0px 10px; background-color: #001;}
	.copyright {font-size:10px; text-align: left;}

	p, td, a, span {font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;}
	a {color:#aad;text-decoration:none;}
	a:hover {color:#ccf;}
	h1{margin:0px 0px 7px 0px;font-size:18px;color:#aaa;}
	hr{color:#445;height:1px;border:0px;border-bottom:2px #445 solid;margin:17px 0px;}
	textarea,input,select{background-color:#445;color:#cce;border-color:#666;border-width:1px; padding:3px;}

	.error {color: #e88;}
	
	#wrapper{position:relative;width:750px;margin:11px auto;border:2px #334 solid;padding:15px;-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	#languages{text-align:right;}
	#languages a, .button{display:inline-block;padding:2px 4px;border:1px #555 solid;background-color:#334;margin:3px;}
	#tb_login{width:100%;}
	#top_menu{border-top:2px #334 solid;border-bottom:2px #334 solid;margin:15px 0px;}
	#top_menu a{display:inline-block;padding:2px 6px;margin:3px;border:1px #555 solid;font-weight:bold;
					letter-spacing:1px;background-color:#334;
					-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	#top_menu a:hover{background-color:#445;}
	-moz-border-radius-topleft: 4px;
	border-top-left-radius: 4px;
	-moz-border-radius-topright: 50px;		
	border-top-right-radius: 50px;

	#top_menu a:hover{background-color:#565;}

	td { color: #aaa;font-size : 80%;font-family : Tahoma, Verdana, Arial, Helvetica, sans-serif;font-weight: 700;}
	.tr_impar{background-color:#18181c;}
	.tr_par{background-color:#202025;}

	.f_l {float:left;}
	.a_l{text-align:left;}
	.a_r{text-align:right;}
	.a_c{text-align:center;}
	
	.breadcrumb{margin:11px 0px;}
	.breadcrumb span{color:#ccc;}
	.breadcrumb em{color:#f88;font-style:normal;}
	.breadcrumb a{background-color:#334;display:inline-block;padding:2px 4px;border:1px #555 solid;}
	.breadcrumb a:hover{background-color:#445;}

	#tb_list{width:100%;}
	#tb_list th{background-color:#111;color:#bbb;font-weight:bold;font-size:12px;padding:5px;}
	#tb_list td{padding:1px 3px;font-weight:normal;}
	#tb_list td a{display:inline-block;padding:2px 6px;margin:3px;border:1px #444 solid;font-size:12px;
			font-weight:normal;background-color:#334;
			-webkit-border-radius: 3px;-moz-border-radius: 3px;}
	#tb_list td a:hover{background-color:#445;}
	#tb_list td a.a_folder{background-color:#334;width:98%;border:1px #555 solid;
					-moz-border-radius-topleft: 10px;border-top-left-radius: 10px;-moz-border-radius-topright: 10px;border-top-right-radius: 10px;}
	#tb_list td a.a_folder:hover{background-color:#445;}
	
	
	.size_b{color:#666;}
	.size_kb{color:#944;}
	.size_mb{color:#c22;}
	.size_gb{color:#f00;}
	
	div.list_dir{background:#223;padding:11px;margin:11px;-webkit-border-radius: 5px;-moz-border-radius: 5px;}
	div.list_dir em{background:#334;font-style:normal;}
	div.list_dir a{display:block;padding:2px;margin:2px;background:#050505;}
	div.list_dir a:hover{background:#151515;}
	
	#main_bottom{margin:11px 0px;margin-top:51px;}
	";
        break;
}
/* ************************************************************** */
/*								LANGUAGES 
/* ************************************************************** */

$a_lang = array('cat'=> "Català", 'en'=> "English", 'es'=> "Castellano");

$lang = (isset($_COOKIE['lang']) && isset($a_lang[$_COOKIE['lang']])) ? $_COOKIE['lang'] : 'en';
if (isset($_GET['lang']) && isset($a_lang[trim($_GET['lang'])])) {
    $lang = trim($_GET['lang']);
    setcookie('lang', $lang, time() + 100000000000);
}

switch ($lang) {
    case 'cat':
        // ====== CATALAN
        define('_LANG_SITETITLE', "Administrador d'arxius");
        define('_LANG_YES', 'Sí');
        define('_LANG_NO', 'No');
        define('_LANG_BT_HOME', 'Inici');
        define('_LANG_BT_UPLOAD', 'Pujar arxiu');
        define('_LANG_BT_CREATE', 'Crear');
        define('_LANG_BT_SEARCH', 'Cercar');
        define('_LANG_BT_LOGOUT', 'Sortir');
        define('_LANG_LOGIN_ERR', "**ERROR: Informació d'accés incorrecta.**");
        define('_LANG_LOGIN_USERNAME', 'Usuari');
        define('_LANG_LOGIN_PASSW', 'Contrasenya');
        define('_LANG_LOGIN_BT', 'Accedir');
        define('_LANG_HOME_BT_EDIT', 'Editar');
        define('_LANG_HOME_BT_OPEN', 'Obrir');
        define('_LANG_HOME_BT_RENAME', 'Renombrar');
        define('_LANG_HOME_BT_DELETE', 'Eliminar');
        define('_LANG_HOME_BT_MOVE', 'Moure');
        define('_LANG_HOME_BT_VIEW', 'Veure');
        define('_LANG_HOME_LAB_BROWS', 'Ruta');
        define('_LANG_HOME_LAB_NUM', "Nombre d'arxius");
        define('_LANG_HOME_LAB_FILE', 'Arxius i directoris');
        define('_LANG_HOME_LAB_PERM', 'Permisos');
        define('_LANG_HOME_LAB_SIZE', 'Tamany');
        define('_LANG_HOME_MSG1', 'Directori no legible/escribible.');
        define('_LANG_HOME_MSG2', 'Arxiu no legible/escribible.');
        define('_LANG_TOP_HOME', 'Inici');
        define('_LANG_TOP_LOGIN', 'Accés');
        define('_LANG_TOP_UPLOAD', 'Pujar archivo');
        define('_LANG_TOP_DELETE', 'Eliminar');
        define('_LANG_TOP_EDIT', 'Editar');
        define('_LANG_TOP_CREATE', 'Crear');
        define('_LANG_TOP_RENAME', 'Renombrar');
        define('_LANG_TOP_MOVE', 'Moure');
        define('_LANG_TOP_VIEW', "Veient l'arxiu");
        define('_LANG_TOP_LOGOUT', 'Sortir');
        define('_LANG_TOP_ERROR', 'Error');
        define('_LANG_UP_DESTINATION', 'Destí');
        define('_LANG_UP_BT', 'Pujar');
        define('_LANG_UP_MSG1', "El tamany de l'arxiu és excessiu ò bytes=0");
        define('_LANG_UP_MSG2', "L'arxiu %1 s'ha pujat correctament");
        define('_LANG_UP_MSG3', "Ha fallat la pujada de l'arxiu %1. Revisi el permís d'escriptura del directori.");
        define('_LANG_UP_MSG4', "Si us plau, escrigui un nom per a l'arxiu.");
        define('_LANG_DEL_MSG1', "**ATENCIÓ: aquest arxiu serà permanentement eliminat. Aquesta acció és irreversible.**");
        define('_LANG_DEL_MSG2', "Està segur de que vol eliminar %1 ?");
        define('_LANG_DEL_MSG3', "%1 ha estat eliminat.");
        define('_LANG_DEL_MSG4', "Ha hagut un problema intentant eliminar el directori %1. ");
        define('_LANG_DEL_MSG5', "Ha hagut un problema intentant eliminar l'arxiu %1. ");
        define('_LANG_EDIT_BT', "Desar els canvis");
        define('_LANG_EDIT_MSG1', "L'arxiu %1 fou editat i desat correctament.");
        define('_LANG_EDIT_MSG2', "Ha hagut un problema intentant desar els canvis.");
        define('_LANG_CR_MSG1', "Si us plau, escrigui un nom per a l'arxiu.");
        define('_LANG_CR_FILENAME', "Nom");
        define('_LANG_CR_DESTINATION', "Destí");
        define('_LANG_CR_FILE', "Archiu");
        define('_LANG_CR_DIRECTORY', "Directori");
        define('_LANG_CR_BT', "Crear");
        define('_LANG_CRT_MSG1', "El directori %1 s'ha creat correctament.");
        define('_LANG_CRT_MSG2', "El directori %1 no s'ha pogut crear. Demani al seu administrador que comprovi que aquest directori és escribible pel servidor.");
        define('_LANG_CRT_MSG3', "L'arxiu %1 s'ha creat correctament.");
        define('_LANG_CRT_MSG4', "L'arxiu %1 no s'ha pogut crear. Demani al seu administrador que comprovi que aquest directori és escribible pel servidor.");
        define('_LANG_REN_RENAMING', "Renombrant");
        define('_LANG_REN_NEW', "Nou nom");
        define('_LANG_REN_BT', "Renombrar");
        define('_LANG_REN_MSG1', "L'arxiu %1 ha estat renombrat com a %2");
        define('_LANG_REN_MSG2', "Ha hagut un problema intentant renombrar l'arxiu. Revisi els permisos.");
        define('_LANG_REN_MSG3', "Ha hagut un problema intentant renombrar el directori. Revisi els permisos.");
        define('_LANG_MOV_MSG1', "Moure %1 a");
        define('_LANG_MOV_BT', "Moure");
        define('_LANG_MOV_MSG2', "%1 ha estat mogut a %2");
        define('_LANG_MOV_MSG3', "Ha hagut un problema intentant moure %1.");
        define('_LANG_FR_MSG1', "**ERROR: va seleccionar veure %1 però està fora del seu directori arrel: %2 **");
        define('_LANG_LOGOUT_MSG1', "Ha tancat la seva sessió.");
        define('_LANG_LOGOUT_MSG2', "Premi aquí per tornar a accedir.");
        define('_LANG_ABOVE', "Cap amunt");
        define('_LANG_BELOW', "Cap avall");
        define('_LANG_SCH_TIT1', 'Opcions de cerca');
        define('_LANG_SCH_DIR', 'Directori a on cercar:');
        define('_LANG_SCH_QRY', 'Expressió a cercar:');
        define('_LANG_SCH_WHERE', 'A on cercar:');
        define('_LANG_SCH_W1', "als noms dels arxius i directoris");
        define('_LANG_SCH_W2', "al contingut dels arxius amb extensió: ");
        define('_LANG_SCH_BT', 'cercar ara');
        define('_LANG_SCH_TIT2', 'Resultats de cerca');
        define('_LANG_SCH_CASE', 'diferenciar majúscules/minúscules: ');
        define('_AVAILABLE_SKINS', 'Temes disponibles: ');
        break;
    case 'es':
        // ====== SPANISH
        define('_LANG_SITETITLE', "Administrador de archivos");
        define('_LANG_YES', 'Sí');
        define('_LANG_NO', 'No');
        define('_LANG_BT_HOME', 'Inicio');
        define('_LANG_BT_UPLOAD', 'Subir archivo');
        define('_LANG_BT_CREATE', 'Crear');
        define('_LANG_BT_SEARCH', 'Buscar');
        define('_LANG_BT_LOGOUT', 'Salir');
        define('_LANG_LOGIN_ERR', '**ERROR: Información de acceso incorrecta.**');
        define('_LANG_LOGIN_USERNAME', 'Usuario');
        define('_LANG_LOGIN_PASSW', 'Contraseña');
        define('_LANG_LOGIN_BT', 'Acceder');
        define('_LANG_HOME_BT_EDIT', 'Editar');
        define('_LANG_HOME_BT_OPEN', 'Abrir');
        define('_LANG_HOME_BT_RENAME', 'Renombrar');
        define('_LANG_HOME_BT_DELETE', 'Eliminar');
        define('_LANG_HOME_BT_MOVE', 'Mover');
        define('_LANG_HOME_BT_VIEW', 'Ver');
        define('_LANG_HOME_LAB_BROWS', 'Ruta');
        define('_LANG_HOME_LAB_NUM', 'Número de archivos');
        define('_LANG_HOME_LAB_FILE', 'Archivos y directorios');
        define('_LANG_HOME_LAB_PERM', 'Permisos');
        define('_LANG_HOME_LAB_SIZE', 'Tamaño');
        define('_LANG_HOME_MSG1', 'Directorio no legible/escribible.');
        define('_LANG_HOME_MSG2', 'Archivo no legible/escribible.');
        define('_LANG_TOP_HOME', 'Inicio');
        define('_LANG_TOP_LOGIN', 'Acceso');
        define('_LANG_TOP_UPLOAD', 'Subir archivo');
        define('_LANG_TOP_DELETE', 'Eliminar');
        define('_LANG_TOP_EDIT', 'Editar');
        define('_LANG_TOP_CREATE', 'Crear');
        define('_LANG_TOP_RENAME', 'Renombrar');
        define('_LANG_TOP_MOVE', 'Mover');
        define('_LANG_TOP_VIEW', 'Viendo el archivo');
        define('_LANG_TOP_LOGOUT', 'Salir');
        define('_LANG_TOP_ERROR', 'Error');
        define('_LANG_UP_DESTINATION', 'Destino');
        define('_LANG_UP_BT', 'Subir');
        define('_LANG_UP_MSG1', 'El tamaño del archivo es excesivo ó bytes=0');
        define('_LANG_UP_MSG2', "El archivo %1 se subió correctamente");
        define('_LANG_UP_MSG3', "Falló la subida del archivo %1.");
        define('_LANG_UP_MSG4', "Por favor, escriba un nombre para el archivo.");
        define('_LANG_DEL_MSG1', "**ATENCIÓN: este archivo va a ser permanentemente eliminado. Esta acción es irreversible.**");
        define('_LANG_DEL_MSG2', "¿está seguro de que desea eliminar %1 ?");
        define('_LANG_DEL_MSG3', "%1 ha sido eliminado.");
        define('_LANG_DEL_MSG4', "Hubo un problema intentando eliminar el directorio %1. ");
        define('_LANG_DEL_MSG5', "Hubo un problema intentando eliminar el archivo %1. ");
        define('_LANG_EDIT_BT', "Grabar cambios");
        define('_LANG_EDIT_MSG1', "El archivo %1 fue editado y grabado correctamente.");
        define('_LANG_EDIT_MSG2', "Hubo un problema intentando guardar los cambios.");
        define('_LANG_CR_MSG1', "Por favor, escriba un nombre para el archivo.");
        define('_LANG_CR_FILENAME', "Nombre");
        define('_LANG_CR_DESTINATION', "Destino");
        define('_LANG_CR_FILE', "Archivo");
        define('_LANG_CR_DIRECTORY', "Directorio");
        define('_LANG_CR_BT', "Crear");
        define('_LANG_CRT_MSG1', "Su directorio %1 se creó correctamente.");
        define('_LANG_CRT_MSG2', "El directorio %1 no se pudo crear. Pida a su administrador que compruebe que este directorio es escribible por el servidor.");
        define('_LANG_CRT_MSG3', "Su archivo %1 se creó correctamente.");
        define('_LANG_CRT_MSG4', "El archivo %1 no pudo ser creado. Pida a su administrador que compruebe que este directorio es escribible por el servidor.");
        define('_LANG_REN_RENAMING', "Renombrando");
        define('_LANG_REN_NEW', "Nuevo nombre");
        define('_LANG_REN_BT', "Renombrar");
        define('_LANG_REN_MSG1', "El archivo %1 ha sido renombrado como %2");
        define('_LANG_REN_MSG2', "Hubo un problema intentando renombrar el archivo. Revise los permisos.");
        define('_LANG_REN_MSG3', "Hubo un problema intentando renombrar el directorio. Revise los permisos.");
        define('_LANG_MOV_MSG1', "Mover %1 a");
        define('_LANG_MOV_BT', "Mover");
        define('_LANG_MOV_MSG2', "%1 ha sido movido a %2");
        define('_LANG_MOV_MSG3', "Hubo un problema intentando mover %1.");
        define('_LANG_FR_MSG1', "**ERROR: seleccionó ver %1 pero está fuera de su directorio raíz: %2 **");
        define('_LANG_LOGOUT_MSG1', "Ha cerrado su sesión.");
        define('_LANG_LOGOUT_MSG2', "Haga clic aquí para volver a acceder.");
        define('_LANG_ABOVE', "Hacia arriba");
        define('_LANG_BELOW', "Hacia abajo");
        define('_LANG_SCH_TIT1', 'Opciones de búsqueda');
        define('_LANG_SCH_DIR', 'Directori en dónde buscar:');
        define('_LANG_SCH_QRY', 'Expressión a buscar:');
        define('_LANG_SCH_WHERE', 'Dónde buscar:');
        define('_LANG_SCH_W1', "en los nombres de los archivos y directorios");
        define('_LANG_SCH_W2', "en el contenido de los archivos con extensión: ");
        define('_LANG_SCH_BT', 'buscar ahora');
        define('_LANG_SCH_TIT2', 'Resultados de búsqueda');
        define('_LANG_SCH_CASE', 'diferenciar mayúsculas/minúsculas: ');
        define('_AVAILABLE_SKINS', 'Temas disponibles: ');
        break;
    case 'en':
        // ====== ENGLISH
        define('_LANG_SITETITLE', "File manager");
        define('_LANG_YES', 'Yes');
        define('_LANG_NO', 'No');
        define('_LANG_BT_HOME', 'Home');
        define('_LANG_BT_UPLOAD', 'Upload file');
        define('_LANG_BT_CREATE', 'Create');
        define('_LANG_BT_SEARCH', 'Search');
        define('_LANG_BT_LOGOUT', 'Logout');
        define('_LANG_LOGIN_ERR', '**ERROR: Incorrect login information.**');
        define('_LANG_LOGIN_USERNAME', 'Username');
        define('_LANG_LOGIN_PASSW', 'Password');
        define('_LANG_LOGIN_BT', 'Login');
        define('_LANG_HOME_BT_EDIT', 'Edit');
        define('_LANG_HOME_BT_OPEN', 'Open');
        define('_LANG_HOME_BT_RENAME', 'Rename');
        define('_LANG_HOME_BT_DELETE', 'Delete');
        define('_LANG_HOME_BT_MOVE', 'Move');
        define('_LANG_HOME_BT_VIEW', 'View');
        define('_LANG_HOME_LAB_BROWS', 'Path');
        define('_LANG_HOME_LAB_NUM', 'Number of Files');
        define('_LANG_HOME_LAB_FILE', 'Files & directories');
        define('_LANG_HOME_LAB_PERM', 'Permissions');
        define('_LANG_HOME_LAB_SIZE', 'Size');
        define('_LANG_HOME_MSG1', 'Not readable/writable directory.');
        define('_LANG_HOME_MSG2', 'Not readable/writable file.');
        define('_LANG_TOP_HOME', 'Home');
        define('_LANG_TOP_LOGIN', 'Login');
        define('_LANG_TOP_UPLOAD', 'Upload file');
        define('_LANG_TOP_DELETE', 'Delete');
        define('_LANG_TOP_EDIT', 'Edit');
        define('_LANG_TOP_CREATE', 'Create');
        define('_LANG_TOP_RENAME', 'Rename');
        define('_LANG_TOP_MOVE', 'Move');
        define('_LANG_TOP_VIEW', 'Viewing file');
        define('_LANG_TOP_LOGOUT', 'Logout');
        define('_LANG_TOP_ERROR', 'Error');
        define('_LANG_UP_DESTINATION', 'Destination');
        define('_LANG_UP_BT', 'Upload');
        define('_LANG_UP_MSG1', 'File size too big or bytes=0');
        define('_LANG_UP_MSG2', "The file %1 uploaded successfully");
        define('_LANG_UP_MSG3', "File %1 failed to upload.");
        define('_LANG_UP_MSG4', "Please enter a filename.");
        define('_LANG_DEL_MSG1', "**WARNING: This will permanently delete this file. This action is irreversible.**");
        define('_LANG_DEL_MSG2', "Are you sure you want to delete %1 ?");
        define('_LANG_DEL_MSG3', "%1 has been deleted.");
        define('_LANG_DEL_MSG4', "There was a problem deleting the directory %1.");
        define('_LANG_DEL_MSG5', "There was a problem deleting the file %1.");
        define('_LANG_EDIT_BT', "Save");
        define('_LANG_EDIT_MSG1', "The file %1 was successfully edited.");
        define('_LANG_EDIT_MSG2', "There was a problem editing this file.");
        define('_LANG_CR_MSG1', "Please enter a filename.");
        define('_LANG_CR_FILENAME', "Filename");
        define('_LANG_CR_DESTINATION', "Destination");
        define('_LANG_CR_FILE', "File");
        define('_LANG_CR_DIRECTORY', "Directory");
        define('_LANG_CR_BT', "Create");
        define('_LANG_CRT_MSG1', "Your directory %1 was successfully created.");
        define('_LANG_CRT_MSG2', "The directory %1 could not be created. Check to make sure that this directory is writable.");
        define('_LANG_CRT_MSG3', "Your file %1 was successfully created.");
        define('_LANG_CRT_MSG4', "The file %1 could not be created. Check to make sure that this directory is writable.");
        define('_LANG_REN_RENAMING', "Renaming");
        define('_LANG_REN_NEW', "New name");
        define('_LANG_REN_BT', "Rename");
        define('_LANG_REN_MSG1', "The file %1 has been renamed to %2");
        define('_LANG_REN_MSG2', "There was a problem renaming this file. Check the permissions.");
        define('_LANG_REN_MSG3', "There was a problem renaming this directory. Check the permissions.");
        define('_LANG_MOV_MSG1', "Move %1 to");
        define('_LANG_MOV_BT', "Move");
        define('_LANG_MOV_MSG2', "%1 has been successfully moved to %2");
        define('_LANG_MOV_MSG3', "There was an error moving %1.");
        define('_LANG_FR_MSG1', "**ERROR: You selected to view %1 but is outside of your home directory: %2 **");
        define('_LANG_LOGOUT_MSG1', "Your are now logged out.");
        define('_LANG_LOGOUT_MSG2', "Click here to Log in again.");
        define('_LANG_ABOVE', "Up");
        define('_LANG_BELOW', "Down");
        define('_LANG_SCH_TIT1', 'Search options');
        define('_LANG_SCH_DIR', 'Directory where search in:');
        define('_LANG_SCH_QRY', 'String to search:');
        define('_LANG_SCH_WHERE', 'Where to search:');
        define('_LANG_SCH_W1', "at the files and directories names");
        define('_LANG_SCH_W2', "within the content of the files with extension: ");
        define('_LANG_SCH_BT', 'search now');
        define('_LANG_SCH_TIT2', 'Search results');
        define('_LANG_SCH_CASE', 'case-sensitive: ');
        define('_AVAILABLE_SKINS', 'Available themes: ');
        break;
}

/****************************************************************/
/* User identification                                          */
/*                                                              */
/* Looks for cookies. Yum.                                      */
/****************************************************************/

if (!isset($config['a_users'][$_COOKIE['user']]) || md5($config['a_users'][$_COOKIE['user']]['pass']) != $_COOKIE['pass']) {
    if (isset($config['a_users'][$_REQUEST['user']]) && $config['a_users'][$_REQUEST['user']]['pass'] == $_REQUEST['pass']) {
        setcookie('user', $_REQUEST['user'], time() + 60 * 60 * 24 * 1);
        setcookie('pass', md5($config['a_users'][$_REQUEST['user']]['pass']), time() + 60 * 60 * 24 * 1);
        $config['user'] = $_REQUEST['user'];
    } else {
        if (isset($config['a_users'][$_REQUEST['user']]) || $_REQUEST['pass']) $er = true;
        login($er);
    }
}

/* ************************************************************** */

$op     = (isset($_POST['op'])) ? $_POST['op'] : (($_GET['op']) ? $_GET['op'] : $_REQUEST['op']);
$folder = (isset($_POST['folder'])) ? $_POST['folder'] : (($_GET['folder']) ? $_GET['folder'] : $_REQUEST['folder']);
$user   = (isset($_COOKIE['user'])) ? $_COOKIE['user'] : $_POST['user'];
//while (preg_match('/\.\.\//',$folder)) $folder = preg_replace('/\.\.\//','/',$folder);
while (preg_match('/\/\//', $folder)) $folder = preg_replace('/\/\//', '/', $folder);

$filefolder = $config['a_users'][$user]['filefolder'];
if ($folder == '') {
    $folder = $filefolder;
} elseif ($filefolder != '') {
    if (!preg_match(_2preg($filefolder), $folder)) {
        $folder = $filefolder;
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
    global $lastsess, $login, $viewing, $user, $pass, $password, $debug, $issuper, $styles, $a_lang;
    echo "<html>\n<head>\n"
        . "\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />"
        . "<title>" . _LANG_SITETITLE . " :: $title</title>\n"
        . "<style>" . $styles . "</style>\n"
        . "</head>\n"
        . "<body>\n";

    echo "<div id='wrapper'>";
    echo "<div id='languages'>\n";
    foreach ($a_lang as $cod=> $tit)
        echo "<a href=\"" . $adminfile . "?op=home&lang=$cod\">$tit</a>\n";
    echo "</div>\n";
    echo "<h1>" . _LANG_SITETITLE . " :: $title</h1>\n";

    if ($showtop) {
        $var_folder = (isset($_GET['folder'])) ? '&folder=' . $_GET['folder'] : '';
        echo "<div id='top_menu'>\n"
            . "<a href='" . $adminfile . "?op=home'>" . _LANG_BT_HOME . "</a>\n"
            . "<a href='" . $adminfile . "?op=up$var_folder'>" . _LANG_BT_UPLOAD . "</a>\n"
            . "<a href='" . $adminfile . "?op=cr$var_folder'>" . _LANG_BT_CREATE . "</a>\n"
            . "<a href='" . $adminfile . "?op=search$var_folder'>" . _LANG_BT_SEARCH . "</a>\n"
            . "<a href='" . $adminfile . "?op=logout'>" . _LANG_BT_LOGOUT . "</a>\n"
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
    global $op;
    setcookie("user", "", time() - 60 * 60 * 24 * 1);
    setcookie("pass", "", time() - 60 * 60 * 24 * 1);
    maintop(_LANG_TOP_LOGIN, false);

    if ($er) {
        echo "<span class='error'>" . _LANG_LOGIN_ERR . "</span><br /><br />\n";
    }

    echo "<hr /><form action=\"" . $adminfile . "?op=" . $op . "\" method=\"post\">\n"
        . "<table id='tb_login'>\n"
        . "<tr><td class='a_r' style='width:40%;'>" . _LANG_LOGIN_USERNAME . ":</td>"
        . "	<td class='a_l'><input type='text' name='user' value=\"" . htmlspecialchars($user) . "\"></td></tr>\n"
        . "<tr><td class='a_r'>" . _LANG_LOGIN_PASSW . ": </td>\n"
        . "	<td class='a_l'><input type='password' name='pass' value=\"" . htmlspecialchars($pass) . "\"></td></tr>\n"
        . "<tr><td>&nbsp;</td><td class='a_l'><br /><input type='submit' name='submitButtonName' value=\"" . htmlspecialchars(_LANG_LOGIN_BT) . "\"></td></tr>\n"
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
    global $folder, $filefolder, $HTTP_HOST;
    maintop(_LANG_TOP_HOME);

    $content1 = "";
    $content2 = "";

    $count = "0";
    $style = opendir($folder);
    $a     = 1;
    $b     = 1;
    if ($folder) {
        if (preg_match(_2preg('/home/'), $folder)) {
            $folderx = preg_replace(_2preg($filefolder), "", $folder);
            $folderx = "http://" . $HTTP_HOST . "/" . $folderx;
        } else {
            $folderx = $folder;
        }
    }

    while ($stylesheet = readdir($style)) {
        if (strlen($stylesheet) > 40) {
            $sstylesheet = substr($stylesheet, 0, 40) . "...";
        } else {
            $sstylesheet = $stylesheet;
        }
        if ($stylesheet != "." && $stylesheet != "..") {
            $perm = substr(sprintf('%o', fileperms($folder . $stylesheet)), -3);
            if (is_dir($folder . $stylesheet)) {
                if (!is_readable($folder . $stylesheet) || !is_writable($folder . $stylesheet)) {
                    $content1[strtolower($stylesheet)] = "<td><a href=\"" . $adminfile . "?op=home&folder=" . $folder . $stylesheet . "/\" class='a_folder'>" . $sstylesheet . "</a></td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap></td>\n"
                        . "<td align=\"left\" colspan='5'>" . _LANG_HOME_MSG1 . "</td></tr>\n"
                        . "<tr height=\"2\"><td height=\"2\" colspan=\"8\">\n";
                } else {
                    $n_size                            = filesize($folder . $stylesheet); //_get_dir_size($folder.$stylesheet);
                    $content1[strtolower($stylesheet)] = "<td><a href=\"" . $adminfile . "?op=home&folder=" . $folder . $stylesheet . "/\" class='a_folder'>" . $sstylesheet . "</a></td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap>" . _size_format($n_size) . "</td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=home&folder=" . $folder . $stylesheet . "/\">" . _LANG_HOME_BT_OPEN . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=ren&file=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_RENAME . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=del&dename=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_DELETE . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=mov&file=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_MOVE . "</a></td>\n"
                        . "<td align=\"center\"></td></tr><tr height=\"2\"><td height=\"2\" colspan=\"8\">\n";
                }
                $a++;

            } elseif (!is_dir($folder . $stylesheet)) {
                if (!is_readable($folder . $stylesheet) || !is_writable($folder . $stylesheet)) {
                    $content2[strtolower($stylesheet)] = "<td>" . $sstylesheet . "</td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap></td>\n"
                        . "<td align=\"left\" colspan='5'>" . _LANG_HOME_MSG2 . "</td></tr>\n"
                        . "<tr height=\"2\"><td height=\"2\" colspan=\"8\">\n";
                } else {
                    $n_size                            = filesize($folder . $stylesheet);
                    $content2[strtolower($stylesheet)] = "<td>" . $sstylesheet . "</td>\n"
                        . "<td align=\"center\">" . $perm . "</td>\n"
                        . "<td align=\"right\" style='letter-spacing:nowrap;' nowrap>" . _size_format($n_size) . "</td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=edit&fename=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_EDIT . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=ren&file=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_RENAME . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=del&dename=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_DELETE . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $adminfile . "?op=mov&file=" . $stylesheet . "&folder=$folder\">" . _LANG_HOME_BT_MOVE . "</a></td>\n"
                        . "<td align=\"center\"><a href=\"" . $folder . $stylesheet . "\" target='_blank'>" . _LANG_HOME_BT_VIEW . "</a></td></tr>\n"
                        . "<tr height=\"2\"><td height=\"2\" colspan=\"8\">\n";
                }
                $b++;

            }
            $count++;
        }
    }
    closedir($style);
    if (is_array($content1))
        ksort($content1);
    if (is_array($content2))
        ksort($content2);

    echo "<div class='breadcrumb'>\n"
        . _LANG_HOME_LAB_BROWS . ": <span>" . breadcrumb($folder) . "</span>\n"
        . "<br />" . _LANG_HOME_LAB_NUM . ": <em>" . $count . "</em>\n"
        . "</div>";
    echo "<table id='tb_list' cellspacing='0'><thead><tr>"
        . "<th>" . _LANG_HOME_LAB_FILE . "\n"
        . "<th style='text-align:center;'>" . _LANG_HOME_LAB_PERM . "</th>\n"
        . "<th style='width:65px;text-align:right;'>" . _LANG_HOME_LAB_SIZE . "</th>\n"
        . "<th align=\"center\" style='width:44px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:58px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:57px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:40px;'>&nbsp;</th>\n"
        . "<th align=\"center\" style='width:44px;'>&nbsp;</th>\n"
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
            $breadcrumb .= " <a href=\"" . $adminfile . "?op=home&folder=" . $subpath . "/\">" . $subfolder . "</a>";
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
    global $folder, $content, $filefolder;
    maintop(_LANG_TOP_UPLOAD);
    $perm = substr(sprintf('%o', fileperms($filefolder)), -3);
    echo "<FORM ENCTYPE=\"multipart/form-data\" ACTION=\"" . $adminfile . "?op=upload\" METHOD=\"POST\">\n"
        . "<font face=\"tahoma\" size=\"2\"><b>File:</b></font><br /><input type=\"File\" name=\"upfile\" size=\"20\" class=\"text\">\n"

        . "<br /><br />" . _LANG_UP_DESTINATION . ":<br /><select name='ndir' style='width:400px;'>\n"
        . "<option value=\"" . $filefolder . "\">[$perm] " . $filefolder . "</option>";
    listdir($filefolder);
    echo "</select><br /><br />"
        . "<input type=\"submit\" value=\"" . _LANG_UP_BT . "\" >\n"
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

    global $folder;
    if (!$upfile) {
        error(_LANG_UP_MSG1);
    } elseif ($upfile['name']) {
        if (@copy($upfile['tmp_name'], $ndir . $upfile['name'])) {
            maintop(_LANG_TOP_UPLOAD);
            echo "<div class='breadcrumb'>" . str_replace('%1', "[ <span style='color:#c00;'>" . breadcrumb($ndir) . " / " . $upfile['name'] . "</span> ]", _LANG_UP_MSG2) . "</div>\n";
            mainbottom();
        } else {
            printerror(str_replace('%1', "[ <span style='color:#c00;'>" . $upfile['name'] . "</span> ]", _LANG_UP_MSG3));
        }
    } else {
        printerror(_LANG_UP_MSG4);
    }
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
    global $folder;
    if (!$dename == "") {
        maintop(_LANG_TOP_DELETE);
        echo ""
            . "<p class='error'>\n"
            . _LANG_DEL_MSG1 . "</p>\n"
            . "<p>" . str_replace('%1', "[ <span style='color:#c00;'>" . $folder . $dename . "</span> ]", _LANG_DEL_MSG2) . "</p>\n"
            . "<a href='" . $adminfile . "?op=delete&dename=" . $dename . "&folder=$folder' class='button'>" . _LANG_YES . "</a>\n"
            . "<a href='" . $adminfile . "?op=home&folder=$folder' class='button'>" . _LANG_NO . "</a>\n";
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
    global $filefolder, $adminfile, $config;
    maintop(_LANG_BT_SEARCH);
    // == load data
    if (!isset($_REQUEST['folder']) || trim($_REQUEST['folder']) == '')
        $folder = $filefolder;
    else
        $folder = trim($_REQUEST['folder']);
    $query      = (isset($_REQUEST['query'])) ? trim(stripslashes($_REQUEST['query'])) : '';
    $where      = (isset($_REQUEST['where'])) ? trim(stripslashes($_REQUEST['where'])) : '';
    $extensions = (isset($_REQUEST['extensions'])) ? stripslashes($_REQUEST['extensions']) : 'php,html,js,css,txt';
    $extensions = trim(str_replace(array('.', ' ', ';', '|'), ',', $extensions));
    if ($extensions == '') $extensions = '*';
    $sensitive = (isset($_REQUEST['sensitive'])) ? trim(stripslashes($_REQUEST['sensitive'])) : '';
    // == search options
    echo "<h3>" . _LANG_SCH_TIT1 . "</h3>\n";
    echo "<div class='list_dir'>\n";
    echo "<form action='" . $adminfile . "?op=search&folder=" . $folder . "' method='post'>\n";
    echo "<p>" . _LANG_SCH_DIR . " <span style='color:#c00;'>" . $folder . "</span></p>\n";
    echo "<div>" . _LANG_SCH_QRY . " <input type='text' name='query' id='query' value=\"" . htmlspecialchars($query) . "\"  style='width:300px;' /> &nbsp; "
        . _LANG_SCH_CASE . " <input type='checkbox' name='sensitive' " . (($sensitive == 'on') ? "checked='checked'" : "") . " /></div>";
    echo "<div>" . _LANG_SCH_WHERE
        . " <p style='margin-left:50px;'><input type='radio' name='where' id='where_1' value='filename' " . (($where == 'filename' or $where == '') ? "checked='true'" : '') . "  />" . _LANG_SCH_W1 . "</p>\n"
        . " <p style='margin-left:50px;'><input type='radio' name='where' id='where_2' value='filecontent' " . (($where == 'filecontent') ? "checked='true'" : '') . "  />" . _LANG_SCH_W2
        . " <input type='text' name='extensions' id='extensions' value=\"" . htmlspecialchars($extensions) . "\"  style='width:200px;' /></p>\n"
        . "</div>";
    echo "<div><br /><input type='submit' value=\"" . htmlspecialchars(_LANG_SCH_BT) . "\" onclick=\"if (document.getElementById('query').value=='') return false;\" /></div>";
    echo "</form></div>\n";
    // == search results
    if ($query != '') {
        $a_ext       = ($extensions != '*') ? explode(',', $extensions) : $extensions;
        $b_sensitive = ($sensitive == 'on') ? '' : 'i';
        if (substr($folder, -1) == '/') $folder = substr($folder, 0, -1);
        $output  = _scanDirectory(_2preg($query, $b_sensitive, $where), $a_ext, $where, opendir($folder), $folder, basename($folder), '');
        $n_found = count($config['SEARCH']['a_found']);
        echo "<h3>" . _LANG_SCH_TIT2 . " &nbsp; &rarr; <span style='color:#c00;'>" . $n_found . "</span> / " . $config['SEARCH']['scanned_files'] . "</h3>\n";
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
    global $folder;
    if (!$dename == "") {
        maintop(_LANG_TOP_DELETE);
        echo "<p>";
        if (is_dir($folder . $dename)) {
            if (_rmdir($folder . $dename))
                $msg = _LANG_DEL_MSG3;
            else
                $msg = _LANG_DEL_MSG4;
        } else {
            if (unlink($folder . $dename))
                $msg = _LANG_DEL_MSG3;
            else
                $msg = _LANG_DEL_MSG5;
        }
        echo "<div class='breadcrumb'>" . str_replace('%1', "[ <span style='color:#c00;'>" . breadcrumb($folder) . " / " . $dename . "</span> ]", $msg) . "</div>";
        mainbottom();
    } else {
        home();
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
    global $folder;
    if (!$fename == "") {
        maintop(_LANG_TOP_EDIT);

        $handle = @fopen($folder . $fename, "r");
        if (!$handle) {
            echo "<p style='color:#c00;'>" . _LANG_HOME_MSG2 . "</p>";
            return;
        }

        echo $folder . $fename;

        echo "<form action=\"" . $adminfile . "?op=save\" method='post'>\n"
            . "<textarea rows='40' name='ncontent' style='width:100%;'>\n";

        $contents = "";

        while ($x < 1) {
            $data = @fread($handle, filesize($folder . $fename));
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
            . "<input type=\"submit\" value=\"" . _LANG_EDIT_BT . "\" >\n"
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
    global $folder;
    if (!$fename == "") {
        maintop(_LANG_TOP_EDIT);
        $loc = $folder . $fename;
        if (!@$fp = fopen($loc, "w")) {
            echo _LANG_EDIT_MSG2 . "\n";
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
            echo str_replace('%1', "[ <span style='color:#c00;'><a href=\"" . $foler . $fename . "\" target='_blank'>" . $folder . $fename . "</a></span> ]", _LANG_EDIT_MSG1);
            $fp = null;
        } else {
            echo _LANG_EDIT_MSG2 . "\n";
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
    global $folder, $content, $filefolder;
    maintop(_LANG_TOP_CREATE);
    if (!$content == "") {
        echo "<br /><br />" . _LANG_CR_MSG1 . ".\n";
    }
    $perm = substr(sprintf('%o', fileperms($filefolder)), -3);
    echo "<form action=\"" . $adminfile . "?op=create\" method=\"post\">\n";
    echo "<input type=\"radio\" size=\"20\" name=\"isfolder\" value=\"0\" checked> " . _LANG_CR_FILE . "<br />\n"
        . "<input type=\"radio\" size=\"20\" name=\"isfolder\" value=\"1\"> " . _LANG_CR_DIRECTORY . "<br /><br />\n";
    echo _LANG_CR_FILENAME . ": <br /><input type=\"text\" size=\"20\" name=\"nfname\" class=\"text\"><br /><br />\n"
        . _LANG_CR_DESTINATION . ":<br /><select name='ndir' style='width:400px;'>\n"
        . "<option value=\"" . $filefolder . "\">[$perm] " . $filefolder . "</option>";
    listdir($filefolder);
    echo "</select><br /><br />\n"
        . "<input type=\"hidden\" name=\"folder\" value=\"$folder\">\n"
        . "<input type=\"submit\" value=\"" . _LANG_CR_BT . "\" >\n"
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
    global $folder;
    if (!$nfname == "") {
        maintop(_LANG_TOP_CREATE);
        if (substr($ndir, -1) != '/') $ndir .= '/';
        if ($isfolder == 1) {
            if (@mkdir($ndir . $nfname, 0777)) {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir . $nfname) . "</span> ]", _LANG_CRT_MSG1) . "\n";
            } else {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir . $nfname) . "</span> ]", _LANG_CRT_MSG2) . "\n";
            }
        } else {
            if (@fopen($ndir . $nfname, "w")) {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir) . " / " . $nfname . "</span> ]", _LANG_CRT_MSG3) . "\n";
            } else {
                echo str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($ndir) . " / " . $nfname . "</span> ]", _LANG_CRT_MSG4) . "\n";
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
    global $folder;
    if (!$file == "") {
        maintop(_LANG_TOP_RENAME);
        echo "<form action=\"" . $adminfile . "?op=rename\" method=\"post\">\n"
            . "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n"
            . _LANG_REN_RENAMING . " [ <span style='color:#c00;'>" . $folder . $file . "</span> ]";

        echo "</table><br />\n"
            . "<input type=\"hidden\" name=\"rename\" value=\"" . $file . "\">\n"
            . "<input type=\"hidden\" name=\"folder\" value=\"" . $folder . "\">\n"
            . _LANG_REN_NEW . ":<br /><input class=\"text\" type=\"text\" size=\"20\" name=\"nrename\" value=\"" . $file . "\">\n"
            . "<input type=\"Submit\" value=\"" . _LANG_REN_BT . "\" >\n";
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
    global $folder;
    if (!$rename == "") {
        maintop(_LANG_TOP_RENAME);
        $loc1 = "$folder" . $rename;
        $loc2 = "$folder" . $nrename;

        if (@rename($loc1, $loc2)) {
            $txt = str_replace('%1', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $rename . "</div>", _LANG_REN_MSG1);
            $txt = str_replace('%2', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $nrename . "</div>", $txt);
            echo $txt;
        } else {
            if (is_dir($loc1))
                echo _LANG_REN_MSG3 . "\n";
            else
                echo _LANG_REN_MSG2 . "\n";
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
    global $filefolder;
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
            if (strpos($realpath, $filefolder) !== false) // $filefolder is the folder which the user has permission to access
                $dir_list[] = "<a href='#' onclick=\"" . $onclick . "('" . $realpath . "')\">" . $realpath . "</a>\n";
        }
    }
    if (count($dir_list) > 0) {
        krsort($dir_list);
        echo "<p>" . _LANG_ABOVE . ":</p>\n" . implode('', $dir_list);
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
        echo "<p>" . _LANG_BELOW . ":</p>\n" . implode('', $dir_list);
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
    global $folder, $content, $filefolder;
    if (!$file == "") {
        maintop(_LANG_TOP_MOVE);
        echo "<form action=\"" . $adminfile . "?op=move\" method=\"post\">\n"
            . "<div>\n"
            . str_replace('%1', "[ <span class='breadcrumb'>" . breadcrumb($folder) . " / " . $file . "</span> ]", _LANG_MOV_MSG1) . ": \n"
            . " <span id='span_target' style='color:#c00;'></span>\n"
            . "<input type='hidden' id='ndir' name='ndir' style='width:400px;'>\n"
            . "<div class='list_dir'>\n";
        a_listdir($folder, 'js_change_dir');
        echo "</div>\n"
            . "<script>function js_change_dir(new_dir){document.getElementById('ndir').value=new_dir;document.getElementById('span_target').innerHTML=new_dir;}</script>\n"
            . "</div><br /><input type='hidden' name='file' value=\"" . $file . "\">\n"
            . "<input type='hidden' name='folder' value=\"" . $folder . "\" />\n"
            . "<input type='submit' value=\"" . htmlspecialchars(_LANG_MOV_BT) . "\"  />\n"
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
    global $folder;
    if (!$file == "") {
        maintop(_LANG_TOP_MOVE);
        if (@rename($folder . $file, $ndir . $file)) {
            $txt = str_replace('%1', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($folder) . " / " . $file . "</div>", _LANG_MOV_MSG2);
            $txt = str_replace('%2', "<div class='breadcrumb' style='margin:30px;'>" . breadcrumb($ndir) . " / " . $file . "</div>", $txt);
            echo $txt;
        } else {
            echo "<p>" . str_replace('%1', " <span class='breadcrumb'>" . breadcrumb($folder) . " / " . $file . "</span> ", _LANG_MOV_MSG3) . "</p>";
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
    global $login;
    setcookie("user", "", time() - 60 * 60 * 24 * 1);
    setcookie("pass", "", time() - 60 * 60 * 24 * 1);

    maintop(_LANG_TOP_LOGOUT, false);
    echo _LANG_LOGOUT_MSG1
        . "<hr /><br /><br />"
        . "<a href=" . $adminfile . "?op=home>" . _LANG_LOGOUT_MSG2 . "</a>";
    mainbottom();
}

/****************************************************************/
/* function mainbottom()                                        */
/*                                                              */
/* Controls the bottom copyright.                               */
/****************************************************************/
function mainbottom()
{
    global $a_skin, $skin, $config;
    $a_skin_html = array();
    foreach ($a_skin as $t) {
        if ($skin != $t)
            $a_skin_html[] = "<a href=\"" . $adminfile . "?op=home&skin=" . urlencode($t) . "\">" . $t . "</a>";
        else
            $a_skin_html[] = $t;
    }
    echo "<div id='main_bottom'>"
        . "<div style='text-align:right;'>version " . $config['version'] . " | GPL 2009 - " . date('Y') . " <a href='http://github.com/srrFileManager' target='_blank'>srrFileManager at GitHub</a>, <a href='http://www.imasdeweb.com' target='_blank'>IMASDEWEB.COM</a></div>\n"
        . "<div style='text-align:right;'>" . _AVAILABLE_SKINS . " " . implode(' | ', $a_skin_html) . "</div>\n"
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
    maintop(_LANG_TOP_ERROR);
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
        }
    }
    closedir($dh);
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
                $dirname_here = '';
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

?>
