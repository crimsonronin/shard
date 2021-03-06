<?php
/**
 * @link       http://zoopcommerce.github.io/shard
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\Shard\State;

use Zoop\Shard\AbstractExtension;

/**
 * Defines the resouces this extension requires
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Extension extends AbstractExtension
{
    protected $subscribers = [
        'subscriber.state.mainsubscriber',
        'subscriber.state.annotationsubscriber',
        'subscirber.state.statePermissionSubscirber',
        'subscirber.state.transitionPermissionSubscriber'
    ];

    protected $serviceManagerConfig = [
        'invokables' => [
            'subscriber.state.mainsubscriber' =>
                'Zoop\Shard\State\MainSubscriber',
            'subscriber.state.annotationsubscriber' =>
                'Zoop\Shard\State\AnnotationSubscriber',
            'subscirber.state.statePermissionSubscirber' =>
                'Zoop\Shard\State\AccessControl\StatePermissionSubscriber',
            'subscirber.state.transitionPermissionSubscriber' =>
                'Zoop\Shard\State\AccessControl\TransitionPermissionSubscriber'
        ]
    ];

    protected $exceptionEvents = [
        Events::TRANSITION_DENIED,
        Events::BAD_STATE,
    ];

    protected $dependencies = [
        'extension.annotation' => true
    ];

    protected $readFilterInclude = [];

    protected $readFilterExclude = [];

    public function getReadFilterInclude()
    {
        return $this->readFilterInclude;
    }

    public function setReadFilterInclude(array $readFilterInclude = [])
    {
        $this->readFilterInclude = $readFilterInclude;
    }

    public function getReadFilterExclude()
    {
        return $this->readFilterExclude;
    }

    public function setReadFilterExclude(array $readFilterExclude = [])
    {
        $this->readFilterExclude = $readFilterExclude;
    }
}
