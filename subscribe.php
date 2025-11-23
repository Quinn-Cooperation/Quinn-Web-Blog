<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // 1. Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email Address'); window.history.back();</script>";
        exit();
    }

    // 2. Check if already subscribed
    $check = $conn->query("SELECT id FROM subscribers WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('You are already subscribed!'); window.history.back();</script>";
        exit();
    }

    // 3. Insert into DB
    $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        
        // --- ADVANCED EMAIL LOGIC (Anti-Spam) ---
        $to = $email;
        $subject = "Welcome to QuinnCoop! 🚀";
        $from_email = "no-reply@quinncoop.org"; // MUST exist in your cPanel
        $from_name = "QuinnCoop";

        // Generate a boundary string
        $random_hash = md5(date('r', time())); 
        $boundary = "PHP-mixed-".$random_hash;

        // Headers
        $headers = "From: $from_name <$from_email>\r\n";
        $headers .= "Reply-To: contact@quinncoop.org\r\n"; // Optional: Your real email
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$random_hash\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // --- PLAIN TEXT VERSION (For Spam Filters) ---
        $message = "--PHP-alt-$random_hash\r\n";
        $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= "Welcome to QuinnCoop!\n\nThank you for subscribing to our blog.\nRead the latest updates here: https://blog.quinncoop.org\n\n(c) 2025 QuinnCoop.\r\n\r\n";

        // --- HTML VERSION (For Humans) ---
        $message .= "--PHP-alt-$random_hash\r\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
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
        $message .= "\r\n--PHP-alt-$random_hash--";

        // --- SEND MAIL WITH RETURN-PATH ---
        // The "-f" parameter is CRITICAL for passing spam filters
        if(mail($to, $subject, $message, $headers, "-f$from_email")) {
            echo "<script>alert('Successfully Subscribed! Please check your email inbox.'); window.location.href='index.php';</script>";
        } else {
            // Fallback without -f if server blocks it
            mail($to, $subject, $message, $headers);
            echo "<script>alert('Successfully Subscribed!'); window.location.href='index.php';</script>";
        }

    } else {
        echo "<script>alert('Database Error'); window.history.back();</script>";
    }
}
?>