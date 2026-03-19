<?php

return [

    'label' => 'Paginação',

    'overview' => '{1} A mostrar 1 registo|[2,*] A mostrar :first a :last de :total registos',

    'fields' => [

        'records_per_page' => [

            'label' => 'Por página',

            'options' => [
                'all' => 'Todos',
            ],

        ],

    ],

    'actions' => [

        'first' => [
            'label' => 'Primeira',
        ],

        'go_to_page' => [
            'label' => 'Ir para a página :page',
        ],

        'last' => [
            'label' => 'Última',
        ],

        'next' => [
            'label' => 'Próxima',
        ],

        'previous' => [
            'label' => 'Anterior',
        ],

    ],

];
