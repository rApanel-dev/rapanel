<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;">

    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <!-- Main card: 600px wide, image left + content right -->
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.12);">
                    <tr>

                        <!-- LEFT: Image column -->
                        <td width="210" valign="top" style="padding:0; width:210px; min-width:210px; background-color:#0f172a;">
                            <img src="{{ asset('images/register.jpg') }}"
                                 alt="{{ $serverName }}"
                                 width="210"
                                 style="display:block; width:210px; height:440px; object-fit:cover; object-position:center;">
                        </td>

                        <!-- RIGHT: Content column -->
                        <td valign="top" style="padding:36px 30px; background-color:#ffffff;">

                            <!-- Server name badge -->
                            <p style="margin:0 0 4px 0; font-size:11px; font-weight:800; color:#4A90E2; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; letter-spacing:2px; line-height:1.1;">
                                {{ $serverName }}
                            </p>

                            <!-- Main title -->
                            <h1 style="margin:0 0 18px 0; font-size:20px; font-weight:700; color:#0f172a; line-height:1.3;">
                                {{ __('Reset your password') }}
                            </h1>

                            <!-- Divider -->
                            <table cellpadding="0" cellspacing="0" border="0" width="40" style="margin-bottom:18px;">
                                <tr><td height="3" style="background-color:#4A90E2; border-radius:2px;"></td></tr>
                            </table>

                            <!-- Greeting -->
                            <p style="margin:0 0 10px 0; font-size:15px; font-weight:600; color:#0f172a;">
                                {{ __('Hello!') }}
                            </p>

                            <!-- Body text -->
                            <p style="margin:0 0 22px 0; font-size:13px; color:#4b5563; line-height:1.65;">
                                {{ __('You are receiving this email because we received a password reset request for your account at :server.', ['server' => $serverName]) }}
                            </p>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">
                                <tr>
                                    <td style="background-color:#4A90E2; border-radius:8px; box-shadow:0 4px 12px rgba(74,144,226,0.35);">
                                        <a href="{{ $url }}"
                                           style="display:inline-block; padding:11px 26px; font-size:13px; font-weight:700; color:#ffffff; text-decoration:none; letter-spacing:0.3px;">
                                            {{ __('Reset Password') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry warning -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 18px 0;">
                                <tr>
                                    <td style="background-color:#fef2f2; border-left:4px solid #E74C3C; border-radius:0 4px 4px 0; padding:10px 14px;">
                                        <p style="margin:0; font-size:12px; font-weight:700; color:#991b1b; line-height:1.5;">
                                            &#9888; {{ __('This link expires in :minutes minutes.', ['minutes' => $expireMinutes]) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Not you? -->
                            <p style="margin:0; font-size:11px; color:#9ca3af; line-height:1.6;">
                                {{ __('If you did not request a password reset, no further action is required.') }}
                            </p>

                        </td>
                    </tr>
                </table>

                <!-- Fallback URL -->
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; margin-top:16px;">
                    <tr>
                        <td style="padding:0 4px;">
                            <p style="margin:0; font-size:11px; color:#6b7280; line-height:1.7; text-align:center;">
                                {{ __('If you are having trouble clicking the button, copy and paste the URL below into your web browser:') }}<br>
                                <a href="{{ $url }}" style="color:#4A90E2; word-break:break-all; font-size:11px;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
