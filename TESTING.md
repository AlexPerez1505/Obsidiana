# Guía de Prueba - Flujo de 13 Pasos

## Resumen de Cambios Realizados

Se ha corregido la lógica de aprobación para que cuando el admin aprueba el **Paso 3 (Aprobación por autoridades de Nuevo Servicio)**, el sistema automáticamente:

1. ✅ Marca el Paso 3 como **COMPLETADO**
2. ✅ Crea el **Paso 4** (Entrada del equipo a las instalaciones)
3. ✅ Crea el **Paso 5** (Salida hacia técnico externo)
4. ✅ Crea el **Paso 6** (Notificación de llegada del técnico externo) con código de verificación
5. ✅ Establece el **Paso 4** como paso actual

## Pasos para Probar

### 1. Crear un Nuevo Servicio
- Ve a "Gestion de Servicios" → "Crear Nuevo Servicio"
- Completa los datos del cliente y equipo
- Haz clic en "Crear Servicio"
- **Resultado esperado**: 
  - Paso 1: PENDIENTE
  - Paso 2: COMPLETADO (se genera automáticamente)
  - Paso 3: PENDIENTE (esperando aprobación)

### 2. Aprobar el Paso 3 como Admin
- Inicia sesión como admin
- Ve a "Aprobaciones" o abre el servicio creado
- Haz clic en "Aprobar" en el Paso 3
- **Resultado esperado**:
  - Paso 1: PENDIENTE
  - Paso 2: COMPLETADO
  - Paso 3: COMPLETADO ✅
  - Paso 4: EN PROCESO (actual)
  - Paso 5: PENDIENTE
  - Paso 6: PENDIENTE (con código de verificación)

### 3. Verificar Código de Verificación
- Abre el servicio
- Busca en los logs del servidor el código generado:
  ```
  Código de verificación generado para paso 6
  ```
- O consulta la BD:
  ```sql
  SELECT verification_code FROM service_trackings 
  WHERE service_step_id = (SELECT id FROM service_steps WHERE slug = 'notificacion-llegada-tecnico')
  LIMIT 1;
  ```

### 4. Completar Pasos 4 y 5
- Escanea el QR del Paso 4
- Completa el formulario
- Escanea el QR del Paso 5
- Completa el formulario
- **Resultado esperado**: Paso 6 se convierte en actual

### 5. Validar Código en Paso 6
- Escanea el QR del Paso 6
- Se mostrará un formulario pidiendo el código de verificación
- Ingresa el código (el que viste en los logs)
- **Resultado esperado**: Se muestra el formulario normal de mantenimiento

### 6. Completar Paso 6 en Adelante
- Completa el formulario del Paso 6
- Continúa con los pasos 7-11
- En el Paso 12, el admin debe aprobar nuevamente
- Completa el Paso 13

## Verificación en BD

Para verificar que todo está funcionando correctamente, ejecuta:

```sql
-- Ver todos los pasos de un servicio
SELECT 
    st.order,
    st.name,
    strack.status,
    strack.verification_code,
    strack.created_at
FROM service_trackings strack
JOIN service_steps st ON strack.service_step_id = st.id
WHERE strack.service_id = 46
ORDER BY st.order;

-- Ver el paso actual del servicio
SELECT 
    s.service_number,
    s.current_step_id,
    st.name as current_step_name,
    s.status
FROM services s
LEFT JOIN service_steps st ON s.current_step_id = st.id
WHERE s.id = 46;
```

## Cambios Técnicos

### Archivo: `routes/web/services/admin_approve.php`

**Cambio principal**: Cuando se aprueba el Paso 3, ahora:
- Obtiene los siguientes 3 pasos (4, 5, 6)
- Crea trackings para todos ellos
- Genera código de verificación para el Paso 6
- Establece el Paso 4 como paso actual

**Antes**:
```php
// Solo creaba el siguiente paso (4)
$nextStep = ServiceStep::where('order', '>', $currentOrder)->first();
ServiceTracking::create([...]);
```

**Después**:
```php
// Crea los pasos 4, 5 y 6 automáticamente
$nextSteps = ServiceStep::where('order', '>', $currentOrder)->get();
foreach ($nextSteps->take(3) as $stepToCreate) {
    ServiceTracking::create([...]);
}
```

## Notas Importantes

1. **Código de Verificación**: Se genera automáticamente como un número de 4 dígitos (0000-9999)
2. **Expiración**: Actualmente no tiene expiración (TODO: implementar si es necesario)
3. **Intentos**: No hay límite de intentos (TODO: implementar si es necesario)
4. **Envío**: El código se registra en logs pero no se envía por SMS/Email (TODO: implementar)

## Troubleshooting

### El Paso 3 sigue mostrando "EN PROCESO" después de aprobar
- Recarga la página (Ctrl+F5)
- Verifica en BD que el tracking tiene `status = 'completado'`
- Verifica que `current_step_id` cambió al Paso 4

### El código de verificación no aparece en el Paso 6
- Verifica en BD que el tracking del Paso 6 tiene un valor en `verification_code`
- Revisa los logs del servidor para el mensaje "Código de verificación generado"

### El Paso 4 no se creó automáticamente
- Verifica que el Paso 3 se aprobó correctamente
- Revisa los logs para errores en la creación de trackings
- Verifica que existen los pasos 4, 5, 6 en la tabla `service_steps`

---

**Última actualización**: 2026-08-12
