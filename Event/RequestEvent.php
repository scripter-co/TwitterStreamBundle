<?php

namespace ScripterCo\Bundle\TwitterStreamBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class RequestEvent extends Event
{

    private $_tweet;

    public function setTweet($tweet)
    {
        $this->_tweet = $tweet;
    }

    public function getTweet()
    {
        return $this->_tweet;
    }

}