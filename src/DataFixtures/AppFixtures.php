<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Company;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Création des sociétés
        $company1 = new Company();
        $company1->setName('TechVision SARL')
                 ->setSiret('123 456 789 00012')
                 ->setAddress('24 Rue du Faubourg Saint-Honoré, Paris');
        $manager->persist($company1);

        $company2 = new Company();
        $company2->setName('Innovatech Industries')
                 ->setSiret('987 654 321 00034')
                 ->setAddress('8 Avenue des Champs-Élysées, Paris');
        $manager->persist($company2);

        $company3 = new Company();
        $company3->setName('Ecom Solutions')
                 ->setSiret('321 654 987 00056')
                 ->setAddress('56 Rue de la République, Lyon');
        $manager->persist($company3);

        $company4 = new Company();
        $company4->setName('Green Energy Group')
                 ->setSiret('456 987 123 00078')
                 ->setAddress('12 Boulevard de l\'Europe, Lille');
        $manager->persist($company4);

        $company5 = new Company();
        $company5->setName('DataWare Consulting')
                 ->setSiret('654 123 987 00090')
                 ->setAddress('78 Quai du Rhône, Lyon');
        $manager->persist($company5);

        // Création des utilisateurs (pas besoin de mot de passe, nous avons juste l'email)
        $users = [
            ['user1@local.host', ['admin', 'manager']],
            ['user2@local.host', ['manager', 'consultant']],
            ['bauch.nya@hotmail.com', ['manager', 'consultant']],
            ['maryam.kshlerin@hotmail.com', ['consultant', 'manager']],
            ['loyal.harvey@gmail.com', ['admin', 'manager']],
            ['fisher.nicole@mayer.com', ['consultant', 'manager']],
            ['cheyanne.runolfsson@hotmail.com', ['consultant', 'manager']],
            ['bhomenick@purdy.com', ['manager', 'consultant']],
            ['vince35@crona.com', ['manager', 'consultant']],
            ['elenor05@hansen.com', ['admin', 'manager']],
            ['opal34@oreilly.org', ['consultant', 'manager']],
            ['taya05@bahringer.com', ['manager', 'admin']],
        ];

        // Associer les utilisateurs aux sociétés avec les rôles appropriés
        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData[0])
                 ->setPassword('$2y$13$j4TIcq7tBOt0e4/s0DtCBOsAfXyo4Rq7oADHbNwxgc2JYgy.yWvSu'); 
            
            $manager->persist($user);

            // Assignation des rôles
            foreach ($userData[1] as $role) {
                if ($role == 'admin') {
                    $user->addCompany($company1);  
                } elseif ($role == 'manager') {
                    $user->addCompany($company2); 
                } elseif ($role == 'consultant') {
                    $user->addCompany($company3);  
                }
            }
        }

        // Sauvegarder les entités dans la base de données
        $manager->flush();
    }
}
