@props(['label' => 'Get Job Alerts'])

@php
    $id = 'push-subscribe-' . uniqid();
@endphp

<div id="{{ $id }}" data-push-root data-label="{{ $label }}" hidden class="inline-flex">
    <button
        type="button"
        data-push-toggle
        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
    >
        <span class="material-symbols-outlined text-[18px]" data-push-icon>notifications</span>
        <span data-push-label>{{ $label }}</span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function attach(root) {
                    if (!window.JobsPicPush || !window.JobsPicPush.isSupported()) return;

                    const btn = root.querySelector('[data-push-toggle]');
                    const iconEl = root.querySelector('[data-push-icon]');
                    const labelEl = root.querySelector('[data-push-label]');
                    const defaultLabel = root.dataset.label || 'Get Job Alerts';

                    let busy = false;
                    let subscribed = false;
                    let blocked = false;

                    function render() {
                        if (blocked) {
                            labelEl.textContent = 'Notifications Blocked';
                            btn.disabled = true;
                            iconEl.textContent = 'notifications_off';
                            btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                            btn.classList.add('bg-slate-400');
                            return;
                        }
                        btn.disabled = busy;
                        iconEl.textContent = subscribed ? 'notifications_active' : 'notifications';
                        labelEl.textContent = busy
                            ? 'Working…'
                            : subscribed ? 'Alerts On' : defaultLabel;
                        btn.classList.toggle('bg-emerald-600', subscribed);
                        btn.classList.toggle('hover:bg-emerald-700', subscribed);
                        btn.classList.toggle('bg-primary', !subscribed);
                        btn.classList.toggle('hover:bg-primary-dark', !subscribed);
                    }

                    async function refresh() {
                        const status = await window.JobsPicPush.getStatus();
                        subscribed = status === 'subscribed';
                        blocked = status === 'blocked';
                        render();
                    }

                    btn.addEventListener('click', async () => {
                        if (!window.JobsPicPush.isSupported() || blocked) return;
                        busy = true;
                        render();
                        try {
                            if (subscribed) {
                                await window.JobsPicPush.unsubscribe();
                                subscribed = false;
                            } else {
                                await window.JobsPicPush.subscribe();
                                subscribed = true;
                            }
                        } catch (e) {
                            console.warn('[JobsPic] push toggle failed:', e);
                            const status = await window.JobsPicPush.getStatus();
                            subscribed = status === 'subscribed';
                            blocked = status === 'blocked';
                        } finally {
                            busy = false;
                            render();
                        }
                    });

                    root.hidden = false;
                    refresh();
                }

                function init() {
                    document.querySelectorAll('[data-push-root]').forEach(attach);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @endpush
@endonce
