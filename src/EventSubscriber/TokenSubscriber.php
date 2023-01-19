<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 20:02
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function onKernelRequest(RequestEvent  $event)
    {
        $path = $event->getRequest()->getPathInfo();

        $isApi = substr($path, 0, 4) === "/api";

        if (!$isApi) {
            return;
        }

        if ($event->getRequest()->getMethod() === 'OPTION') {
            return;
        }

        $token = $event->getRequest()->query->get('token');
        if ($token !== $this->token) {
            throw new AccessDeniedHttpException('This action needs a valid token!');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
