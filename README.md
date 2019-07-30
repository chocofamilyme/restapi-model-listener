# Библиотека для прослушки событий моделей Phalcon

Библиотека позволяет слушать встроенные события модели и отправлять сообщения в очередь.

## Требуется
    - Phalcon > 3.0.0
    - chocofamilyme/pubsub > 2.*
    
## Использование

Прикрепляем слушатель в **Events Manager** для прослушки событий моделей **Phalcon:**

````php
class ServiceProvider extends AbstractServiceProvider
{
    protected $serviceName = 'eventsManager';
   
    public function register()
    {
        $this->di->setShared(
            $this->serviceName,
            function () {
                $eventsManager = new Manager();
                $eventsManager->attach('model', new ModelsListener());
                return $eventsManager;
            }
        );
    }
}
````

Прослушивает события ``afterCreate``, ``afterUpdate``, ``afterDelete``. Для прослушки модель должна реализовывать 
интерфейс ``HasEvents``

Метод ``getQueueName`` интерфейса должен возвращать имя очереди по имени события.

Пример:

````php
class Order extends Model implements HasEvents
{
    const EVENTS = [
            'paid' => 'rahmet.order.paid',
            'created' => 'rahmet.order.created',
            'updated' => 'rahmet.order.updated'
        ];
        
    /**
     * @param string $eventName
     * @return string
     */
    public function getQueueName(string $eventName) : string
    {
        return self::EVENTS[$eventName];
    }
    
    //other logic..
}
````

По умолчанию в payload отправляются все поля модели. Для выборочной отправки или добавления дополнительных полей
реализуем в модели интерфейс ``SerializerInterface`` библиотеки **Chocofamily Pubsub**.
Метод ``getAttributes`` принимает в качестве аргумента модель, должен возвращать массив с полями для отправки в очередь.

Можно создать свой кастомный сериалайзер и внедрить его в слушатель. В таком случае этот сериалайзер будет
применим для всех прослушиваемых моделей:

````php
public function register()
    {
        $this->di->setShared(
            $this->serviceName,
            function () {
                $serializer = new CustomSerializer();
                $listener = new ModelsListener();
                $listener->setObjectSerializer($serializer);

                $eventsManager = new Manager();
                $eventsManager->attach('model', $listener);
                return $eventsManager;
            }
        );
    }
````

Соответственно, данный сериалайзер должен реализовывать ``SerializerInterface``, либо наследоваться от базового
``ObjectSerializer`` библиотеки **Chocofamily Pubsub**.