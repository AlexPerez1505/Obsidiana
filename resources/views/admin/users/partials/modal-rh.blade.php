{{--
    Ficha de Recursos Humanos de un usuario ya registrado.

    No crea cuentas: eso pasa por el registro y su aprobación. Aquí se
    completan los datos laborales, los roles y los documentos.

    Se abre desde cualquier botón [data-abrir-rh]; si el botón trae
    data-usuario, el select llega ya seleccionado.

    Recibe: $users, $roles.
--}}

<dialog id="modalRh" class="rh-modal">
    <form method="POST" action="{{ route('admin.users.hrProfile.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="rh-head">
            <div>
                <x-ui.section-title style="margin:0 0 4px;">Datos del usuario</x-ui.section-title>
                <p class="campo-nota" style="margin:0;">
                    Elige a alguien ya registrado y completa su ficha. Aquí no se crean cuentas nuevas.
                </p>
            </div>
            <button type="button" class="btn-icono" data-cerrar-rh aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="rh-body">
            <x-ui.form-group label="Usuario *" for="hr_user_id">
                <select id="hr_user_id" name="user_id" required onchange="rellenarDatosRhUsuario(this)">
                    <option value="">— Selecciona un usuario —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}"
                            data-position="{{ $u->position }}"
                            data-cargo="{{ $u->cargo }}"
                            data-phone="{{ $u->phone }}"
                            data-payroll="{{ $u->payroll_number }}"
                            data-checador="{{ $u->checador_id }}"
                            data-curp="{{ $u->curp }}"
                            data-ine="{{ $u->ine }}"
                            data-acta="{{ $u->acta_nacimiento }}"
                            data-licencia="{{ $u->licencia }}"
                            data-domicilio="{{ $u->domicilio }}"
                            data-fecha-ingreso="{{ optional($u->fecha_ingreso)->format('Y-m-d') }}"
                            data-vacaciones="{{ $u->vacaciones_disponibles }}"
                            data-nce="{{ $u->nombre_contacto_emergencia }}"
                            data-numce="{{ $u->numero_contacto_emergencia }}"
                            data-domce="{{ $u->domicilio_contacto_emergencia }}"
                            data-nces="{{ $u->nombre_contacto_emergencia_secundario }}"
                            data-numces="{{ $u->numero_contacto_emergencia_secundario }}"
                            data-domces="{{ $u->domicilio_contacto_emergencia_secundario }}"
                            data-roles="{{ $u->roles->pluck('id')->implode(',') }}"
                            data-docs='@json($u->employeeDocuments->pluck("file_path", "name")->map(fn ($p) => \Illuminate\Support\Facades\Storage::url($p)))'>
                            {{ $u->name }} — {{ $u->email }}
                        </option>
                    @endforeach
                </select>
            </x-ui.form-group>

            {{-- ---------------- Roles ---------------- --}}
            <div class="rh-seccion">
                <x-ui.section-title style="margin:0 0 4px;">Roles</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 10px;">
                    Definen qué puede hacer. Se configuran en
                    <a href="{{ route('configuracion.roles.index') }}" class="link">Roles y permisos</a>.
                    Con varios roles, se queda con la suma.
                </p>
                <div class="rh-chips">
                    @foreach ($roles as $role)
                        <label class="rh-chip">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}">
                            <span>{{ $role->label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ---------------- Datos laborales ---------------- --}}
            <div class="rh-seccion">
                <x-ui.section-title style="margin:0 0 10px;">Datos laborales</x-ui.section-title>
                <div class="rh-grid">
                    <x-ui.form-group label="Puesto" name="position" placeholder="Ej. Ingeniero de sistemas" />
                    <x-ui.form-group label="Cargo" name="cargo" placeholder="Ej. Ingeniero, Licenciado" />
                    <x-ui.form-group label="Teléfono" name="phone" />
                    <x-ui.form-group label="Número de nómina" name="payroll_number" />
                    <x-ui.form-group label="ID de checador" name="checador_id" />
                    <x-ui.form-group label="Fecha de ingreso" name="fecha_ingreso" type="date" />
                    <x-ui.form-group label="Vacaciones disponibles (días)" name="vacaciones_disponibles" for="vacaciones_disponibles">
                        <input id="vacaciones_disponibles" type="number" name="vacaciones_disponibles" min="0" step="1">
                    </x-ui.form-group>
                </div>
            </div>

            {{-- ---------------- Identificación ---------------- --}}
            <div class="rh-seccion">
                <x-ui.section-title style="margin:0 0 4px;">Identificación</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 10px;">
                    Captura el número o folio y, si quieres, sube el documento escaneado (PDF o imagen, máx. 5 MB).
                </p>
                <div class="rh-grid">
                    <div>
                        <x-ui.form-group label="CURP" name="curp" maxlength="18" />
                        <x-ui.file-chip name="curp_file" doc="CURP" />
                    </div>
                    <div>
                        <x-ui.form-group label="INE" name="ine" />
                        <x-ui.file-chip name="ine_file" doc="INE" />
                    </div>
                    <div>
                        <x-ui.form-group label="Acta de nacimiento" name="acta_nacimiento" />
                        <x-ui.file-chip name="acta_nacimiento_file" doc="Acta de nacimiento" />
                    </div>
                    <div>
                        <x-ui.form-group label="Licencia de manejo" name="licencia" />
                        <x-ui.file-chip name="licencia_file" doc="Licencia de manejo" />
                    </div>
                </div>
                <x-ui.form-group label="Domicilio" name="domicilio" />
            </div>

            {{-- ---------------- Contactos de emergencia ---------------- --}}
            <div class="rh-seccion">
                <x-ui.section-title style="margin:0 0 10px;">Contacto de emergencia</x-ui.section-title>
                <div class="rh-grid">
                    <x-ui.form-group label="Nombre" name="nombre_contacto_emergencia" />
                    <x-ui.form-group label="Teléfono" name="numero_contacto_emergencia" />
                </div>
                <x-ui.form-group label="Domicilio del contacto" name="domicilio_contacto_emergencia" />
            </div>

            <div class="rh-seccion rh-seccion--ultima">
                <x-ui.section-title style="margin:0 0 10px;">Contacto de emergencia secundario</x-ui.section-title>
                <div class="rh-grid">
                    <x-ui.form-group label="Nombre" name="nombre_contacto_emergencia_secundario" />
                    <x-ui.form-group label="Teléfono" name="numero_contacto_emergencia_secundario" />
                </div>
                <x-ui.form-group label="Domicilio del contacto" name="domicilio_contacto_emergencia_secundario" />
            </div>
        </div>

        <div class="rh-pie">
            <button type="button" class="btn btn--ghost" data-cerrar-rh>Cancelar</button>
            <button type="submit" class="btn">Guardar</button>
        </div>
    </form>
</dialog>

<style>
    .rh-modal { width:min(680px, calc(100vw - 24px)); max-height:88vh; padding:0; overflow:hidden;
                border:1px solid var(--border); border-radius:16px;
                background:var(--surface); color:var(--text); }
    .rh-modal::backdrop { background:rgba(15,23,42,.45); }
    .rh-modal form { display:flex; flex-direction:column; max-height:88vh; }

    .rh-head { display:flex; align-items:flex-start; gap:12px; padding:20px 24px 16px;
               border-bottom:1px solid var(--border); }
    .rh-head > div:first-child { flex:1; min-width:0; }

    .rh-body { flex:1; overflow-y:auto; padding:18px 24px; }

    .rh-seccion { padding-top:16px; margin-top:16px; border-top:1px solid var(--border); }
    .rh-seccion--ultima { padding-bottom:4px; }

    .rh-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:0 16px; }

    .rh-chips { display:flex; gap:8px; flex-wrap:wrap; }
    .rh-chip input { position:absolute; opacity:0; width:0; height:0; }
    .rh-chip span { display:inline-flex; align-items:center; padding:7px 14px; border-radius:999px;
                    border:1px solid var(--border); font-size:13px; cursor:pointer; user-select:none;
                    transition:background .15s, border-color .15s, color .15s; }
    .rh-chip span:hover { border-color:var(--primary); color:var(--primary); }
    .rh-chip input:checked + span { background:var(--primary); border-color:var(--primary); color:#fff; }
    .rh-chip input:focus-visible + span { outline:2px solid var(--primary); outline-offset:2px; }

    .rh-pie { display:flex; justify-content:flex-end; gap:10px; padding:16px 24px;
              border-top:1px solid var(--border); background:var(--surface-2); }

    @media (max-width: 640px) {
        .rh-modal { width:100vw; max-width:100vw; max-height:100vh; border-radius:0; }
        .rh-modal form { max-height:100vh; }
        .rh-pie { flex-direction:column-reverse; }
        .rh-pie .btn { width:100%; justify-content:center; }
    }
</style>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('modalRh');
    if (!modal) return;

    /*
    | En fase de captura a propósito: el menú de tres puntos llama a
    | stopPropagation() en los clics de su interior, así que un listener
    | normal en document nunca vería el botón "Editar datos y roles".
    */
    document.addEventListener('click', function (e) {
        var abrir = e.target.closest('[data-abrir-rh]');

        if (abrir) {
            var select = document.getElementById('hr_user_id');
            var id = abrir.dataset.usuario || '';

            select.value = id;
            rellenarDatosRhUsuario(select);   // vacío también limpia el formulario
            modal.showModal();
            return;
        }

        if (e.target === modal || e.target.closest('[data-cerrar-rh]')) modal.close();
    }, true);

    document.querySelectorAll('.hr-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var etiqueta = input.closest('.hr-file-chip').querySelector('.hr-file-name');
            etiqueta.textContent = input.files.length ? input.files[0].name : 'Subir documento';
        });
    });
})();

function rellenarDatosRhUsuario(select) {
    var opt = select.options[select.selectedIndex];
    var modal = document.getElementById('modalRh');
    var d = opt ? opt.dataset : {};

    var set = function (name, valor) {
        var el = modal.querySelector('[name="' + name + '"]');
        if (el) el.value = valor || '';
    };

    set('position', d.position);
    set('cargo', d.cargo);
    set('phone', d.phone);
    set('payroll_number', d.payroll);
    set('checador_id', d.checador);
    set('curp', d.curp);
    set('ine', d.ine);
    set('acta_nacimiento', d.acta);
    set('licencia', d.licencia);
    set('domicilio', d.domicilio);
    set('fecha_ingreso', d.fechaIngreso);
    set('vacaciones_disponibles', d.vacaciones);
    set('nombre_contacto_emergencia', d.nce);
    set('numero_contacto_emergencia', d.numce);
    set('domicilio_contacto_emergencia', d.domce);
    set('nombre_contacto_emergencia_secundario', d.nces);
    set('numero_contacto_emergencia_secundario', d.numces);
    set('domicilio_contacto_emergencia_secundario', d.domces);

    var suyos = (d.roles || '').split(',').filter(Boolean);
    modal.querySelectorAll('input[name="roles[]"]').forEach(function (cb) {
        cb.checked = suyos.indexOf(cb.value) !== -1;
    });

    var docs = {};
    try { docs = JSON.parse(d.docs || '{}'); } catch (e) { docs = {}; }

    modal.querySelectorAll('.hr-file-link').forEach(function (link) {
        var url = docs[link.dataset.doc];
        link.href = url || '#';
        link.style.display = url ? 'inline-flex' : 'none';
    });
}
</script>
@endpush
