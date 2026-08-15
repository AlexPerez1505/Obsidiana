@include('structure.commercial_management._documento_pdf', [
    'doc' => $cotizacion,
    'titulo' => 'Cotización',
    'leyenda' => 'Grupo MediBuy · Cotización sin compromiso · Precios en MXN · Válida por 15 días.',
])
