<?php

define('__CBO_ROOT_PATH__', $_SERVER['DOCUMENT_ROOT']);
define('__CBO_LOGSQL_PATH__', __CBO_ROOT_PATH__ . '/logsql/');
require_once 'config.params.php';
require_once 'cnx.php';
require_once 'fonctions.php';
require_once  dirname( __FILE__ ).'/../../class/Outils.class.php';