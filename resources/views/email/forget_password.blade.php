<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #333;
                background-color: #f5f5f5;
                padding: 40px;
            }

            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            p {
                margin-bottom: 20px;
                font-size: 16px;
                line-height: 1.5;
            }

            a {
                color: #007bff;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <h1>Dear {{ $user->name }},</h1>
        <p>We received a request to reset the password for your account. If you made this request, please follow the instructions below to reset your password.</p>
        <p>Please click the link below to reset your password:</p>
        <p><a href="http://localhost:3000/fotget-password/{{$token}}">http://localhost:3000/fotget-password/{{$token}}</a></p>
        <p>If you cannot click the link, please copy and paste it into your browser's address bar.</p>
        <p>If you did not request to reset your password, please ignore this email. Your account will remain secure and no changes will be made.</p>
        <p>If you have any questions or concerns, please do not hesitate to reach out to us at {{ $contactEmail }}. We're here to help!</p>
        <p>Best regards,</p>
        <p>{{env('APP_NAME')}}</p>
    </body>
</html>
