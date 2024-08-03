<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo esc_html( $error_messages[0] ); ?></title>
		<style>
			body {
				font-family: Arial, sans-serif;
				background-color: #f8f8f8;
				color: #333;
				display: flex;
				justify-content: center;
				align-items: center;
				height: 100vh;
				margin: 0;
				padding: 20px;
			}
			.container {
				text-align: center;
				background-color: #fff;
				padding: 35px;
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
				border-radius: 8px;
			}
			h1 {
				color: #e74c3c;
			}
			p {
				font-size: 18px;
				margin: 15px 0;
			}
			.suggestion {
				font-size: 16px;
				color: #555;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<h1><?php echo esc_html( $error_messages[0] ); ?></h1>
			<p><?php echo esc_html( $error_messages[1] ); ?></p>
			<p class="suggestion"><?php echo esc_html( $error_messages[2] ); ?></p>
		</div>
	</body>
</html>
