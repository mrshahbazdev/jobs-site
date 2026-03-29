@props(['variant' => 'sidebar', 'title' => null, 'subtitle' => null, 'icon' => null])

@if($variant === 'sidebar')
<div class="bg-gradient-to-br from-[#128C7E] to-[#25D366] rounded-3xl p-6 text-white shadow-xl mb-4 relative overflow-hidden group">
    <!-- Decorative background elements -->
    <div class="absolute -right-6 -bottom-6 opacity-20 group-hover:scale-110 transition-transform duration-700">
        <span class="material-symbols-outlined text-9xl">chat</span>
    </div>
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>

    <div class="relative z-10 flex flex-col gap-4">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-xl backdrop-blur-md border border-white/30">
                <span class="material-symbols-outlined text-white">whatsapp</span>
            </div>
            <div>
                <h3 class="text-xl font-black leading-tight text-white mb-0.5">{{ $title ?? 'WhatsApp Alerts' }}</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#d1fae5]">{{ $subtitle ?? 'Never miss a job' }}</p>
            </div>
        </div>

        @if(!$title)
        <p class="text-xs font-bold leading-relaxed text-emerald-50/90">Get the latest Government & Private jobs directly on your phone, every single day!</p>
        @endif
        
        <div class="flex flex-col gap-3">
            <div class="group/input relative">
                <input type="text" id="wa_name_v2" placeholder="Full Name" class="w-full bg-white/10 border border-white/20 rounded-2xl px-4 py-3 text-sm placeholder:text-white/60 focus:bg-white/20 focus:outline-none focus:border-white/50 transition-all font-bold">
            </div>
            <div class="group/input relative">
                <input type="text" id="wa_number_v2" placeholder="WhatsApp Number" class="w-full bg-white/10 border border-white/20 rounded-2xl px-4 py-3 text-sm placeholder:text-white/60 focus:bg-white/20 focus:outline-none focus:border-white/50 transition-all font-bold">
            </div>
            
            <button type="button" onclick="subscribeWhatsAppV2()" class="w-full bg-white text-[#075E54] font-black py-4 rounded-2xl shadow-lg hover:shadow-emerald-900/20 hover:bg-emerald-50 transition-all active:scale-95 text-xs uppercase tracking-widest mt-1 border border-white">
                Subscribe Free
            </button>
        </div>
        
        <p class="text-[10px] text-center font-bold text-emerald-100/70 italic italic">No Spam. Secure & Encrypted.</p>
    </div>
</div>

<script>
    function subscribeWhatsAppV2() {
        const name = document.getElementById('wa_name_v2').value;
        const number = document.getElementById('wa_number_v2').value;
        if(!number) return alert('Please enter your WhatsApp number');
        
        const text = `Assalamu Alaikum! I want to subscribe to Jobs Alerts.%0AName: ${name}%0ANumber: ${number}`;
        window.open(`https://wa.me/{{ $settings['whatsapp_alert_number'] ?? '923000000000' }}?text=${text}`, '_blank');
    }
</script>

@else
<!-- Large CTA Variant for Homepage Hero or Middle -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-sm relative overflow-hidden flex flex-col md:flex-row items-center gap-8">
    <div class="md:w-2/3">
        <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mb-4">{{ $title ?? 'Join Our Exclusive Job Networks' }}</h2>
        <p class="text-slate-500 dark:text-slate-400 mb-6 font-medium leading-relaxed">{{ $subtitle ?? 'Stay updated with 200+ daily opportunities across Pakistan. Join 50,000+ candidates receiving instant alerts.' }}</p>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('jobs.whatsapp') }}" class="flex items-center gap-2 bg-[#075E54] text-white px-6 py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#064e46] transition-all shadow-lg shadow-emerald-600/20">
                <span class="material-symbols-outlined">whatsapp</span>
                Join Groups
            </a>
            <a href="{{ route('jobs.whatsapp') }}" class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-6 py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined">chat</span>
                Learn More
            </a>
        </div>
    </div>
    <div class="md:w-1/3 flex justify-center">
        <div class="relative">
            <div class="w-32 h-32 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center animate-pulse">
                <span class="material-symbols-outlined text-6xl text-emerald-600">{{ $icon ?? 'groups' }}</span>
            </div>
            <div class="absolute -top-2 -right-2 bg-rose-500 text-white text-[10px] font-black px-2 py-1 rounded-full shadow-lg">99+ Online</div>
        </div>
    </div>
</div>
@endif
