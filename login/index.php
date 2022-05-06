<?php
    //Composer autoloader
    require $_SERVER['DOCUMENT_ROOT'].'\vendor\autoload.php';

    use Dotenv;

    //initialize the .env where local config settings are kept
    $dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    header('location:https://id.twitch.tv/oauth2/authorize?client_id='.$_ENV['CLIENT_ID'].'&redirect_uri='.$_ENV['REDIRECT_URI'].'&scope=openid chat:read chat:edit&response_type=code&claims={ "id_token": { "picture": null, "preferred_username": null } }&state=thisIsTheStateOfThings');