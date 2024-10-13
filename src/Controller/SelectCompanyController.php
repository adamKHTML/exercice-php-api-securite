<?php 


namespace App\Controller;

// src/Controller/SelectCompanyController.php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SelectCompanyController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/api/select', name: 'api_select_company', methods: ['GET'])]
    public function selectCompanyApi(Request $request, TokenInterface $token): JsonResponse
    {
        // Vérifier si un token est disponible
        if (!$token || !$token->getUser()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $user = $token->getUser(); // L'utilisateur est directement récupéré via le token

        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Récupérer toutes les sociétés auxquelles l'utilisateur est affilié
        $companies = $user->getCompany(); 

        // Vérifier si l'utilisateur n'a pas de société
        if ($companies->isEmpty()) {
            return new JsonResponse(['error' => 'No companies found for user'], 404);
        }

        // Retourner les sociétés sous forme de JSON
        return new JsonResponse([
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->getId(),
                    'name' => $company->getName(), 
                ];
            })
        ]);
    }

    #[Route(path: '/select', name: 'select_company', methods: ['GET'])]
    public function selectCompanyHtml(Request $request, TokenInterface $token)
    {
        // Vérifier si un token est disponible
        if (!$token || !$token->getUser()) {
            throw $this->createAccessDeniedException('Authentication required');
        }

        $user = $token->getUser(); 

        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Récupérer toutes les sociétés auxquelles l'utilisateur est affilié
        $companies = $user->getCompany(); 

        // Vérifier si l'utilisateur n'a pas de société
        if ($companies->isEmpty()) {
            throw $this->createNotFoundException('No companies found for user');
        }

        // Passer les données au template Twig
        return $this->render('select/select.html.twig', [
            'companies' => $companies,
        ]);
    }
}
