<?php
    require $_SERVER['DOCUMENT_ROOT'].'\ini.php';

    use BetterHalf\Streamer;
    use GuzzleHttp\Client;
    use Firebase\JWT\JWT;
    use Firebase\JWT\JWK;

    //new http client
    $client = new Client();

    /**************************************/
    /* This bit is to get login twitch ID */
    /* and update the logged in user in   */
    /* the db                             */
    /**************************************/

    //set options for request to get authentication
    $method     = 'POST';
    $url        = 'https://id.twitch.tv/oauth2/token';
    $payload    = ['json' =>[
                        'client_id'     => $_ENV['CLIENT_ID'],
                        'client_secret' => $_ENV['CLIENT_SECRET'],
                        'code'          => $_GET['code'],                             
                        'grant_type'    => 'authorization_code',
                        'redirect_uri'  => $_ENV['REDIRECT_URI']
                        ]
                    ];

    //get access(oauth) token - for making requests to the api, and id_token - for identifying user(sent as JWT) from twitch
    $response = $client->request($method, $url, $payload);

    //for 200 ok
    if($response->getStatusCode() == 200){
        $resBody = json_decode($response->getBody(), TRUE);
     
        // raw jwt that needs decoded and taken apart
        $id_token = $resBody['id_token'];

        //access token
        $access_token = $resBody['access_token'];

        //refresh token
        $refresh_token = $resBody['refresh_token'];

        //get JWK public key from twitch to use to open and validate jwt
        $jwks = json_decode($client->get('https://id.twitch.tv/oauth2/keys')->getBody(), TRUE);

        //set an offset in jwt validator/decoder to account for any variance in clocks between client and server
        JWT::$leeway = 60;

        //decode the id_token that came in with the access and refresh tokens into "claims" object 
        //with the fields 'aud', 'exp', 'iat',' 'iss', 'sub', 'azp','preferred_username' , and 'picture'. 'sub' field is the twitch user id
        $claims = JWT::decode($id_token, JWK::parseKeySet($jwks));
        
        //instantiate streamer object
        $streamer = new Streamer($pdoDB, $claims->sub);

        //check if user attempting to log in is in that list
        if($streamer->checkRegistration()) {

            //set twitch display name and profile pic url
            $streamer->setAccessToken($access_token)
                    ->setRefreshToken($refresh_token)
                    ->setName($claims->preferred_username)
                    ->setProfilePic($claims->picture);

            //update streamer data in db
            $streamer->updateOrPutTwitchUser();

            //pass some data to be used in the application
            $_SESSION['tId']    = $streamer->getId();
            $_SESSION['letsgo'] = TRUE;
            
            header("location: /");
        }else{
            //TODO: buld a registration or landing page.
            header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
            echo "<h1>Not Authorized yo</h1>";
            exit();
        }   
    }else{
        //Set 401 status and redirect to a landing page
        header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
        echo "<h1>Not Authorized</h1>";
        exit();
    }