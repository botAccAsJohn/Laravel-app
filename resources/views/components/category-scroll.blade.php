@props(['categories'])

<section class="group/section space-y-6">
    <div class="flex items-center justify-between px-2">
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Shop by Category</h2>
    </div>

    <div class="relative">
        <div class="flex overflow-x-auto gap-4 pb-4 scrollbar-hide no-scrollbar -mx-6 px-6 md:mx-0 md:px-0">
            @foreach($categories as $category)
                <a href="{{ route('products.index', ['categories[]' => $category->id]) }}" 
                   class="flex-none group/cat px-8 py-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-xl hover:border-indigo-500 transition-all duration-300 flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-slate-200 group-hover/cat:bg-indigo-500 transition-colors"></span>
                    <span class="font-bold text-slate-700 group-hover/cat:text-indigo-600">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
