<?php

declare(strict_types=1);

namespace App\Service;

final class NormCatalog
{
    /** 
     * Central definition of the catalog.
     * Structure: Category -> ProductKey -> Details
     */
    private const CATALOG = [
        'burger' => [
            'big_tasty' => [
                'name' => 'Big Tasty',
                'norms' => [
                    'sla' => ['label' => 'IJsbergsla', 'norm' => 30],
                    'ui' => ['label' => 'Ui', 'norm' => 7],
                ]
            ],
            'big_mac' => [
                'name' => 'Big Mac',
                'norms' => [
                    'sla' => ['label' => 'IJsbergsla', 'norm' => 30], // 2 × 15 g
                ]
            ],
            'crispy' => [
                'name' => 'Crispy',
                'norms' => [
                    'batavia' => ['label' => 'Batavia sla', 'norm' => 8],
                ]
            ],
            'mcchicken' => [
                'name' => 'McChicken',
                'norms' => [
                    'sla' => ['label' => 'IJsbergsla', 'norm' => 30],
                ]
            ],
            'quarter_pounder' => [
                'name' => 'Quarter Pounder',
                'norms' => [
                    'ui' => ['label' => 'Ui', 'norm' => 7],
                ]
            ],
            'chili_chicken' => [
                'name' => 'Chili Chicken',
                'norms' => [
                    'sla' => ['label' => 'IJsbergsla', 'norm' => 15],
                ]
            ],
        ],
        'ijs' => [
            'sundae' => [
                'name' => 'Sundae',
                'norms' => [
                    'ijs' => ['label' => 'IJs', 'norm' => 120],
                ]
            ],
            'flurry' => [
                'name' => 'McFlurry',
                'norms' => [
                    'ijs' => ['label' => 'IJs', 'norm' => 170],
                ]
            ],
            'ijshoorntje' => [
                'name' => 'IJshoorntje',
                'norms' => [
                    'ijs' => ['label' => 'IJs', 'norm' => 110],
                ]
            ],
        ],
        'friet' => [
            'small' => [
                'name' => 'Klein',
                'norms' => [
                    'friet' => ['label' => 'Friet', 'norm' => 80],
                ]
            ],
            'medium' => [
                'name' => 'Medium',
                'norms' => [
                    'friet' => ['label' => 'Friet', 'norm' => 114],
                ]
            ],
            'large' => [
                'name' => 'Groot',
                'norms' => [
                    'friet' => ['label' => 'Friet', 'norm' => 160],
                ]
            ],
        ],
    ];

    /**
     * Returns products grouped by category for the selection view.
     * 
     * Output format:
     * [
     *    'burger' => [
     *        'big_tasty' => 'Big Tasty',
     *        ...
     *    ],
     *    ...
     * ]
     */
    public function getProductsGroupedByCategory(): array
    {
        $grouped = [];
        foreach (self::CATALOG as $catKey => $products) {
            foreach ($products as $prodKey => $data) {
                $grouped[$catKey][$prodKey] = $data['name'];
            }
        }
        return $grouped;
    }

    /**
     * Returns detailed norms for a list of product IDs.
     */
    public function getNormsForProducts(array $productIds): array
    {
        $result = [];

        foreach (self::CATALOG as $products) {
            foreach ($products as $key => $data) {
                if (in_array($key, $productIds, true)) {
                    $result[$key] = $data;
                }
            }
        }

        // Sort results to match input order if needed, or by category order implicitly
        return $result;
    }

    /**
     * Validates a list of product IDs.
     * Returns only the IDs that actually exist in the catalog.
     */
    public function validateProductIds(array $ids): array
    {
        $validIds = [];
        $allKeys = $this->getAllProductKeys();

        foreach ($ids as $id) {
            if (is_string($id) && in_array($id, $allKeys, true)) {
                $validIds[] = $id;
            }
        }

        return $validIds;
    }

    private function getAllProductKeys(): array
    {
        $keys = [];
        foreach (self::CATALOG as $products) {
            foreach (array_keys($products) as $key) {
                $keys[] = $key;
            }
        }
        return $keys;
    }
}