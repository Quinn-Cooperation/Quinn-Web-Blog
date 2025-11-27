<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email Address'); window.history.back();</script>";
        exit();
    }

    // Check if already subscribed
    $check = $conn->query("SELECT id FROM subscribers WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('You are already subscribed!'); window.history.back();</script>";
        exit();
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        
        // --- SEND EMAIL LOGIC ---
        $to = $email;
        $subject = "Welcome to QuinnCoop! 🚀";
        
        // Headers for HTML Email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: QuinnCoop <no-reply@quinncoop.org>" . "\r\n"; // Update with your domain email

        // The HTML Email Content
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: #ffffff; padding: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .header { background-color: #000000; padding: 30px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 2px; }
                .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
                .btn { display: inline-block; padding: 12px 24px; background-color: #ff9800; color: #000000; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
                .footer { background-color: #eeeeee; padding: 20px; text-align: center; font-size: 12px; color: #777777; }
                .footer a { color: #777777; text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>QUINNCOOP</h1>
                </div>
                <div class="content">
                    <h2>Welcome Aboard! 🎉</h2>
                    <p>Hi there,</p>
                    <p>Thank you for subscribing to the QuinnCoop Blog newsletter. We are thrilled to have you with us.</p>
                    <p>You will now be the first to know about our latest insights on technology, design, and innovation.</p>
                    <div style="text-align: center;">
                        <a href="https://blog.quinncoop.org" class="btn">Read Latest Posts</a>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; 2025 QuinnCoop. All rights reserved.</p>
                    <p><a href="https://blog.quinncoop.org/privacy.php">Privacy Policy</a> | <a href="https://quinncoop.org">Main Website</a></p>
                </div>
            </div>
        </body>
        </html>
        ';

        // Send the mail
        mail($to, $subject, $message, $headers);

        echo "<script>alert('Successfully Subscribed! Please check your email inbox.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Database Error'); window.history.back();</script>";
    }
}
?>