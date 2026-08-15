@include('structure.commercial_management._documento_pdf', [
    'doc' => $venta,
    'titulo' => 'Venta',
    'leyenda' => 'Grupo MediBuy · Comprobante de venta · Precios en MXN.',
])
