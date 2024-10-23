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

    
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager
    )
    {
        $this->jwtManager = $jwtManager;
        $this->entityManager = $entityManager;
    }

   
    #[Route(path: '/api/login', methods: ['POST'])]
    public function apiLogin(Request $request): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

      
        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe sont requis'], 400);
        }

      
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        
        
        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Identifiants invalides'], 401);
        }

      
        $token = $this->jwtManager->create($user);

      
        return new JsonResponse(['token' => $token]);
    }

    #[Route('/api/logoff', name: 'api_logoff', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $this->getUser();
    
       
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non connecté'], 401); 
        }
    
      
        $roles = $user->getRoles();
    
        
        $filteredRoles = array_filter($roles, function ($role) {
            return $role === 'ROLE_USER';
        });
    
        $user->setRoles(array_values($filteredRoles));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
      
        return new JsonResponse(['message' => 'Déconnexion réussie'], 200);
    }
    
}
