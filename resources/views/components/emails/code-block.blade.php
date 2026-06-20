@props([
    'label' => null,
])
<div style="margin:24px 0; text-align:center;">
    @if($label)
        <div
            style="margin-bottom:10px; font-size:13px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#64748b;">{{ $label }}</div>
    @endif
    <div
        style="display:inline-block; min-width:220px; padding:20px 24px; border-radius:16px; border:1px dashed #cbd5e1; background:#f8fafc; color:#0f172a; font-size:34px; font-weight:800; letter-spacing:0.18em; line-height:1; font-family:monospace;">
        {{ $slot }}
    </div>
</div>
