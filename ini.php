<?php
    //Display all errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(-1);
    ini_set("log_errors", 1);
    ini_set("error_log", $_SERVER['DOCUMENT_ROOT']."/tmp/php-error.log");
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1 );

    //Composer autoloader
    require $_SERVER['DOCUMENT_ROOT'].'\vendor\autoload.php';

    use Dotenv;

    //initialize the .env where local config settings are kept
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    //create pdo instance
    $pdoDB  = new PDO(
        $_ENV['DSN'],
        $_ENV['DBUSER'],
        $_ENV['DBPASS']
    );

    /** set folder for twig templates */
    $loader = new \Twig\Loader\FilesystemLoader($_SERVER['DOCUMENT_ROOT'].'\templates');
    
    /** Instantiate $twig */
    $twig = new \Twig\Environment($loader);

    session_start();