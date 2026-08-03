        <!-- Paso 1: Cliente -->
        <div class="step-panel active" data-step="1">
            <div class="client-tabs">
                <button type="button" class="tab-btn active" data-tab="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Buscar cliente existente
                </button>
                <button type="button" class="tab-btn" data-tab="new">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Registrar nuevo cliente
                </button>
            </div>

            <div id="tab-search">
                <div class="search-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" placeholder="Buscar por nombre, telefono o correo" id="client-search">
                </div>
                <p class="muted" style="text-align:center; margin:14px 0; font-size:13px;">Resultados encontrados</p>

                <div class="client-list">
                    @forelse($customers as $customer)
                        @php
                            $customerNames = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
                            $initials = count($customerNames) >= 2
                                ? strtoupper(substr($customerNames[0], 0, 1) . substr($customerNames[1], 0, 1))
                                : (count($customerNames) === 1 ? strtoupper(substr($customerNames[0], 0, 2)) : 'CL');
                            $fullName = trim($customer->nombre . ' ' . $customer->apellido) ?: 'Sin nombre';
                        @endphp
                        <div class="client-card {{ $loop->first ? 'selected' : '' }}" data-client="{{ $loop->index }}">
                            <div class="client-avatar" style="{{ $loop->first ? '' : 'background:var(--muted);' }}">{{ $initials }}</div>
                            <div class="client-meta">
                                <div class="client-name">{{ $fullName }}</div>
                                <div class="client-info">
                                    <span>{{ $customer->telefono ?? 'Sin teléfono' }}</span>
                                    <span>{{ $customer->correo ?? 'Sin correo' }}</span>
                                </div>
                            </div>
                            <button type="button" class="btn {{ $loop->first ? '' : 'btn--ghost' }}" style="padding:8px 14px; font-size:13px;" onclick="selectClient({{ $loop->index }})">{{ $loop->first ? 'Seleccionado' : 'Seleccionar' }}</button>
                        </div>
                    @empty
                        <p class="muted" style="text-align:center; margin:14px 0; font-size:13px;">No hay clientes registrados.</p>
                    @endforelse
                </div>
            </div>

            <div id="tab-new" style="display:none;">
                <p class="muted" style="margin-bottom:14px;">Completa los datos del nuevo cliente.</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="new_client_name" placeholder="Ej. Dr. Juan Perez">
                    </div>
                    <div class="form-group">
                        <label>Telefono</label>
                        <input type="text" name="new_client_phone" placeholder="Ej. 551 234 5678">
                    </div>
                    <div class="form-group">
                        <label>Correo</label>
                        <input type="text" name="new_client_email" placeholder="Ej. cliente@mail.com">
                    </div>
                </div>
            </div>

            <p style="margin-top:18px; font-size:13px; color:var(--muted);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                ¿No encuentras al cliente? Cambiate a "Registrar nuevo cliente" para agregar un nuevo cliente al sistema.
            </p>
        </div>

@push('scripts')
@php
$clients = $customers->map(function($customer) {
    $names = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
    if (count($names) >= 2) {
        $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
    } elseif (count($names) === 1) {
        $initials = strtoupper(substr($names[0], 0, 2));
    } else {
        $initials = 'CL';
    }
    return [
        'name' => trim($customer->nombre . ' ' . $customer->apellido) ?: 'Sin nombre',
        'phone' => $customer->telefono ?? '',
        'email' => $customer->correo ?? '',
        'initials' => $initials,
    ];
})->values();
@endphp
<script>
    const clients = @json($clients);
    let selectedClient = 0;
    function selectClient(index) {
        selectedClient = index;
        document.querySelectorAll('.client-card').forEach((card, i) => {
            const btn = card.querySelector('button');
            if (i === index) {
                card.classList.add('selected');
                btn.className = 'btn';
                btn.style.cssText = 'padding:8px 14px; font-size:13px;';
                btn.textContent = 'Seleccionado';
            } else {
                card.classList.remove('selected');
                btn.className = 'btn btn--ghost';
                btn.style.cssText = 'padding:8px 14px; font-size:13px;';
                btn.textContent = 'Seleccionar';
            }
        });
        document.getElementById('sel-client-name').textContent = clients[index].name;
        document.getElementById('tech-client-name').textContent = clients[index].name;
    }
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-search').style.display = btn.dataset.tab === 'search' ? 'block' : 'none';
            document.getElementById('tab-new').style.display = btn.dataset.tab === 'new' ? 'block' : 'none';
        });
    });
</script>
@endpush
