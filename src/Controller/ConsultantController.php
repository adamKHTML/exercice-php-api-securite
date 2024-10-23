<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Company;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ConsultantController extends AbstractController
{
    //Affiche les projets 
    #[Route('/api/projects', name: 'projects', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getProjects(ProjectRepository $projectRepository, Request $request): JsonResponse
    {
        $companyId = $request->query->get('company_id');

        if (!$companyId) {
            return new JsonResponse(['error' => 'ID de la société manquant'], 400);
        }

        $projects = $projectRepository->findBy(['company' => $companyId]);

        
        $projectData = array_map([$this, 'transformProject'], $projects);

        return $this->json($projectData);
    }

     
    private function transformProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
            'company' => $this->transformCompany($project->getCompany()), 
        ];
    }

   
    private function transformCompany(?Company $company): ?array
    {
        if ($company === null) {
            return null;
        }

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
          
        ];
    }
}
