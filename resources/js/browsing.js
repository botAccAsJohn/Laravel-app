document.addEventListener('DOMContentLoaded', function () {
    const listEl = document.getElementById('active-browsers-list');
    if (!listEl) return;

    const currentUserId = parseInt(listEl.dataset.adminId) || null;
    const transActiveNow = listEl.dataset.transActiveNow || 'ACTIVE NOW';
    const transNoUsers = listEl.dataset.transNoUsers || 'No active users online';
    const countEl = document.getElementById('active-browsers-count');
    
    let activeUsers = [];
    let presenceRetries = 0;

    function initBrowsingPresence() {
        if (window.Echo) {
            console.log('Echo presence join: store.browsing (Admin view)');
            window.Echo.join('store.browsing')
                .here((users) => {
                    activeUsers = users;
                    updateBrowsersUI();
                })
                .joining((user) => {
                    activeUsers.push(user);
                    updateBrowsersUI();
                })
                .leaving((user) => {
                    activeUsers = activeUsers.filter(u => u.id !== user.id);
                    updateBrowsersUI();
                });
        } else if (presenceRetries < 10) {
            presenceRetries++;
            setTimeout(initBrowsingPresence, 500);
        }
    }

    function updateBrowsersUI() {
        if (countEl) {
            countEl.textContent = activeUsers.length;
        }

        const others = activeUsers.filter(u => u.id !== currentUserId);

        if (others.length === 0) {
            listEl.innerHTML = `<li id="no-browsers-msg" class="text-xs text-center py-6 text-gray-400 font-medium italic">${transNoUsers}</li>`;
            return;
        }

        listEl.innerHTML = others.map(u => `
            <li class="flex flex-col gap-1 p-3 rounded-xl hover:bg-gray-50 transition-all border border-transparent hover:border-indigo-50 group">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center text-[10px] font-bold uppercase transition-transform group-hover:rotate-12">
                        ${u.name.charAt(0)}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-gray-900 truncate">${u.name}</p>
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[9px] text-emerald-600 font-black uppercase tracking-widest">${transActiveNow}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 pl-11">
                    <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 rounded-md border border-gray-200/50">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="text-[10px] font-mono text-gray-500 truncate" title="${u.path}">${u.path}</span>
                    </div>
                </div>
            </li>
        `).join('');
    }

    initBrowsingPresence();
});
