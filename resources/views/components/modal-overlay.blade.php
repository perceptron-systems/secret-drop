@props(['show', 'close'])

<template x-teleport="body">
    <div
        x-show="{{ $show }}"
        x-cloak
        @click="{{ $close }}"
        @keydown.escape.window="{{ $close }}"
        x-transition:enter="transition ease-out duration-150 motion-reduce:duration-0"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100 motion-reduce:duration-0"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-100 bg-black/50"
        {{ $attributes }}
    >
        {{ $slot }}
    </div>
</template>
