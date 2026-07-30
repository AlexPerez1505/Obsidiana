@props(['logs'])

<div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>IP</th>
                <th>Ubicación</th>
                <th>Navegador</th>
                <th>Sistema</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->logged_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                    <td>{{ $log->location ?? '—' }}</td>
                    <td>{{ $log->browser ?? '—' }}</td>
                    <td>{{ $log->platform ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
