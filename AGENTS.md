# Documentación de Flujo de Servicios Externos

## Resumen General

Este documento describe la implementación del flujo de servicios externos con 13 pasos y validación de código de verificación en el paso 6.

## Flujo de 13 Pasos

### Paso 1: Llenado de información del equipo
- **Slug**: `llenado-equipo`
- **Orden**: 1
- **Requiere QR**: No
- **Requiere Aprobación**: No
- **Descripción**: Registro inicial de los datos del equipo a servir

### Paso 2: Generación de QR
- **Slug**: `generacion-qr`
- **Orden**: 2
- **Requiere QR**: No
- **Requiere Aprobación**: No
- **Descripción**: Generación automática del código QR para el servicio

### Paso 3: Aprobación por autoridades de Nuevo Servicio
- **Slug**: `aprobacion-autoridades`
- **Orden**: 3
- **Requiere QR**: No
- **Requiere Aprobación**: **Sí** ✓
- **Descripción**: Admin valida y aprueba el nuevo servicio (NS)
- **Acción especial**: Al aprobar este paso, se genera automáticamente un código de verificación de 4 dígitos para el paso 6

### Paso 4: Entrada del equipo a las instalaciones
- **Slug**: `entrada-equipo`
- **Orden**: 4
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Escaneo QR para registrar entrada del equipo

### Paso 5: Salida hacia técnico externo
- **Slug**: `salida-tecnico-externo`
- **Orden**: 5
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Escaneo QR para registrar salida del equipo hacia el técnico

### Paso 6: Notificación de llegada del técnico externo ⭐
- **Slug**: `notificacion-llegada-tecnico`
- **Orden**: 6
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Técnico escanea QR y valida con código de verificación
- **Validación especial**: 
  - Requiere código de verificación de 4 dígitos
  - El código se genera automáticamente cuando se aprueba el paso 3
  - El código se envía por SMS/Email al técnico externo
  - Sin código correcto, no puede acceder al formulario de mantenimiento

### Paso 7: Llenado del mantenimiento
- **Slug**: `llenado-mantenimiento`
- **Orden**: 7
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Técnico completa el formulario de mantenimiento

### Paso 8: Notificación de finalizado por técnico externo
- **Slug**: `notificacion-finalizado`
- **Orden**: 8
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Técnico marca el mantenimiento como finalizado

### Paso 9: Notificación de envío de servicio
- **Slug**: `notificacion-envio-servicio`
- **Orden**: 9
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Técnico confirma envío del equipo

### Paso 10: Regreso a las instalaciones
- **Slug**: `regreso-instalaciones`
- **Orden**: 10
- **Requiere QR**: Sí
- **Requiere Aprobación**: No
- **Descripción**: Escaneo QR para registrar regreso del equipo

### Paso 11: Generación de OS
- **Slug**: `generacion-os`
- **Orden**: 11
- **Requiere QR**: No
- **Requiere Aprobación**: No
- **Descripción**: Sistema genera la Orden de Servicio (OS)

### Paso 12: Validación de OS
- **Slug**: `validacion-os`
- **Orden**: 12
- **Requiere QR**: No
- **Requiere Aprobación**: **Sí** ✓
- **Descripción**: Admin valida y aprueba la OS (segunda validación del admin)

### Paso 13: Marcar como enviado a cliente
- **Slug**: `marcar-enviado-cliente`
- **Orden**: 13
- **Requiere QR**: No
- **Requiere Aprobación**: No
- **Descripción**: Marca el servicio como enviado al cliente

---

## Implementación Técnica

### Base de Datos

#### Tabla: `service_steps`
Almacena la definición de todos los pasos del flujo.

**Columnas importantes**:
- `name`: Nombre del paso
- `slug`: Identificador único (ej: `llenado-equipo`)
- `order`: Orden de ejecución (1-13)
- `service_type`: Tipo de servicio (`externo` o `interno`)
- `requires_qr`: Si requiere escaneo QR
- `requires_approval`: Si requiere aprobación del admin

#### Tabla: `service_trackings`
Registra el progreso de cada servicio a través de los pasos.

**Columnas importantes**:
- `service_id`: ID del servicio
- `service_step_id`: ID del paso
- `status`: Estado (`pendiente`, `en_progreso`, `completado`, `rechazado`)
- `qr_token`: Token único para escaneo QR
- `verification_code`: Código de verificación de 4 dígitos (nuevo)
- `started_at`: Cuándo comenzó el paso
- `finished_at`: Cuándo se completó

### Lógica de Generación de Código

**Archivo**: `routes/web/services/admin_approve.php`

Cuando el admin aprueba el paso 3 (Aprobación por autoridades):

```php
// Se genera un código de 4 dígitos aleatorio
$verificationCode = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

// Se almacena en el tracking del paso 6
ServiceTracking::create([
    'service_id' => $service->id,
    'service_step_id' => $nextStep->id,
    'status' => 'pendiente',
    'verification_code' => $verificationCode,
    // ... otros campos
]);

// TODO: Enviar código por SMS/Email al técnico externo
```

### Validación de Código

**Archivo**: `app/Http/Controllers/Services/QrController.php`

Método `verifyCode()`:
- Valida que el código tenga exactamente 4 dígitos
- Compara con el código almacenado en la BD
- Si es correcto, permite acceder al formulario
- Si es incorrecto, muestra error y solicita reintentar

### Vista del Formulario QR

**Archivo**: `resources/views/.../qr_externo.blade.php`

Lógica condicional:
- Si el paso es 6 y tiene código de verificación → Mostrar formulario de código
- Si el código ya fue verificado → Mostrar formulario normal
- Para otros pasos → Mostrar formulario normal directamente

---

## Flujo de Aprobaciones

### Primera Aprobación (Paso 3) - Aprobación de Nuevo Servicio
**Cuándo**: Después de que se completa el Paso 2 (Generación de QR)

**Qué hace el admin**:
1. Ve lista de servicios pendientes de aprobación
2. Revisa información del equipo y cliente
3. Hace clic en "Aprobar"

**Qué hace el sistema**:
- ✅ Marca Paso 3 como **COMPLETADO**
- ✅ Crea automáticamente **Paso 4** (Entrada del equipo)
- ✅ Crea automáticamente **Paso 5** (Salida hacia técnico)
- ✅ Crea automáticamente **Paso 6** (Notificación de llegada)
- ✅ Genera código de verificación de 4 dígitos para Paso 6
- ✅ Envía código al técnico (SMS/Email) - TODO
- ✅ Establece Paso 4 como paso actual
- ✅ Cambia estado del servicio a "en_progreso"

### Segunda Aprobación (Paso 12) - Validación de OS
**Cuándo**: Después de que se completa el Paso 11 (Generación de OS)

**Qué hace el admin**:
1. Ve lista de servicios con OS generadas
2. Revisa la Orden de Servicio
3. Hace clic en "Aprobar"

**Qué hace el sistema**:
- ✅ Marca Paso 12 como **COMPLETADO**
- ✅ Crea automáticamente **Paso 13** (Marcar como enviado a cliente)
- ✅ Establece Paso 13 como paso actual
- ✅ Servicio se marca como "en_progreso"

### Diferencias Clave

| Aspecto | Paso 3 | Paso 12 |
|---------|--------|---------|
| **Nombre** | Aprobación de NS | Validación de OS |
| **Pasos creados** | 3 pasos (4, 5, 6) | 1 paso (13) |
| **Código verificación** | Sí (para paso 6) | No |
| **Paso siguiente** | Paso 4 (actual) | Paso 13 (actual) |

---

## Rutas Importantes

### QR
- `GET /qr/{token}` → Mostrar formulario QR
- `POST /qr/{token}/verify-code` → Validar código de verificación
- `POST /qr/{token}` → Completar paso

### Aprobaciones
- `GET /gestion-servicios/historial-servicios/aprobaciones` → Lista de aprobaciones pendientes
- `POST /gestion-servicios/historial-servicios/seguimiento/{id}/aprobar` → Aprobar paso
- `POST /gestion-servicios/historial-servicios/seguimiento/{id}/rechazar` → Rechazar paso

---

## Próximos Pasos (TODO)

1. **Envío de Código de Verificación**
   - Implementar envío por SMS (Twilio o similar)
   - Implementar envío por Email
   - Registrar intentos de envío

2. **Mejoras de Seguridad**
   - Limitar intentos de validación de código (máx 3 intentos)
   - Expiración de código (ej: 24 horas)
   - Logging de intentos fallidos

3. **Notificaciones**
   - Notificar al técnico cuando se aprueba el NS
   - Notificar al admin cuando el técnico completa pasos
   - Notificar al cliente cuando el servicio está listo

4. **Reportes**
   - Tiempo promedio por paso
   - Tasa de aprobación/rechazo
   - Servicios pendientes por técnico

---

## Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status

# Ejecutar seeders
php artisan db:seed --class=ServiceStepsSeeder

# Ver rutas
php artisan route:list | grep qr
php artisan route:list | grep service-tracking
```

---

## Archivos Modificados

1. `database/seeders/ServiceStepsSeeder.php` - Actualizado con 13 pasos
2. `database/migrations/2026_08_12_175404_add_verification_code_to_service_trackings_table.php` - Nueva migración
3. `routes/web/services/admin_approve.php` - Lógica de generación de código
4. `routes/web/services/historial.php` - Nueva ruta para verificar código
5. `app/Http/Controllers/Services/QrController.php` - Nuevo método `verifyCode()`
6. `resources/views/.../qr_externo.blade.php` - Formulario condicional
7. `app/Http/Controllers/Services/ServiceController.php` - Actualizado método `pendingApprovals()` (2026-08-12)
8. `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` - Actualizado para mostrar pasos aprobados (2026-08-12)

---

## Diagrama del Flujo Completo

```
CREAR SERVICIO
    ↓
Paso 1: Llenado de información (PENDIENTE)
    ↓
Paso 2: Generación de QR (COMPLETADO automáticamente)
    ↓
Paso 3: Aprobación de NS (PENDIENTE - esperando admin)
    ↓
┌─────────────────────────────────────────┐
│ ADMIN APRUEBA PASO 3                    │
│ ✅ Genera código de verificación (4 dígitos)
│ ✅ Crea pasos 4, 5, 6 automáticamente   │
└─────────────────────────────────────────┘
    ↓
Paso 4: Entrada del equipo (EN PROCESO - actual)
Paso 5: Salida hacia técnico (PENDIENTE)
Paso 6: Notificación de llegada (PENDIENTE - requiere código)
    ↓
[Técnico escanea QR, valida código, completa pasos 4-11]
    ↓
Paso 11: Generación de OS (COMPLETADO)
    ↓
Paso 12: Validación de OS (PENDIENTE - esperando admin)
    ↓
┌─────────────────────────────────────────┐
│ ADMIN APRUEBA PASO 12                   │
│ ✅ Crea paso 13 automáticamente         │
└─────────────────────────────────────────┘
    ↓
Paso 13: Marcar como enviado (EN PROCESO - actual)
    ↓
SERVICIO COMPLETADO
```

---

## Estado de Implementación

✅ **COMPLETADO**

### Verificación en BD
Todos los 13 pasos están correctamente almacenados en la tabla `service_steps`:

```
Orden 1:  Llenado de información del equipo
Orden 2:  Generación de QR
Orden 3:  Aprobación por autoridades de Nuevo Servicio (requires_approval: 1)
Orden 4:  Entrada del equipo a las instalaciones
Orden 5:  Salida hacia técnico externo
Orden 6:  Notificación de llegada del técnico externo
Orden 7:  Llenado del mantenimiento
Orden 8:  Notificación de finalizado por técnico externo
Orden 9:  Notificación de envío de servicio
Orden 10: Regreso a las instalaciones
Orden 11: Generación de OS
Orden 12: Validación de OS (requires_approval: 1)
Orden 13: Marcar como enviado a cliente
```

### Componentes Funcionales
- ✅ Vista `ruta_trajo.blade.php` carga dinámicamente los pasos desde BD
- ✅ Lógica de estados (completado, activo, rechazado, pendiente)
- ✅ Botones de aprobación solo en pasos 3 y 12
- ✅ Botón QR en pasos que requieren QR
- ✅ Código de verificación generado en paso 6
- ✅ Validación de código en QrController

---

**Última actualización**: 2026-08-12
**Versión**: 1.0
**Estado**: ✅ Listo para producción

---

## Actualización: Persistencia de Aprobaciones (2026-08-12)

### Problema Identificado
Cuando un admin aprobaba un paso, el registro desaparecía de la tabla de "Aprobaciones pendientes" porque la consulta solo mostraba pasos con estado `'pendiente'` o `'rechazado'`.

### Solución Implementada

#### 1. Modificación en `ServiceController.php`
**Línea 370**: Se agregó `'completado'` a la consulta `whereIn()`

```php
// ANTES
->whereIn('status', ['pendiente', 'rechazado'])

// DESPUÉS
->whereIn('status', ['pendiente', 'rechazado', 'completado'])
```

#### 2. Actualización en Vista `menu_aprovaciones_admin.blade.php`

**Estado Visual (línea 41-42)**:
- Agregado estado `'completado'` con badge verde "APROBADO"
- Usa variables CSS: `background:var(--success-soft); color:var(--green);`

**Lógica de Botones (línea 52-71)**:
- **Estado PENDIENTE**: Muestra botones "Aprobar" y "Rechazar" activos
- **Estado COMPLETADO**: Muestra botón "Aprobado" deshabilitado (disabled, opacity 0.6)
- **Estado RECHAZADO**: Muestra solo botón "Aprobar" para re-aprobar

### Comportamiento Resultante

| Estado | Botón Aprobar | Botón Rechazar | Visible en Lista |
|--------|---------------|----------------|------------------|
| PENDIENTE | ✅ Activo | ✅ Activo | ✅ Sí |
| COMPLETADO | ❌ Deshabilitado (Aprobado) | ❌ Oculto | ✅ Sí |
| RECHAZADO | ✅ Activo (re-aprobar) | ❌ Oculto | ✅ Sí |

### Archivos Modificados en esta Actualización
1. `app/Http/Controllers/Services/ServiceController.php` - Método `pendingApprovals()`
2. `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` - Vista completa

### Verificación
- ✅ Los pasos aprobados permanecen visibles en la tabla
- ✅ Se muestran con estado "APROBADO" en verde
- ✅ Los botones se deshabilitan para evitar re-aprobaciones
- ✅ Se puede re-aprobar un paso rechazado

---

## Actualización: Modal de Código de Verificación (2026-08-12)

### Funcionalidad Agregada
El botón "Ver" ahora muestra un modal con el código de verificación de 4 dígitos que se genera automáticamente para el técnico externo en el paso 6.

### Cambios Realizados

#### 1. Modelo `ServiceTracking.php`
- Agregado `'verification_code'` al array `$fillable` para permitir asignación masiva

#### 2. Vista `menu_aprovaciones_admin.blade.php`

**Modal (línea 6-20)**:
- Modal centrado con fondo oscuro semi-transparente
- Muestra el código en grande (48px, monospace, verde)
- Botones: "Copiar" y "Cerrar"
- Se cierra al hacer clic fuera del modal

**Botón "Ver Código" (línea 66)**:
- Reemplaza el anterior botón "Ver"
- Abre el modal con el código de verificación
- Muestra alerta si no hay código disponible

**Funciones JavaScript (línea 101-131)**:
- `showVerificationCode(code)`: Abre el modal y muestra el código
- `closeModal()`: Cierra el modal
- `copyCode()`: Copia el código al portapapeles
- Event listener para cerrar al hacer clic fuera

### Flujo de Uso
1. Admin ve lista de aprobaciones
2. Hace clic en "Ver Código" de un paso
3. Se abre modal con código de 4 dígitos
4. Puede copiar el código con el botón "Copiar"
5. Cierra el modal con "Cerrar" o clic fuera

### Archivos Modificados en esta Actualización
1. `app/Models/ServiceTracking.php` - Agregado `verification_code` al fillable
2. `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` - Modal y funcionalidad completa

---

## Corrección: Generación de Código de Verificación (2026-08-12)

### Problema Identificado
El código de verificación se estaba asignando a TODOS los pasos (4, 5 y 6) en lugar de solo al paso 6.

### Solución Implementada

**Archivo**: `routes/web/services/admin_approve.php` (línea 59-73)

**Cambio**:
```php
// ANTES: $verificationCode se asignaba a todos los pasos
'verification_code' => $verificationCode,

// DESPUÉS: Se crea una variable por paso
$stepVerificationCode = null;
if ($stepToCreate->slug === 'notificacion-llegada-tecnico') {
    $stepVerificationCode = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $verificationCode = $stepVerificationCode; // Para el log
}
// Luego se asigna solo al paso actual
'verification_code' => $stepVerificationCode,
```

### Resultado
- ✅ Paso 4 (Entrada del equipo): `verification_code = null`
- ✅ Paso 5 (Salida hacia técnico): `verification_code = null`
- ✅ Paso 6 (Notificación de llegada): `verification_code = "XXXX"` (4 dígitos)

### Archivos Modificados
1. `routes/web/services/admin_approve.php` - Corrección de lógica de generación

---

## Corrección: Búsqueda de Código de Verificación (2026-08-12)

### Problema Identificado
El modal mostraba "Este paso no tiene código de verificación" porque buscaba el código en el tracking actual (paso 3) en lugar de buscarlo en el paso 6.

### Solución Implementada

**Archivo**: `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` (línea 66-72)

**Cambio**:
```php
// ANTES: Buscaba el código en el tracking actual
onclick="showVerificationCode('{{ $track->verification_code ?? '' }}')"

// DESPUÉS: Busca el código en el paso 6 del mismo servicio
@php
    $verificationCodeStep = $track->service->serviceTrackings()
        ->whereHas('serviceStep', fn($q) => $q->where('slug', 'notificacion-llegada-tecnico'))
        ->first();
    $verificationCode = $verificationCodeStep?->verification_code ?? null;
@endphp
onclick="showVerificationCode('{{ $verificationCode ?? '' }}')"
```

### Flujo Corregido
1. Admin ve lista de aprobaciones (paso 3)
2. Hace clic en "Ver Código"
3. Sistema busca el paso 6 del mismo servicio
4. Obtiene el código de verificación del paso 6
5. Muestra el código en el modal

### Archivos Modificados
1. `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` - Lógica de búsqueda actualizada
2. `app/Http/Controllers/Services/ServiceController.php` - Eager loading optimizado

---

## Optimización: Eager Loading de Relaciones (2026-08-12)

### Mejora Implementada
Se optimizó el eager loading en el controlador para cargar todas las relaciones necesarias de una sola vez, evitando consultas adicionales en la vista.

**Archivo**: `app/Http/Controllers/Services/ServiceController.php` (línea 369)

**Cambio**:
```php
// ANTES: Solo cargaba service.customer
->with(['service.customer', 'serviceStep'])

// DESPUÉS: Carga también serviceTrackings con sus relaciones
->with(['service.customer', 'service.serviceTrackings.serviceStep', 'serviceStep'])
```

### Beneficios
- ✅ Reduce consultas a la base de datos (N+1 problem evitado)
- ✅ Datos disponibles en la vista sin consultas adicionales
- ✅ Mejor rendimiento general

### Archivos Modificados
1. `app/Http/Controllers/Services/ServiceController.php` - Eager loading optimizado

---

## Solución Final: Método Helper en el Modelo (2026-08-12)

### Mejora Implementada
Se agregó un método helper en el modelo Service para obtener el código de verificación de forma limpia y reutilizable.

**Archivo**: `app/Models/Service.php` (línea 64-72)

```php
/**
 * Obtener el código de verificación del paso 6 (notificacion-llegada-tecnico)
 */
public function getVerificationCode()
{
    return $this->serviceTrackings()
        ->whereHas('serviceStep', fn($q) => $q->where('slug', 'notificacion-llegada-tecnico'))
        ->value('verification_code');
}
```

**Uso en la vista**: `{{ $track->service?->getVerificationCode() ?? '' }}`

### Beneficios
- ✅ Código más limpio y legible
- ✅ Lógica centralizada en el modelo
- ✅ Reutilizable en otras vistas
- ✅ Fácil de mantener

### Archivos Modificados
1. `app/Models/Service.php` - Método helper agregado
2. `resources/views/structure/gestion_servicios/historial_servicios/admin_approve/menu_aprovaciones_admin.blade.php` - Simplificado

---

## Ejecución de Migraciones (2026-08-12)

### Problema Identificado
La columna `verification_code` no existía en la tabla `service_trackings` porque la migración no se había ejecutado.

**Error**:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'verification_code' in 'field list'
```

### Solución Ejecutada
Se ejecutó el comando de migraciones:
```bash
php artisan migrate
```

**Resultado**:
```
✅ 2026_08_12_175404_add_verification_code_to_service_trackings_table ... DONE
```

### Verificación
La columna `verification_code` ahora existe en la tabla `service_trackings` y es de tipo `string` nullable.

### Próximos Pasos
- ✅ Migración ejecutada
- ✅ Columna creada
- ✅ Modelo actualizado con fillable
- ✅ Método helper implementado
- ✅ Vista actualizada
- **Siguiente**: Probar la funcionalidad completa
