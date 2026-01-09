<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class, method: 'onLoginSuccess')]
class AuthenticationSuccessListener
{
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        // Ne pas intercepter les requêtes authentifiées normales
        // Ce listener ne devrait s'activer que pour la route /api/login
        // Mais comme on utilise JWT, le login est géré directement dans AuthController::login
        // Donc on désactive ce listener pour éviter qu'il intercepte toutes les requêtes authentifiées
        
        // Vérifier si c'est une requête vers /api/login
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Si ce n'est PAS la route /api/login, ne rien faire
        // (laisser le contrôleur normal gérer la réponse)
        if ($path !== '/api/login') {
            return;
        }
        
        // Pour /api/login, le AuthController::login gère déjà la réponse
        // Donc on ne fait rien ici non plus
        // Ce listener est maintenant désactivé car le login JWT est géré dans le contrôleur
    }
}

