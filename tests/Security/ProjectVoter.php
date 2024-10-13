<?php

namespace App\Tests\Security;

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\User;
use App\Security\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProjectVoterTest extends KernelTestCase
{
    private ProjectVoter $voter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->voter = self::$container->get(ProjectVoter::class);
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
        $project = new Project();
        $project->setCompany($company);

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur peut voir le projet
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $project, ['PROJECT_VIEW']));
    }

    public function testVoteOnEditGrantedForManager(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'manager'); // L'utilisateur est manager dans la société
        $project = new Project();
        $project->setCompany($company);

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur peut modifier le projet
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $project, ['PROJECT_EDIT']));
    }

    public function testVoteOnEditDeniedForConsultant(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'consultant'); // L'utilisateur est consultant dans la société
        $project = new Project();
        $project->setCompany($company);

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur ne peut pas modifier le projet
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $project, ['PROJECT_EDIT']));
    }

    public function testVoteOnDeleteGrantedForAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company();
        $company->addUser($user, 'admin'); // L'utilisateur est admin dans la société
        $project = new Project();
        $project->setCompany($company);

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur peut supprimer le projet
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $project, ['PROJECT_DELETE']));
    }

    public function testVoteOnViewDeniedForNonMember(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $company = new Company(); // Société différente ou l'utilisateur n'est pas membre
        $project = new Project();
        $project->setCompany($company);

        $token = $this->createToken($user);

        // Vérifiez que l'utilisateur ne peut pas voir le projet
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $project, ['PROJECT_VIEW']));
    }
}
