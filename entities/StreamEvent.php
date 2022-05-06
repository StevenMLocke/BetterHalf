<?PHP
    namespace BetterHalf;

    use PDO;

    /**
     * StreamEvent
     */
    class StreamEvent {
        private PDO $db;
        private int $id;
        private string $name;
        private array $roster;
        private Streamer $streamer;
        public int $raceNumber;

        /**
         * __construct
         *
         * @param Streamer $streamer
         * @param string $streamEventName
         */
        public function __construct(Streamer $streamer, string $streamEventName) {
            $this->streamer = $streamer;
            $this->db       = $this->streamer->getDb();
            $this->name     = $streamEventName;

            if($this->checkRegistration()) {
                $this->setId()
                    ->setRaceNumber();
            }else{
                $this->putStreamEvent()
                    ->setId()
                    ->setRaceNumber();
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
         * putStreamEvent
         *
         * @return StreamEvent
         */
        private function putStreamEvent(): StreamEvent {
            $sql = "INSERT INTO betterhalf.`stream_event`(`streamer_id`, `name`) 
                    VALUES(:sId, :name)";

            $stmt= $this->db->prepare($sql);
            $stmt->execute(['sId' => $this->streamer->getId(), 'name' => $this->name]);

            return $this;
        }

        /**
         * getName
         *
         * @return string
         */
        public function getName(): string {
            return $this->name ?? 'Name not set';
        }

        /**
         * setName
         *
         * @param string $streamEventName
         * @return StreamEvent
         */
        public function setName(string $streamEventName):StreamEvent {
            $this->name = $streamEventName;
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
         * @return StreamEvent
         */
        private function setId():StreamEvent {
            $sql = "SELECT `id` 
            FROM betterhalf.`stream_event` 
            WHERE `name` = :name 
                AND `streamer_id` = :sId";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['name' => $this->name, 'sId' => $this->streamer->getId()]);

            $this->id = $stmt->fetchColumn();

            return $this;
        }

        /**
         * checkRegistration
         *
         * @return boolean
         */
        private function checkRegistration(): bool {
            $sql = "SELECT `id` 
                        FROM betterhalf.`stream_event` 
                        WHERE `name` = :name 
                            AND `streamer_id` = :sId";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['name' => $this->name, 'sId' => $this->streamer->getId()]);
            
            return $stmt->fetchColumn() ? TRUE : FALSE;
        }

        /**
         * populateRoster
         *
         * @return StreamEvent
         */
        public function populateRoster(): StreamEvent {
            $sql = "SELECT DISTINCT(t.`name`)
                    FROM betterhalf.`standing` s
                    JOIN betterhalf.`twitch_user` t
                        ON s.`racer_id` = t.`id`
                    WHERE s.`stream_event_id` = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $this->id]);

            $res = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $this->roster = $res ? $res : [];

            return $this;
        }

        /**
         * getRoster
         *
         * @return array
         */
        public function getRoster(): array {
            return $this->roster ?? [];
        }

        /**
         * getRaceNumber
         *
         * @return integer
         */
        private function setRaceNumber(): StreamEvent {
            $sql = "SELECT `race_number` 
            FROM betterhalf.`standing`
            WHERE `stream_event_id` = :seId
            ORDER BY `race_number` DESC 
            LIMIT 1";
    
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['seId' => $this->id]);

            $res = $stmt->fetchColumn();
            
            $this->raceNumber = $res ? $res + 1 : 1;

            return $this;
        }

        /**
         * getLastRaceForRacer
         *
         * @param Racer $racer
         * @return array
         */
        public function getLastRaceForRacer(Racer $racer): array {
            $sql = "SELECT `race_number`,
                            `qualifying_cnt`
                        FROM betterhalf.`standing` 
                        WHERE `stream_event_id` = :seId 
                            AND `racer_id` = :rId
                            AND `finish_position` != 0
                         ORDER BY `race_number` DESC 
                         LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['seId' => $this->id, 'rId' => $racer->getId()]);

            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return  $res ? $res : [];
        }

        /**
         * getRaceResults
         *
         * @param integer $streamEventId
         * @param integer $raceNumber
         * @return array
         * 
         * **DESCRIPTION:** This method returns an array for each racer that participated in the race including racer name, finish position, qualifying count, and qualified.
         */
        public function getRaceResults(): array {
            $sql = "SELECT s.`race_number`, 
                            r.`name`, 
                            s.`finish_position`, 
                            s.`qualifying_cnt`, 
                            s.`qualified` 
                    FROM betterhalf.`standing` s 
                    INNER JOIN betterhalf.`twitch_user` r 
                        ON s.`racer_id` = r.`id` 
                    WHERE s.`stream_event_id` = :seId
                        AND s.`race_number` = :rNum
                        AND s.`finish_position` > 0
                    ORDER BY s.`qualified` Desc, 
                            s.`qualifying_cnt` DESC, 
                            s.`finish_position` ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['seId' => $this->id, 'rNum' => $this->raceNumber]);

            $ret = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ret[] = $row;
            }

            return $ret;
        }
    }