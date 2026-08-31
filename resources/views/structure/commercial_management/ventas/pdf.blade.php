@include('structure.commercial_management._documento_pdf', [
    'doc' => $venta,
    'titulo' => 'Venta',
    'leyenda' => 'Grupo MediBuy · Comprobante de venta · Precios en MXN.',
    'urlPublica' => $venta->public_token
        ? route('publico.venta', $venta->public_token)
        : null,
])
