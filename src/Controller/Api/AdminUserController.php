<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Repository\UserRepository;
use App\Repository\UserProfileRepository;
use App\Repository\TestSessionRepository;
use App\Repository\CityRepository;
use App\Service\OldEtawjihiClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserProfileRepository $userProfileRepository,
        private TestSessionRepository $testSessionRepository,
        private CityRepository $cityRepository,
        private OldEtawjihiClientService $oldEtawjihiClientService,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Liste paginée des utilisateurs avec filtres.
     * Query params: page, limit, search, hasProfile, hasOrientationTest, userType, typeLycee, bacType, niveau, filiere, specialite1, specialite2, specialite3, ville.
     * Filière et spécialités 1/2/3 s'appliquent typiquement quand bacType = normal.
     */
    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $search = trim((string) $request->query->get('search', ''));
        $hasProfile = $request->query->get('hasProfile', '');
        $hasOrientationTest = $request->query->get('hasOrientationTest', '');
        $userType = trim((string) $request->query->get('userType', ''));
        $typeLycee = trim((string) $request->query->get('typeLycee', ''));
        $bacType = trim((string) $request->query->get('bacType', ''));
        $niveau = trim((string) $request->query->get('niveau', ''));
        $filiere = trim((string) $request->query->get('filiere', ''));
        $specialite1 = trim((string) $request->query->get('specialite1', ''));
        $specialite2 = trim((string) $request->query->get('specialite2', ''));
        $specialite3 = trim((string) $request->query->get('specialite3', ''));
        $ville = trim((string) $request->query->get('ville', ''));

        $qb = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.profile', 'p')
            ->leftJoin('p.ville', 'c')
            ->orderBy('u.id', 'DESC');

        if ($search !== '') {
            $qb->andWhere(
                'u.phone LIKE :search OR u.email LIKE :search OR p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search'
            )->setParameter('search', '%' . $search . '%');
        }

        if ($hasProfile === '1') {
            $qb->andWhere('p.id IS NOT NULL');
        } elseif ($hasProfile === '0') {
            $qb->andWhere('p.id IS NULL');
        }

        if ($hasOrientationTest === '1') {
            $qb->andWhere(
                'EXISTS (SELECT 1 FROM App\Entity\TestSession ts WHERE ts.user = u AND ts.testType = :otsType AND ts.isCompleted = true)'
            )->setParameter('otsType', 'orientation');
        } elseif ($hasOrientationTest === '0') {
            $qb->andWhere(
                'NOT EXISTS (SELECT 1 FROM App\Entity\TestSession ts WHERE ts.user = u AND ts.testType = :otsType AND ts.isCompleted = true)'
            )->setParameter('otsType', 'orientation');
        }

        if ($hasProfile !== '0') {
            if ($userType !== '' && \in_array($userType, ['student', 'tutor'], true)) {
                $qb->andWhere('p.userType = :userType')->setParameter('userType', $userType);
            }
            if ($typeLycee !== '' && \in_array($typeLycee, ['public', 'prive'], true)) {
                $qb->andWhere('p.typeLycee = :typeLycee')->setParameter('typeLycee', $typeLycee);
            }
            if ($bacType !== '' && \in_array($bacType, ['normal', 'mission'], true)) {
                $qb->andWhere('p.bacType = :bacType')->setParameter('bacType', $bacType);
            }
            if ($niveau !== '') {
                $qb->andWhere('p.niveau = :niveau')->setParameter('niveau', $niveau);
            }
            if ($filiere !== '') {
                $qb->andWhere('p.filiere = :filiere')->setParameter('filiere', $filiere);
            }
            if ($specialite1 !== '') {
                $qb->andWhere('p.specialite1 = :specialite1')->setParameter('specialite1', $specialite1);
            }
            if ($specialite2 !== '') {
                $qb->andWhere('p.specialite2 = :specialite2')->setParameter('specialite2', $specialite2);
            }
            if ($specialite3 !== '') {
                $qb->andWhere('p.specialite3 = :specialite3')->setParameter('specialite3', $specialite3);
            }
            if ($ville !== '') {
                $qb->andWhere('c.titre = :ville')->setParameter('ville', $ville);
            }
        }

        $total = (int) (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        $users = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $userIds = array_map(fn (User $u) => $u->getId(), $users);
        $profilesByUser = [];
        $sessionsByUser = [];
        if (!empty($userIds)) {
            $profiles = $this->userProfileRepository->createQueryBuilder('p')
                ->where('p.user IN (:ids)')
                ->setParameter('ids', $userIds)
                ->getQuery()
                ->getResult();
            foreach ($profiles as $p) {
                $profilesByUser[$p->getUser()->getId()] = $p;
            }
            // Dernière session d'orientation par user (complétée ou en cours), ordre par date desc
            $sessions = $this->testSessionRepository->createQueryBuilder('ts')
                ->where('ts.user IN (:ids)')
                ->andWhere('ts.testType = :type')
                ->setParameter('ids', $userIds)
                ->setParameter('type', 'orientation')
                ->orderBy('ts.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
            foreach ($sessions as $s) {
                $uid = $s->getUser()->getId();
                if (!isset($sessionsByUser[$uid])) {
                    $sessionsByUser[$uid] = $s; // une seule session (la plus récente) par user
                }
            }
        }

        $items = [];
        foreach ($users as $u) {
            $latestSession = $sessionsByUser[$u->getId()] ?? null;
            $items[] = $this->userToArray($u, $profilesByUser[$u->getId()] ?? null, $latestSession);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    /**
     * Statistiques : nouveaux users, comptes créés (profiles), tests réalisés
     */
    #[Route('/stats', name: 'api_admin_users_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $totalUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalProfiles = (int) $this->userProfileRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalOrientationTests = (int) $this->testSessionRepository->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.testType = :type')
            ->andWhere('ts.isCompleted = true')
            ->setParameter('type', 'orientation')
            ->getQuery()
            ->getSingleScalarResult();

        // Nouveaux utilisateurs (derniers 30 jours) — basé sur user.created_at
        $since = new \DateTimeImmutable('-30 days');
        $newUsersLast30Days = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        // Nouvelles configurations / profils (derniers 30 jours) — basé sur user_profile.created_at
        $newProfilesLast30Days = (int) $this->userProfileRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'success' => true,
            'data' => [
                'totalUsers' => $totalUsers,
                'totalProfiles' => $totalProfiles,
                'totalOrientationTests' => $totalOrientationTests,
                'newUsersLast30Days' => $newUsersLast30Days,
                'newProfilesLast30Days' => $newProfilesLast30Days,
            ],
        ]);
    }

    /**
     * Vérifie si des numéros sont clients sur old.e-tawjihi.ma.
     * Body: {"tel": ["0622073449", ...]}. Retourne {"success": true, "data": {"0622073449": {...}|null, ...}}.
     */
    #[Route('/check-old-clients', name: 'api_admin_users_check_old_clients', methods: ['POST'])]
    public function checkOldClients(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $tel = $payload['tel'] ?? null;
        if (!\is_array($tel)) {
            return new JsonResponse(['success' => false, 'message' => 'Body must contain "tel" (array of phone numbers)'], Response::HTTP_BAD_REQUEST);
        }
        $tel = array_values(array_unique(array_map('trim', array_filter($tel, fn ($t) => \is_string($t) && $t !== ''))));
        $data = $this->oldEtawjihiClientService->checkClients($tel);
        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Détail d'un utilisateur (avec profile et infos test)
     */
    #[Route('/{id}', name: 'api_admin_users_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $profile = $this->userProfileRepository->findOneBy(['user' => $user]);
        $sessions = $this->testSessionRepository->findAllByUser($user->getId(), 'orientation');
        $latestSession = $sessions[0] ?? null;
        $completedSession = null;
        foreach ($sessions as $s) {
            if ($s->isIsCompleted()) {
                $completedSession = $s;
                break;
            }
        }

        $userData = $this->userToArray($user, $profile, $latestSession);

        $userData['profile'] = null;
        if ($profile) {
            $userData['profile'] = $this->profileToArray($profile);
        }

        $userData['hasOrientationReport'] = $completedSession !== null;

        return new JsonResponse(['success' => true, 'data' => $userData]);
    }

    /**
     * Mise à jour d'un utilisateur (user + profile) par l'admin.
     */
    #[Route('/{id}', name: 'api_admin_users_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['phone']) && $data['phone'] !== null && $data['phone'] !== '') {
            $user->setPhone((string) $data['phone']);
        }
        if (array_key_exists('email', $data)) {
            $user->setEmail($data['email'] === null || $data['email'] === '' ? null : (string) $data['email']);
        }
        if (array_key_exists('isSetup', $data)) {
            $user->setIsSetup((bool) $data['isSetup']);
        }

        $plainPassword = isset($data['password']) && $data['password'] !== null && $data['password'] !== ''
            ? (string) $data['password']
            : null;
        if ($plainPassword !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $profile = $this->userProfileRepository->findOneBy(['user' => $user]);
        if (!$profile) {
            $profile = new UserProfile();
            $profile->setUser($user);
            $user->setProfile($profile);
            $profile->setCreatedAt(new \DateTime());
        }
        $profile->setUpdatedAt(new \DateTime());

        if (isset($data['userType'])) $profile->setUserType($data['userType'] === '' ? null : (string) $data['userType']);
        if (isset($data['niveau'])) $profile->setNiveau($data['niveau'] === '' ? null : (string) $data['niveau']);
        if (isset($data['bacType'])) $profile->setBacType($data['bacType'] === '' ? null : (string) $data['bacType']);
        if (isset($data['filiere'])) $profile->setFiliere($data['filiere'] === '' ? null : (string) $data['filiere']);
        if (isset($data['specialite1'])) $profile->setSpecialite1($data['specialite1'] === '' ? null : (string) $data['specialite1']);
        if (isset($data['specialite2'])) $profile->setSpecialite2($data['specialite2'] === '' ? null : (string) $data['specialite2']);
        if (isset($data['specialite3'])) $profile->setSpecialite3($data['specialite3'] === '' ? null : (string) $data['specialite3']);
        if (isset($data['diplomeEnCours'])) $profile->setDiplomeEnCours($data['diplomeEnCours'] === '' ? null : (string) $data['diplomeEnCours']);
        if (isset($data['nomEtablissement'])) $profile->setNomEtablissement($data['nomEtablissement'] === '' ? null : (string) $data['nomEtablissement']);
        if (isset($data['typeLycee'])) $profile->setTypeLycee($data['typeLycee'] === '' ? null : (string) $data['typeLycee']);
        if (isset($data['nom'])) $profile->setNom($data['nom'] === '' ? null : (string) $data['nom']);
        if (isset($data['prenom'])) $profile->setPrenom($data['prenom'] === '' ? null : (string) $data['prenom']);
        if (isset($data['genre'])) $profile->setGenre($data['genre'] === '' ? null : (string) $data['genre']);
        if (isset($data['tuteur'])) $profile->setTuteur($data['tuteur'] === '' ? null : (string) $data['tuteur']);
        if (isset($data['nomTuteur'])) $profile->setNomTuteur($data['nomTuteur'] === '' ? null : (string) $data['nomTuteur']);
        if (isset($data['prenomTuteur'])) $profile->setPrenomTuteur($data['prenomTuteur'] === '' ? null : (string) $data['prenomTuteur']);
        if (isset($data['telTuteur'])) $profile->setTelTuteur($data['telTuteur'] === '' ? null : (string) $data['telTuteur']);
        if (isset($data['professionTuteur'])) $profile->setProfessionTuteur($data['professionTuteur'] === '' ? null : (string) $data['professionTuteur']);
        if (isset($data['adresseTuteur'])) $profile->setAdresseTuteur($data['adresseTuteur'] === '' ? null : (string) $data['adresseTuteur']);
        if (array_key_exists('consentContact', $data)) $profile->setConsentContact((bool) $data['consentContact']);

        if (isset($data['email'])) {
            $email = $data['email'] === null || $data['email'] === '' ? null : (string) $data['email'];
            $profile->setEmail($email);
            $user->setEmail($email);
        }
        if (isset($data['dateNaissance'])) {
            if ($data['dateNaissance'] === null || $data['dateNaissance'] === '') {
                $profile->setDateNaissance(null);
            } else {
                try {
                    $profile->setDateNaissance(new \DateTime((string) $data['dateNaissance']));
                } catch (\Throwable $e) {
                    // ignore invalid date
                }
            }
        }

        if (isset($data['ville'])) {
            if ($data['ville'] === null || $data['ville'] === '') {
                $profile->setVille(null);
            } elseif (is_numeric($data['ville'])) {
                $city = $this->cityRepository->find((int) $data['ville']);
                if ($city) {
                    $profile->setVille($city);
                }
            }
        }

        if (!empty($data['typeEcolePrefere']) && \is_array($data['typeEcolePrefere'])) {
            $profile->setTypeEcolePrefere($data['typeEcolePrefere']);
        }
        if (!empty($data['servicesPrefere']) && \is_array($data['servicesPrefere'])) {
            $profile->setServicesPrefere($data['servicesPrefere']);
        }

        $this->em->persist($profile);
        $this->em->persist($user);
        $this->em->flush();

        $profile = $this->userProfileRepository->findOneBy(['user' => $user]);
        $sessions = $this->testSessionRepository->findAllByUser($user->getId(), 'orientation');
        $latestSession = $sessions[0] ?? null;
        $completedSession = null;
        foreach ($sessions as $s) {
            if ($s->isIsCompleted()) {
                $completedSession = $s;
                break;
            }
        }
        $userData = $this->userToArray($user, $profile, $latestSession);
        $userData['profile'] = $profile ? $this->profileToArray($profile) : null;
        $userData['hasOrientationReport'] = $completedSession !== null;

        return new JsonResponse(['success' => true, 'data' => $userData]);
    }

    /**
     * Rapport d'orientation d'un utilisateur (même format que orientation-test/get-all)
     */
    #[Route('/{id}/orientation-report', name: 'api_admin_users_orientation_report', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function orientationReport(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
        if (!$session || !$session->isIsCompleted()) {
            return new JsonResponse([
                'success' => true,
                'hasTest' => false,
                'data' => null,
                'message' => 'Aucun rapport d\'orientation complété pour cet utilisateur',
            ]);
        }

        $currentStep = $session->getCurrentStep();
        $metadata = $session->getMetadata();
        $totalDuration = 0;
        if (isset($metadata['stepDurations'])) {
            $totalDuration = array_sum($metadata['stepDurations']);
        }

        $data = [
            'currentStep' => $currentStep['currentStep'] ?? null,
            'isCompleted' => $session->isIsCompleted(),
            'startedAt' => $session->getStartedAt()?->format('Y-m-d H:i:s'),
            'completedAt' => $session->getCompletedAt()?->format('Y-m-d H:i:s'),
            'personalInfo' => $currentStep['personalInfo'] ?? null,
            'riasec' => $currentStep['riasec'] ?? null,
            'personality' => $currentStep['personality'] ?? null,
            'aptitude' => $currentStep['aptitude'] ?? null,
            'interests' => $currentStep['interests'] ?? null,
            'career' => $currentStep['career'] ?? $currentStep['careerCompatibility'] ?? null,
            'constraints' => $currentStep['constraints'] ?? null,
            'languages' => $currentStep['languageSkills'] ?? $currentStep['languages'] ?? null,
            'testMetadata' => $metadata,
            'totalDuration' => $totalDuration,
            'currentStepData' => $currentStep,
        ];

        return new JsonResponse([
            'success' => true,
            'hasTest' => true,
            'data' => $data,
            'sessionId' => $session->getId(),
        ]);
    }

    /**
     * @param \App\Entity\TestSession|null $latestOrientationSession Dernière session d'orientation (complétée ou en cours)
     */
    private function userToArray(User $u, $profile, $latestOrientationSession): array
    {
        $hasProfile = $profile !== null;
        $hasOrientationTest = $latestOrientationSession !== null && $latestOrientationSession->isIsCompleted();
        $orientationCompletedAt = $hasOrientationTest ? $latestOrientationSession->getCompletedAt()?->format('Y-m-d H:i:s') : null;
        $orientationCurrentStep = null;
        if ($latestOrientationSession !== null && !$latestOrientationSession->isIsCompleted()) {
            $currentStepData = $latestOrientationSession->getCurrentStep();
            $orientationCurrentStep = \is_array($currentStepData) && isset($currentStepData['currentStep'])
                ? (string) $currentStepData['currentStep']
                : null;
        }

        $data = [
            'id' => $u->getId(),
            'phone' => $u->getPhone(),
            'email' => $u->getEmail(),
            'roles' => $u->getRoles(),
            'isSetup' => $u->getIsSetup(),
            'createdAt' => $u->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $u->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'hasProfile' => $hasProfile,
            'hasOrientationTest' => $hasOrientationTest,
            'orientationCompletedAt' => $orientationCompletedAt,
            'orientationCurrentStep' => $orientationCurrentStep,
        ];

        if ($profile !== null) {
            $data['profile'] = [
                'id' => $profile->getId(),
                'nom' => $profile->getNom(),
                'prenom' => $profile->getPrenom(),
                'niveau' => $profile->getNiveau(),
                'bacType' => $profile->getBacType(),
                'filiere' => $profile->getFiliere(),
                'ville' => $profile->getVille()?->getTitre(),
                'email' => $profile->getEmail(),
                'dateNaissance' => $profile->getDateNaissance()?->format('Y-m-d'),
                'genre' => $profile->getGenre(),
                'userType' => $profile->getUserType(),
                'specialite1' => $profile->getSpecialite1(),
                'specialite2' => $profile->getSpecialite2(),
                'specialite3' => $profile->getSpecialite3(),
                'typeLycee' => $profile->getTypeLycee(),
                'planReussiteSteps' => $profile->getPlanReussiteSteps(),
            ];
        } else {
            $data['profile'] = null;
        }

        return $data;
    }

    private function profileToArray(UserProfile $profile): array
    {
        $ville = $profile->getVille();
        return [
            'id' => $profile->getId(),
            'userType' => $profile->getUserType(),
            'niveau' => $profile->getNiveau(),
            'bacType' => $profile->getBacType(),
            'filiere' => $profile->getFiliere(),
            'specialite1' => $profile->getSpecialite1(),
            'specialite2' => $profile->getSpecialite2(),
            'specialite3' => $profile->getSpecialite3(),
            'nom' => $profile->getNom(),
            'prenom' => $profile->getPrenom(),
            'email' => $profile->getEmail(),
            'dateNaissance' => $profile->getDateNaissance()?->format('Y-m-d'),
            'genre' => $profile->getGenre(),
            'ville' => $ville?->getTitre(),
            'villeId' => $ville?->getId(),
            'typeLycee' => $profile->getTypeLycee(),
            'diplomeEnCours' => $profile->getDiplomeEnCours(),
            'nomEtablissement' => $profile->getNomEtablissement(),
            'tuteur' => $profile->getTuteur(),
            'nomTuteur' => $profile->getNomTuteur(),
            'prenomTuteur' => $profile->getPrenomTuteur(),
            'telTuteur' => $profile->getTelTuteur(),
            'professionTuteur' => $profile->getProfessionTuteur(),
            'adresseTuteur' => $profile->getAdresseTuteur(),
            'consentContact' => $profile->getConsentContact(),
            'typeEcolePrefere' => $profile->getTypeEcolePrefere(),
            'servicesPrefere' => $profile->getServicesPrefere(),
            'planReussiteSteps' => $profile->getPlanReussiteSteps(),
            'createdAt' => $profile->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $profile->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
