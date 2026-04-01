@if (session('success'))
<div style="background:#d1fae5;border-left:4px solid #059669;
                padding:.85rem 1.2rem;border-radius:0 6px 6px 0;
                margin-bottom:1rem;color:#065f46;display:flex;
                justify-content:space-between;align-items:center;">
    <span>{{ session('success') }}</span>
    <button onclick="this.parentElement.remove()"
        style="background:none;border:none;cursor:pointer;
                       font-size:1.1rem;color:#065f46;line-height:1;">×</button>
</div>
@endif

@if (session('error'))
<div style="background:#fee2e2;border-left:4px solid #dc2626;
                padding:.85rem 1.2rem;border-radius:0 6px 6px 0;
                margin-bottom:1rem;color:#991b1b;display:flex;
                justify-content:space-between;align-items:center;">
    <span>{{ session('error') }}</span>
    <button onclick="this.parentElement.remove()"
        style="background:none;border:none;cursor:pointer;
                       font-size:1.1rem;color:#991b1b;line-height:1;">×</button>
</div>
@endif

@if (session('warning'))
<div style="background:#fef9c3;border-left:4px solid #ca8a04;
                padding:.85rem 1.2rem;border-radius:0 6px 6px 0;
                margin-bottom:1rem;color:#854d0e;display:flex;
                justify-content:space-between;align-items:center;">
    <span>{{ session('warning') }}</span>
    <button onclick="this.parentElement.remove()"
        style="background:none;border:none;cursor:pointer;
                       font-size:1.1rem;color:#854d0e;line-height:1;">×</button>
</div>
@endif

@if (session('info'))
<div style="background:#dbeafe;border-left:4px solid #2563eb;
                padding:.85rem 1.2rem;border-radius:0 6px 6px 0;
                margin-bottom:1rem;color:#1e40af;display:flex;
                justify-content:space-between;align-items:center;">
    <span>{{ session('info') }}</span>
    <button onclick="this.parentElement.remove()"
        style="background:none;border:none;cursor:pointer;
                       font-size:1.1rem;color:#1e40af;line-height:1;">×</button>
</div>
@endif