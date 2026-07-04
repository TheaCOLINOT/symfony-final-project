<?php



namespace App\Service;



use App\Entity\Cat;

use App\Entity\Reservation;

use App\Repository\ReservationRepository;



/**

 * Service qui prépare les vues planning (semaine / mois) d'un masseur chat.

 * Regroupe les réservations confirmées par jour.

 */

final class ReservationPlanningService

{

    public function __construct(

        private readonly ReservationRepository $reservationRepository,

    ) {

    }



    /**

     * Calcule la période d'une semaine à partir d'une date de référence (lundi → lundi+7).

     *

     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, reference: \DateTimeImmutable}

     */

    public function resolveWeekPeriod(?string $date): array

    {

        $reference = $this->parseReferenceDate($date);

        $start = $reference->modify('monday this week')->setTime(0, 0);

        $end = $start->modify('+7 days');



        return [

            'start' => $start,

            'end' => $end,

            'reference' => $reference,

        ];

    }



    /**

     * Calcule la période d'un mois calendaire (1er jour → 1er jour du mois suivant).

     *

     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, reference: \DateTimeImmutable}

     */

    public function resolveMonthPeriod(?string $date): array

    {

        $reference = $this->parseReferenceDate($date);

        $start = $reference->modify('first day of this month')->setTime(0, 0);

        $end = $start->modify('first day of next month');



        return [

            'start' => $start,

            'end' => $end,

            'reference' => $reference,

        ];

    }



    /**

     * Construit les 7 jours de la semaine avec leurs réservations.

     *

     * @return list<array{date: \DateTimeImmutable, label: string, reservations: list<Reservation>}>

     */

    public function buildWeekDays(Cat $cat, \DateTimeImmutable $start, \DateTimeImmutable $end): array

    {

        $reservations = $this->reservationRepository->findConfirmedByCatBetween($cat, $start, $end);

        $grouped = $this->groupByDay($reservations);



        $days = [];

        for ($offset = 0; $offset < 7; ++$offset) {

            $day = $start->modify(sprintf('+%d days', $offset));

            $key = $day->format('Y-m-d');

            $days[] = [

                'date' => $day,

                'label' => $this->formatDayLabel($day),

                'reservations' => $grouped[$key] ?? [],

            ];

        }



        return $days;

    }



    /**

     * Construit la grille du mois (avec jours du mois précédent/suivant pour remplir les semaines).

     *

     * @return list<array{date: \DateTimeImmutable, inMonth: bool, reservations: list<Reservation>}>

     */

    public function buildMonthGrid(Cat $cat, \DateTimeImmutable $start, \DateTimeImmutable $end): array

    {

        $reservations = $this->reservationRepository->findConfirmedByCatBetween($cat, $start, $end);

        $grouped = $this->groupByDay($reservations);



        // On étend aux lundis entourant le mois pour avoir des semaines complètes

        $gridStart = $start->modify('monday this week')->setTime(0, 0);

        $gridEnd = $end->modify('monday next week')->setTime(0, 0);



        $cells = [];

        for ($day = $gridStart; $day < $gridEnd; $day = $day->modify('+1 day')) {

            $key = $day->format('Y-m-d');

            $cells[] = [

                'date' => $day,

                'inMonth' => $day >= $start && $day < $end,

                'reservations' => $grouped[$key] ?? [],

            ];

        }



        return $cells;

    }



    /**

     * Regroupe les réservations par clé jour (Y-m-d).

     *

     * @param list<Reservation> $reservations

     *

     * @return array<string, list<Reservation>>

     */

    private function groupByDay(array $reservations): array

    {

        $grouped = [];

        foreach ($reservations as $reservation) {

            $date = $reservation->getReservationDate();

            if ($date === null) {

                continue;

            }



            $key = $date->format('Y-m-d');

            $grouped[$key][] = $reservation;

        }



        return $grouped;

    }



    /**

     * Parse une date au format Y-m-d ou retourne aujourd'hui si invalide.

     */

    private function parseReferenceDate(?string $date): \DateTimeImmutable

    {

        if ($date !== null && $date !== '') {

            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

            if ($parsed instanceof \DateTimeImmutable) {

                return $parsed->setTime(0, 0);

            }

        }



        return new \DateTimeImmutable('today');

    }



    /**

     * Formate un jour pour l'affichage (ex : "Lundi 04/07").

     */

    private function formatDayLabel(\DateTimeImmutable $day): string

    {

        $labels = [

            'Monday' => 'Lundi',

            'Tuesday' => 'Mardi',

            'Wednesday' => 'Mercredi',

            'Thursday' => 'Jeudi',

            'Friday' => 'Vendredi',

            'Saturday' => 'Samedi',

            'Sunday' => 'Dimanche',

        ];



        $weekday = $labels[$day->format('l')] ?? $day->format('l');



        return sprintf('%s %s', $weekday, $day->format('d/m'));

    }



    /**

     * Titre du mois en français pour la vue calendrier (ex : "Juillet 2026").

     */

    public function formatMonthTitle(\DateTimeImmutable $reference): string

    {

        $months = [

            'January' => 'Janvier',

            'February' => 'Février',

            'March' => 'Mars',

            'April' => 'Avril',

            'May' => 'Mai',

            'June' => 'Juin',

            'July' => 'Juillet',

            'August' => 'Août',

            'September' => 'Septembre',

            'October' => 'Octobre',

            'November' => 'Novembre',

            'December' => 'Décembre',

        ];



        $month = $months[$reference->format('F')] ?? $reference->format('F');



        return sprintf('%s %s', $month, $reference->format('Y'));

    }

}


