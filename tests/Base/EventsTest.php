<?php
namespace DBtrack\Base;

class EventsTest extends \PHPUnit_Framework_TestCase
{
    public function testEvents()
    {
        // Invalid event structure.
        $listener = array(
            'event' => 'eventTest',
        );
        try {
            Events::addEventListener((object)$listener);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // Invalid event name.
        $listener = array(
            'event' => 'eventTest',
            'function' => 'dbTrackTestEvents'
        );
        try {
            Events::addEventListener((object)$listener);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $testClass = new TestEventsClass();

        // Add event.
        $listener = array(
            'event' => 'eventDisplayMessage',
            'function' => array($testClass, 'dbTrackTestEvents')
        );
        Events::addEventListener((object)$listener);

        // Invalid event structure.
        $event = array(
            'event' => 'eventDisplayMessage'
        );
        try {
            Events::trigger((object)$event);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // Invalid event.
        $event = array(
            'event' => 'invalidEvent',
            'params' => array('test')
        );
        try {
            Events::trigger((object)$event);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // Valid event call.
        $event = array(
            'event' => 'eventDisplayMessage',
            'params' => array('test')
        );
        Events::trigger((object)$event);

        // Broken event call.
        $event = array(
            'event' => 'eventDisplayMessage',
            'params' => array('test', true)
        );
        try {
            Events::trigger((object)$event);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        Events::triggerSimple('eventDisplayMessage', 'test');
        Events::triggerSimple('eventDisplayMessage', array('test'));

        // Check if trigger is enabled.
        $this->assertTrue(
            Events::isEventListenerEnabled('eventDisplayMessage')
        );

        $this->assertNull(
            Events::isEventListenerEnabled('does-not-exist')
        );

        $this->assertFalse(
            Events::toggleTriggers('does-not-exist', false)
        );

        Events::toggleTriggers('eventDisplayMessage', false);
        $this->assertFalse(
            Events::isEventListenerEnabled('eventDisplayMessage')
        );
    }
}

class TestEventsClass
{
    public function dbTrackTestEvents($data, $throwException = false)
    {
        if ($throwException) {
            throw new \Exception($data);
        }
        return $data;
    }
}
