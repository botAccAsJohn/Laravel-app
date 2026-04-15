@props(['title', 'products', 'viewAllUrl' => '#'])

<section class="group/section space-y-6">
    <div class="flex items-center justify-between px-2">
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $title }}</h2>
        <a href="{{ $viewAllUrl }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-1 group">
            See all
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>

    <div class="relative">
        {{-- Previous Button --}}
        <button onclick="this.nextElementSibling.scrollBy({left: -400, behavior: 'smooth'})" 
                class="absolute -left-4 top-1/2 -translate-y-1/2 z-10 p-4 bg-white/90 backdrop-blur-sm border border-slate-200 rounded-full shadow-xl text-slate-400 hover:text-indigo-600 hover:scale-110 opacity-0 group-hover/section:opacity-100 transition-all duration-300 hidden md:flex">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        </button>

        {{-- Scrollable Area --}}
        <div class="flex overflow-x-auto gap-6 pb-8 snap-x snap-mandatory scrollbar-hide no-scrollbar -mx-6 px-6 md:mx-0 md:px-0">
            @foreach($products as $product)
                <div class="flex-none w-[280px] md:w-[320px] snap-start">
                    <x-productCard :product="$product" />
                </div>
            @endforeach
        </div>

        {{-- Next Button --}}
        <button onclick="this.previousElementSibling.scrollBy({left: 400, behavior: 'smooth'})" 
                class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 p-4 bg-white/90 backdrop-blur-sm border border-slate-200 rounded-full shadow-xl text-slate-400 hover:text-indigo-600 hover:scale-110 opacity-0 group-hover/section:opacity-100 transition-all duration-300 hidden md:flex">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
        </button>
    </div>
</section>
