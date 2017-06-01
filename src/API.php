<?php

namespace uawc;

use go\DB\DB;

class API
{
    /**
     * @var DB
     */
    private $DB;

    public function __construct(DB $DB)
    {
        $this->DB = $DB;
    }
}