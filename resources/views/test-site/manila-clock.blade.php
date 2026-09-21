<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Test Site - Manila Time</title>

    <style>
        html,
        body {
            margin: 0;
            min-height: 100%;
            background: #008000;
            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .clock-wrap {
            padding: 2rem;
        }

        .label {
            font-size: clamp(1.2rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #ffffff;
        }

        .time {
            margin-top: 1rem;
            font-size: clamp(4rem, 14vw, 12rem);
            font-weight: 900;
            line-height: 1;
            color: #ffffff;
            text-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .date {
            margin-top: 1rem;
            font-size: clamp(1.1rem, 3vw, 2.2rem);
            font-weight: 700;
            color: #ffffff;
        }

        .timezone {
            margin-top: 0.75rem;
            font-size: clamp(0.9rem, 2vw, 1.3rem);
            font-weight: 700;
            color: #ffffff;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <main class="clock-wrap">
        <div class="label">
            Philippine Standard Time
        </div>

        <div
            id="manila-time"
            class="time"
        >
            --:--:--
        </div>

        <div
            id="manila-date"
            class="date"
        >
            Loading date...
        </div>

        <div class="timezone">
            Asia/Manila · UTC+8
        </div>
    </main>

    <script>
        function updateManilaClock() {
            const now = new Date();

            document.getElementById('manila-time').textContent =
                new Intl.DateTimeFormat('en-PH', {
                    timeZone: 'Asia/Manila',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                }).format(now);

            document.getElementById('manila-date').textContent =
                new Intl.DateTimeFormat('en-PH', {
                    timeZone: 'Asia/Manila',
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                }).format(now);
        }

        updateManilaClock();
        setInterval(updateManilaClock, 1000);
    </script>
@include('filament.components.back-to-top')
</body>
</html>
