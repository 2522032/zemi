<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
    <h1> 入力画面</h1>
    <form action = "insert.php" method="post">
        <div>
            <label>
                <input type="checkbox" name="window">窓<br><br>

            </label><br>
        </div>
        <div>
            <label>
                <input type="checkbox" name="gas">ガス<br><br>
            </label><br>
        </div>
        <div>
            <label>
                <input type="checkbox" name="tv">テレビ<br><br>
            </label><br>
        </div>
        <div>
            <label>
                <input type="checkbox" name="light">電気<br><br>
            </label><br>
        </div>
        <div>
            <label>
                <input type="checkbox" name="doorkey">家の鍵<br><br>
            </label><br>
        </div>
        <input type="submit" value="送信">


  
    </form>
</body>
</html>