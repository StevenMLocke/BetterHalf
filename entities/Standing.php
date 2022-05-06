<?PHP

namespace BetterHalf;

use PDO;

/**
 * Standing
 */
class Standing{
    private PDO $db;
    private StreamEvent $streamEvent;
    private Racer $racer;
    private int $finishPosition;
    private int $timestamp; 
    private int $qualifyingCount;
    private int $qualified;
    
    /**
     * __construct
     *
     * @param StreamEvent $streamEvent
     * @param Racer $racer
     */
    public function __construct(StreamEvent $streamEvent, Racer $racer){
        $this->racer        = $racer;
        $this->streamEvent  = $streamEvent;
        $this->db           = $this->streamEvent->getDb();
    }

   /**
    * setFinishPosition
    *
    * @param integer $finishPosition
    * @return Standing
    */
    public function setFinishPosition(int $finishPosition): Standing{
        $this->finishPosition = $finishPosition;
        return $this;
    }
    
    /**
     * setTimestamp
     *
     * @param integer $timestamp
     * @return Standing
     */
    public function setTimestamp(int $timestamp):Standing{
        $this->timestamp = $timestamp;
        return $this;
    }
    
    /**
     * setQualifyingCOunt
     *
     * @param integer $qualifyingCount
     * @return Standing
     */
    public function setQualifyingCount(int $qualifyingCount): Standing{
        $this->qualifyingCount = $qualifyingCount;
        return $this;
    }
    
    /**
     * setQualified
     *
     * @param integer $qualified
     * @return Standing
     */
    public function setQualified(int $qualified):Standing{
        $this->qualified = $qualified;
        return $this;
    }
    
    /**
     * getFinishPosition
     *
     * @return integer
     */
    public function getFinishPosition(): int{
        return $this->finishPosition ?? 0;
    }
    
    /**
     * getTimestamp
     *
     * @return integer
     */
    public function getTimestamp(): int{
        return $this->timestamp ?? 0;
    }
    
    /**
     * getQualifyingCOunt
     *
     * @return integer
     */
    public function getQualifyingCount(): int{
        return $this->qualifyingCount ?? 0;
    }
    
    /**
     * getQualified
     *
     * @return integer
     */
    public function getQualified(): int{
        return $this->qualified ?? 0;
    }

    /**
     * putStanding
     *
     * @return Standing
     */
    public function putStanding(): Standing {
        $sql = "INSERT INTO betterhalf.`standing`(stream_event_id, racer_id, race_number, finish_position, `timestamp`, qualifying_cnt, qualified) 
                    VALUES(:seId, :rId, :rNum, :fPos, :ts, :qCnt, :q)";
        
        $stmt = $this->db->prepare($sql);     
        $stmt->execute([
            'seId'  => $this->streamEvent->getId(),
            'rId'   => $this->racer->getId(),
            'rNum'  => $this->streamEvent->raceNumber,
            'fPos'  => $this->finishPosition,
            'ts'    => $this->timestamp,
            'qCnt'  => $this->qualifyingCount,
            'q'     => $this->qualified
        ]);

        return $this;
    }
}