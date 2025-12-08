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

    #[Route('/gotenberg-symfony-bundle-generate-twig-pdf', name: 'gotenberg_symfony_bundle_generate_twig_pdf')]
    public function gotenbergSymfonyBundleGenerateTwigPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->html()
            ->skipNetworkIdleEvent(true)
            ->content('pdf/gotenberg-template.html.twig')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-url-pdf', name: 'gotenberg_symfony_bundle_generate_url_pdf')]
    public function gotenbergSymfonyBundleGenerateUrlPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->url()
            ->url('https://gotenberg.dev/')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-basic-docx-pdf', name: 'gotenberg_symfony_bundle_generate_basic_docx_pdf')]
    public function gotenbergSymfonyBundleGenerateBasicDocxPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/basic_docx.docx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-complex-docx-pdf', name: 'gotenberg_symfony_bundle_generate_complex_docx_pdf')]
    public function gotenbergSymfonyBundleGenerateComplexDocxPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/complex_docx.docx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-basic-pptx-pdf', name: 'gotenberg_symfony_bundle_generate_basic_pptx_pdf')]
    public function gotenbergSymfonyBundleGenerateBasicPptxPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/basic_pptx.pptx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-complex-pptx-pdf', name: 'gotenberg_symfony_bundle_generate_complex_pptx_pdf')]
    public function gotenbergSymfonyBundleGenerateComplexPptxPdf(GotenbergPdfInterface $gotenberg): StreamedResponse
    {
        $this->stopwatch->start('generate_pdf', 'PDF');

        $pdf = $gotenberg->office()
            ->files('/srv/public/dist/example-files/complex_pptx.pptx')
            ->generate();

        $this->stopwatch->stop('generate_pdf');

        return $pdf->stream();
    }

    #[Route('/gotenberg-symfony-bundle-generate-twig-split-merge-pdf', name: 'gotenberg_symfony_bundle_generate_twig_split_merge_pdf')]
    public function gotenbergSymfonyBundleGenerateTwigSplitMergePdf(GotenbergPdfInterface $gotenberg): StreamedResponse
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

    #[Route('/gotenberg-symfony-bundle-generate-twig-async-pdf', name: 'gotenberg_symfony_bundle_generate_twig_async_pdf')]
    public function gotenbergSymfonyBundleGenerateTwigAsyncPdf(GotenbergPdfInterface $gotenberg): Response
    {
        $this->stopwatch->start('webhook_generate_pdf', 'PDF');

        $pdf = $gotenberg->html()
            ->webhookConfiguration('webhook_pdf')
            ->skipNetworkIdleEvent()
            ->content('pdf/gotenberg-template.html.twig')
            ->fileName('webhook_generated');

        $pdf->generateAsync();

        $this->stopwatch->stop('webhook_generate_pdf');

        return new Response('Votre pdf est en cours de création');
    }

    #[Route('/gotenberg-symfony-bundle-webhook-success-pdf', name: 'gotenberg_symfony_bundle_webhook_success_pdf')]
    public function gotenbergSymfonyBundleWebhookSuccessPdf(Request $request): Response
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

    #[Route('/gotenberg-symfony-bundle-webhook-error-pdf', name: 'gotenberg_symfony_bundle_webhook_error_pdf')]
    public function gotenbergSymfonyBundleWebhookErrorPdf(Request $request): Response
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
