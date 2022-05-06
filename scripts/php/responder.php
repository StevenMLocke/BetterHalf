<?PHP
require $_SERVER['DOCUMENT_ROOT'].'\ini.php';

use BetterHalf\Racer;
use BetterHalf\TwitchApiClient;
use BetterHalf\Streamer;
use BetterHalf\StreamEvent;

if(!isset($_SESSION['letsgo'])){
        exit();
    }

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!isset($_SESSION['streamEventName'])){
        echo json_encode(["result" => "no event has started yet. Please stand by."]);
    }else{
        $twitchApiClient    = new TwitchApiClient($pdoDB, $_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']);
        $streamer           = new Streamer($pdoDB, $_SESSION['tId']);
        $streamEvent        = new StreamEvent($streamer, $_SESSION['streamEventName']);
        $racer              = new Racer($pdoDB, $_POST['racer']);
        $lastRace           = $streamEvent->getLastRaceForRacer($racer);
        $qualified          = FALSE;
        $qualifyingCnt      = 0;
        $RaceNum            = 0;

        if(empty($lastRace)) {
            echo json_encode(['result' => ' you have not raced in this event yet.']);
        }else{
            $raceNum = $lastRace['race_number'];
            $qualifyingCnt = $lastRace['qualifying_cnt'];
            
            if($raceNum < 3 AND $raceNum == $qualifyingCnt) {
                $qualified = TRUE;
            }else{
                if($qualifyingCnt >= 3) {
                    $qualified = TRUE;
                }
            }
           
            $q = $qualified ? '' : 'not ';
            echo json_encode(["result" => " your last race was race number ".$raceNum.". You are ".$q."qualified with ".$qualifyingCnt." consecutive Better Half Finishes."] );
        }
    }
}