<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;">

<table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td align="center" style="padding:40px 16px; background-color:#f1f5f9;">

            <!-- Main card -->
            <table cellpadding="0" cellspacing="0" border="0" width="600"
                   style="max-width:600px; border-radius:16px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.12);">
                <tr>

                    <!-- LEFT: imagen de fondo -->
                    <td width="210" valign="middle"
                        style="width:210px; min-width:210px; background-color:#0f172a;
                               background-image:url('{{ asset('images/register.jpg') }}');
                               background-size:cover; background-position:center center;">
                        <div style="width:210px; min-height:420px; font-size:0; line-height:0;">&nbsp;</div>
                    </td>

                    <!-- RIGHT: contenido -->
                    <td valign="middle" style="padding:40px 32px; background-color:#ffffff;">

                        <!-- Nombre del servidor -->
                        <p style="margin:0 0 4px 0;
                                  font-size:11px;
                                  font-weight:800;
                                  color:#E74C3C;
                                  font-family:Arial, Helvetica, sans-serif;
                                  text-transform:uppercase;
                                  letter-spacing:2px;
                                  line-height:1.1;">
                            {{ $serverName }}
                        </p>

                        <!-- Título -->
                        <h1 style="margin:0 0 14px 0;
                                   font-size:20px;
                                   font-weight:700;
                                   color:#0f172a;
                                   line-height:1.3;
                                   font-family:Arial, Helvetica, sans-serif;">
                            {{ __('security_alert_subject') }}
                        </h1>

                        <!-- Línea decorativa roja -->
                        <table cellpadding="0" cellspacing="0" border="0" width="44" style="margin-bottom:18px;">
                            <tr>
                                <td height="3" bgcolor="#E74C3C"
                                    style="background-color:#E74C3C; border-radius:2px; font-size:0; line-height:0;">
                                </td>
                            </tr>
                        </table>

                        <!-- Mensaje del evento -->
                        <p style="margin:0 0 6px 0;
                                  font-size:14px;
                                  color:#0f172a;
                                  line-height:1.6;
                                  font-family:Arial, Helvetica, sans-serif;">
                            {{ __('security_alert_' . $eventKey) }}
                        </p>

                        <p style="margin:0 0 20px 0;
                                  font-size:13px;
                                  color:#4b5563;
                                  line-height:1.65;
                                  font-family:Arial, Helvetica, sans-serif;">
                            {{ __('security_alert_not_you') }}
                        </p>

                        <!-- Detalles: IP y fecha -->
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:22px;">
                            <tr>
                                <td bgcolor="#f8fafc"
                                    style="background-color:#f8fafc; border-radius:6px; padding:12px 16px; border:1px solid #e2e8f0;">
                                    <p style="margin:0 0 4px 0; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:1px; font-family:Arial, Helvetica, sans-serif;">
                                        {{ __('security_alert_details') }}
                                    </p>
                                    <p style="margin:0 0 2px 0; font-size:12px; color:#374151; font-family:Arial, Helvetica, sans-serif;">
                                        <strong>{{ __('IP Address') }}:</strong> {{ $ip }}
                                    </p>
                                    <p style="margin:0; font-size:12px; color:#374151; font-family:Arial, Helvetica, sans-serif;">
                                        <strong>{{ __('Date & Time') }}:</strong> {{ $datetime }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- Botón Reset Password -->
                        <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:0;">
                            <tr>
                                <td>
                                    <a href="{{ $resetUrl }}"
                                       style="display:inline-block;
                                              background-color:#E74C3C;
                                              border-radius:8px;
                                              padding:12px 28px;
                                              font-size:14px;
                                              font-weight:700;
                                              color:#ffffff;
                                              text-decoration:none;
                                              font-family:Arial, Helvetica, sans-serif;">
                                        {{ __('Reset Password') }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- Espacio -->
                        <div style="height:20px; font-size:0; line-height:0;">&nbsp;</div>

                        <!-- Si fuiste tú -->
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td bgcolor="#f0fdf4"
                                    style="background-color:#f0fdf4;
                                           border-left:4px solid #22c55e;
                                           border-radius:0 6px 6px 0;
                                           padding:11px 14px;">
                                    <p style="margin:0;
                                              font-size:12px;
                                              font-weight:600;
                                              color:#15803d;
                                              line-height:1.55;
                                              font-family:Arial, Helvetica, sans-serif;">
                                        &#10003; {{ __('security_alert_was_you') }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
