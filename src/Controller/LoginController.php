<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

class LoginController extends AbstractController
{
    private $jwtManager;
    private $entityManager;

    // Le constructeur pour injecter le JWTManager et l'EntityManager
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager
    )
    {
        $this->jwtManager = $jwtManager;
        $this->entityManager = $entityManager;
    }

    // Définition de la route pour l'API de login
    #[Route(path: '/api/login', methods: ['POST'])]
    public function apiLogin(Request $request): JsonResponse
    {
        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        // Vérifier si l'email et le mot de passe ont été fournis
        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe sont requis'], 400);
        }

        // Rechercher l'utilisateur par son email dans la base de données
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        
        // Si l'utilisateur n'existe pas ou si le mot de passe est incorrect
        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }

        // Générer un token JWT pour l'utilisateur
        $token = $this->jwtManager->create($user);

        // Retourner le token dans la réponse
        return new JsonResponse(['token' => $token]);
    }
}
