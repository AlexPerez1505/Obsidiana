-- Script para limpiar servicios duplicados
-- Mantiene solo el servicio más reciente por cliente

-- Primero, obtener los IDs de servicios a eliminar
-- (todos excepto el más reciente por customer_id)
DELETE FROM service_trackings
WHERE service_id IN (
    SELECT id FROM services
    WHERE id NOT IN (
        SELECT MAX(id)
        FROM services
        GROUP BY customer_id
    )
);

DELETE FROM service_equipment
WHERE service_id IN (
    SELECT id FROM services
    WHERE id NOT IN (
        SELECT MAX(id)
        FROM services
        GROUP BY customer_id
    )
);

DELETE FROM services
WHERE id NOT IN (
    SELECT MAX(id)
    FROM services
    GROUP BY customer_id
);

-- Mostrar resultado
SELECT 'Limpieza completada' as resultado;
SELECT COUNT(*) as total_servicios FROM services;
