<div class="flex items-center gap-3">
    <img 
        src="{{ asset('images/logo-pbb_htllogo.png') }}" 
        alt="PT. Panca Bima Bergawa" 
        class="h-8 w-auto object-contain dark:hidden"
        onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
    >
    <img 
        src="{{ asset('images/logo-pbb-white.png') }}" 
        alt="PT. Panca Bima Bergawa" 
        class="h-8 w-auto object-contain hidden dark:block"
        onerror="this.style.display='none';"
    >
    <div class="hidden flex flex-col">
        <span class="font-bold text-sm tracking-tight text-zinc-900 dark:text-white" style="font-family: 'Outfit', sans-serif;">
            PT. PANCA BIMA BERGAWA
        </span>
        <span class="text-[9px] uppercase tracking-wider text-sky-600 dark:text-sky-400 font-semibold font-mono">
            Commercial ERP
        </span>
    </div>
</div>
