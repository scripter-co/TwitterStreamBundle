<?php

namespace ScripterCo\Bundle\TwitterStreamBundle\Request;

use ScripterCo\Bundle\TwitterStreamBundle\Model\TweetModel,
    ScripterCo\Bundle\TwitterStreamBundle\Model\UserModel,
    ScripterCo\Bundle\TwitterStreamBundle\RequestEvents,
    ScripterCo\Bundle\TwitterStreamBundle\Event\RequestEvent;

class Request implements RequestInterface
{
    private $_oauth_consumer_key;
    private $_oauth_consumer_secret;
    private $_oauth_token;
    private $_oauth_token_secret;
    private $_keywords = array();

    private $_oauth_nonce;
    private $_oauth_signature;
    private $_oauth_signature_method = 'HMAC-SHA1';
    private $_oauth_timestamp;
    private $_oauth_version = '1.0';

    public function __construct($event_dispatcher)
    {
        set_time_limit(0);
        $this->_event_dispatcher = $event_dispatcher;
        $this->_oauth_nonce = md5(mt_rand());
    }
    
    public function setConsumerKey($consumer_key)
    {
        $this->_oauth_consumer_key = $consumer_key;
        
        return $this;
    }
    
    public function setConsumerSecret($consumer_secret)
    {
        $this->_oauth_consumer_secret = $consumer_secret;
        
        return $this;
    }
    
    public function setToken($token)
    {
        $this->_oauth_token = $token;
        
        return $this;
    }
    
    public function setTokenSecret($token_secret)
    {
        $this->_oauth_token_secret = $token_secret;
        
        return $this;
    }
    
    public function setKeywords($keywords)
    {
        if(!is_array($keywords)){
            throw new \Exception('setKeywords expects parameter one to be an array.');
        }
        if(count($keywords) === 0){
            throw new \Exception('setKeywords expects keywords to contain at least one array element.');
        }
        $this->_keywords = $keywords;
        
        return $this;
    }

    private function _buildRequest()
    {
        $data = 'track=' . rawurlencode(implode($this->_keywords, ','));
        $this->_oauth_timestamp = time();
        $base_string = 'POST&' . 
            rawurlencode('https://stream.twitter.com/1.1/statuses/filter.json') . '&' .
            rawurlencode('oauth_consumer_key=' . $this->_oauth_consumer_key . '&' .
                'oauth_nonce=' . $this->_oauth_nonce . '&' .
                'oauth_signature_method=' . $this->_oauth_signature_method . '&' . 
                'oauth_timestamp=' . $this->_oauth_timestamp . '&' .
                'oauth_token=' . $this->_oauth_token . '&' .
                'oauth_version=' . $this->_oauth_version . '&' .
                $data);
        $secret = rawurlencode($this->_oauth_consumer_secret) . '&' . 
            rawurlencode($this->_oauth_token_secret);
        $raw_hash = hash_hmac('sha1', $base_string, $secret, true);
        $this->_oauth_signature = rawurlencode(base64_encode($raw_hash));
        $oauth = 'OAuth oauth_consumer_key="' . $this->_oauth_consumer_key . '", ' .
                'oauth_nonce="' . $this->_oauth_nonce . '", ' .
                'oauth_signature="' . $this->_oauth_signature . '", ' .
                'oauth_signature_method="' . $this->_oauth_signature_method . '", ' .
                'oauth_timestamp="' . $this->_oauth_timestamp . '", ' .
                'oauth_token="' . $this->_oauth_token . '", ' .
                'oauth_version="' . $this->_oauth_version . '"';
        
        $request  = "POST /1.1/statuses/filter.json HTTP/1.1\r\n";
        $request .= "Host: stream.twitter.com\r\n";
        $request .= "Authorization: " . $oauth . "\r\n";
        $request .= "Content-Length: " . strlen($data) . "\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n\r\n";
        $request .= $data;
        return $request;
    }
    
    public function start()
    {
        while(1)
        {
            $fp = fsockopen('ssl://stream.twitter.com', 443, $errno, $errstr, 30);
            if(!$fp){
                throw new \Exception('Twitter stream failed to open socket');
            }else{

                fwrite($fp, $this->_buildRequest());

                stream_set_blocking($fp, 0);

                while(!feof($fp))
                {
                    $read = array($fp);
                    $write = null;
                    $except = null;

                    $res = stream_select($read, $write, $except, 600, 0);
                    if($res === false || $res === 0){
                        break;
                    }

                    $json = fgets($fp);

                    if(strncmp($json, 'HTTP/1.1', 8) === 0){
                        $json = trim($json);
                        if ($json !== 'HTTP/1.1 200 OK'){
                            throw new \Exception('ERROR: ' . $json . "\n");
                        }
                    }

                    if(($json !== false) && (strlen($json) > 0)){
                        $tweet = json_decode($json, true);
                        if($tweet && is_array($tweet)){
                            
                            $tweet_model = new TweetModel();
                            $user_model = new UserModel();
                            foreach($tweet as $key => $value){
                                if($key === 'user'){
                                    foreach($value as $key => $value){
                                        $user_model->$key = $value;
                                    }
                                    $tweet_model->user = $user_model;
                                }else{
                                    $tweet_model->$key = $value;
                                }
                            }
                            
                            $request_event = new RequestEvent();
                            $request_event->setTweet($tweet_model);
                            
                            $this->_event_dispatcher->dispatch(RequestEvents::TWEET_RECIEVED, $request_event);
                        }
                    }
                }
            }
            fclose($fp);
            sleep(10);
        }
        return;
    }
};