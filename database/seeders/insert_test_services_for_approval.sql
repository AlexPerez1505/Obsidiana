-- Script para insertar servicios de prueba que requieren aprobación
-- Ejecuta este script en tu base de datos MySQL/PostgreSQL

-- Primero, obtén los IDs necesarios (ajusta según tu BD):
-- SELECT id FROM users WHERE email = 'admin@example.com'; -- Guarda este ID como @adminUserId
-- SELECT id FROM clientes LIMIT 1; -- Guarda este ID como @customerId
-- SELECT id FROM users WHERE role = 'tecnico' LIMIT 1; -- Guarda este ID como @technicianId
-- SELECT id FROM service_steps WHERE slug = 'autorizacion_admin'; -- Guarda como @autorizacionStepId
-- SELECT id FROM service_steps WHERE slug = 'validacion_os'; -- Guarda como @validacionStepId
-- SELECT id FROM service_steps WHERE slug = 'salida_foranea'; -- Guarda como @salidaStepId
-- SELECT id FROM service_steps WHERE slug = 'regreso_foranea'; -- Guarda como @regresoStepId

-- Luego ejecuta:

-- 1. Insertar servicio interno en paso de aprobación
INSERT INTO services (
    service_number, customer_id, service_type, internal_technician_id, 
    external_technician_id, registered_by, current_step_id, qr_token, 
    qr_expires_at, signature, status, started_at, finished_at, created_at, updated_at
) VALUES (
    'OS-TEST-INT-001', @customerId, 'interno', @technicianId, 
    NULL, @adminUserId, @autorizacionStepId, CONCAT('test-qr-int-', UUID()), 
    DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, 'en_progreso', NOW(), NULL, NOW(), NOW()
);

SET @internalServiceId = LAST_INSERT_ID();

-- 2. Insertar tracking para servicio interno
INSERT INTO service_trackings (
    service_id, service_step_id, status, performed_by, qr_token, 
    qr_expires_at, notes, evidence_1_path, evidence_2_path, evidence_3_path, 
    video_path, signature, started_at, finished_at, created_at, updated_at
) VALUES (
    @internalServiceId, @autorizacionStepId, 'pendiente', NULL, CONCAT('test-qr-int-', UUID()), 
    DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, NULL, NULL, NULL, 
    NULL, NULL, NOW(), NULL, NOW(), NOW()
);

-- 3. Insertar servicio externo en paso de aprobación
INSERT INTO services (
    service_number, customer_id, service_type, internal_technician_id, 
    external_technician_id, registered_by, current_step_id, qr_token, 
    qr_expires_at, signature, status, started_at, finished_at, created_at, updated_at
) VALUES (
    'OS-TEST-EXT-001', @customerId, 'externo', NULL, 
    @technicianId, @adminUserId, @validacionStepId, CONCAT('test-qr-ext-', UUID()), 
    DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, 'en_progreso', NOW(), NULL, NOW(), NOW()
);

SET @externalServiceId = LAST_INSERT_ID();

-- 4. Insertar tracking completado para paso 1 (Salida foránea)
INSERT INTO service_trackings (
    service_id, service_step_id, status, performed_by, qr_token, 
    qr_expires_at, notes, evidence_1_path, evidence_2_path, evidence_3_path, 
    video_path, signature, started_at, finished_at, created_at, updated_at
) VALUES (
    @externalServiceId, @salidaStepId, 'completado', @technicianId, NULL, 
    NULL, 'Equipo enviado a mantenimiento foráneo', NULL, NULL, NULL, 
    NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 
    DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
);

-- 5. Insertar tracking completado para paso 2 (Regreso foráneo)
INSERT INTO service_trackings (
    service_id, service_step_id, status, performed_by, qr_token, 
    qr_expires_at, notes, evidence_1_path, evidence_2_path, evidence_3_path, 
    video_path, signature, started_at, finished_at, created_at, updated_at
) VALUES (
    @externalServiceId, @regresoStepId, 'completado', @technicianId, NULL, 
    NULL, 'Equipo regresó de mantenimiento foráneo', NULL, NULL, NULL, 
    NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 12 HOUR), 
    DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 12 HOUR)
);

-- 6. Insertar tracking pendiente para paso 3 (Validación OS - requiere aprobación)
INSERT INTO service_trackings (
    service_id, service_step_id, status, performed_by, qr_token, 
    qr_expires_at, notes, evidence_1_path, evidence_2_path, evidence_3_path, 
    video_path, signature, started_at, finished_at, created_at, updated_at
) VALUES (
    @externalServiceId, @validacionStepId, 'pendiente', NULL, CONCAT('test-qr-ext-val-', UUID()), 
    DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, NULL, NULL, NULL, 
    NULL, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL, 
    DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)
);

-- Verificar que los datos fueron insertados correctamente
SELECT 'Servicios creados:' as resultado;
SELECT service_number, service_type, status FROM services WHERE service_number LIKE 'OS-TEST-%';

SELECT 'Trackings pendientes de aprobación:' as resultado;
SELECT st.id, s.service_number, ss.name, st.status 
FROM service_trackings st
JOIN services s ON st.service_id = s.id
JOIN service_steps ss ON st.service_step_id = ss.id
WHERE s.service_number LIKE 'OS-TEST-%'
AND st.status = 'pendiente'
AND ss.requires_approval = true;
