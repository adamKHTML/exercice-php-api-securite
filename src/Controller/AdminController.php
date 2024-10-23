<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Project;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Security\Voter\ProjectVoter;


class AdminController extends AbstractController
{
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

    //Création Projet
    #[Route('/api/projects/create', name: 'admin_create_project', methods: ['POST'])]
public function createProject(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $this->denyAccessUnlessGranted(ProjectVoter::PROJECT_CREATE, new Project());
    $data = json_decode($request->getContent(), true);
    $companyId = $request->query->get('company_id');

    if (!$companyId) {
        return new JsonResponse(['error' => 'ID de la société manquant'], 400);
    }

    if (!$data || !isset($data['title']) || !isset($data['description'])) {
        return new JsonResponse(['error' => 'Données invalides ou JSON mal formé'], 400);
    }

    $project = new Project();
    $project->setTitle($data['title']);
    $project->setDescription($data['description']);
    $project->setCreatedAt(new \DateTime());

    $company = $entityManager->getRepository(Company::class)->find($companyId);
    if (!$company) {
        return new JsonResponse(['error' => 'Société introuvable'], 404);
    }

    $project->setCompany($company);
    $entityManager->persist($project);
    $entityManager->flush();

    return $this->json($this->transformProject($project), 201);
}

    //Modification Projet
    #[Route('/api/projects/edit/{projectId}', name: 'admin_update_project', methods: ['PUT'])]
    public function updateProject(int $projectId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
       
        $project = $entityManager->getRepository(Project::class)->find($projectId);
    
        if (!$project) {
            return new JsonResponse(['error' => 'Projet introuvable'], 404);
        }
    
        $this->denyAccessUnlessGranted(ProjectVoter::PROJECT_EDIT, $project);

        $data = json_decode($request->getContent(), true);
    
        if (!$data || !isset($data['title']) || !isset($data['description'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }
    
       
        $project->setTitle($data['title']);
        $project->setDescription($data['description']);
        $entityManager->persist($project);
        $entityManager->flush();
    
      
        return $this->json($this->transformProject($project));
    }

    // Supprimer Projet 
    #[Route('/api/projects/delete/{projectId}', name: 'admin_delete_project', methods: ['DELETE'])]
    public function deleteProject(int $projectId, EntityManagerInterface $entityManager): JsonResponse
    {
        $project = $entityManager->getRepository(Project::class)->find($projectId);
    
        if (!$project) {
            return new JsonResponse(['error' => 'Projet introuvable'], 404);
        }
        $this->denyAccessUnlessGranted(ProjectVoter::PROJECT_DELETE, $project);

        $entityManager->remove($project);
        $entityManager->flush();
    
        return new JsonResponse(null, 204);
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

    // Affichage des Utilisateurs
    #[Route('/api/users', name: 'get_company_users', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getUsers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $companyId = $request->query->get('company_id');
        
        if (!$companyId) {
            return new JsonResponse(['error' => 'ID de la société manquant'], 400);
        }
    
        
        $company = $entityManager->getRepository(Company::class)->find($companyId);
        if (!$company) {
            return new JsonResponse(['error' => 'Société introuvable'], 404);
        }
    
       
        $users = $company->getUsers();  
    
      
        $userData = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
               
            ];
        }, $users->toArray());
    
        return $this->json($userData);
    }
    
    //Affichage des utilisateurs non inscrit 
    #[Route('/api/users/non-company', name: 'non_company_users', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getNonCompanyUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $companyId = $request->query->get('company_id');
    
        if (!$companyId) {
            return new JsonResponse(['error' => 'ID de la société manquant'], 400);
        }
    
        
        $users = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->where('c.id IS NULL OR u.id NOT IN (
                SELECT u2.id 
                FROM App\Entity\User u2
                JOIN u2.company c2
                WHERE c2.id = :companyId
            )')
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getResult();
    
       
        $userData = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ];
        }, $users);
    
        return $this->json($userData, 200);
    }
    
    //Ajout d'utilisateurs
    #[Route('/api/users/add', name: 'add_user_to_company', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function addUserToCompany(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $userId = $data['user_id'] ?? null;
    $companyId = $data['company_id'] ?? null;

    if (!$userId || !$companyId) {
        return new JsonResponse(['error' => 'ID de l\'utilisateur ou ID de la société manquant'], 400);
    }

    $company = $entityManager->getRepository(Company::class)->find($companyId);
    if (!$company) {
        return new JsonResponse(['error' => 'Société introuvable'], 404);
    }

    $user = $userRepository->find($userId);
    if (!$user) {
        return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
    }


    $user->addCompany($company);
    $entityManager->persist($user);
    $entityManager->flush();

    return $this->json(['message' => 'Utilisateur ajouté à la société avec succès'], 201);
   
}

    
// Retirer Utilisateurs 
#[Route('/api/users/remove/{userId}', name: 'remove_user_from_company', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
public function removeUserFromCompany(int $userId, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
{
    $companyId = $request->query->get('company_id');

    if (!$companyId) {
        return new JsonResponse(['error' => 'ID de la société manquant'], 400);
    }

    $company = $entityManager->getRepository(Company::class)->find($companyId);
    if (!$company) {
        return new JsonResponse(['error' => 'Société introuvable'], 404);
    }

    $user = $userRepository->find($userId);
    if (!$user || !$user->getCompany()->contains($company)) {
        return new JsonResponse(['error' => 'Utilisateur introuvable ou n\'appartenant pas à cette société'], 404);
    }

   
    $user->removeCompany($company); 
    $entityManager->persist($user);
    $entityManager->flush();

    return new JsonResponse(['message' => 'Utilisateur retiré de la société'], 200);
}

}

