<?php
session_start();

// Optional: Retrieve ticket data passed from create_ticket.php
$issue = $_GET['issue'] ?? '';
$company = $_GET['company'] ?? '';
$department = $_GET['department'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket Created Successfully</title>

<style>
body{
    font-family: Arial, sans-serif;
    background:#0b1220;
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    margin:0;
}

.container{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.15);
    padding:30px;
    border-radius:15px;
    width:500px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.4);
}

.success-icon{
    font-size:60px;
    color:#22c55e;
}

h1{
    color:#22c55e;
    margin-bottom:10px;
}

.ticket-details{
    text-align:left;
    margin-top:20px;
    padding:15px;
    background:rgba(255,255,255,0.05);
    border-radius:10px;
}

.ticket-details p{
    margin:10px 0;
}

.btn{
    display:inline-block;
    margin-top:20px;
    padding:12px 20px;
    background:#2563eb;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
}
</style>

<meta http-equiv="refresh" content="3;url=index.php">

</head>
<body>

<div class="container">

    <div class="success-icon">✓</div>

    <h1>Ticket Created Successfully!</h1>

    <p>Your ticket has been submitted and saved.</p>

    <div class="ticket-details">
        <p><strong>Issue:</strong> <?php echo htmlspecialchars($issue); ?></p>
        <p><strong>Company:</strong> <?php echo htmlspecialchars($company); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></p>
    </div>

    <p>You will be redirected automatically in 3 seconds...</p>

    <a href="index.php" class="btn">Return Now</a>

</div>

</body>
</html>