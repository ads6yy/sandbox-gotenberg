<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Stopwatch\Stopwatch;

final class MainController extends AbstractController
{
    public function __construct(
        protected readonly Stopwatch $stopwatch,
    )
    {
    }


    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/template', name: 'template_pdf')]
    public function template(): Response
    {
        return $this->render('pdf/template.html.twig');
    }
}
