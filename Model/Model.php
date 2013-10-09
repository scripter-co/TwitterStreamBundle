<?php

namespace ScripterCo\Bundle\TwitterStreamBundle\Model;

abstract class Model
{

    public function get($variable_name)
    {
        return isset($this->$variable_name) ? $this->$variable_name : false;
    }

}