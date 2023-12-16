<?php
/**
 * Event names to be dispatched via the EventDispatcher are structured as:
 * - xarEvents.{scope}.{event} e.g. xarEvents.user.UserLogin
 * - xarHooks.{scope}.{event} e.g. xarHooks.item.ItemCreate
 */

namespace Xaraya\Bridge\Events;

use Symfony\Component\EventDispatcher\GenericEvent;
use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;

class DefaultEvent extends GenericEvent implements ContextInterface
{
    use ContextTrait;
}
