<?PHP

namespace BetterHalf;

use PDO;

/**
 * Streamer
 */
class Streamer extends TwitchUser {
    protected array $stream_events = [];
    protected ?string $access_token;
    protected ?string $refresh_token;

    /**
     * __constuct
     *
     * @param PDO $pdoDb
     * @param integer $twitchUserId
     */
    public function __construct(PDO $pdoDb, int $twitchUserId) {
        $this->db = $pdoDb;
        $this->id = $twitchUserId;
        if($this->checkRegistration()) {
            $this->populate();
        }
    }

    /**
     * populate
     *
     * @return void
     */
    protected function populate():void {
        $sql = "SELECT `name`,
                    `profile_pic_url`,
                    `access_token`,
                    `refresh_token`
                FROM betterhalf.`twitch_user`
                 WHERE `id` = :sId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sId' => $this->id]);

        $resArr = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->setStreamEvents()
            ->setAccessToken($resArr['access_token'])
            ->setRefreshToken($resArr['refresh_token'])
            ->setName($resArr['name'])
            ->setProfilePic($resArr['profile_pic_url']);
    }

    /**
     * checkRegistration
     *
     * @return boolean
     */
    public function checkRegistration(): bool {
        $sql = "SELECT `registered_streamer` 
                FROM betterhalf.`twitch_user` 
                WHERE `id` = :sId";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sId' => $this->id]);

        return $stmt->fetchColumn() == 1 ? TRUE : FALSE;
    }

    /**
     * updateTwitchUser
     *
     * @return Streamer
     */
    protected function updateTwitchUser(): Streamer {
        $sql = "UPDATE betterhalf.`twitch_user`
                SET `name` = :name, 
                    `profile_pic_url` = :ppu,
                    `access_token` = :aTok,
                    `refresh_token` = :rTok,
                    `date_last_modified` = :dlm 
                WHERE `id` = :sId";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name'  => $this->name,
            'ppu'   => $this->profilePicUrl,
            'aTok'  => $this->access_token,
            'rTok'  => $this->refresh_token,
            'dlm'   => time(),
            'sId'   =>$this->id
        ]);

        return $this;
    }

    /**
     * putTwitchUser
     *
     * @return Streamer
     */
    protected function putTwitchUser(): Streamer {
        $sql = "INSERT INTO betterhalf.`twitch_user`(`id`, `name`, `profile_pic_url`, `date_added`, `access_token`, `refresh_token`) 
                    VALUES(:sId, :name, :ppu, :da, :aTok, :rTok)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sId'   => $this->id,
            'name'  => $this->name,
            'ppu'   => $this->profilePicUrl,
            'da'    => time(),
            'atok'  => $this->access_token,
            'rTok'  => $this->refresh_token
        ]);

        return $this;
    }

    /**
     * setStreamEvents
     *
     * @return Streamer
     */
    protected function setStreamEvents(): Streamer {
        $sql = "SELECT `name` 
                FROM betterhalf.`stream_event`
                 WHERE `streamer_id` = :sId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sId' => $this->id]);

        $eventNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach($eventNames as $eventName){
            $this->stream_events[$eventName] = new StreamEvent($this, $eventName);
        }

        return $this;
    }

    /**
     * getStreamEvent
     *
     * @param string $streamEventName
     * @return StreamEvent
     */
    public function getStreamEvent(string $streamEventName): StreamEvent {
        if (array_key_exists($streamEventName, $this->stream_events)){
            return new StreamEvent($this, $streamEventName);
        }
    }

    /**
     * getDb
     *
     * @return PDO
     */
    public function getDb(): PDO {
        return $this->db;
    }

    /**
     * getStreamEvents
     *
     * @return array
     */
    public function getStreamEvents(): array {
        return $this->stream_events;
    }

    /**
     * setAccessToken
     *
     * @param string|null $accessToken
     * @return Streamer
     */
    public function setAccessToken(?string $accessToken): Streamer {
        $this->access_token = $accessToken;
        return $this;
    }

    /**
     * getAccessToken
     *
     * @return string
     */
    public function getAccessToken(): string {
        return $this->access_token;
    }

    /**
     * setRefreshToken
     *
     * @param string|null $refreshToken
     * @return Streamer
     */
    public function setRefreshToken(?string $refreshToken): Streamer {
        $this->refresh_token = $refreshToken;
        return $this;
    }

    /**
     * getRefreshToken
     *
     * @return string
     */
    public function getRefreshToken(): string {
        return $this->refresh_token;
    }

    /**
     * refreshToken
     *
     * @param TwitchApiClient $twitchApiClient
     * @return Streamer
     */
    public function refreshToken(TwitchApiClient $twitchApiClient): Streamer {
        $options = ['query' =>[
            'client_id'     => $twitchApiClient->getClientId(),
            'client_secret' => $twitchApiClient->getClientSecret(),
            'grant_type'    => 'refresh_token',
            'refresh_token' => urlencode($this->refresh_token)
        ]];

        $resArr = json_decode($twitchApiClient->getData('POST', 'https://id.twitch.tv/oauth2/token', $options)->getBody(), TRUE);
        
        $this->setAccessToken($resArr['access_token'])
            ->setRefreshToken($resArr['refresh_token'])
            ->updateOrPutTwitchUser();

        return $this;
    }
}