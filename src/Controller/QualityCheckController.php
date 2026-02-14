<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\QualityCheckStartType;
use App\Service\NormCatalog;
use App\Service\QualityCheckSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/quality-check', name: 'quality_check')]
final class QualityCheckController extends AbstractController
{
    public function __construct(
        private QualityCheckSession $qcSession,
        private NormCatalog $normCatalog,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('/', name: '_start', methods: ['GET', 'POST'])]
    public function start(Request $request): Response
    {
        $form = $this->createForm(QualityCheckStartType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Initialize session with security migration
            $this->qcSession->start($data['shiftManager'], $data['crew']);

            return $this->redirectToRoute('quality_check_selection');
        }

        return $this->render('quality_check/start.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/selection', name: '_selection', methods: ['GET', 'POST'])]
    public function selection(Request $request): Response
    {
        // Guard: Must have started a session
        if (!$this->qcSession->hasShiftInfo()) {
            return $this->redirectToRoute('quality_check_start');
        }

        if ($request->isMethod('POST')) {
            $token = new CsrfToken('qc_selection', $request->request->get('_token'));
            if (!$this->csrfTokenManager->isTokenValid($token)) {
                $this->addFlash('error', 'Ongeldige sessie status (CSRF). Probeer het opnieuw.');
                return $this->redirectToRoute('quality_check_selection');
            }

            $selectedIds = $request->request->all('products') ?? [];

            if (empty($selectedIds)) {
                $this->addFlash('error', 'Selecteer ten minste één product.');
            } else {
                $this->qcSession->updateSelection($selectedIds);
                return $this->redirectToRoute('quality_check_measure');
            }
        }

        // Generate token for view
        $csrfToken = $this->csrfTokenManager->refreshToken('qc_selection');

        return $this->render('quality_check/selection.html.twig', [
            'groupedProducts' => $this->normCatalog->getProductsGroupedByCategory(),
            'currentSelection' => $this->qcSession->getSelection(),
            'csrf_token' => $csrfToken->getValue(),
        ]);
    }

    #[Route('/measure', name: '_measure', methods: ['GET', 'POST'])]
    public function measure(Request $request): Response
    {
        if (!$this->qcSession->hasShiftInfo()) {
            return $this->redirectToRoute('quality_check_start');
        }
        if (!$this->qcSession->hasSelection()) {
            return $this->redirectToRoute('quality_check_selection');
        }

        $selection = $this->qcSession->getSelection();
        $productsData = $this->normCatalog->getNormsForProducts($selection);

        if ($request->isMethod('POST')) {
            $token = new CsrfToken('qc_measure', $request->request->get('_token'));
            if (!$this->csrfTokenManager->isTokenValid($token)) {
                 $this->addFlash('error', 'Ongeldige sessie status (CSRF). Probeer het opnieuw.');
                 return $this->redirectToRoute('quality_check_measure');
            }

            $measurements = $request->request->all('measurements');

            // Basic validation: ensure we have data
            if (empty($measurements)) {
                $this->addFlash('error', 'Vul de metingen in.');
            } else {
                $this->qcSession->saveMeasurements($measurements);
                return $this->redirectToRoute('quality_check_result');
            }
        }

        $csrfToken = $this->csrfTokenManager->refreshToken('qc_measure');

        return $this->render('quality_check/measure.html.twig', [
            'products' => $productsData,
            'csrf_token' => $csrfToken->getValue(),
        ]);
    }

    #[Route('/result', name: '_result', methods: ['GET'])]
    public function result(): Response
    {
        if (!$this->qcSession->hasMeasurements()) {
            return $this->redirectToRoute('quality_check_start');
        }

        $reportData = $this->qcSession->getReportData();
        $clipboardText = $this->qcSession->buildCtMessage();
        $csrfToken = $this->csrfTokenManager->refreshToken('qc_reset');

        return $this->render('quality_check/result.html.twig', [
            'report' => $reportData,
            'clipboardText' => $clipboardText,
            'csrf_token' => $csrfToken->getValue(),
        ]);
    }

    #[Route('/message/{type}', name: '_message', methods: ['GET'], requirements: ['type' => 'crew|ct'])]
    public function message(string $type): Response
    {
        if (!$this->qcSession->hasShiftInfo() || !$this->qcSession->hasSelection() || !$this->qcSession->hasMeasurements()) {
            return $this->redirectToRoute('quality_check_start');
        }

        $text = match ($type) {
            'crew' => $this->qcSession->buildCrewMessage(),
            'ct' => $this->qcSession->buildCtMessage(),
            default => throw $this->createNotFoundException(),
        };

        return $this->render('quality_check/message.html.twig', [
            'message_text' => $text,
            'type' => $type,
        ]);
    }

    #[Route('/reset', name: '_reset', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        $token = new CsrfToken('qc_reset', $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Ongeldige sessie status (CSRF). Probeer het opnieuw.');
            return $this->redirectToRoute('quality_check_result');
        }

        $this->qcSession->clear();
        return $this->redirectToRoute('quality_check_start');
    }
}