<?php

namespace App\Security\Voter;

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

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [self::PROJECT_CREATE, self::PROJECT_EDIT, self::PROJECT_DELETE]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::PROJECT_CREATE:
                return $this->canCreate($user);
            case self::PROJECT_EDIT:
                return $this->canEdit($user, $subject);
            case self::PROJECT_DELETE:
                return $this->canDelete($user, $subject);
        }

        return false;
    }

    private function canCreate(User $user): bool
    {
        // Seuls les admins ou managers peuvent créer des projets
        return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MANAGER', $user->getRoles());
    }

    private function canEdit(User $user, $project): bool
    {
       // Seuls les admins ou managers peuvent modifier des projets
        return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MANAGER', $user->getRoles());
    }

    private function canDelete(User $user, $project): bool
    {
        // Seuls les admins ou managers peuvent supprimer des projets
        return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MANAGER', $user->getRoles());
    }
}
