<?php

namespace ScripterCo\Bundle\TwitterStreamBundle\Request;

interface RequestInterface
{

    public function setConsumerKey($consumer_key);
    
    public function setConsumerSecret($consumer_secret);
    
    public function setToken($token);
    
    public function setTokenSecret($token_secret);
        
    public function start();

}