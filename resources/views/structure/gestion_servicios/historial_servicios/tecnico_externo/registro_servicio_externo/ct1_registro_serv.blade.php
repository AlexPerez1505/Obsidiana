        <!-- Paso 1: Cliente -->
        <div class="step-panel active" data-step="1">
            <div class="client-tabs">
                <button type="button" class="tab-btn active" data-tab="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Buscar cliente existente
                </button>
                <a href="{{ route('commercial.clientes.create', ['return_to' => route('gestion.servicios.historial.nueva_orden.type')]) }}" class="tab-btn" style="text-decoration:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Registrar nuevo cliente
                </a>
            </div>

            <input type="hidden" name="customer_id" id="customer_id" value="{{ $customers->first()?->id }}">

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
                        <div class="client-card {{ $loop->first ? 'selected' : '' }}" data-client="{{ $loop->index }}" data-name="{{ $fullName }}" data-phone="{{ $customer->telefono ?? '' }}" data-email="{{ $customer->correo ?? '' }}">
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
        'id' => $customer->id,
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
        const client = clients[index];
        if (client) {
            const nameEl = document.getElementById('sel-client-name');
            if (nameEl) nameEl.textContent = client.name;
            const techNameEl = document.getElementById('tech-client-name');
            if (techNameEl) techNameEl.textContent = client.name;
            const selAvatarEl = document.getElementById('sel-client-avatar');
            if (selAvatarEl) selAvatarEl.textContent = client.initials || 'CL';
            const techAvatarEl = document.getElementById('tech-client-avatar');
            if (techAvatarEl) techAvatarEl.textContent = client.initials || 'CL';
            const contactText = [client.phone, client.email].filter(Boolean).join(' · ') || 'Sin contacto';
            const infoEl = document.getElementById('sel-client-info');
            if (infoEl) infoEl.textContent = contactText;
            const techInfoEl = document.getElementById('tech-client-info');
            if (techInfoEl) techInfoEl.textContent = contactText;
            const customerInput = document.getElementById('customer_id');
            if (customerInput) customerInput.value = client.id;
        }
    }
    document.querySelectorAll('button.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('button.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    document.getElementById('client-search').addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.client-card').forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const phone = (card.dataset.phone || '').toLowerCase();
            const email = (card.dataset.email || '').toLowerCase();
            const match = name.includes(term) || phone.includes(term) || email.includes(term);
            card.style.display = match ? '' : 'none';
        });
    });

    if (clients.length > 0) selectClient(0);
</script>
@endpush
