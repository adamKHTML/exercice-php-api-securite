<?php

namespace App\Tests\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProjectControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/project/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Project::class);

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
        self::assertPageTitleContains('Project index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'project[title]' => 'Test Project',
            'project[description]' => 'This is a test project description.',
            'project[createdAt]' => (new \DateTime())->format('Y-m-d H:i:s'),
            'project[company]' => '2',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setTitle('My Test Project');
        $fixture->setDescription('This is a test project description.');
        $fixture->setCreatedAt(new \DateTime());
        $fixture->setCompany('DataWare Consulting');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Project');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setTitle('My Test Project');
        $fixture->setDescription('This is a test project description.');
        $fixture->setCreatedAt(new \DateTime());
        $fixture->setCompany('DataWare Consulting');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'project[title]' => 'New Project 2',
            'project[description]' => 'We got a fresh description Ronnie',
            'project[createdAt]' => (new \DateTime())->format('Y-m-d H:i:s'),
            'project[company]' => '2',
        ]);

        self::assertResponseRedirects('/project/');

        $fixture = $this->repository->findAll();

        self::assertSame('New Project 2', $fixture[0]->getTitle());
        self::assertSame('We got a fresh description Ronnie', $fixture[0]->getDescription());
        self::assertSame((new \DateTime()), $fixture[0]->getCreatedAt());
        self::assertSame('DataWare Consulting', $fixture[0]->getCompany());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setTitle('New Project 2');
        $fixture->setDescription('We got a fresh description Ronnie');
        $fixture->setCreatedAt(new \DateTime());
        $fixture->setCompany('DataWare Consulting');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/project/');
        self::assertSame(0, $this->repository->count([]));
    }
}