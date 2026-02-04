<?php

// src/Service/NormCatalog.php
namespace App\Service;

final class NormCatalog
{
    public function getAll(): array
    {
        return [
            // ===== BURGERS =====
            'big_mac' => [
                'sla' => ['label' => 'IJsbergsla', 'norm' => 30], // 2 × 15 g
            ],

            'quarter_pounder' => [
                'ui' => ['label' => 'Ui', 'norm' => 7],
            ],

            'mcchicken' => [
                'sla' => ['label' => 'IJsbergsla', 'norm' => 30],
            ],

            'chili_chicken' => [
                'sla' => ['label' => 'IJsbergsla', 'norm' => 15],
            ],

            'big_tasty' => [
                'sla' => ['label' => 'IJsbergsla', 'norm' => 30],
                'ui'  => ['label' => 'Ui', 'norm' => 7],
            ],

            'crispy' => [
                'batavia' => ['label' => 'Batavia sla', 'norm' => 8],
            ],

            // ===== IJS =====
            'sundae' => [
                'ijs' => ['label' => 'IJs', 'norm' => 120],
            ],

            'flurry' => [
                'ijs' => ['label' => 'IJs', 'norm' => 170],
            ],

            'ijshoorntje' => [
                'ijs' => ['label' => 'IJs', 'norm' => 110],
            ],

            // ===== FRIET =====
            'small' => [
                'friet' => ['label' => 'Friet', 'norm' => 80],
            ],

            'medium' => [
                'friet' => ['label' => 'Friet', 'norm' => 114],
            ],

            'large' => [
                'friet' => ['label' => 'Friet', 'norm' => 160],
            ],
        ];
    }
}
