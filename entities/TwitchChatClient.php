<?php
//this is taken almost entirely from https://eheidi.dev/blog/creating-a-twitch-irc-chatbot-in-php-with-minicli-45mo

namespace BetterHalf;

use Socket;

class TwitchChatClient
{
    protected $socket;
    protected $nick;
    protected $oauth;

    static $host = "irc.chat.twitch.tv";
    static $port = "6667";

    /**
     * __construct
     *
     * @param [type] $nick
     * @param [type] $oauth
     */
    public function __construct($nick, $oauth)
    {
        $this->nick = strtolower($nick);
        $this->oauth = $oauth;
    }

    /**
     * connect
     *
     * @return void
     */
    public function connect()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (socket_connect($this->socket, self::$host, self::$port) === FALSE) {
            return null;
        }

        $this->authenticate();
        $this->setNick();
        $this->joinChannel($this->nick);
    }

    /**
     * authenticate
     *
     * @return void
     */
    public function authenticate()
    {
        $this->send("PASS oauth:".$this->oauth);
    }

    /**
     * setNick
     *
     * @return void
     */
    public function setNick()
    {
        $this->send(sprintf("NICK %s", $this->nick));
    }

   /**
    * joinChannel
    *
    * @param [type] $channel
    * @return void
    */
    public function joinChannel($channel)
    {
        $this->send(sprintf("JOIN #%s", $channel));
    }

    /**
     * getLastError
     *
     * @return void
     */
    public function getLastError()
    {
        return socket_last_error($this->socket);
    }

    /**
     * isConnected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return !is_null($this->socket);
    }

    /**
     * read
     *
     * @param integer $size
     * @return void
     */
    public function read($size = 256)
    {
        if (!$this->isConnected()) {
            return null;
        }

        return socket_read($this->socket, $size);
    }

    /**
     * send
     *
     * @param [type] $message
     * @return void
     */
    public function send($message)
    {
        if (!$this->isConnected()) {
            return null;
        }

        return socket_write($this->socket, $message . "\n");
    }

    /**
     * close
     *
     * @return void
     */
    public function close()
    {
        socket_close($this->socket);
    }

    /**
     * sendChat
     *
     * @param string $message
     * @return void
     */
    public function sendChat(string $message) {
        return $this->send("PRIVMSG #".$this->nick." :".$message);
    }
}