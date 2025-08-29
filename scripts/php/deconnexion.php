<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Script de déconnexion propre
------------------------------------------------------*/
//ini_set('display_errors',1);
$skipAuth = true;
include_once 'config.php';

// On vide la mémoire de session
$_SESSION = array();

// On détruit la session
session_destroy();

// On détruit toutes les données
unset($_SESSION);

// On détruit le cookie de connexion étendue
setcookie('IPREXCNXUSERID', 0, time()-10,'/'); //écrasement du cookie par un cookie vide
unset($_COOKIE['IPREXCNXUSERID']); //destruction de la valeur en local ce qui évite de l'employer plus tard


// on redirige sur l'index
header('Location: ' . __CBO_ROOT_URL__.'index.php');
exit();