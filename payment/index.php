<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Tạo mới đơn hàng</title>
        <!-- Bootstrap core CSS -->
        <link href="/payment/assets/bootstrap.min.css" rel="stylesheet"/>
        <!-- Custom styles for this template -->
        <link href="/payment/assets/jumbotron-narrow.css" rel="stylesheet">  
        <script src="/payment/assets/jquery-1.11.3.min.js"></script>
    </head>

    <body>
    <div class="container">
           <div class="header clearfix">

                <h3 class="text-muted">VNPAY DEMO</h3>
            </div>
                <div class="form-group">
                    <button onclick="pay()">Giao dịch thanh toán</button><br>
                </div>
                <div class="form-group">
                    <button onclick="querydr()">API truy vấn kết quả thanh toán</button><br>
                </div>
                <div class="form-group">
                    <button onclick="refund()">API hoàn tiền giao dịch</button><br>
                </div>
                <div class="form-group">
                    <button onclick="testData()" style="background: #4CAF50; color: white;">🧪 Test Database Data</button><br>
                </div>
            <p>
                &nbsp;
            </p>
            <footer class="footer">
                   <p>&copy; VNPAY <?php echo date('Y')?></p>
            </footer>
        </div> 
        <script>
             function pay() {
              window.location.href = "/payment/vnpay_pay.php";
            }
            function querydr() {
              window.location.href = "/payment/vnpay_querydr.php";
            }
             function refund() {
              window.location.href = "/payment/vnpay_refund.php";
            }
        </script>
    </body>
</html>
