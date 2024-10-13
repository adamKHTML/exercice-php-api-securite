<?php

namespace App\Security\Voter;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CompanyVoter extends Voter
{
    const COMPANY_VIEW = 'company_view';
    const COMPANY_EDIT = 'company_edit';
    const COMPANY_DELETE = 'company_delete';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::COMPANY_VIEW, self::COMPANY_EDIT, self::COMPANY_DELETE])
            && $subject instanceof Company;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // L'utilisateur n'est pas authentifié
            return false;
        }

        /** @var Company $company */
        $company = $subject;

        // Récupération du rôle de l'utilisateur dans la société
        $userCompanyRole = $this->getUserRoleInCompany($user, $company);

        switch ($attribute) {
            case self::COMPANY_VIEW:
                return $this->canView($userCompanyRole);
            case self::COMPANY_EDIT:
                return $this->canEdit($userCompanyRole);
            case self::COMPANY_DELETE:
                return $this->canDelete($userCompanyRole);
        }

        return false;
    }

    private function canView(?string $userCompanyRole): bool
    {
        // Tous les rôles peuvent consulter une société
        return $userCompanyRole !== null;
    }

    private function canEdit(?string $userCompanyRole): bool
    {
        // Seuls les admins et managers peuvent éditer une société
        return in_array($userCompanyRole, ['ROLE_ADMIN', 'ROLE_MANAGER']);
    }

    private function canDelete(?string $userCompanyRole): bool
    {
        // Seuls les admins peuvent supprimer une société
        return $userCompanyRole === 'ROLE_ADMIN';
    }

    private function getUserRoleInCompany(User $user, Company $company): ?string
    {
        // On suppose que l'entité User a une relation avec la société (UserCompany) et que cette méthode
        // renvoie le rôle de l'utilisateur dans la société en question.
        return $this->entityManager->getRepository(User::class)->findUserRoleInCompany($user, $company);
    }
}
