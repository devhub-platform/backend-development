@php
    use Agencetwogether\AlertBox\AlertBox;
    use Filament\Support\View\Components\CalloutComponent\IconComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = AlertBox::getColor($type->value) ?? $type->getColor();
    $styles = AlertBox::resolveColorStyles($color, [100, 400, 500, 950]);
@endphp

<div
    @class([
        'flex items-center gap-1 rounded-md px-2 py-1 text-sm font-bold',
        'bg-custom-400/10 text-custom-950 dark:bg-custom-500/20 dark:text-custom-100' => $type->value != 'none',
        'bg-white text-black dark:bg-gray-500/20 dark:text-gray-100' => $type->value == 'none',
    ])
    style="{{ $styles }}"
>
    @if (filled($type->getIcon()))
        {{ \Filament\Support\generate_icon_html($type->getIcon(), null, (new ComponentAttributeBag)->color(IconComponent::class, $color)) }}
    @endif

    <span>{{ $type->getLabel() }}</span>
</div>
