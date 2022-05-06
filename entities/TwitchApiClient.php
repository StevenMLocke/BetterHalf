<?PHP
namespace BetterHalf;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use PDO;

class TwitchApiClient{
    private string $client_id;
    private string $client_secret;
    private const URL = 'https://api.twitch.tv/helix';
    private string $access_token;
    private Client $client;
    private PDO $db;

    /**
     * __construct
     *
     * @param PDO $pdoDb
     */
    public function __construct(PDO $pdoDb, string $clientId, string $clientSecret) {
        $this->client_id        = $clientId;
        $this->client_secret    = $clientSecret;
        $this->client           = new Client();
        $this->db               = $pdoDb;
        $this->fetchToken();
    }

    /**
     * getAccessToken
     *
     * @return TwitchApiClient
     */
    private function getAccessToken(): TwitchApiClient {
        $response = $this->client->post('https://id.twitch.tv/oauth2/token', ['query' => [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'    => 'client_credentials',
            'scope'         => 'chat:read chat:edit'
        ]]);

        if ($response->getStatusCode() == 200) {
            $this->access_token = json_decode($response->getBody(), TRUE)['access_token'];
        }

        return $this;
    }


   /**
    * fetchToken
    *
    * @return TwitchApiClient
    */
    private function fetchToken(): TwitchApiClient {
        $sql = "SELECT `token` 
                FROM betterhalf.`authentication` 
                WHERE `owner` = 'App'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        if ($result) {
            $this->access_token = $result;
        }else{
            $this->getAccessToken();
            $this->insertToken();
        }
        return $this;
    }

    /**
     * insertToken
     *
     * @return TwitchApiClient
     */
    private function insertToken():TwitchApiClient {
        $sql = "INSERT INTO betterhalf.`authentication`(`owner`, `token`)
                 VALUES(:owner, :token)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner' => 'App', 'token' => $this->access_token]);

        return $this;
    }

    /**
     * updateToken
     *
     * @return TwitchApiClient
     */
    private function updateToken(): TwitchApiClient{
        $sql = "UPDATE betterhalf.`authentication` 
                SET `token` = :token 
                WHERE `owner` = 'App'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $this->access_token]);

        return $this;
    }

    /**
     * getRacersInfo
     *
     * @param array $users
     * @return array
     */
    public function getRacersInfo(array $users): array {
        $uri = self::URL."/users";
        $headers = ['headers' => [
            'Authorization' => 'Bearer '. $this->access_token,
            'Client-id' => $this->client_id
        ]];
        $params = "?";

        $queryChunks = array_chunk($users, 100, TRUE);
        $promises = [];
       
        foreach($queryChunks as $chunkArray) {
            foreach($chunkArray as $fPos => $racer) {
                $params .= 'login='.$racer.'&';
            }
            $params = substr($params,0, -1);
            $promises[] = $this->client->getAsync($uri.$params, $headers);
        }

        $responses = Promise\Utils::settle($promises)->wait();

        $retArray = [];

        foreach($responses as $response) {
            if($response['state'] == 'fulfilled') {
                $retArray = array_merge($retArray, json_Decode($response['value']->getBody()->getContents(), TRUE)['data']);
            }
        }

        return $retArray;
    }

    /**
     * getData
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return Response
     */
    public function getData(string $method, string $url, array $options=[]):Response {
        return $this->client->requestAsync($method, $url, $options)->wait();
    }

    /**
     * token
     *
     * @return string
     */
    public function token(): string {
        return $this->access_token;
    }

    /**
     * getClientId
     *
     * @return string
     */
    public function getClientId(): string {
        return $this->client_id;
    }

    /**
     * getClientSecret
     *
     * @return string
     */
    public function getClientSecret(): string {
        return $this->client_secret;
    }
}