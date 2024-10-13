<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    const PROJECT_CREATE = 'project_create';
    const PROJECT_EDIT = 'project_edit';
    const PROJECT_DELETE = 'project_delete';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::PROJECT_CREATE, self::PROJECT_EDIT, self::PROJECT_DELETE])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // L'utilisateur n'est pas authentifié
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        // Récupération du rôle de l'utilisateur dans la société associée au projet
        $userCompanyRole = $this->getUserRoleInCompany($user, $project);

        switch ($attribute) {
            case self::PROJECT_CREATE:
                return $this->canCreate($userCompanyRole);
            case self::PROJECT_EDIT:
                return $this->canEdit($userCompanyRole);
            case self::PROJECT_DELETE:
                return $this->canDelete($userCompanyRole);
        }

        return false;
    }

    private function canCreate(?string $userCompanyRole): bool
    {
        // Seuls les admins ou managers peuvent créer des projets
        return in_array($userCompanyRole, ['ROLE_ADMIN', 'ROLE_MANAGER']);
    }

    private function canEdit(?string $userCompanyRole): bool
    {
        // Seuls les admins ou managers peuvent modifier un projet
        return in_array($userCompanyRole, ['ROLE_ADMIN', 'ROLE_MANAGER']);
    }

    private function canDelete(?string $userCompanyRole): bool
    {
        // Seuls les admins peuvent supprimer des projets
        return $userCompanyRole === 'ROLE_ADMIN';
    }

    private function getUserRoleInCompany(User $user, Project $project): ?string
    {
        // Récupérer la société associée au projet
        $company = $project->getCompany();

        // Retourner le rôle de l'utilisateur dans la société
        return $this->entityManager->getRepository(User::class)->findUserRoleInCompany($user, $company);
    }
}
