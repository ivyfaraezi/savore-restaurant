<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';

class EmailService
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->setupSMTP();
    }

    private function setupSMTP()
    {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'savore.2006@gmail.com';
            $this->mail->Password   = 'sxqh eyis wzuf dami';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;

            // Set charset
            $this->mail->CharSet = 'UTF-8';

            // Default sender
            $this->mail->setFrom('savore.2006@gmail.com', 'Savoré Restaurant');
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }

    public function sendOrderConfirmation($customerEmail, $customerName, $orderId, $orderDetails)
    {
        try {
            $this->mail->addAddress($customerEmail, $customerName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Order Confirmation - Savoré Restaurant (Order #' . $orderId . ')';
            $emailBody = $this->generateOrderConfirmationHTML($customerName, $orderId, $orderDetails);
            $this->mail->Body = $emailBody;
            $this->mail->AltBody = $this->generateOrderConfirmationText($customerName, $orderId, $orderDetails);
            $this->mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            error_log("Email sending error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $this->mail->ErrorInfo];
        }
    }

    private function generateOrderConfirmationHTML($customerName, $orderId, $orderDetails)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #2c2c2c; color: #d4af37; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; color: #d4af37; }
                .header h2 { margin: 10px 0 0 0; font-size: 18px; color: #f0f0f0; font-weight: normal; }
                .content { padding: 30px; background-color: #ffffff; }
                .order-details { background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4af37; }
                .order-details h4 { color: #2c2c2c; margin-top: 0; font-size: 16px; }
                .info-section { margin: 15px 0; }
                .info-section p { margin: 8px 0; }
                .item-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .item-table th { background-color: #d4af37; color: white; padding: 12px; text-align: left; font-weight: bold; }
                .item-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
                .item-table tr:hover { background-color: #f8f9fa; }
                .total-row { background-color: #2c2c2c; color: white; font-weight: bold; font-size: 1.1em; }
                .total-row td { border-bottom: none; }
                .status-box { padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-weight: bold; }
                .status-pending { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
                .status-confirmed { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .status-preparing { background-color: #cce5ff; border: 1px solid #99d6ff; color: #004085; }
                .status-ready { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
                .status-delivered { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .status-cancelled { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
                .status-default { background-color: #e2e3e5; border: 1px solid #d6d8db; color: #383d41; }
                .next-steps { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .next-steps h4 { margin-top: 0; color: #0c5460; }
                .contact-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #2c2c2c; color: #d4af37; padding: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Savoré Restaurant</h1>
                    <h2>Order Confirmation</h2>
                </div>
                
                <div class="content">
                    <h3>Thank you for your order, ' . htmlspecialchars($customerName) . '!</h3>
                    <p>We\'re excited to prepare your delicious meal. Here are your order details:</p>
                    
                    <div class="order-details">
                        <h4>Order Information</h4>
                        <div class="info-section">
                            <p><strong>Order Number:</strong> ORD-' . date('Ymd') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . '</p>
                            <p><strong>Order Date:</strong> ' . date('F j, Y') . ' at ' . date('g:i A') . '</p>
                            <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
                            <p><strong>Email:</strong> ' . htmlspecialchars($orderDetails['email']) . '</p>
                        </div>
                        
                        <h4>Order Items</h4>
                        <table class="item-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>';

        $totalAmount = 0;
        foreach ($orderDetails['items'] as $item) {
            $itemTotal = floatval($item['total']);
            $totalAmount += $itemTotal;
            $html .= '
                                <tr>
                                    <td>' . htmlspecialchars($item['item']) . '</td>
                                    <td>' . $item['quantity'] . '</td>
                                    <td>$' . number_format($itemTotal, 2) . '</td>
                                </tr>';
        }

        $html .= '
                                <tr class="total-row">
                                    <td colspan="2"><strong>Total:</strong></td>
                                    <td><strong>৳' . number_format($totalAmount, 2) . '</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="status-box ' . $this->getStatusClass($orderDetails['status']) . '">
                        <strong>Status:</strong> ' . htmlspecialchars($orderDetails['status']) . '
                    </div>
                    
                    <div class="next-steps">
                        <h4>What\'s Next?</h4>
                        ' . $this->getStatusMessage($orderDetails['status']) . '
                    </div>
                    
                    <p>Thank you for choosing Savoré Restaurant. We appreciate your business!</p>
                    
                    <div class="contact-info">
                        <strong>Contact Information:</strong><br>
                        <strong>Contact Number:</strong> +880-1992346336 , +880-1857048383<br>
                        <strong>Email:</strong> savore.2006@gmail.com<br>
                    </div>
                </div>
                
                <div class="footer">
                    <p>© 2025 Savoré Restaurant. All rights reserved.</p>
                    <p>123 Savoré Street, Dhaka, Bangladesh | Contact : +880-1992346336 , +880-1857048383</p>
                    <p><strong>Email:</strong> savore.2006@gmail.com</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function generateOrderConfirmationText($customerName, $orderId, $orderDetails)
    {
        $text = "Savoré Restaurant - Order Confirmation\n\n";
        $text .= "Dear " . $customerName . ",\n\n";
        $text .= "Thank you for your order! We have received your order and it is being prepared.\n\n";
        $text .= "Order Details:\n";
        $text .= "Order ID: #" . $orderId . "\n";
        $text .= "Order Date: " . date('F j, Y, g:i a') . "\n";
        $text .= "Customer: " . $customerName . "\n";
        $text .= "Email: " . $orderDetails['email'] . "\n";
        $text .= "Mobile: " . $orderDetails['mobile'] . "\n\n";
        $text .= "Items Ordered:\n";

        $totalAmount = 0;
        foreach ($orderDetails['items'] as $item) {
            $itemTotal = floatval($item['total']);
            $totalAmount += $itemTotal;
            $text .= "- " . $item['item'] . " (x" . $item['quantity'] . ") - ৳" . number_format($itemTotal, 2) . "\n";
        }

        $text .= "\nTotal Amount: ৳" . number_format($totalAmount, 2) . "\n\n";
        $text .= "Status: " . $orderDetails['status'] . "\n\n";
        $text .= $this->getStatusTextMessage($orderDetails['status']) . "\n\n";
        $text .= "If you have any questions about your order, please contact us at:\n";
        $text .= "Mobile: +880-1992346336 , +880-1857048383 \n";
        $text .= "Email: savore.2006@gmail.com\n\n";
        $text .= "Thank you for choosing Savoré Restaurant!\n";
        $text .= "© 2025 Savoré Restaurant. All rights reserved.";

        return $text;
    }

    private function getStatusClass($status)
    {
        $status = strtolower(trim($status));

        switch ($status) {
            case 'pending':
                return 'status-pending';
            case 'confirmed':
                return 'status-confirmed';
            case 'preparing':
            case 'in progress':
                return 'status-preparing';
            case 'ready':
            case 'completed':
                return 'status-ready';
            case 'delivered':
                return 'status-delivered';
            case 'cancelled':
                return 'status-cancelled';
            default:
                return 'status-default';
        }
    }

    private function getStatusMessage($status)
    {
        $status = strtolower(trim($status));

        switch ($status) {
            case 'pending':
                return '
                        <ul>
                            <li>Your order is being prepared by our skilled chefs</li>
                            <li>You\'ll receive updates on your order status</li>
                            <li>Estimated preparation time: 25-35 minutes</li>
                            <li>For any questions, contact us at +880-1992346336 , +880-1857048383</li>
                        </ul>';
            case 'confirmed':
                return '
                        <ul>
                            <li>Your order has been confirmed and is in our kitchen</li>
                            <li>Our chefs are preparing your delicious meal</li>
                            <li>Estimated preparation time: 20-30 minutes</li>
                            <li>We\'ll notify you when it\'s ready for pickup/delivery</li>
                        </ul>';
            case 'preparing':
            case 'in progress':
                return '
                        <ul>
                            <li>Your order is currently being prepared</li>
                            <li>Our skilled chefs are working on your meal</li>
                            <li>Estimated completion: 15-25 minutes</li>
                            <li>You\'ll be notified once it\'s ready</li>
                        </ul>';
            case 'ready':
            case 'completed':
                return '
                        <ul>
                            <li>Your order is ready for pickup/delivery!</li>
                            <li>Please collect your order at your earliest convenience</li>
                            <li>Thank you for choosing Savoré Restaurant</li>
                            <li>We hope you enjoy your meal!</li>
                        </ul>';
            case 'delivered':
                return '
                        <ul>
                            <li>Your order has been successfully delivered</li>
                            <li>We hope you enjoyed your meal from Savoré Restaurant</li>
                            <li>Thank you for your business!</li>
                            <li>Please rate your experience and order again soon</li>
                        </ul>';
            case 'cancelled':
                return '
                        <ul>
                            <li>Your order has been cancelled</li>
                            <li>If this was unexpected, please contact us immediately</li>
                            <li>Any payments will be refunded within 3-5 business days</li>
                            <li>We apologize for any inconvenience</li>
                        </ul>';
            default:
                return '
                        <ul>
                            <li>Your order status: ' . htmlspecialchars($status) . '</li>
                            <li>We\'re processing your order</li>
                            <li>For updates, contact us at +880-1992346336 , +880-1857048383</li>
                            <li>Thank you for choosing Savoré Restaurant</li>
                        </ul>';
        }
    }

    private function getStatusTextMessage($status)
    {
        $status = strtolower(trim($status));

        switch ($status) {
            case 'pending':
                return "Your order is being prepared by our skilled chefs. You'll receive updates on your order status. Estimated preparation time: 25-35 minutes.";
            case 'confirmed':
                return "Your order has been confirmed and is in our kitchen. Our chefs are preparing your delicious meal. Estimated preparation time: 20-30 minutes.";
            case 'preparing':
            case 'in progress':
                return "Your order is currently being prepared by our skilled chefs. Estimated completion: 15-25 minutes.";
            case 'ready':
            case 'completed':
                return "Your order is ready for pickup! Please collect your order at your earliest convenience.";
            case 'delivered':
                return "Your order has been successfully delivered. We hope you enjoyed your meal from Savoré Restaurant!";
            case 'cancelled':
                return "Your order has been cancelled. If this was unexpected, please contact us immediately. Any payments will be refunded within 3-5 business days.";
            default:
                return "Your order status: $status. We're processing your order. For updates, contact us at +880-1992346336 , +880-1857048383.";
        }
    }

    public function sendStatusUpdateEmail($customerEmail, $customerName, $orderId, $orderDetails, $newStatus)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($customerEmail, $customerName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Order Status Update - Savoré Restaurant (Order #' . $orderId . ')';
            $emailBody = $this->generateStatusUpdateHTML($customerName, $orderId, $orderDetails, $newStatus);
            $this->mail->Body = $emailBody;
            $this->mail->AltBody = $this->generateStatusUpdateText($customerName, $orderId, $orderDetails, $newStatus);
            $this->mail->send();
            return ['success' => true, 'message' => 'Status update email sent successfully'];
        } catch (Exception $e) {
            error_log("Email sending error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $this->mail->ErrorInfo];
        }
    }

    private function generateStatusUpdateHTML($customerName, $orderId, $orderDetails, $newStatus)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Status Update</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #2c2c2c; color: #d4af37; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; color: #d4af37; }
                .header h2 { margin: 10px 0 0 0; font-size: 18px; color: #f0f0f0; font-weight: normal; }
                .content { padding: 30px; background-color: #ffffff; }
                .order-details { background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4af37; }
                .status-box { padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-weight: bold; }
                .status-pending { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
                .status-confirmed { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .status-preparing { background-color: #cce5ff; border: 1px solid #99d6ff; color: #004085; }
                .status-ready { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
                .status-delivered { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .status-cancelled { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
                .status-default { background-color: #e2e3e5; border: 1px solid #d6d8db; color: #383d41; }
                .next-steps { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #2c2c2c; color: #d4af37; padding: 20px; text-align: center; }
                .contact-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Savoré Restaurant</h1>
                    <h2>Order Status Update</h2>
                </div>
                
                <div class="content">
                    <h3>Hi ' . htmlspecialchars($customerName) . ',</h3>
                    <p>We have an update on your order!</p>
                    
                    <div class="order-details">
                        <h4>Order Information</h4>
                        <p><strong>Order Number:</strong> ORD-' . date('Ymd') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . '</p>
                        <p><strong>Updated Status:</strong> ' . htmlspecialchars($newStatus) . '</p>
                        <p><strong>Update Time:</strong> ' . date('F j, Y') . ' at ' . date('g:i A') . '</p>
                    </div>
                    
                    <div class="status-box ' . $this->getStatusClass($newStatus) . '">
                        <strong>Current Status:</strong> ' . htmlspecialchars($newStatus) . '
                    </div>
                    
                    <div class="next-steps">
                        <h4>What\'s Next?</h4>
                        ' . $this->getStatusMessage($newStatus) . '
                    </div>
                    
                    <p>Thank you for choosing Savoré Restaurant. We appreciate your business!</p>
                    
                    <div class="contact-info">
                        <strong>Contact Information:</strong><br>
                        <strong>Contact Number:</strong> +880-1992346336 , +880-1857048383<br>
                        <strong>Email:</strong> savore.2006@gmail.com<br>
                    </div>
                </div>
                
                <div class="footer">
                    <p>© 2025 Savoré Restaurant. All rights reserved.</p>
                    <p>123 Savoré Street, Dhaka, Bangladesh | Phone: +880-1992346336 , +880-1857048383</p>
                    <p><strong>Email:</strong> savore.2006@gmail.com</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function generateStatusUpdateText($customerName, $orderId, $orderDetails, $newStatus)
    {
        $text = "Savoré Restaurant - Order Status Update\n\n";
        $text .= "Hi " . $customerName . ",\n\n";
        $text .= "We have an update on your order!\n\n";
        $text .= "Order Information:\n";
        $text .= "Order Number: ORD-" . date('Ymd') . "-" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . "\n";
        $text .= "Updated Status: " . $newStatus . "\n";
        $text .= "Update Time: " . date('F j, Y') . " at " . date('g:i A') . "\n\n";
        $text .= "Current Status: " . $newStatus . "\n\n";
        $text .= $this->getStatusTextMessage($newStatus) . "\n\n";
        $text .= "Thank you for choosing Savoré Restaurant. We appreciate your business!\n\n";
        $text .= "Contact Information:\n";
        $text .= "Contact Number: +880-1992346336 , +880-1857048383\n";
        $text .= "Email: savore.2006@gmail.com\n";
        $text .= "© 2025 Savoré Restaurant. All rights reserved.";

        return $text;
    }

    public function sendTableReservationConfirmation($customerEmail, $customerName, $reservationId, $reservationDetails)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($customerEmail, $customerName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Table Reservation Confirmation - Savoré Restaurant (Reservation #' . $reservationId . ')';
            $emailBody = $this->generateTableReservationHTML($customerName, $reservationId, $reservationDetails, 'confirmation');
            $this->mail->Body = $emailBody;
            $this->mail->AltBody = $this->generateTableReservationText($customerName, $reservationId, $reservationDetails, 'confirmation');

            $this->mail->send();
            return ['success' => true, 'message' => 'Table reservation confirmation email sent successfully'];
        } catch (Exception $e) {
            error_log("Table reservation confirmation email error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $this->mail->ErrorInfo];
        }
    }

    public function sendTableReservationUpdate($customerEmail, $customerName, $reservationId, $reservationDetails)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($customerEmail, $customerName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Table Reservation Update - Savoré Restaurant (Reservation #' . $reservationId . ')';
            $emailBody = $this->generateTableReservationHTML($customerName, $reservationId, $reservationDetails, 'update');
            $this->mail->Body = $emailBody;
            $this->mail->AltBody = $this->generateTableReservationText($customerName, $reservationId, $reservationDetails, 'update');
            $this->mail->send();
            return ['success' => true, 'message' => 'Table reservation update email sent successfully'];
        } catch (Exception $e) {
            error_log("Table reservation update email error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $this->mail->ErrorInfo];
        }
    }

    private function generateTableReservationHTML($customerName, $reservationId, $reservationDetails, $type)
    {
        $isUpdate = ($type === 'update');
        $title = $isUpdate ? 'Table Reservation Update' : 'Table Reservation Confirmation';
        $greeting = $isUpdate ? 'Your table reservation has been updated!' : 'Thank you for your table reservation!';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $title . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #2c2c2c; color: #d4af37; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; color: #d4af37; }
                .header h2 { margin: 10px 0 0 0; font-size: 18px; color: #f0f0f0; font-weight: normal; }
                .content { padding: 30px; background-color: #ffffff; }
                .reservation-details { background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4af37; }
                .reservation-details h4 { color: #2c2c2c; margin-top: 0; font-size: 16px; }
                .info-section { margin: 15px 0; }
                .info-section p { margin: 8px 0; }
                .reservation-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .reservation-table th { background-color: #d4af37; color: white; padding: 12px; text-align: left; font-weight: bold; }
                .reservation-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
                .reservation-table tr:hover { background-color: #f8f9fa; }
                .status-box { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-weight: bold; }
                .next-steps { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .next-steps h4 { margin-top: 0; color: #0c5460; }
                .contact-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #2c2c2c; color: #d4af37; padding: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Savoré Restaurant</h1>
                    <h2>' . $title . '</h2>
                </div>
                
                <div class="content">
                    <h3>Dear ' . htmlspecialchars($customerName) . ',</h3>
                    <p>' . $greeting . '</p>
                    
                    <div class="reservation-details">
                        <h4>Reservation Information</h4>
                        <div class="info-section">
                            <p><strong>Reservation Number:</strong> RES-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT) . '</p>
                            <p><strong>' . ($isUpdate ? 'Updated' : 'Reservation') . ' Date:</strong> ' . date('F j, Y', strtotime($reservationDetails['date'])) . '</p>
                            <p><strong>Time:</strong> ' . date('g:i A', strtotime($reservationDetails['time'])) . ' (1-hour duration)</p>
                            <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
                            <p><strong>Email:</strong> ' . htmlspecialchars($reservationDetails['email']) . '</p>
                            <p><strong>Mobile:</strong> ' . htmlspecialchars($reservationDetails['mobile']) . '</p>
                        </div>
                        
                        <h4>Table Details</h4>
                        <table class="reservation-table">
                            <thead>
                                <tr>
                                    <th>Table Number</th>
                                    <th>Guests</th>
                                    <th>Table Capacity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Table ' . $reservationDetails['tableno'] . '</td>
                                    <td>' . $reservationDetails['guests'] . ' guests</td>
                                    <td>Up to ' . $reservationDetails['capacity'] . ' guests</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="status-box">
                        <strong>Status:</strong> ' . ($isUpdate ? 'Reservation Updated' : 'Reservation Confirmed') . '
                    </div>
                    
                    <div class="next-steps">
                        <h4>What\'s Next?</h4>
                        <ul>
                            <li>Your table is reserved for 1 hour starting from your booking time</li>
                            <li>Please arrive on time to ensure your table is ready</li>
                            <li>If you need to make changes, contact us at least 2 hours in advance</li>
                            <li>For any questions, contact us at +880-1992346336 , +880-1857048383</li>
                        </ul>
                    </div>
                    
                    <p>We look forward to serving you at Savoré Restaurant!</p>
                    
                    <div class="contact-info">
                        <strong>Contact Information:</strong><br>
                        <strong>Contact Number:</strong> +880-1992346336 , +880-1857048383<br>
                        <strong>Email:</strong> savore.2006@gmail.com<br>
                    </div>
                </div>
                
                <div class="footer">
                    <p>© 2025 Savoré Restaurant. All rights reserved.</p>
                    <p>123 Savoré Street, Dhaka, Bangladesh | Contact Number: +880-1992346336 , +880-1857048383</p>
                    <p><strong>Email:</strong> savore.2006@gmail.com</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function generateTableReservationText($customerName, $reservationId, $reservationDetails, $type)
    {
        $isUpdate = ($type === 'update');
        $title = $isUpdate ? 'Table Reservation Update' : 'Table Reservation Confirmation';
        $greeting = $isUpdate ? 'Your table reservation has been updated!' : 'Thank you for your table reservation!';

        $text = "Savoré Restaurant - $title\n\n";
        $text .= "Dear $customerName,\n\n";
        $text .= "$greeting\n\n";
        $text .= "Reservation Information:\n";
        $text .= "Reservation Number: RES-" . date('Ymd') . "-" . str_pad($reservationId, 6, '0', STR_PAD_LEFT) . "\n";
        $text .= ($isUpdate ? 'Updated' : 'Reservation') . " Date: " . date('F j, Y', strtotime($reservationDetails['date'])) . "\n";
        $text .= "Time: " . date('g:i A', strtotime($reservationDetails['time'])) . " (1-hour duration)\n";
        $text .= "Customer: $customerName\n";
        $text .= "Email: " . $reservationDetails['email'] . "\n";
        $text .= "Mobile: " . $reservationDetails['mobile'] . "\n\n";
        $text .= "Table Details:\n";
        $text .= "Table Number: Table " . $reservationDetails['tableno'] . "\n";
        $text .= "Guests: " . $reservationDetails['guests'] . " guests\n";
        $text .= "Table Capacity: Up to " . $reservationDetails['capacity'] . " guests\n\n";
        $text .= "Status: " . ($isUpdate ? 'Reservation Updated' : 'Reservation Confirmed') . "\n\n";
        $text .= "What's Next?\n";
        $text .= "- Your table is reserved for 1 hour starting from your booking time\n";
        $text .= "- Please arrive on time to ensure your table is ready\n";
        $text .= "- If you need to make changes, contact us at least 2 hours in advance\n";
        $text .= "- For any questions, contact us at +880-1992346336 , +880-1857048383\n\n";
        $text .= "We look forward to serving you at Savoré Restaurant!\n\n";
        $text .= "Contact Information:\n";
        $text .= "Contact Number: +880-1992346336 , +880-1857048383\n";
        $text .= "Email: savore.2006@gmail.com\n";
        $text .= "© 2025 Savoré Restaurant. All rights reserved.";

        return $text;
    }
}
