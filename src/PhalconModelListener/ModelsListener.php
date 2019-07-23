<?php


namespace Chocofamily\PhalconModelListener;

use Chocofamily\PubSub\Provider\Adapter;
use Chocofamily\PubSub\Publisher;
use Chocofamily\PubSub\SerializerInterface;
use Phalcon\Di;
use Phalcon\Events\Event;
use Phalcon\Mvc\Model;

class ModelsListener
{
    /** @var Adapter $eventSource */
    protected $eventSource;

    /** @var bool $isPublish */
    protected $isPublish = false;

    /** @var SerializerInterface $objectSerializer */
    protected $objectSerializer;

    /**
     * @param SerializerInterface $objectSerializer
     */
    public function setObjectSerializer(SerializerInterface $objectSerializer)
    {
        $this->objectSerializer = $objectSerializer;
    }

    /**
     * @param Event $event
     * @param Model $model
     */
    protected function afterSave(Event $event, Model $model)
    {
        $queueName = $this->getModelQueueName($model, 'created');

        $this->publish($model, $queueName);
    }

    /**
     * @param Event $event
     * @param Model $model
     */
    protected function afterUpdate(Event $event, Model $model)
    {
        $queueName = $this->getModelQueueName($model, 'updated');

        $this->publish($model, $queueName);
    }

    /**
     * @param Event $event
     * @param Model $model
     */
    protected function afterDelete(Event $event, Model $model)
    {
        $queueName = $this->getModelQueueName($model, 'deleted');

        $this->publish($model, $queueName);
    }

    /**
     * @param Model $model
     * @param string $eventName
     * @return string|null
     */
    protected function getModelQueueName(Model $model, string $eventName)
    {
        try {
            /** @var $model HasEvents */
            return $model->getQueueName($eventName);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param $arguments
     * @return bool
     */
    private function checkClass($arguments)
    {
        /** @var Model $model */
        $model = $arguments[1];

        if ($model instanceof HasEvents) {
            return true;
        }

        return false;
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ($this->checkClass($arguments)) {
            $this->setEventSource();
            $this->isPublish = true;
        }

        return call_user_func_array(array($this, $method), $arguments);
    }

    /**
     * @return void
     */
    private function setEventSource()
    {
        $this->eventSource = Di::getDefault()->get('eventsource');
    }

    /**
     * @param $model
     * @param $queueName
     * @return void
     */
    protected function publish($model, $queueName)
    {
        if ($this->isPublish and $queueName) {

            $payload = $this->getPayload($model);

            $publisher = new Publisher($this->eventSource);
            $publisher->send($payload, $queueName);
        }

        $this->isPublish = false;
    }

    /**
     * @param Model $model
     * @return array
     */
    protected function getPayload($model)
    {
        if ($model instanceof SerializerInterface) {
            /** @var $model SerializerInterface|Model */
            $payload = $model->getAttributes($model);
        }
        elseif (!empty($this->objectSerializer)) {
            $payload = $this->objectSerializer->getAttributes($model);
        }
        else {
            /** @var $model HasEvents|Model */
            $payload = $model->toArray();
        }

        $payload['event_id'] = uniqid();

        return $payload;
    }
}
