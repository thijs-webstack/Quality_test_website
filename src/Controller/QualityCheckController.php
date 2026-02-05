<?php

namespace App\Controller;
use App\Service\NormCatalog;
use App\Form\QualityCheckStartType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quality-check', name: 'quality_check')]
final class QualityCheckController extends AbstractController
{
    #[Route('/', name: '_start', methods: ['GET', 'POST'])]
    public function start(Request $request, SessionInterface $session): Response
    {
        $form = $this->createForm(QualityCheckStartType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $session->set('qc.shift_manager', $data['shiftManager']);
            $session->set('qc.crew', $data['crew']);
            $session->set('qc.category', $data['category']);

            return $this->redirectToRoute('quality_check_product');
        }

        return $this->render('quality_check/start.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product', name: '_product', methods: ['GET', 'POST'])]
    public function product(Request $request, SessionInterface $session): Response
    {
        $category = $session->get('qc.category');
        if (!$category) {
            return $this->redirectToRoute('quality_check_start');
        }

        $productsByCategory = [
            'burger' => [
                'big_tasty' => 'Big Tasty',
                'big_mac' => 'Big Mac',
                'crispy' => 'Crispy',
                'mcchicken' => 'Mcchicken',
                'quarter_pounder' => 'Quarter Pounder',
                'chili_chicken' => 'Chili Chicken',
            ],
            'ijs' => [
                'sundae' => 'Sundae',
                'flurry' => 'McFlurry',
                'ijshoorntje' => 'IJshoorntje',
            ],
            'friet' => [
                'small' => 'Klein',
                'medium' => 'Medium',
                'large' => 'Groot',
            ],
        ];

        if (!isset($productsByCategory[$category])) {
            return $this->redirectToRoute('quality_check_start');
        }

        if ($request->isMethod('POST')) {
            $selected = $request->request->all('products') ?? [];

            $valid = array_intersect(
                $selected,
                array_keys($productsByCategory[$category])
            );

            if (!empty($valid)) {
                $session->set('qc.products', array_values($valid));
                return $this->redirectToRoute('quality_check_measure');
            }
        }

        return $this->render('quality_check/product.html.twig', [
            'category' => $category,
            'products' => $productsByCategory[$category],
        ]);
    }

    #[Route('/measure', name: '_measure', methods: ['GET', 'POST'])]
    public function measure(
        Request $request,
        SessionInterface $session,
        NormCatalog $normCatalog
    ): Response {
        $products = $session->get('qc.products');
        if (!$products || !is_array($products)) {
            return $this->redirectToRoute('quality_check_product');
        }

        $norms = $normCatalog->getAll();

        $structure = [];
        foreach ($products as $product) {
            if (isset($norms[$product])) {
                $structure[$product] = $norms[$product];
            }
        }

        if (empty($structure)) {
            return $this->redirectToRoute('quality_check_product');
        }

        if ($request->isMethod('POST')) {
            $measurements = $request->request->all('measurements');
            $session->set('qc.measurements', $measurements);

            return $this->redirectToRoute('quality_check_result');
        }

        return $this->render('quality_check/measure.html.twig', [
            'structure' => $structure,
        ]);
    }


    #[Route('/result', name: '_result', methods: ['GET'])]
    public function result(
        SessionInterface $session,
        NormCatalog $normCatalog
    ): Response {
        $measurements = $session->get('qc.measurements');
        if (!$measurements) {
            return $this->redirectToRoute('quality_check_start');
        }

        $norms = $normCatalog->getAll();
        $results = [];

        foreach ($measurements as $product => $parts) {
            foreach ($parts as $key => $value) {
                if (!isset($norms[$product][$key])) {
                    continue;
                }

                $norm = $norms[$product][$key]['norm'];

                $results[] = [
                    'product' => $product,
                    'label' => $norms[$product][$key]['label'],
                    'measured' => (float) $value,
                    'norm' => $norm,
                    'diff' => (float) $value - $norm,
                ];
            }
        }

        return $this->render('quality_check/result.html.twig', [
            'shiftManager' => $session->get('qc.shift_manager'),
            'crew' => $session->get('qc.crew'),
            'results' => $results,
        ]);
    }

    #[Route('/reset', name: '_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): Response
    {
        foreach ($session->all() as $key => $value) {
            if (str_starts_with($key, 'qc.')) {
                $session->remove($key);
            }
        }

        return $this->redirectToRoute('quality_check_start');
    }

}
