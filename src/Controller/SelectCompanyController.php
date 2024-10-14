<?php 


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SelectCompanyController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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

        $companyData = $companies->map(fn($company) => [
            'id' => $company->getId(),
            'name' => $company->getName(),
        ]);

        return new JsonResponse(['companies' => $companyData->toArray()]);
    }
}
