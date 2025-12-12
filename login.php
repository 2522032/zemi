<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loginpage</title>
        <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center;     
            height: 70vh;           
            margin: 0;
            background: #f0f4ff;
            font-family: Arial, sans-serif;
        }

        form {
         text-align: center;
        }

        h1{
            text-align: center;
            height: 50px;
            margin: 0;
        }
        p{
            text-align: center;
            font-size: 18px;
            color: #333333;
        }
        label{
            font-size: 16px;
            color: #555555;

        }
    </style>

</head>
<body>
    <h1>ログイン画面</h1>
    <p> ユーザー名とパスワードを入力してください</p><br>
    <form action = "login.php" method="post">
        <label>ユーザー名</label><br>
        <input type="text" name="username"><br><br>

        <label>パスワード</label><br>
        <input type="password" name="password"><br><br>
        <input type="submit" value="Login">
    </form>
    

</body>
</html>