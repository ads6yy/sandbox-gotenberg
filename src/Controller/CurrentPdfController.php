<?php

namespace App\Controller;

use Dompdf\Dompdf;
use iio\libmergepdf\Merger;
use iio\libmergepdf\Pages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Stopwatch\Stopwatch;

final class CurrentPdfController extends AbstractController
{
    public function __construct(
        protected readonly Stopwatch                                $stopwatch,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    )
    {
    }

    #[Route('/dom-pdf', name: 'generate_dom_pdf')]
    public function generateDomPdf(): void
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $content = $this->renderView('pdf/template.html.twig');

        // PDF generation via DomPDF.
        $dompdf = new DomPdf();
        $dompdf->loadHtml($content);
        // (Optional) Set up the paper size and orientation
        $dompdf->setPaper('A4');
        // Render the HTML as PDF
        $dompdf->render();

        $this->stopwatch->stop('generate_pdf');

        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    #[Route('/dom-pdf-split-merge', name: 'split_dom_pdf')]
    public function splitMergeDomPdf(): Response
    {
        $fileName = sprintf('test_%s.pdf', time());
        $filePath = sprintf('%s/%s/%s',
            $this->projectDir,
            'public/dist/export',
            $fileName,
        );
        $fileToSplitMergeName = 'pdf_to_split_merge.pdf';
        $fileToSplitMergePath = sprintf('%s/%s/%s',
            $this->projectDir,
            'public/dist/example-files',
            $fileToSplitMergeName,
        );
        $fileMergedPath = sprintf('%s/%s/merged_%s',
            $this->projectDir,
            'public/dist/export',
            $fileName,
        );

        // GENERATE.
        $this->stopwatch->start('generate_pdf', 'PDF');

        $content = $this->renderView('pdf/template.html.twig');
        $dompdf = new DomPdf();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents(
            $filePath,
            $output,
        );

        $this->stopwatch->stop('generate_pdf');

        // SPLIT & MERGE.
        $this->stopwatch->start('split_merge_pdf', 'PDF');

        $merger = new Merger();
        $merger->addFile($filePath, new Pages('1-2'));
        $merger->addFile($fileToSplitMergePath, new Pages('3'));
        $merger->addFile($filePath, new Pages('3-4'));
        $merger->addFile($fileToSplitMergePath, new Pages('1-2'));
        $mergedPdfsOutput = $merger->merge();
        file_put_contents(
            $fileMergedPath,
            $mergedPdfsOutput,
        );

        $this->stopwatch->stop('split_merge_pdf');

        return new BinaryFileResponse($fileMergedPath);
    }
}
