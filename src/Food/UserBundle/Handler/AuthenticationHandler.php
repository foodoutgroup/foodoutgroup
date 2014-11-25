<?php

namespace Food\UserBundle\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationHandler implements AuthenticationFailureHandlerInterface,
                                       AuthenticationSuccessHandlerInterface
{
    protected $cartService;

    public function setCartService($service)
    {
        $this->cartService = $service;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => 0, 'error' => $exception->getMessage()]);
        }

        return new JsonResponse([]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $session = $request->getSession();
        $currentSessionId = $session->getId();
        $oldSessionId = $session->get('session_id_before_login');

        if (!empty($oldSessionId) && $currentSessionId != $oldSessionId) {
            $this->cartService
                 ->migrateCartBetweenSessionIds($oldSessionId,
                                                $currentSessionId);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => 1]);
        }

        return new JsonResponse([]);
    }
}
