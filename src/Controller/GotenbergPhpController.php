<?php

namespace App\Controller;

use Gotenberg\Gotenberg;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Stopwatch\Stopwatch;

final class GotenbergPhpController extends AbstractController
{
    public function __construct(
        protected readonly Stopwatch $stopwatch,
    )
    {
    }

    #[Route('/gotenberg-php-pdf', name: 'generate_gotenberg_php_pdf')]
    public function generateGotenbergPhpPdf(): BinaryFileResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $filepath = '/tmp';
        $filename = Gotenberg::save(
            Gotenberg::chromium('http://gotenberg:3000')->pdf()->url('https://gotenberg.dev/'),
            $filepath
        );

        $this->stopwatch->stop('generate_pdf');

        return new BinaryFileResponse("$filepath/$filename");
    }
}
