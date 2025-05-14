<?php

namespace App\Security;

use App\Repository\UtilisateurRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JwtAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private string $jwtSecret;
    private UtilisateurRepository $utilisateurRepository;

    public function __construct(UtilisateurRepository $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
        // Utiliser une clé JWT fixe pour le développement - à remplacer par une variable d'environnement en production
        $this->jwtSecret = 'pfe_jwt_secret_key_2024'; // Doit correspondre à la clé dans AuthController
    }

    public function supports(Request $request): ?bool
    {
        // Vérifier si la requête contient un token JWT dans l'en-tête Authorization
        $hasAuth = $request->headers->has('Authorization');
        $authHeader = $request->headers->get('Authorization', '');
        $isBearer = str_starts_with($authHeader, 'Bearer ');

        error_log('JwtAuthenticator::supports - Path: ' . $request->getPathInfo());
        error_log('JwtAuthenticator::supports - Method: ' . $request->getMethod());
        error_log('JwtAuthenticator::supports - Has Authorization: ' . ($hasAuth ? 'Yes' : 'No'));
        error_log('JwtAuthenticator::supports - Auth Header: ' . $authHeader);
        error_log('JwtAuthenticator::supports - Is Bearer: ' . ($isBearer ? 'Yes' : 'No'));

        // Afficher tous les en-têtes pour le débogage
        error_log('JwtAuthenticator::supports - All Headers:');
        foreach ($request->headers->all() as $key => $value) {
            error_log("  $key: " . (is_array($value) ? implode(', ', $value) : $value));
        }

        return $hasAuth && $isBearer;
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (null === $authHeader) {
            error_log('JwtAuthenticator: Token JWT manquant pour ' . $request->getPathInfo());
            throw new CustomUserMessageAuthenticationException('Token JWT manquant');
        }

        // Extraire le token
        $token = str_replace('Bearer ', '', $authHeader);
        error_log('JwtAuthenticator: Token reçu pour ' . $request->getPathInfo());

        try {
            // Décoder le token
            try {
                $payload = (array) JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
                error_log('JwtAuthenticator: Token décodé avec succès');
                error_log('JwtAuthenticator: Payload - ' . json_encode($payload));
            } catch (\Firebase\JWT\ExpiredException $e) {
                error_log('JwtAuthenticator: Token JWT expiré: ' . $e->getMessage());
                throw new CustomUserMessageAuthenticationException('Token JWT expiré');
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                error_log('JwtAuthenticator: Signature du token invalide: ' . $e->getMessage());
                throw new CustomUserMessageAuthenticationException('Signature du token invalide');
            } catch (\Exception $e) {
                error_log('JwtAuthenticator: Erreur de décodage du token: ' . $e->getMessage());
                throw new CustomUserMessageAuthenticationException('Erreur de décodage du token: ' . $e->getMessage());
            }

            // Vérifier si le token est expiré
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                error_log('JwtAuthenticator: Token JWT expiré');
                throw new CustomUserMessageAuthenticationException('Token JWT expiré');
            }

            // Vérifier que les champs requis sont présents
            if (!isset($payload['id']) || !isset($payload['email'])) {
                error_log('JwtAuthenticator: Token JWT invalide - champs requis manquants');
                throw new CustomUserMessageAuthenticationException('Token JWT invalide - champs requis manquants');
            }

            // Créer un UserBadge avec l'identifiant de l'utilisateur
            return new SelfValidatingPassport(
                new UserBadge($payload['email'], function ($userIdentifier) use ($payload) {
                    // Charger l'utilisateur à partir de l'ID dans le token
                    $user = $this->utilisateurRepository->find($payload['id']);

                    if (!$user) {
                        error_log('JwtAuthenticator: Utilisateur non trouvé avec ID ' . $payload['id']);
                        throw new CustomUserMessageAuthenticationException('Utilisateur non trouvé');
                    }

                    error_log('JwtAuthenticator: Utilisateur trouvé - ID: ' . $user->getId() . ', Email: ' . $user->getEmail() . ', Rôle: ' . $user->getRole() . ', Approuvé: ' . ($user->isApproved() ? 'Oui' : 'Non'));

                    // Vérifier si l'utilisateur est approuvé
                    if (!$user->isApproved()) {
                        error_log('JwtAuthenticator: Utilisateur non approuvé - ID: ' . $user->getId());
                        throw new CustomUserMessageAuthenticationException('Votre compte est en attente d\'approbation par un administrateur');
                    }

                    // Vérifier les rôles
                    $roles = $user->getRoles();
                    error_log('JwtAuthenticator: Rôles de l\'utilisateur: ' . implode(', ', $roles));

                    return $user;
                })
            );
        } catch (CustomUserMessageAuthenticationException $e) {
            // Relancer les exceptions personnalisées
            throw $e;
        } catch (\Exception $e) {
            error_log('JwtAuthenticator: Exception non gérée: ' . $e->getMessage());
            throw new CustomUserMessageAuthenticationException('Token JWT invalide: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Laisser la requête continuer
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log('JwtAuthenticator::onAuthenticationFailure - Path: ' . $request->getPathInfo());
        error_log('JwtAuthenticator::onAuthenticationFailure - Exception: ' . $exception->getMessage());

        return new JsonResponse(
            [
                'message' => $exception->getMessage(),
                'path' => $request->getPathInfo(),
                'error' => 'authentication_failed'
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Cette méthode est appelée lorsqu'une authentification est nécessaire mais n'a pas été envoyée.
     * Implémentation de AuthenticationEntryPointInterface.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        error_log('JwtAuthenticator::start - Path: ' . $request->getPathInfo());
        if ($authException) {
            error_log('JwtAuthenticator::start - Exception: ' . $authException->getMessage());
        }

        return new JsonResponse(
            [
                'message' => 'Authentification requise',
                'path' => $request->getPathInfo(),
                'error' => 'authentication_required'
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
