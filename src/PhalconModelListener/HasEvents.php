<?php

namespace Chocofamily\PhalconModelListener;

/**
 * Interface HasEvents
 * @package Chocofamily\PhalconModelListener
 */
interface HasEvents
{
    /**
     * @param string $event
     * @return string
     */
    public function getQueueName(string $event) : string;
}
