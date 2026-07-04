<?php



namespace App\Security\Voter;



use App\Entity\User;

use App\Service\ManagerContextService;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;



/**

 * Voter Symfony : vérifie si l'utilisateur connecté est administrateur global.

 *

 * @extends Voter<string, mixed>

 */

final class GlobalAdminVoter extends Voter

{

    // Attribut utilisé dans les contrôleurs : $this->denyAccessUnlessGranted('GLOBAL_ADMIN')

    public const GLOBAL_ADMIN = 'GLOBAL_ADMIN';



    public function __construct(

        private readonly ManagerContextService $managerContextService,

    ) {

    }



    protected function supports(string $attribute, mixed $subject): bool

    {

        // Ce voter ne s'occupe que de l'attribut GLOBAL_ADMIN

        return $attribute === self::GLOBAL_ADMIN;

    }



    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool

    {

        $user = $token->getUser();



        // Utilisateur anonyme ou non reconnu → refus

        if (!$user instanceof User) {

            return false;

        }



        // Délègue la logique métier au service (manager avec isAdmin sur la localisation globale)

        return $this->managerContextService->isGlobalAdmin($user);

    }

}

