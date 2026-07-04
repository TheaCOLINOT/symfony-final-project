<?php
namespace App;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
    /**
     * Environnements Symfony autorisés pour APP_ENV (prod, dev, test).
     *
     * @return list<string>
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
