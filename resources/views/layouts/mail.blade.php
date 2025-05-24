<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ config('app.name') }}</title>
    <style>
        /* メールクライアント対応のリセット */
        body,
        table,
        td,
        p,
        a,
        li,
        blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            line-height: 1.5 !important;
            color: #333333 !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #f8f9fa !important;
            width: 100% !important;
            min-width: 100% !important;
        }

        .email-container {
            max-width: 600px !important;
            margin: 20px auto !important;
            background-color: #ffffff !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            border: 1px solid #dee2e6 !important;
        }

        .email-header {
            background-color: #007bff !important;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: #ffffff !important;
            padding: 20px !important;
            text-align: center !important;
        }

        .email-logo {
            width: 40px !important;
            height: 40px !important;
            background-color: #ffffff !important;
            border-radius: 50% !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 18px !important;
            color: #007bff !important;
            margin-bottom: 10px !important;
            line-height: 40px !important;
            text-align: center !important;
        }

        .email-title {
            font-size: 20px !important;
            margin: 0 0 5px 0 !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            text-align: center !important;
        }

        .email-subtitle {
            font-size: 13px !important;
            margin: 0 !important;
            opacity: 0.9 !important;
            color: #ffffff !important;
            text-align: center !important;
        }

        .email-body {
            padding: 25px 20px !important;
            background-color: #ffffff !important;
            color: #333333 !important;
        }

        .email-content {
            font-size: 15px !important;
            line-height: 1.6 !important;
            margin-bottom: 20px !important;
            color: #333333 !important;
        }

        .email-content p {
            margin: 0 0 15px 0 !important;
            color: #333333 !important;
        }

        .email-content p:last-child {
            margin-bottom: 0 !important;
        }

        .email-button {
            display: inline-block !important;
            background-color: #007bff !important;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 12px 25px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            text-align: center !important;
            margin: 15px 0 !important;
            border: none !important;
        }

        .email-button:hover {
            background-color: #0056b3 !important;
            color: #ffffff !important;
            text-decoration: none !important;
        }

        .email-info {
            background-color: #f8f9fa !important;
            border-left: 3px solid #007bff !important;
            padding: 12px 15px !important;
            margin: 15px 0 !important;
            border-radius: 0 6px 6px 0 !important;
            font-size: 14px !important;
            color: #333333 !important;
        }

        .email-warning {
            background-color: #fff3cd !important;
            border-left: 3px solid #ffc107 !important;
            padding: 12px 15px !important;
            margin: 15px 0 !important;
            border-radius: 0 6px 6px 0 !important;
            color: #856404 !important;
            font-size: 14px !important;
        }

        .email-footer {
            background-color: #f8f9fa !important;
            padding: 20px !important;
            text-align: center !important;
            border-top: 1px solid #dee2e6 !important;
            color: #6c757d !important;
        }

        .email-footer-text {
            font-size: 13px !important;
            color: #6c757d !important;
            margin: 0 0 10px 0 !important;
            line-height: 1.4 !important;
        }

        .email-footer-text:last-child {
            margin-bottom: 0 !important;
        }

        .email-link {
            color: #007bff !important;
            text-decoration: none !important;
        }

        .email-link:hover {
            text-decoration: underline !important;
            color: #0056b3 !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .small {
            font-size: 12px !important;
        }

        .url-section {
            background-color: #f8f9fa !important;
            padding: 12px !important;
            border-radius: 6px !important;
            margin: 15px 0 !important;
            word-break: break-all !important;
            color: #6c757d !important;
        }

        /* ダークモード対応 */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #ffffff !important;
                border: 1px solid #dee2e6 !important;
            }

            .email-body {
                background-color: #ffffff !important;
                color: #333333 !important;
            }

            .email-content,
            .email-content p {
                color: #333333 !important;
            }

            .email-info {
                background-color: #f8f9fa !important;
                color: #333333 !important;
            }

            .url-section {
                background-color: #f8f9fa !important;
                color: #6c757d !important;
            }
        }

        /* モバイル対応 */
        @media (max-width: 600px) {
            .email-container {
                margin: 10px !important;
                box-shadow: none !important;
            }

            .email-header {
                padding: 15px !important;
            }

            .email-body {
                padding: 20px 15px !important;
            }

            .email-footer {
                padding: 15px !important;
            }

            .email-title {
                font-size: 18px !important;
            }

            .email-button {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>

<body
    style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.5; color: #333333; margin: 0; padding: 0; background-color: #f8f9fa; width: 100%; min-width: 100%;">
    <div class="email-container"
        style="max-width: 600px; margin: 20px auto; background-color: #ffffff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6;">
        @yield('content')
    </div>
</body>

</html>