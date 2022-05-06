<?php

namespace BetterHalf;

use PDO;

class Racer extends TwitchUser {
    public function __construct(PDO $pdoDb, string $racerName) {
        $this->db = $pdoDb;
        $this->name = $racerName;
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
        $sql = "SELECT `id`,
                    `profile_pic_url`
                FROM betterhalf.`twitch_user`
                 WHERE `name` = :name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $this->name]);

        $resArr = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->setId($resArr['id'])
            ->setProfilePic($resArr['profile_pic_url']);
    }

    /**
     * checkRegistration
     *
     * @return boolean
     */
    public function checkRegistration(): bool {
        $sql = "SELECT `id` 
                FROM betterhalf.`twitch_user` 
                WHERE `name` = :name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $this->name]);

        return $stmt->fetchColumn() ? TRUE : FALSE;
    }

    /**
     * updateTwitchUser
     *
     * @return TwitchUser
     */
    protected function updateTwitchUser(): TwitchUser {
        $sql = "UPDATE betterhalf.`twitch_user` 
                SET `id` = :id, `profile_pic_url` = :ppu 
                WHERE `name` = :name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $this->id, 
            'ppu' => $this->profilePicUrl, 
            'name' => $this->name]);

        return $this;
    }

    /**
     * putTwitchUser
     *
     * @return TwitchUser
     */
    protected function putTwitchUser(): TwitchUser {
        $sql = "INSERT INTO betterhalf.`twitch_user`(`id`, `name`, `profile_pic_url`) 
                VALUES(:id, :name, :ppu)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $this->id,
            'name' => $this->name,
            'ppu' => $this->profilePicUrl]);

        return $this;
    }
}