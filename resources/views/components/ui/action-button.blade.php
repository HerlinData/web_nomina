<div class="relative group/tooltip">
    <button {{ $attributes->merge([
        'type' => 'button',
        'class' => 'w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:text-white transition-colors duration-300 cursor-pointer ' . $hoverClass()
    ]) }}>
        <i class="fa-solid {{ $icon }} text-sm pointer-events-none"></i>
    </button>
    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 invisible group-hover/tooltip:visible opacity-0 group-hover/tooltip:opacity-100 transition-opacity bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-50">
        {{ $title }}
        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
    </span>
</div>
