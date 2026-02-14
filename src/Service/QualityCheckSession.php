<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class QualityCheckSession
{
    private const SESSION_KEY = 'qc';

    public function __construct(
        private RequestStack $requestStack,
        private NormCatalog $normCatalog
        )
    {
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function start(string $manager, string $crew): void
    {
        $session = $this->getSession();

        // Security: Regenerate session ID to prevent fixation
        $session->migrate(true);

        // Initialize clean state
        $session->set(self::SESSION_KEY, [
            'meta' => [
                'manager' => $manager,
                'crew' => $crew,
                'started_at' => time(),
            ],
            'selection' => [],
            'measurements' => [],
        ]);
    }

    public function updateSelection(array $productIds): void
    {
        // Guard: Integrity check
        $validIds = $this->normCatalog->validateProductIds($productIds);

        $this->updateState(function (array &$state) use ($validIds) {
            $state['selection'] = array_values(array_unique($validIds));
            // Clear old measurements if selection changes to prevent stale data?
            // Decision: Yes, clear measurements to avoid confusion or orphan data.
            $state['measurements'] = [];
        });
    }

    public function getSelection(): array
    {
        return $this->getState()['selection'] ?? [];
    }

    public function saveMeasurements(array $measurements): void
    {
        // Sanitize: cast all values to float to prevent storing arbitrary strings
        $sanitized = [];
        foreach ($measurements as $productKey => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            foreach ($fields as $fieldKey => $value) {
                $sanitized[(string)$productKey][(string)$fieldKey] = (float)$value;
            }
        }

        $this->updateState(function (array &$state) use ($sanitized) {
            $state['measurements'] = $sanitized;
        });
    }

    public function getMeasurements(): array
    {
        return $this->getState()['measurements'] ?? [];
    }

    /**
     * returns ['manager', 'crew', 'results' => [...]]
     */
    public function getReportData(): array
    {
        $state = $this->getState();
        $selection = $state['selection'] ?? [];
        $rawMeasurements = $state['measurements'] ?? [];

        $productData = $this->normCatalog->getNormsForProducts($selection);

        $results = [];
        foreach ($productData as $prodKey => $data) {
            $prodName = $data['name'];
            $norms = $data['norms'];

            // Check if we have measurements for this product
            if (!isset($rawMeasurements[$prodKey])) {
                continue;
            }

            foreach ($norms as $fieldKey => $normDef) {
                if (isset($rawMeasurements[$prodKey][$fieldKey])) {
                    $val = (float)$rawMeasurements[$prodKey][$fieldKey];
                    $target = (float)$normDef['norm'];

                    $results[] = [
                        'product' => $prodKey,
                        'product_name' => $prodName,
                        'label' => $normDef['label'],
                        'measured' => $val,
                        'norm' => $target,
                        'diff' => $val - $target,
                    ];
                }
            }
        }

        return [
            'manager' => $state['meta']['manager'] ?? 'Unknown',
            'crew' => $state['meta']['crew'] ?? 'Unknown',
            'results' => $results,
        ];
    }

    public function buildClipboardText(): string
    {
        $data = $this->getReportData();
        $lines = [];

        // Greeting based on time
        $hour = (int)date('H');
        $greeting = match (true) {
                $hour < 12 => 'Goedemorgen',
                $hour < 18 => 'Goedemiddag',
                default => 'Goedeavond',
            };

        $lines[] = "$greeting allemaal,";
        $lines[] = "";
        $lines[] = "Vandaag heb ik de volgende metingen uitgevoerd bij {$data['crew']}";
        $lines[] = "Shiftmanager: {$data['manager']}";
        $lines[] = "";

        $currentProduct = null;
        foreach ($data['results'] as $row) {
            if ($currentProduct !== $row['product']) {
                if ($currentProduct !== null) {
                    $lines[] = "";
                }
                $currentProduct = $row['product'];
                $lines[] = $row['product_name'];
            }

            $lines[] = "- {$row['label']}: {$row['measured']}g (procedure {$row['norm']}g)";
        }

        return implode("\n", $lines);
    }

    // === GUARDS ===

    public function hasShiftInfo(): bool
    {
        $state = $this->getState();
        return !empty($state['meta']['manager']) && !empty($state['meta']['crew']);
    }

    public function hasSelection(): bool
    {
        $state = $this->getState();
        return !empty($state['selection']);
    }

    public function hasMeasurements(): bool
    {
        $state = $this->getState();
        return !empty($state['measurements']);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    // === INTERNALS ===

    private function getState(): array
    {
        return $this->getSession()->get(self::SESSION_KEY, []);
    }

    private function updateState(callable $callback): void
    {
        $session = $this->getSession();
        $state = $session->get(self::SESSION_KEY, []);
        $callback($state);
        $session->set(self::SESSION_KEY, $state);
    }
}