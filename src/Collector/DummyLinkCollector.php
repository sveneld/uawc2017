<?php

namespace uawc\Collector;

class DummyLinkCollector implements LinkCollectorInterface
{
    /**
     * @inheritdoc
     */
    public function collect()
    {
        return [];
    }
}