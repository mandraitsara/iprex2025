<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Chargeur de classes
------------------------------------------------------*/
function chargerClass($class) {

	if (strpos($class, "PhpSpreadsheet") || strpos($class, "Smarty")) { return false; }

	$path = str_replace('/scripts/php', '../../', dirname($_SERVER['PHP_SELF']));
	$path = str_replace('/scripts/ajax', '../../', dirname($_SERVER['PHP_SELF']));

	if ($_SESSION['sub_domain'] != '') {
		$path = str_replace($_SESSION['sub_domain'], '', $path);
	}

	if ($class == 'PHPMailer' || $class == 'SMTP' || $class == 'Smarty_Autoloader' || $class == 'AppAPI') {
		return false;
	} else {
		$_SESSION['classcalls'][] = $class;

		//$path = __CBO_ROOT_PATH__;
        $path = '/home/brutos/Documents/Brutos2024/iprex/iprex';
        //var_dump($path);
		require_once($path.'/class/'.$class.'.class.php');
	}
}

spl_autoload_register('chargerClass');