<?php
// Manual PHPMailer includes (NO COMPOSER NEEDED)
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load email config
require_once __DIR__ . '/../config/email.php';

// Helper functions
function clean($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        
        // For localhost testing only - REMOVE IN PRODUCTION
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo('support@carhub.com', 'CarHub Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getVerificationEmailTemplate($name, $code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4; 
            }
            .container { 
                max-width: 600px; 
                margin: 30px auto; 
                background: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 40px 30px; }
            .code-box { 
                background: #f8f9fa; 
                border: 2px dashed #667eea; 
                border-radius: 10px; 
                padding: 30px; 
                text-align: center; 
                margin: 30px 0; 
            }
            .code { 
                font-size: 48px; 
                font-weight: bold; 
                color: #667eea; 
                letter-spacing: 10px; 
                font-family: 'Courier New', monospace; 
            }
            .footer { 
                background: #f8f8f8; 
                padding: 20px; 
                text-align: center; 
                font-size: 12px; 
                color: #666; 
            }
            .warning { 
                background: #fff3cd; 
                border-left: 4px solid #ffc107; 
                padding: 15px; 
                margin: 20px 0; 
                font-size: 14px; 
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚗 Welcome to CarHub!</h1>
            </div>
            <div class='content'>
                <h2>Hi {$name},</h2>
                <p>Thank you for registering with CarHub! To verify your email address, please enter this verification code:</p>
                
                <div class='code-box'>
                    <div class='code'>{$code}</div>
                    <p style='margin-top: 15px; color: #666; font-size: 14px;'>Enter this code on the verification page</p>
                </div>
                
                <div class='warning'>
                    <strong>⏰ Important:</strong> This code will expire in 24 hours.
                </div>
                
                <p style='font-size: 14px; color: #666;'>If you didn't create an account with CarHub, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>© 2024 CarHub. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getPasswordResetEmailTemplate($name, $code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 40px 30px; }
            .code-box { background: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
            .code { font-size: 48px; font-weight: bold; color: #667eea; letter-spacing: 10px; font-family: 'Courier New', monospace; }
            .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .warning { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset</h1>
            </div>
            <div class='content'>
                <h2>Hi {$name},</h2>
                <p>We received a request to reset your password. Use this code to complete the process:</p>
                
                <div class='code-box'>
                    <div class='code'>{$code}</div>
                    <p style='margin-top: 15px; color: #666; font-size: 14px;'>Enter this code on the password reset page</p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Security Alert:</strong><br>
                    This code expires in 1 hour.<br>
                    If you didn't request this, please ignore this email.
                </div>
            </div>
            <div class='footer'>
                <p>© 2024 CarHub. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>