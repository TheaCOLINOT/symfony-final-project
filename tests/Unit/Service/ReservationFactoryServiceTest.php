<?php

namespace App\Tests\Unit\Service;

use App\Enum\ReservationStatus;
use App\Service\ReservationFactoryService;
use App\Tests\Unit\TestEntityBuilderTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests de création / validation des réservations.
 */
class ReservationFactoryServiceTest extends TestCase
{
    use TestEntityBuilderTrait;

    private ReservationFactoryService $service;

    protected function setUp(): void
    {
        $this->service = new ReservationFactoryService();
    }

    public function testCreerReservationLiveChat(): void
    {
        $user = $this->createUser();
        $service = $this->createRemoteLiveChatService();
        $location = $this->createRemoteLocation();
        $cat = $this->createCat();
        $date = new \DateTimeImmutable('2026-07-05 14:00:00');

        $reservation = $this->service->create($user, $service, $location, $cat, $date);

        $this->assertSame($user, $reservation->getUser());
        $this->assertSame($service, $reservation->getService());
        $this->assertSame($location, $reservation->getLocation());
        $this->assertSame('Live chat avec masseur chat', $reservation->getServiceLabel());
        $this->assertSame('Relaxant (Siamois)', $reservation->getCatLabel());
        $this->assertSame(15, $reservation->getPrice());
        $this->assertSame(ReservationStatus::Confirmed, $reservation->getStatus());
        $this->assertTrue($reservation->getCats()->contains($cat));
    }

    public function testRefusePrestationSalonSurLieuDistance(): void
    {
        $service = $this->createClassicService();
        $location = $this->createRemoteLocation();
        $cat = $this->createCat();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Cette prestation n\'est pas disponible.');

        $this->service->assertOfferAvailable($service, $location, $cat);
    }

    public function testRefuseLiveChatSansLieuDistance(): void
    {
        $service = $this->createRemoteLiveChatService();
        $location = $this->createSalonLocation();
        $cat = $this->createCat();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Cette prestation se fait uniquement à distance.');

        $this->service->assertRemoteOfferAvailable($service, $location, $cat);
    }

    public function testRefuseMasseurQuiNeProposePasLaPrestation(): void
    {
        $service = $this->createClassicService();
        $location = $this->createSalonLocation();
        $cat = $this->createCat();
        $cat->addLocation($location);
        // le chat n'a pas coché le service dans service_cat

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Ce masseur chat ne propose pas cette prestation.');

        $this->service->assertOfferAvailable($service, $location, $cat);
    }

    public function testAccepteLiveChatPourNimporteQuelChat(): void
    {
        $service = $this->createRemoteLiveChatService();
        $location = $this->createRemoteLocation();
        $cat = $this->createCat();

        // Ne doit pas lever d'exception même sans salon ni prestation cochée
        $this->service->assertRemoteOfferAvailable($service, $location, $cat);

        $this->assertTrue(true);
    }
}
