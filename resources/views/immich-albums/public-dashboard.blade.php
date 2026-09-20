<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Churches of Quezon Province Albums</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            color-scheme: dark;
        }

        body {
            margin: 0;
            background: #09090b;
            color: #f4f4f5;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page {
            width: 100%;
            padding: 28px;
        }

        .header {
            margin-bottom: 24px;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .header-copy {
            min-width: 0;
        }

        .slideshow-button {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 24px;
            border-radius: 14px;
            background: #0284c7;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition:
                background .18s ease,
                transform .18s ease,
                box-shadow .18s ease;
        }

        .slideshow-button:hover {
            background: #0369a1;
            transform: translateY(-1px);
            box-shadow:
                0 10px 24px rgb(2 132 199 / .25);
        }

        .title {
            margin: 0;
            font-size: clamp(28px, 4vw, 46px);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .subtitle {
            margin: 7px 0 0;
            color: #a1a1aa;
            font-size: 15px;
        }

        .search-wrap {
            margin-top: 22px;
            width: 100%;
        }

        .search {
            width: 100%;
            min-height: 48px;
            border: 1px solid #3f3f46;
            border-radius: 14px;
            background: #18181b;
            color: white;
            padding: 0 16px;
            font: inherit;
            outline: none;
        }

        .search:focus {
            border-color: #38bdf8;
            box-shadow:
                0 0 0 1px #38bdf8;
        }

        /*
         * Full-width public gallery.
         *
         * Phone        = 1 column
         * Tablet       = 2 columns
         * Desktop      = 3 columns
         * Large screen = 4 columns
         */
        .gallery {
            display: grid;
            grid-template-columns:
                repeat(1, minmax(0, 1fr));
            gap: 18px;
            width: 100%;
        }

        @media (min-width: 680px) {
            .gallery {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1050px) {
            .gallery {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1380px) {
            .gallery {
                grid-template-columns:
                    repeat(4, minmax(0, 1fr));
            }
        }

        .album-card {
            min-width: 0;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #3f3f46;
            border-radius: 18px;
            background: #18181b;
            cursor: pointer;
            transition:
                border-color .18s ease,
                transform .18s ease,
                box-shadow .18s ease;
        }

        @media (hover: hover) and (pointer: fine) {
            .album-card:hover {
                border-color: #38bdf8;
                transform: translateY(-2px);
                box-shadow:
                    0 12px 28px rgb(0 0 0 / .28);
            }

            .album-card:hover .flip-inner {
                transform: rotateY(180deg);
            }
        }

        .album-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            perspective: 1000px;
            background: #27272a;
            overflow: hidden;
        }

        .flip-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition:
                transform .5s ease;
        }

        .album-thumb.is-flipped .flip-inner {
            transform: rotateY(180deg);
        }

        .flip-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .flip-front img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #71717a;
            font-size: 56px;
        }

        .flip-back {
            transform: rotateY(180deg);
            display: grid;
            place-items: center;
            padding: 8px;
            background: white;
        }

        .qr-target {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
        }

        .qr-target img,
        .qr-target canvas {
            width: 100% !important;
            height: auto !important;
            max-width: 100%;
            max-height: 100%;
            display: block;
            margin: 0 auto;
        }

        .card-body {
            padding: 16px;
            flex: 1;
        }

        .album-name {
            margin: 0;
            font-size: 18px;
            line-height: 1.3;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .meta {
            margin-top: 7px;
            color: #a1a1aa;
            font-size: 13px;
        }

        .description {
            margin: 13px 0 0;
            color: #d4d4d8;
            font-size: 14px;
            line-height: 1.55;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .card-footer {
            margin-top: auto;
            padding: 14px 16px;
            border-top: 1px solid #3f3f46;
            background: #09090b;
        }

        .footer-label {
            color: #a5b4fc;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .footer-date {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 700;
        }

        .empty,
        .error {
            grid-column: 1 / -1;
            border: 1px solid #3f3f46;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            color: #a1a1aa;
            background: #18181b;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 679px) {
            .page {
                padding: 16px;
            }

            .header-top {
                align-items: stretch;
                flex-direction: column;
                gap: 16px;
            }

            .slideshow-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="header">
            <div class="header-top">
                <div class="header-copy">
                    <h1 class="title">
                        Churches of Quezon Province Albums
                    </h1>

                    <p class="subtitle">
                        Public album gallery
                    </p>
                </div>

                <a
                    href="https://immich-kiosk.overcomers.win/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="slideshow-button"
                >
                    View Photo Slideshow
                </a>
            </div>

            <div class="search-wrap">
                <input
                    id="album-search"
                    class="search"
                    type="search"
                    autocomplete="off"
                    placeholder="Search albums..."
                    aria-label="Search albums"
                >
            </div>
        </header>

        <section
            id="album-gallery"
            class="gallery"
        >
            @if ($error)
                <div class="error">
                    {{ $error }}
                </div>
            @endif

            @forelse ($albums as $album)
                @php
                    $updated =
                        $album[
                            'lastModifiedAssetTimestamp'
                        ]
                        ?? $album['updatedAt']
                        ?? null;

                    $start =
                        $album['startDate']
                        ?? null;

                    $end =
                        $album['endDate']
                        ?? null;

                    $searchText =
                        strtolower(
                            trim(
                                ($album['albumName'] ?? '')
                                . ' '
                                . ($album['description'] ?? '')
                            )
                        );
                @endphp

                <article
                    class="album-card"
                    data-album-card
                    data-search="{{ $searchText }}"
                    data-url="{{ $album['sharedUrl'] }}"
                    tabindex="0"
                    role="link"
                >
                    <div
                        class="album-thumb"
                        data-album-thumb
                        data-qr-url="{{ $album['sharedUrl'] }}"
                    >
                        <div class="flip-inner">
                            <div class="flip-face flip-front">
                                @if (
                                    filled(
                                        $album[
                                            'localThumbnailUrl'
                                        ]
                                        ?? null
                                    )
                                )
                                    <img
                                        src="{{ $album['localThumbnailUrl'] }}"
                                        alt="{{ $album['albumName'] }}"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="photo-placeholder">
                                        ▧
                                    </div>
                                @endif
                            </div>

                            <div class="flip-face flip-back">
                                <div
                                    class="qr-target"
                                    data-qr-target
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h2 class="album-name">
                            {{ $album['albumName'] }}
                        </h2>

                        <div class="meta">
                            {{ number_format($album['assetCount']) }}
                            asset(s)

                            @if ($updated)
                                · Updated
                                {{ \Carbon\CarbonImmutable::parse($updated)->format('M j, Y') }}
                            @endif
                        </div>

                        @if (
                            filled(
                                $album['description']
                            )
                        )
                            <p class="description">
                                {{ $album['description'] }}
                            </p>
                        @endif
                    </div>

                    <footer class="card-footer">
                        <div class="footer-label">
                            Album Dates
                        </div>

                        <div class="footer-date">
                            @if ($start)
                                {{ \Carbon\CarbonImmutable::parse($start)->format('M j, Y') }}

                                @if ($end)
                                    to
                                    {{ \Carbon\CarbonImmutable::parse($end)->format('M j, Y') }}
                                @endif
                            @else
                                Date not specified
                            @endif
                        </div>
                    </footer>
                </article>
            @empty
                @unless ($error)
                    <div class="empty">
                        No public albums are available.
                    </div>
                @endunless
            @endforelse
        </section>
    </main>

    <script
        src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"
    ></script>

    <script>
        (() => {
            const cards =
                document.querySelectorAll(
                    '[data-album-card]'
                );

            const qrThumbs = [];

            const qrSizeForThumb = (thumb) => {
                const width =
                    thumb?.clientWidth ?? 0;

                if (width <= 0) {
                    return 220;
                }

                /*
                 * Scale QR with the visible square.
                 * Bigger screens get a bigger QR,
                 * while small screens still keep
                 * comfortable padding.
                 */
                return Math.max(
                    180,
                    Math.min(
                        420,
                        Math.floor(width - 16)
                    )
                );
            };

            const renderQr = (thumb) => {
                const qrTarget =
                    thumb?.querySelector(
                        '[data-qr-target]'
                    );

                const qrUrl =
                    thumb?.dataset.qrUrl;

                if (
                    ! thumb
                    || ! qrTarget
                    || ! qrUrl
                    || typeof QRCode === 'undefined'
                ) {
                    return;
                }

                qrTarget.innerHTML = '';

                const size =
                    qrSizeForThumb(thumb);

                new QRCode(
                    qrTarget,
                    {
                        text: qrUrl,
                        width: size,
                        height: size,
                        correctLevel:
                            QRCode.CorrectLevel.M,
                    }
                );
            };

            cards.forEach((card) => {
                const thumb =
                    card.querySelector(
                        '[data-album-thumb]'
                    );

                if (thumb) {
                    qrThumbs.push(thumb);
                    renderQr(thumb);
                }

                let suppressClick = false;

                thumb?.addEventListener(
                    'pointerup',
                    (event) => {
                        if (
                            event.pointerType !== 'touch'
                            && event.pointerType !== 'pen'
                        ) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        thumb.classList.toggle(
                            'is-flipped'
                        );

                        suppressClick = true;

                        window.setTimeout(
                            () => {
                                suppressClick = false;
                            },
                            450
                        );
                    }
                );

                thumb?.addEventListener(
                    'click',
                    (event) => {
                        if (! suppressClick) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                    }
                );

                const openAlbum = () => {
                    const url =
                        card.dataset.url;

                    if (! url) {
                        return;
                    }

                    window.open(
                        url,
                        '_blank',
                        'noopener,noreferrer'
                    );
                };

                card.addEventListener(
                    'click',
                    openAlbum
                );

                card.addEventListener(
                    'keydown',
                    (event) => {
                        if (
                            event.key !== 'Enter'
                            && event.key !== ' '
                        ) {
                            return;
                        }

                        event.preventDefault();
                        openAlbum();
                    }
                );
            });

            let resizeTimer = null;

            window.addEventListener(
                'resize',
                () => {
                    window.clearTimeout(
                        resizeTimer
                    );

                    resizeTimer =
                        window.setTimeout(
                            () => {
                                qrThumbs.forEach(
                                    renderQr
                                );
                            },
                            120
                        );
                }
            );

            const search =
                document.getElementById(
                    'album-search'
                );

            search?.addEventListener(
                'input',
                () => {
                    const query =
                        search.value
                            .trim()
                            .toLowerCase();

                    document
                        .querySelectorAll(
                            '[data-album-card]'
                        )
                        .forEach(
                            (card) => {
                                const haystack =
                                    card.dataset.search
                                    ?? '';

                                card.classList.toggle(
                                    'hidden',
                                    query !== ''
                                    && ! haystack.includes(
                                        query
                                    )
                                );
                            }
                        );
                }
            );
        })();
    </script>
</body>
</html>
