<?php

/*
|--------------------------------------------------------------------------
| Información de la empresa
|--------------------------------------------------------------------------
| Valores por defecto usados cuando el metadato correspondiente no está
| registrado en la tabla `metadatos`. Las claves aquí deben coincidir con
| las claves guardadas por la página de Metadatos del panel admin.
| Se usan para construir la factura (orders/{order}/print) y el pie de
| página / redes sociales del frontend.
*/

return [
    'company_name'    => 'MEDIN FASHION FEELS',
    'company_nit'     => '901544814-1',
    'company_address' => 'CC Punto Once Local 6017',
    'company_city'    => 'Bogotá, D.C. - Colombia',
    'company_phone'   => '3024197103',
    'company_website' => 'www.camisasmedin.com',

    'social_instagram_url'    => 'https://www.instagram.com/medinfashionfeels',
    'social_instagram_handle' => '@medinfashionfeels',
    'social_facebook_url'     => 'https://www.facebook.com/medinfashionfeels',
    'social_facebook_handle'  => 'Medin Fashion Feels',
    'social_tiktok_url'       => 'https://www.tiktok.com/@camisasmedin',
    'social_tiktok_handle'    => '@camisasmedin',
];
