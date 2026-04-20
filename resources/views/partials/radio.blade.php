@props(['name', 'value', 'label', 'help' => null, 'model'])

<label class="radio-option {{ $model === $value ? 'selected' : '' }}" wire:click="$set('data.{{ $name }}', '{{ $value }}')">
    <span class="radio-dot"></span>
    <span>
        <span class="radio-label">{{ $label }}</span>
        @if($help)
            <div class="radio-help">{{ $help }}</div>
        @endif
    </span>
</label>
