document.addEventListener('click', async (event) => {
    const copyButton = event.target.closest('[data-copy-text]');

    if (!copyButton) {
        return;
    }

    const originalText = copyButton.textContent;
    const value = copyButton.dataset.copyText;

    try {
        await navigator.clipboard.writeText(value ?? '');
        copyButton.textContent = 'Copied';
        window.setTimeout(() => {
            copyButton.textContent = originalText;
        }, 1400);
    } catch {
        copyButton.textContent = 'Gagal';
        window.setTimeout(() => {
            copyButton.textContent = originalText;
        }, 1400);
    }
});

const initLinkEditorFocus = () => {
    let activeEditor = null;
    let cleanupTimer = null;

    const activeClasses = [
        'border-cyan-300/30',
        'bg-cyan-400/10',
        'ring-2',
        'ring-cyan-300/20',
        'shadow-2xl',
    ];

    const clearActiveEditor = () => {
        if (cleanupTimer) {
            window.clearTimeout(cleanupTimer);
            cleanupTimer = null;
        }

        if (!activeEditor) {
            return;
        }

        activeEditor.classList.remove(...activeClasses);
        activeEditor = null;
    };

    const activateEditor = (targetId) => {
        const editor = document.getElementById(targetId);

        if (!editor || !editor.matches('[data-link-editor]')) {
            return;
        }

        clearActiveEditor();

        activeEditor = editor;
        activeEditor.classList.add(...activeClasses);
        activeEditor.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });

        const urlInput = activeEditor.querySelector('[data-link-url-input]');

        window.setTimeout(() => {
            urlInput?.focus({ preventScroll: true });
            urlInput?.select();
        }, 220);

        cleanupTimer = window.setTimeout(() => {
            clearActiveEditor();
        }, 2600);
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-link-editor-trigger]');

        if (!trigger) {
            return;
        }

        event.preventDefault();

        const hash = trigger.getAttribute('href');

        if (!hash || !hash.startsWith('#')) {
            return;
        }

        const targetId = hash.slice(1);

        if (window.location.hash !== hash) {
            window.history.replaceState(null, '', hash);
        }

        activateEditor(targetId);
    });

    if (window.location.hash.startsWith('#link-editor-')) {
        window.setTimeout(() => {
            activateEditor(window.location.hash.slice(1));
        }, 180);
    }
};

const initAnalyticsCharts = () => {
    document.querySelectorAll('[data-analytics-chart]').forEach((chart) => {
        if (chart.dataset.initialized === 'true') {
            return;
        }

        chart.dataset.initialized = 'true';

        const tooltip = chart.querySelector('[data-chart-tooltip]');
        const crosshair = chart.querySelector('[data-chart-crosshair]');
        const slots = [...chart.querySelectorAll('[data-chart-slot]')];
        const markers = [...chart.querySelectorAll('[data-chart-marker]')];

        if (!tooltip || slots.length === 0) {
            return;
        }

        const resetMarkers = () => {
            markers.forEach((marker) => {
                marker.setAttribute('r', marker.dataset.baseRadius ?? '4.5');
                marker.setAttribute('stroke', 'rgba(7, 17, 29, 0.9)');
                marker.setAttribute('stroke-width', '2');
            });
        };

        const highlightMarkers = (dayIndex) => {
            markers.forEach((marker) => {
                const active = marker.dataset.dayIndex === dayIndex;
                marker.setAttribute('r', active ? '7' : (marker.dataset.baseRadius ?? '4.5'));
                marker.setAttribute('stroke', active ? 'rgba(255, 255, 255, 0.9)' : 'rgba(7, 17, 29, 0.9)');
                marker.setAttribute('stroke-width', active ? '3' : '2');
            });
        };

        const renderTooltip = (slot) => {
            tooltip.innerHTML = `
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">${slot.dataset.label}</div>
                <div class="mt-3 grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-4"><span class="text-slate-300">Page View</span><span class="font-semibold text-orange-300">${slot.dataset.views}</span></div>
                    <div class="flex items-center justify-between gap-4"><span class="text-slate-300">Visitor Unik</span><span class="font-semibold text-violet-300">${slot.dataset.uniqueVisitors}</span></div>
                    <div class="flex items-center justify-between gap-4"><span class="text-slate-300">CTA Click</span><span class="font-semibold text-cyan-300">${slot.dataset.ctaClicks}</span></div>
                    <div class="flex items-center justify-between gap-4"><span class="text-slate-300">Link Click</span><span class="font-semibold text-rose-300">${slot.dataset.linkClicks}</span></div>
                </div>
                <div class="mt-3 border-t border-white/8 pt-3 text-xs uppercase tracking-[0.22em] text-slate-500">CTA Rate ${slot.dataset.ctaRate}%</div>
            `;
        };

        const showTooltip = (slot) => {
            renderTooltip(slot);
            tooltip.classList.remove('hidden');

            const chartRect = chart.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const x = Number(slot.dataset.x ?? 0);
            const left = Math.min(
                Math.max(x - (tooltipRect.width / 2), 12),
                chartRect.width - tooltipRect.width - 12,
            );

            tooltip.style.left = '0px';
            tooltip.style.top = '0px';
            tooltip.style.transform = `translate(${left}px, 8px)`;

            if (crosshair) {
                crosshair.setAttribute('x1', slot.dataset.x ?? '0');
                crosshair.setAttribute('x2', slot.dataset.x ?? '0');
                crosshair.setAttribute('opacity', '1');
            }

            highlightMarkers(slot.dataset.dayIndex ?? '');
        };

        const hideTooltip = () => {
            tooltip.classList.add('hidden');

            if (crosshair) {
                crosshair.setAttribute('opacity', '0');
            }

            resetMarkers();
        };

        slots.forEach((slot) => {
            slot.addEventListener('mouseenter', () => showTooltip(slot));
            slot.addEventListener('mousemove', () => showTooltip(slot));
            slot.addEventListener('focus', () => showTooltip(slot));
            slot.addEventListener('blur', hideTooltip);
        });

        chart.addEventListener('mouseleave', hideTooltip);
    });
};

initAnalyticsCharts();
initLinkEditorFocus();

window.setTimeout(() => {
    document.querySelectorAll('[data-flash]').forEach((element) => {
        element.remove();
    });
}, 2600);
