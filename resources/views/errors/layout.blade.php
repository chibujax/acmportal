<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') – ACM Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --acm-green: #1a6b3c;
            --acm-gold:  #c8a84b;
            --acm-dark:  #0f3d22;
        }
        body {
            background: #f4f6f4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .error-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            max-width: 520px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: var(--acm-green);
            line-height: 1;
        }
        .error-icon {
            font-size: 3rem;
            color: var(--acm-gold);
            margin-bottom: 1rem;
        }
        .brand {
            color: var(--acm-green);
            font-weight: 700;
            text-decoration: none;
            font-size: 1.1rem;
        }
        .btn-home {
            background: var(--acm-green);
            color: #fff;
            border: none;
            padding: .6rem 1.6rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
        }
        .btn-home:hover { background: var(--acm-dark); color: #fff; }
        .divider {
            border-top: 3px solid var(--acm-gold);
            width: 60px;
            margin: 1rem auto;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <a href="{{ url('/') }}" class="brand">ACM Portal</a>
        <div class="divider"></div>
        @yield('content')
        <a href="{{ url('/') }}" class="btn-home">Go to Home</a>
    </div>
</body>
</html>
