<button
    id="global-back-to-top"
    type="button"
    onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
    aria-label="Back to top"
    title="Back to top"
    style="
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: 30;
        width: 44px;
        height: 44px;
        border-radius: 9999px;
        border: 1px solid rgba(107, 114, 128, 0.35);
        background: rgba(31, 41, 55, 0.92);
        color: white;
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);

        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(6px);

        transition:
            opacity 160ms ease,
            transform 160ms ease,
            visibility 160ms ease;
    "
>
    ↑
</button>

<script>
    (() => {
        const button =
            document.getElementById(
                'global-back-to-top'
            );

        if (! button) {
            return;
        }

        const updateBackToTop = () => {
            const visible =
                window.scrollY > 300;

            document.documentElement.style.setProperty(
                '--coqp-report-right',
                visible ? '4.75rem' : '1.25rem'
            );

            button.style.opacity =
                visible ? '1' : '0';

            button.style.visibility =
                visible ? 'visible' : 'hidden';

            button.style.pointerEvents =
                visible ? 'auto' : 'none';

            button.style.transform =
                visible
                    ? 'translateY(0)'
                    : 'translateY(6px)';
        };

        updateBackToTop();

        window.addEventListener(
            'scroll',
            updateBackToTop,
            {
                passive: true,
            }
        );
    })();
</script>

@include('components.global-toasts')
@include('components.problem-report')
