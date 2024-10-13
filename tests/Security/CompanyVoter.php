<?php

namespace App\Tests\Security;

use App\Entity\Company;
use App\Entity\User;
use App\Security\CompanyVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CompanyVoterTest extends KernelTestCase
{
    private CompanyVoter $voter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->voter = self::$container->get(CompanyVoter::class);
    }

    private function createToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());
    }

    public function testVoteOnViewGranted(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'consultant'); // L'utilisateur est consultant dans la société

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur peut voir la société
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $company, ['COMPANY_VIEW']));
    }

    public function testVoteOnAddUserGrantedForAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'admin'); // L'utilisateur est admin dans la société

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur peut ajouter des utilisateurs à la société
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $company, ['COMPANY_ADD_USER']));
    }

    public function testVoteOnAddUserDeniedForNonAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'manager'); // L'utilisateur est manager, pas admin

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur ne peut pas ajouter d'utilisateurs à la société
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $company, ['COMPANY_ADD_USER']));
    }

    public function testVoteOnViewDeniedForNonMember(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company(); // Société où l'utilisateur n'est pas membre

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur ne peut pas voir la société
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $company, ['COMPANY_VIEW']));
    }
}
