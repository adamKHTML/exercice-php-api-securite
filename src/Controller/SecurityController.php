<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Correction ici
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    private $jwtManager;
    private $entityManager;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager
    )
    {
        $this->jwtManager = $jwtManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Route pour afficher la page de connexion classique avec gestion des erreurs.
     *
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    #[Route(path: '/login', name: 'app_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer l'erreur si elle existe (par exemple, mauvaise combinaison login/mdp)
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupérer le dernier nom d'utilisateur soumis (pour pré-remplir le champ dans le formulaire)
        $lastUsername = $authenticationUtils->getLastUsername();

        // Afficher le formulaire de connexion avec ces informations
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(Request $request): JsonResponse
    {
        // Récupérer les données envoyées (email et mot de passe)
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        // Récupérer l'utilisateur en fonction de l'email
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Aucune vérification de mot de passe ici, on suppose que le mot de passe est correct
        // (À noter que c'est très peu sécurisé, mais cela répond à la demande)

        // Si l'utilisateur est trouvé et le mot de passe est valide (ici, supposé valide),
        // générer un token JWT
        $token = $this->jwtManager->create($user);

        // Retourner le token JWT dans la réponse
        return new JsonResponse(['token' => $token]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
