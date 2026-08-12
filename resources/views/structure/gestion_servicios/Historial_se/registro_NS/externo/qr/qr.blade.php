@php
    $qrValue = $qrUrl ?? request()->fullUrl();
    $qrSize = $size ?? 180;
    $qrAlt = $alt ?? 'Código QR del servicio';
    $qrId = 'qrImage' . (isset($idSuffix) ? '_' . $idSuffix : '');
    $containerId = 'qrContainer' . (isset($idSuffix) ? '_' . $idSuffix : '');
@endphp

<div id="{{ $containerId }}" style="display:none;"></div>
<img id="{{ $qrId }}" class="ns-qr-img" alt="{{ $qrAlt }}" style="display:none;">

<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
    (function() {
        const container = document.getElementById('{{ $containerId }}');
        const img = document.getElementById('{{ $qrId }}');
        if (!container || !img) return;

        new QRCode(container, {
            text: '{{ addslashes($qrValue) }}',
            width: {{ $qrSize }},
            height: {{ $qrSize }},
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        const canvas = container.querySelector('canvas');
        if (canvas) {
            img.src = canvas.toDataURL('image/png');
            img.style.display = 'block';
        } else {
            const fallbackImg = container.querySelector('img');
            if (fallbackImg) {
                img.src = fallbackImg.src;
                img.style.display = 'block';
            }
        }
    })();
</script>
