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

    #[Route('/gotenberg-php-client-generate-url-pdf', name: 'gotenberg_php_client_generate_url_pdf')]
    public function gotenbergPhpClientGenerateUrlPdf(): BinaryFileResponse
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
