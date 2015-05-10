<?php

namespace Rad\Event;

use Closure;
use SplPriorityQueue;

/**
 * RadPHP EventManager
 *
 * @package Rad\Event
 */
class EventManager
{
    /**
     * @var SplPriorityQueue[]
     */
    protected static $listener = [];

    /**
     * Attach listener
     *
     * @param string               $eventType
     * @param array|Closure|object $callable
     * @param int                  $priority
     */
    public function attach($eventType, $callable, $priority = 10)
    {
        if (!isset(self::$listener[$eventType])) {
            self::$listener[$eventType] = new SplPriorityQueue();
        }

        self::$listener[$eventType]->insert($callable, $priority);
    }

    /**
     * Detach listener
     *
     * @param string $eventType
     */
    public function detach($eventType)
    {
        if (isset(self::$listener[$eventType])) {
            unset(self::$listener[$eventType]);
        }
    }

    /**
     * Detach all listener
     */
    public function detachAll()
    {
        self::$listener = [];
    }

    /**
     * Dispatch event
     *
     * @param string $eventType
     * @param null   $subject
     * @param null   $data
     * @param bool   $cancelable
     *
     * @return Event
     */
    public function dispatch($eventType, $subject = null, $data = null, $cancelable = true)
    {
        $event = new Event($eventType, $subject, $data, $cancelable);

        if (isset(self::$listener[$eventType])) {
            $queue = self::$listener[$eventType];
            $queue->top();

            while ($queue->valid()) {
                $this->callListener($queue->current(), $event);

                if ($event->isImmediatePropagationStopped()) {
                    break;
                }

                $queue->next();
            }
        }

        return $event;
    }

    /**
     * Call listener
     *
     * @param array|Closure|object $callable
     * @param Event                $event
     */
    protected function callListener($callable, Event $event)
    {
        if ($callable instanceof Closure || is_array($callable)) {
            $result = call_user_func_array($callable, [$event, $event->getSubject(), $event->getData()]);
            $event->setResult($result);
        } elseif (is_object($callable)) {
            $result = $callable->{$event->getType()}($event, $event->getSubject(), $event->getData());
            $event->setResult($result);
        }
    }
}
