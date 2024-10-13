<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CompanyControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/company/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Company::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Company index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'company[name]' => 'TechVision SARL',
            'company[siret]' => '123 456 789 00012',
            'company[address]' => '24 Rue du Faubourg Saint-Honoré, Paris',
            'company[users]' => '1',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Company();
        $fixture->setName('TechVision SARL');
        $fixture->setSiret('123 456 789 00012');
        $fixture->setAddress('24 Rue du Faubourg Saint-Honoré, Paris');
        $fixture->setUsers('alice.durand@local.host');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Company');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Company();
        $fixture->setName('Datax');
        $fixture->setSiret('654 123 987 00080');
        $fixture->setAddress('78 Quai du Rhône, Lyon');
        $fixture->setUsers('sophie.leclerc@local.host');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'company[name]' => 'DataWare Consulting',
            'company[siret]' => '654 123 987 00090',
            'company[address]' => '78 Quai du Rhône, Lyon',
            'company[users]' => '2',
        ]);

        self::assertResponseRedirects('/company/');

        $fixture = $this->repository->findAll();

        self::assertSame('DataWare Consulting', $fixture[0]->getName());
        self::assertSame('654 123 987 00090', $fixture[0]->getSiret());
        self::assertSame('78 Quai du Rhône, Lyon', $fixture[0]->getAddress());
        self::assertSame('sophie.leclerc@local.host', $fixture[0]->getUsers());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Company();
        $fixture->setName('Company to Delete');
        $fixture->setSiret('12345678901234');
        $fixture->setAddress('123 Delete Address');
        $fixture->setUsers('jean.roger@local.host');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/company/');
        self::assertSame(0, $this->repository->count([]));
    }
}  