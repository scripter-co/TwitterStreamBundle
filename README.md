Twitter Stream Bundle
=============

A Symfony bundle that connects you to Twitters stream API, currently only supporting **statuses/filter** (see https://dev.twitter.com/docs/api/1.1/post/statuses/filter)

Twitter Stream Bundle simply:

  - Connects to Twitters streaming API
  - Recieves any data which matches your `track` keywords.
  - Triggers an event when a new Tweet is received

 

Version
----

0.1 - Initial Release

Installation
--------------

Add TwitterStreamBundle to your composer.json:

```
{
    "require": {
        "scripter-co/twitter-stream-bundle": "dev-master"
    }
}
```

Get composer to fetch the package for you:

```
php composer.phar update scripter-co/twitter-stream-bundle
```

Usage
-----

Craete a `command` under your bundle (e.g. `CoreBundle\Command\TwitterStreamCommand.php`) and place the :

```
$twitter_request_service = $this->container->get('scripterco_twitter_stream.request');
$twitter_request_service->setConsumerKey('KEY')
                        ->setConsumerSecret('SECRET')
                        ->setToken('TOKEN')
                        ->setTokenSecret('SECRET')
                        ->setKeywords(array(
                            'my_keyword'
                        ))
                        ->start();
```

When a tweet is found with your `my_keyword` keyword, it will trigger an event (`scripterco_twitter_stream.received`), so we need to create an event listener and add it to our services file:

`CoreBundle\EventHandler\RequestEventHandler`:
```
<?php

namespace Acme\CoreBundle\EventHandler;

class RequestEventHandler
{
    
    public $_container;
    
    public function __construct($container)
    {
        $this->_container = $container;
    }

    public function processTweet($request_event)
    {
        $doctrine = $this->_container->get('doctrine');
        
        $tweet_model = $request_event->getTweet();
                
        // tweet id
        echo $tweet_model->get('id');
        
        // user screen name
        echo $tweet_model->get('user')->get('screen_name')
    }
}
```

**Note:** If you want to see all the available parameters, see this [here](https://gist.github.com/scripter-co/6905227).

Now, we just need to add the event listener to the services file, I'm (using yaml):

```
acme_core.twitter.event:
    class: Acme\CoreBundle\EventHandler\RequestEventHandler
    arguments: [@service_container]
    tags:
        - { name: kernel.event_listener, event: scripterco_twitter_stream.received, method: processTweet }
```

That's it, you can easily add in saving of the tweets to a database as the `container` is available within your event handler.


MIT License
----

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is furnished
    to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
    
