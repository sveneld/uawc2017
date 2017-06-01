<?php

namespace uawc\Collector;

interface LinkCollectorInterface
{
    /**
     * @return string[]
     */
    public function collect();
}