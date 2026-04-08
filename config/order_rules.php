<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Regla de Protección de Tallas Grandes
    |--------------------------------------------------------------------------
    |
    | Si las tallas grandes superan el umbral (threshold) del total de unidades
    | del pedido, se aplica un recargo fijo por cada prenda de talla grande.
    | El recargo NO bloquea la compra; incentiva a combinar tallas.
    |
    | large_size_codes : códigos de AttributeValue que se consideran "talla grande"
    | threshold        : proporción mínima (0.70 = 70%) para activar el recargo
    | surcharge        : recargo en COP por cada prenda de talla grande
    |
    */

    'large_size_protection' => [

        'threshold' => 0.70,

        'surcharge' => 2000,

        'large_size_codes' => [
            // Tallas camiseta / ropa superior
            'XL', 'XXL', '2XL', '3XL', '4XL',

            // Tallas pantalón (cintura)
            '38', '40', '42', '44', '46', '48', '50',
        ],
    ],

];
