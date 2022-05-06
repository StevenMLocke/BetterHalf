<?php
    require $_SERVER['DOCUMENT_ROOT'].'\ini.php';

    if(!isset($_SESSION['letsgo'])){
        header("location: /login");
        exit();
    }

    use BetterHalf\Racer;
    use BetterHalf\Standing;
    use BetterHalf\Streamer;
    use BetterHalf\StreamEvent;
    use BetterHalf\TwitchApiClient;
    use BetterHalf\TwitchChatClient;

    //instantiate streamer object
    $streamer = new Streamer($pdoDB, $_SESSION['tId']);

    //instantiate twitchApiClient
    $twitchApiClient = new TwitchApiClient($pdoDB, $_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);

    //get fresh token
    $streamer->refreshToken($twitchApiClient); //this could go into the constructor

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        //instantiate stream event object
        $streamEvent                = new StreamEvent($streamer, $_POST['stream_event_name']);

        //set event name in session for chat responses
        $_SESSION['streamEventName']= $_POST['stream_event_name'];
        
        //break participant list(text) into individual pieces at newlines for array of racer names
        $racerArray                 = explode(PHP_EOL, $_POST['racers']);

        //establish betterhalf cutoff taking number of participants over 2.
        $betterHalfCutoff           = count($racerArray) / 2; //TODO: potentially make this variable

        //get racers who have raced in this event at least one race before
        $eventRoster                = $streamEvent->populateRoster() //this could go into the constructor
                                                ->getRoster();

        //find stream event registered racers that did not race in this race
        $rosterRacersThatDidNotRace = array_diff($eventRoster, $racerArray);

        //combine that list with the racers who have raced this race to get all participants.
        $eventRacers                = array_merge($racerArray, $rosterRacersThatDidNotRace);

        //attempt to get racers' infos from Twitch by racer name for racers that raced in this race. Non racing roster racers info is in DB.
        //(getting racer info from twitch in bulk saves api calls)
        $racerInfoArray             = $twitchApiClient->getRacersInfo($racerArray);

        //array of racer objects
        $racersObjs                 = [];

        //register any racer who isn't registered already
        foreach($eventRacers as $racerName) {
            //instantiate racer
            $racer = new Racer($pdoDB, $racerName);

            //if the racer does not exist in the db put it in there
            if (!$racer->getId() > 0) {
                foreach($racerInfoArray as $racerInfo) {
                    if($racerName == $racerInfo['display_name']) {
                        $racer->setId($racerInfo['id'])
                                ->setProfilePic($racerInfo['profile_image_url'])
                                ->updateOrPutTwitchUser();
                        break;
                    }
                }
            }

            //put the racer into an array
            $racersObjs[] = $racer;
        }

        //establish number of remaining qualified racers
        $stillAlive = 0;

        // do the work here. Follow the Better Half Logic for qualification, set the record into a standing obj, then persist the record in the db
        foreach($racersObjs as $racer) {
            
            //instantiate standing
            $standing   = new Standing($streamEvent, $racer);
            $standing->setTimestamp(time()); //this could go into the constructor

            //set default qualfied to 0
            $standing->setQualified(0); //this could go into the constructor
            
            //set default qualifying count to 0
            $standing->setQualifyingCount(0); //this could go into the constructor

            //set default finish position to 0
            $standing->setFinishPosition(0); //this could go into the constructor
            //if the eventRacer racer raced   
            if(in_array($racer->getName(), $racerArray)) {
                
                //establish racer's finish position
                $standing->setFinishPosition(array_search($racer->getName(), $racerArray) + 1);
                
                //figure out if the racer is in the better half
                if($standing->getFinishPosition() <= $betterHalfCutoff) {              
                    //yes
                    //is it race num 1?
                    if($streamEvent->raceNumber == 1) {
                        
                        //yes
                        //set qCnt to 1 and qualified to 1
                        $standing->setQualifyingCount(1);
                        $standing->setQualified(1);
                    }else{
                                                
                        //no
                        //get previous race standing record (raceNum, qCnt);
                        $lastRaceforRacer = $streamEvent->getLastRaceForRacer($racer);
                        
                        //consecutive races
                        if(!empty($lastRaceforRacer) && $lastRaceforRacer['race_number'] = $streamEvent->raceNumber - 1) {
                            
                            //yes
                            //set qCnt to +1
                            $standing->setQualifyingCount($lastRaceforRacer['qualifying_cnt'] + 1);
                                
                                //is raceNum 2 && qCnt 2?
                                if($streamEvent->raceNumber == 2 and $standing->getQualifyingCount() == 2) {
                                    
                                    //yes
                                    //set qualified to 1
                                    $standing->setQualified(1);
                                }else{
                                    
                                    //no
                                    //is qCnt >= 3
                                    if($standing->getQualifyingCount() >= 3){
                                        //yes
                                        //set qualified to 1
                                        $standing->setQualified(1);
                                    }
                                }
                        }else{
                            //no
                            //set qCnt to 1 and qualfied to 0
                            $standing->setQualifyingCount(1);
                        }
                    }
                }                  
            }

            //put record for racer for this race into standings table
            $standing->putStanding();

            //if the racer is qualified inc the $stillAlive counter
            if($standing->getQualified()) {
                $stillAlive++;
            }
        }            
        
        //get results for last race to feed to twig
        $res = $streamEvent->getRaceResults();

        //There is a winner if $stillAlive  = 1
        if($stillAlive == 1) {
            //init winner
            $winner = "";

            //sift through race results to find out who it is
            foreach($res as $standing) {
                if ($standing['qualified'] == 1) {
                    $winner = $standing['name'];

                    // send message to channel
                    $tcc = new TwitchChatClient($streamer->getName(), $streamer->getAccessToken());

                    $tcc->connect();

                    $tcc->sendChat("Your ".$streamEvent->getName()." winner is @".$winner."!!");
                    $tcc->sendChat("Congratualtions, @".$winner."!!");

                    $tcc->close();

                    //kill the loop
                    break;
                }
            }
        }

        //render into a rudimentary table.
        //'channel' and 'key' must be sent to any template that uses _base.html.twig to make the channel chat responder work
        echo $twig->render('\resultform\_resultform.html.twig', [
            'tableData' => $res,
            'channel'   => strtolower($streamer->getName()),
            'key'       => $streamer->getAccessToken()
        ]);

    }else{
        //render the initial submission page.
        //'channel' and 'key' must be sent to any template that uses _base.html.twig to make the channel chat responder work
        echo $twig->render('\submitform\_form.html.twig', [
                                                            'profile_pic'   => $streamer->getProfilePic(),
                                                            'username'      => $streamer->getName(),
                                                            'events'        => $streamer->getStreamEvents(),
                                                            'channel'       => strtolower($streamer->getName()),
                                                            'key'           => $streamer->getAccessToken()
                                                        ]);
    }