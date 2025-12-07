<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Sensiolabs\GotenbergBundle\Enumeration\SplitMode;
use Sensiolabs\GotenbergBundle\GotenbergPdfInterface;
use Sensiolabs\GotenbergBundle\Processor\FileProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Stopwatch\Stopwatch;

final class GotenbergBundlePdfController extends AbstractController
{
    public function __construct(
        protected readonly Stopwatch                                $stopwatch,
        protected readonly Filesystem                               $filesystem,
        protected readonly LoggerInterface                          $logger,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    )
    {
    }

    #[Route('/gotenberg-bundle-pdf', name: 'generate_gotenberg_bundle_pdf')]
    public function generateGotenbergBundlePdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->html()
            ->skipNetworkIdleEvent(true)
            ->content('pdf/gotenberg-template.html.twig')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-bundle-pdf-url', name: 'generate_gotenberg_bundle_pdf_url')]
    public function generateGotenbergBundlePdfUrl(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->url()
            ->url('https://gotenberg.dev/')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-bundle-basic-docx', name: 'generate_gotenberg_bundle_basic_docx')]
    public function generateGotenbergBundleBasicDocxUrl(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/basic_docx.docx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-bundle-complex-docx', name: 'generate_gotenberg_bundle_complex_docx')]
    public function generateGotenbergBundleComplexDocxUrl(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/complex_docx.docx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-bundle-basic-pptx', name: 'generate_gotenberg_bundle_basic_pptx')]
    public function generateGotenbergBundleBasicPptxUrl(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/basic_pptx.pptx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-bundle-complex-pptx', name: 'generate_gotenberg_bundle_complex_pptx')]
    public function generateGotenbergBundleComplexPptxUrl(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/complex_pptx.pptx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }


    #[Route('/gotenberg-bundle-split-merge-pdf', name: 'split-merge_gotenberg_bundle_pdf')]
    public function splitMergeGotenbergBundlePdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $fileName = sprintf('2gotenberg_test_%s', time());
        $fileNameWithExtension = sprintf('%s.pdf', $fileName);
        $fileFolder = sprintf('%s/%s',
            $this->projectDir,
            'public/dist/export',
        );
        $filePath = sprintf('%s/%s',
            $fileFolder,
            $fileNameWithExtension,
        );
        $fileToSplitMergeName = 'pdf_to_split_merge';
        $fileToSplitMergeNameWithExtension = "$fileToSplitMergeName.pdf";
        $fileToSplitMergePath = sprintf('%s/%s/%s',
            $this->projectDir,
            'public/dist/example-files',
            $fileToSplitMergeNameWithExtension,
        );
        $fileMergedName = "merged_final_result";

        // Empty current directory.
        $finder = new Finder();
        $finder->in($fileFolder)->depth('== 0');
        foreach ($finder as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        // GENERATE.
        $this->stopwatch->start('generate_pdf', 'PDF');
        $gotenberg->html()
            ->skipNetworkIdleEvent()
            ->content('pdf/gotenberg-template.html.twig')
            ->fileName("$fileName")
            ->processor(new FileProcessor(
                $this->filesystem,
                $fileFolder,
            ))
            ->generate()
            ->process();
        $this->stopwatch->stop('generate_pdf');

        // SPLIT.
        $this->stopwatch->start('split_pdf', 'PDF');

        $pdfSplit1 = $gotenberg->split()
            ->files($fileToSplitMergePath)
            ->splitMode(SplitMode::Pages)
            ->splitSpan('1-2')
            ->splitUnify()
            ->fileName("1splited1_$fileToSplitMergeName")
            ->processor(new FileProcessor(
                $this->filesystem,
                $fileFolder,
            ))
            ->processor(new FileProcessor(
                $this->filesystem,
                $fileFolder,
            ))
            ->generate()
            ->process();

        $pdfSplit2 = $gotenberg->split()
            ->files($fileToSplitMergePath)
            ->splitMode(SplitMode::Pages)
            ->splitSpan('3')
            ->splitUnify()
            ->fileName("3splited2_$fileToSplitMergeName")
            ->processor(new FileProcessor(
                $this->filesystem,
                $fileFolder,
            ))
            ->generate()
            ->process();

        $pdfSplit1RealPath = $pdfSplit1 instanceof \SplFileInfo ? $pdfSplit1->getRealPath() : null;
        $pdfSplit2RealPath = $pdfSplit2 instanceof \SplFileInfo ? $pdfSplit2->getRealPath() : null;

        $this->stopwatch->stop('split_pdf');

        // MERGE.
        $this->stopwatch->start('merge_pdf', 'PDF');

        $pdfMerged = $gotenberg->merge()
            ->files(
                $pdfSplit1RealPath, $filePath, $pdfSplit2RealPath)
            ->fileName($fileMergedName)
            ->processor(new FileProcessor(
                $this->filesystem,
                $fileFolder,
            ))
            ->generate();

        $this->stopwatch->stop('merge_pdf');

        return $pdfMerged->stream();
    }


    #[Route('/gotenberg-bundle-webhook-pdf', name: 'webhook_generate_gotenberg_bundle_pdf')]
    public function webhookGenerateGotenbergBundlePdf(GotenbergPdfInterface $gotenberg): Response
    {
        $this->stopwatch->start('webhook_generate_pdf', 'PDF');

        $pdf = $gotenberg->html()
            ->skipNetworkIdleEvent()
            ->content('pdf/gotenberg-template.html.twig')
            ->fileName('webhook_generated');

        $pdf->generateAsync();

        $this->stopwatch->stop('webhook_generate_pdf');

        return new Response('Votre pdf est en cours de création');
    }

    #[Route('/gotenberg-bundle-webhook-success', name: 'webhook_success_gotenberg_bundle_pdf')]
    public function webhookSuccessGotenbergBundlePdf(Request $request): Response
    {
        $this->logger->info('[GOTENBERG] - [WEBHOOK] - Réception du webhook SUCCESS de Gotenberg.');

        // @todo ne fonctionne pas.
        $fileFolder = sprintf('%s/%s',
            $this->projectDir,
            'public/dist/export',
        );
        $fileMergedName = "massive_merged_final_result.pdf";

        file_put_contents("$fileFolder/$fileMergedName", $request->getContent());

        return new Response();
    }

    #[Route('/gotenberg-bundle-webhook-error', name: 'webhook_error_gotenberg_bundle_pdf')]
    public function webhookErrorGotenbergBundlePdf(Request $request): Response
    {
        $this->logger->info('[GOTENBERG] - [WEBHOOK] - Réception du webhook ERROR de Gotenberg.');

        return new Response();
    }

    #[Route('/gotenberg-bundle-webhook-merge-massive-pdf', name: 'webhook_merge_massive_gotenberg_bundle_pdf')]
    public function mergeMassiveGotenbergBundlePdf(GotenbergPdfInterface $gotenberg): Response
    {
        $fileToMergeFolderPath = sprintf('%s/%s',
            $this->projectDir,
            'public/dist/example-files',
        );
        $fileFolder = sprintf('%s/%s',
            $this->projectDir,
            'public/dist/export',
        );

        // Empty current directory.
        $finder = new Finder();
        $finder->in($fileFolder)->depth('== 0');
        foreach ($finder as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        // MERGE.
        $this->stopwatch->start('massive_merge_pdf', 'PDF');

        $pdfMerged = $gotenberg->merge()
            ->files(
                "$fileToMergeFolderPath/document-merge-1.pdf",
                "$fileToMergeFolderPath/document-merge-2.pdf",
            )
            ->generateAsync();

        $this->stopwatch->stop('massive_merge_pdf');

        return new Response('Votre pdf est en cours de création');
    }
}
