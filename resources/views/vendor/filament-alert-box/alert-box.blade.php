@php
    use Agencetwogether\AlertBox\AlertBox;
    use Agencetwogether\AlertBox\Enums\AlertType;
    use Filament\Forms\Components\RichEditor\RichContentRenderer;
    use Filament\Support\View\Components\CalloutComponent\IconComponent;
    use Illuminate\View\ComponentAttributeBag;

    if (! $preview && $config) {
        $style = $config['style'];
        $showIcon = $config['showIcon'];
        $title = $config['title'];
        $content = $config['content'];
    } else {
        $style = $get('style');
        $showIcon = $get('showIcon');
        $title = $get('title');
        $content = $get('content');
    }

    $type = AlertType::tryFrom($style) ?? AlertType::NONE;
    $color = AlertBox::getColor($type->value) ?? $type->getColor();
    $styles = AlertBox::resolveColorStyles($color, [100, 400, 500, 600, 950]);
@endphp

@if ($preview)
    <div class="fi-fo-field mb-2">
        <div class="fi-fo-field-label-col">
            <div class="fi-fo-field-label-ctn">
                <div class="fi-fo-field-label">
                    <span class="fi-fo-field-label-content">
                        {{ __('filament-alert-box::alert-box.form.common.preview') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif

<div
    @class([
        'alert-block prose-p:my-0 my-4 rounded-lg px-4 py-3',
        "alert-block-{$type->value}",
        'border-custom-400 bg-custom-400/10 text-custom-950 dark:bg-custom-500/20 dark:text-custom-100 prose-code:[p_&]:bg-custom-600/10 prose-code:dark:[p_&]:bg-white/20 border-s-4' => $type->value !== 'none',
        'bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => $type->value == 'none',
    ])
    style="{{ $styles }}"
>
    @if (filled($title) || $showIcon)
        <div class="flex items-center gap-2 pb-2">
            @if (filled($type->getIcon()) && $showIcon)
                {{ \Filament\Support\generate_icon_html($type->getIcon(), null, (new ComponentAttributeBag)->color(IconComponent::class, $color)) }}
            @endif

            @if (filled($title))
                <p class="m-0! text-sm font-bold">{{ $title }}</p>
            @endif
        </div>
    @endif

    {!! RichContentRenderer::make($content)->toHtml() !!}
</div>
