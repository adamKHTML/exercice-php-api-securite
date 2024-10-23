<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Company;
use Symfony\Component\HttpFoundation\Session\SessionInterface;  

class SelectCompanyController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Récupèrer les entreprises de l'utilisateur
    #[Route(path: '/api/select', name: 'api_select_company', methods: ['GET'])]
    public function selectCompanyApi(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }

        $companies = $user->getCompany(); 
        if ($companies->isEmpty()) {
            return new JsonResponse(['error' => 'Aucune société trouvée'], 404);
        }

        $companyData = [];
        foreach ($companies as $company) {
            $companyData[] = [
                'id' => $company->getId(),
                'name' => $company->getName(),
            ];
        }

        return new JsonResponse(['companies' => $companyData]);
    }

    //Assigne un rôle à l'utilisateur
    #[Route(path: '/api/assign-role', name: 'api_assign_role', methods: ['POST'])]
    public function assignRole(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentification requise'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $companyId = $data['company_id'] ?? null;
        $role = $data['role'] ?? null; 

        if (!$companyId || !$role) {
            return new JsonResponse(['error' => 'Société et rôle requis'], 400);
        }

        $company = $this->entityManager->getRepository(Company::class)->find($companyId);
        if (!$company) {
            return new JsonResponse(['error' => 'Société introuvable'], 404);
        }

       
        $currentRoles = $user->getRoles();

        
        if (!in_array($role, $currentRoles, true)) {
            $currentRoles[] = $role;  
        }

      
        $user->setRoles($currentRoles);

        // Stock l'ID de la société sélectionnée dans la session
        $session->set('selected_company_id', $companyId);

        $this->entityManager->flush();

        return new JsonResponse(['success' => 'Rôle attribué avec succès']);
    }
}
