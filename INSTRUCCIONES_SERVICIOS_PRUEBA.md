# Instrucciones para Insertar Servicios de Prueba

## Problema Identificado

Los servicios no aparecían en la página de aprobaciones porque:

1. **Filtro en el controlador**: La consulta en `pendingApprovals()` solo muestra pasos que tienen `requires_approval = true`
2. **Pasos que requieren aprobación**:
   - Servicios internos: "Autorización admin" (orden 1)
   - Servicios externos: "Validación OS" (orden 3)
3. **No había servicios en estos pasos**: Los servicios nunca llegaban a estos pasos en la base de datos

## Solución Implementada

Se han creado servicios de prueba que están exactamente en los pasos que requieren aprobación.

### Opción 1: Usar la Ruta Web (Recomendado)

1. Abre tu navegador y accede a:
   ```
   http://localhost:8000/test/insert-services-for-approval
   ```

2. Deberías ver una respuesta JSON indicando que los servicios fueron creados exitosamente

3. Luego accede a la página de aprobaciones:
   ```
   http://localhost:8000/gestion-servicios/historial-servicios/aprobaciones
   ```

### Opción 2: Usar el Comando Artisan

Si prefieres usar la línea de comandos:

```bash
php artisan app:insert-test-services-for-approval
```

### Opción 3: Ejecutar la Migración

Si las migraciones están configuradas:

```bash
php artisan migrate
```

## Servicios Creados

Se crean automáticamente:

1. **Servicio Interno (OS-TEST-INT-YYYYMMDDHHMMSS)**
   - Tipo: Interno
   - Paso actual: Autorización admin (requiere aprobación)
   - Estado: en_progreso
   - Tracking: Pendiente de aprobación

2. **Servicio Externo (OS-TEST-EXT-YYYYMMDDHHMMSS)**
   - Tipo: Externo
   - Pasos completados: Salida foránea, Regreso foráneo
   - Paso actual: Validación OS (requiere aprobación)
   - Estado: en_progreso
   - Tracking: Pendiente de aprobación

## Verificación

Después de ejecutar una de las opciones anteriores:

1. Accede a la página de aprobaciones
2. Deberías ver 2 servicios listados esperando aprobación
3. Puedes hacer clic en "Aprobar" o "Rechazar" para probar la funcionalidad

## Limpiar Datos de Prueba

Para eliminar los servicios de prueba, ejecuta:

```bash
php artisan migrate:rollback --step=1
```

O ejecuta manualmente en tu base de datos:

```sql
DELETE FROM service_trackings WHERE service_id IN (
    SELECT id FROM services WHERE service_number LIKE 'OS-TEST-%'
);

DELETE FROM services WHERE service_number LIKE 'OS-TEST-%';
```

## Archivos Creados

- `/routes/web/test_services.php` - Ruta web para insertar datos
- `/app/Console/Commands/InsertTestServicesForApproval.php` - Comando Artisan
- `/database/migrations/2026_08_11_160000_seed_test_services_for_approval.php` - Migración

## Notas

- La ruta web `/test/insert-services-for-approval` solo funciona en desarrollo
- Los servicios de prueba tienen números que comienzan con `OS-TEST-` para identificarlos fácilmente
- Puedes ejecutar la ruta web múltiples veces para crear más servicios de prueba
