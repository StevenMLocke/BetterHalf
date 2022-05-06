<?PHP
    namespace BetterHalf;

    use PDO;

    /**
     * Twitch User
     */
    abstract class TwitchUser {
        protected int $id;
        protected ?string $name = NULL;
        protected ?string $profilePicUrl = NULL;
        protected PDO $db;

        /**
         * getProfilePic
         *
         * @param integer $twitchUserId
         * @return string
         */
        public function getProfilePic(): string {
            return $this->profilePicUrl ?? 'Profile Pic Not Set';
        }

        /**
         * setProfilePic
         *
         * @param string $twitchProfilePicUrl
         * @return TwitchUser
         */
        public function setProfilePic(string $twitchProfilePicUrl): TwitchUser {
            $this->profilePicUrl = $twitchProfilePicUrl;
            return $this;
        }

        /**
         * getName
         *
         * @return string
         */
        public function getName(): string {
            return $this->name ?? 'Name Not Set';
        }

        /**
         * setName
         *
         * @param string $twitchDisplayName
         * @return TwitchUser
         */
        public function setName(string $twitchDisplayName): TwitchUser {
            $this->name = $twitchDisplayName;

            return $this;
        }        

        /**
         * getId
         *
         * @return integer
         */
        public function getId(): int {
            return $this->id ?? 0;
        }

        /**
         * setId
         *
         * @param integer $twitchUserId
         * @return TwitchUser
         */
        public function setId(int $twitchUserId): TwitchUser {
            $this->id = $twitchUserId;

            return $this;
        }

         /**
         * getDb
         *
         * @return PDO
         */
        public function getDb():PDO {
            return $this->db;
        }

        /**
         * updateOrPutTwitchUser
         *
         * @return TwitchUser
         */
        public function updateOrPutTwitchUser(): TwitchUser {
            if ($this->checkRegistration()) {
                $this->updateTwitchUser();
            }else{
                $this->putTwitchUser();
            }

            return $this;
        }

        /**
         * updateTwitchUser
         *
         */
        abstract protected function updateTwitchUser(): TwitchUser;

        /**
         * putTwitchUser
         *
         */
        abstract protected function putTwitchUser(): TwitchUser;

        /**
         * checkRegistration
         *
         * @return boolean
         */
        abstract public function checkRegistration(): bool;

    }