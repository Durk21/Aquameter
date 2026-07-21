<template>
    <button
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-br from-red-500 to-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition-all duration-200 ease-out hover:shadow-elevated hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 active:translate-y-0 disabled:opacity-50 disabled:pointer-events-none disabled:translate-y-0 disabled:shadow-none"
    >
        <slot />
    </button>
</template>
