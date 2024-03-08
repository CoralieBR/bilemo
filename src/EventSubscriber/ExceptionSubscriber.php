<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
        } else if ($exception instanceof NotEncodableValueException) {
            $status = Response::HTTP_BAD_REQUEST;
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $data = [
            'status' => $status,
            'message' => $exception->getMessage(),
        ];
        $event->setResponse(new JsonResponse($data, $status));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
