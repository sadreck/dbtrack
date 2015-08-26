<?php
namespace DBtrack\Base;

class Events
{
    /**
     * Only events in this list can be added - If you need one add it here.
     * @var array
     */
    private static $validEvents = array(
        'eventDisplayMessage' => true
    );

    /** @var array Holds all the registered listeners. */
    private static $listeners = array();

    /**
     * Add an event listener.
     * @param \stdClass $listener Must have ->event (name) and ->function.
     * @throws \Exception
     */
    public static function addEventListener(\stdClass $listener)
    {
        // I'm throwing exceptions as it's a coding error.
        if (!isset($listener->event, $listener->function)) {
            throw new \Exception('Tried to add an invalid event listener.');
        } elseif (!isset(self::$validEvents[$listener->event]) ||
            !self::$validEvents[$listener->event]) {

            throw new \Exception('Invalid event listener: ' . $listener->event);
        }

        $event = new \stdClass();
        $event->function = $listener->function;

        if (!isset(self::$listeners[$listener->event])) {
            self::$listeners[$listener->event] = array();
        }
        self::$listeners[$listener->event][] = $event;
    }

    /**
     * Trigger a custom event.
     * @param \stdClass $event Must have ->event (name) and ->params (array).
     * @return bool
     * @throws \Exception
     */
    public static function trigger(\stdClass $event)
    {
        // I'm throwing exceptions as it's a coding error.
        if (!isset($event->event, $event->params)) {
            throw new \Exception('Tried to trigger event with invalid params.');
        } elseif (!isset(self::$listeners[$event->event])) {
            // This means that this event has no event listeners.
            return false;
        }

        foreach (self::$listeners[$event->event] as $listener) {
            if (is_callable($listener->function)) {
                try {
                    call_user_func_array($listener->function, $event->params);
                } catch (\Exception $e) {
                    throw new \Exception(
                        'Error in function ' . $listener->function . ' while ' .
                        'trying to trigger event: ' . $event->event,
                        0,
                        $e
                    );
                }
            }
        }

        return true;
    }

    /**
     * Simple wrapper around the trigger function.
     * @param $eventName
     * @param array $params
     * @throws \Exception
     */
    public static function triggerSimple($eventName, $params)
    {
        if (!is_array($params)) {
            $params = array($params);
        }

        $event = array(
            'event' => $eventName,
            'params' => $params
        );

        self::trigger((object)$event);
    }
}