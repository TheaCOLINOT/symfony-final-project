$user->setRole('ROLE_USER');

$entityManager->persist($user);
$entityManager->flush();