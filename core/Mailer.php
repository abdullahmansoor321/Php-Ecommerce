<?php

class Mailer
{
    /**
     * Sends an HTML email over SMTP.
     *
     * Never throws. Returns false and logs on any failure so a mail problem can
     * never roll back or break a checkout that has already been committed.
     */
    public static function send(string $toName, string $toEmail, string $subject, string $html): bool
    {
        $config = require __DIR__ . '/../config/mail.php';

        if (empty($config['username']) || empty($config['password']) || empty($config['from_address'])) {
            error_log('[Mailer] SMTP is not configured. Check .env for MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM_ADDRESS.');
            return false;
        }

        // Gmail silently discards mail whose From address is not the
        // authenticated account, with no SMTP error to explain why. Compare the
        // two parts before connecting so a stray character is reported instead
        // of vanishing.
        if (strcasecmp($config['from_address'], $config['username']) !== 0) {
            error_log('[Mailer] MAIL_FROM_ADDRESS (' . $config['from_address']
                . ') does not match MAIL_USERNAME (' . $config['username']
                . '). Gmail will drop the message without reporting an error.');
            return false;
        }

        // PHPMailer is installed with Composer (phpmailer/phpmailer ^7.1). The
        // autoloader is required inside the try below on purpose: vendor/ is
        // gitignored, so on a fresh clone or a deploy that skipped
        // "composer install" this file is missing and require_once throws a
        // fatal Error. Guarded here, send() returns false like any other mail
        // failure and the caller's redirect still runs, so a missing dependency
        // can never roll back a committed order or show a paid customer a
        // payment-failed page. require_once keeps it idempotent when both
        // process-cod.php and process-stripe.php run in one request.
        try {
            require_once BASE_PATH . '/vendor/autoload.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 15;

            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

            return $mail->send();
        } catch (Throwable $e) {
            error_log('[Mailer] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Builds the shared order email body.
     *
     * Table based with inline styles so it survives Gmail / Outlook stripping
     * external CSS. Product images are deliberately omitted: product_image is a
     * relative path resolving to APP_URL, unreachable from a mail client, so
     * thumbnails would render as broken icons.
     *
     * @param string $heading Banner headline
     * @param string $message Paragraph under the banner
     * @param array  $order   order_number, total_amount, created_at, shipping_address
     * @param array  $items   Rows of name / quantity / unit_price / subtotal
     */
    public static function renderOrder(string $heading, string $message, array $order, array $items): string
    {
        $e = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');

        $orderNumber = (string)($order['order_number'] ?? '');
        $total = number_format((float)($order['total_amount'] ?? 0), 2);
        $createdAt = strtotime((string)($order['created_at'] ?? '')) ?: time();
        $address = trim((string)($order['shipping_address'] ?? ''));
        $shopUrl = FRONT_URL . '/index.php';

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td style="padding:10px 0;border-bottom:1px solid #eee;font-size:14px;color:#333;">'
                . $e($item['name'] ?? '') . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #eee;font-size:14px;color:#666;text-align:center;width:60px;">'
                . (int)($item['quantity'] ?? 0) . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #eee;font-size:14px;color:#666;text-align:right;width:90px;">'
                . '$' . $e(number_format((float)($item['unit_price'] ?? 0), 2)) . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #eee;font-size:14px;color:#333;text-align:right;width:90px;">'
                . '$' . $e(number_format((float)($item['subtotal'] ?? 0), 2)) . '</td>'
                . '</tr>';
        }

        $addressBlock = $address === '' ? '' :
            '<tr><td style="padding:20px 0 0;">'
            . '<div style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#999;margin-bottom:6px;">Delivering to</div>'
            . '<div style="font-size:14px;color:#333;line-height:1.7;white-space:pre-line;">' . $e($address) . '</div>'
            . '</td></tr>';

        $metaRow = static function (string $label, string $value) {
            return '<tr>'
                . '<td style="padding:6px 0;font-size:14px;color:#666;">' . $label . '</td>'
                . '<td style="padding:6px 0;font-size:14px;color:#333;text-align:right;font-weight:bold;">' . $value . '</td>'
                . '</tr>';
        };

        $th = 'padding:0 0 8px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#999;font-weight:normal;';
        $fromName = $e(getenv('MAIL_FROM_NAME') ?: 'Molla');
        $paymentLabel = $e($order['payment_method'] ?? 'Card (Stripe)');

        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f6f6f6;font-family:Helvetica,Arial,sans-serif;">'
            // Hidden preheader so the inbox preview does not read "View in browser".
            . '<div style="display:none;font-size:1px;color:#f6f6f6;line-height:1px;max-height:0;overflow:hidden;">' . $e($message) . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f6f6;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;">'

            // Header
            . '<tr><td style="padding:28px 32px;border-bottom:3px solid #c96;">'
            . '<div style="font-size:22px;font-weight:bold;color:#333;letter-spacing:-.5px;">' . $fromName . '</div>'
            . '<div style="font-size:13px;color:#999;margin-top:2px;">Your order is in good hands</div>'
            . '</td></tr>'

            // Status banner
            . '<tr><td style="padding:28px 32px 0;">'
            . '<div style="font-size:18px;font-weight:bold;color:#333;margin-bottom:10px;">' . $e($heading) . '</div>'
            . '<div style="font-size:15px;color:#555;line-height:1.7;">' . $e($message) . '</div>'
            . '</td></tr>'

            // Order meta
            . '<tr><td style="padding:24px 32px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
            . $metaRow('Order number', $e($orderNumber))
            . $metaRow('Placed on', $e(date('F j, Y', $createdAt)))
            . $metaRow('Payment method', $paymentLabel)
            . '</table></td></tr>'

            // Items
            . '<tr><td style="padding:24px 32px 0;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><th align="left" style="' . $th . '">Item</th>'
            . '<th style="' . $th . 'text-align:center;">Qty</th>'
            . '<th style="' . $th . 'text-align:right;">Price</th>'
            . '<th style="' . $th . 'text-align:right;">Subtotal</th></tr>'
            . $rows
            . '<tr><td colspan="3" style="padding:14px 0 0;font-size:15px;color:#333;font-weight:bold;text-align:right;">Total</td>'
            . '<td style="padding:14px 0 0;font-size:16px;color:#c96;font-weight:bold;text-align:right;">$' . $e($total) . '</td></tr>'
            . '</table></td></tr>'

            // Shipping address
            . '<tr><td style="padding:24px 32px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
            . $addressBlock
            . '</table></td></tr>'

            // CTA. Deep-linking order-confirmation.php is not an option: it
            // requires an active login session and would bounce to the login page.
            . '<tr><td align="center" style="padding:30px 32px;">'
            . '<a href="' . $e($shopUrl) . '" style="display:inline-block;padding:13px 30px;background:#c96;color:#ffffff;text-decoration:none;border-radius:40px;font-size:14px;font-weight:bold;">Continue shopping</a>'
            . '</td></tr>'

            // Footer
            . '<tr><td style="padding:20px 32px 28px;border-top:1px solid #eee;text-align:center;">'
            . '<div style="font-size:13px;color:#333;font-weight:bold;">Questions? Call us 24/7</div>'
            . '<div style="font-size:13px;color:#999;margin-top:4px;">+0123 456 789</div>'
            . '<div style="font-size:12px;color:#bbb;margin-top:12px;">You are receiving this because you placed an order with us.</div>'
            . '</td></tr>'

            . '</table></td></tr></table></body></html>';
    }
}

