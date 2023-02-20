<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	include 'includes/session.php';

	if(isset($_POST['signup'])){
		$firstname = $_POST['firstname'];
		$lastname = $_POST['lastname'];
		$email = $_POST['email'];
		$password = $_POST['password'];
		$repassword = $_POST['repassword'];

		$_SESSION['firstname'] = $firstname;
		$_SESSION['lastname'] = $lastname;
		$_SESSION['email'] = $email;

		if(!isset($_SESSION['captcha'])){
			require('recaptcha/src/autoload.php');		
			// $recaptcha = new \ReCaptcha\ReCaptcha('6LcCbgAiAAAAAI9EBXGwFMTpLJu771czmuZDKke6', new \ReCaptcha\RequestMethod\SocketPost());
			// $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
			
			if(isset($_POST['g-recaptch-response']) && !empty($_POST['g-recaptch-response']))
			{
				$secret = "6LeFAgwiAAAAAAyFIzvCDSa6V5EFToUnQtDxvbuO";

				$response = file_get_contents(('https://www.google.com/recaptch/api/siteverify?
				secret'.$secret.'$response='.$_POST['g-recaptch-response']));

				$data = json_decode($response);

				if (!$resp->isSuccess()){
					$_SESSION['error'] = 'Please answer recaptcha correctly';
					header('location: signup.php');	
					exit();	
				}	
				else{
					$_SESSION['captcha'] = time() + (10*60);
				}
			}


		}
		if($password != $repassword){
			$_SESSION['error'] = 'Passwords did not match';
			header('location: signup.php');
		}
		else{
			$conn = $pdo->open();

			$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM users WHERE email=:email");
			$stmt->execute(['email'=>$email]);
			$row = $stmt->fetch();
			if($row['numrows'] > 0){
				$_SESSION['error'] = 'Email already taken';
				header('location: signup.php');
			}
			else{
				$now = date('Y-m-d');
				$password = password_hash($password, PASSWORD_DEFAULT);

				//generate code
				$set='123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$code=substr(str_shuffle($set), 0, 12);

				try{
					$stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, activate_code, created_on) VALUES (:email, :password, :firstname, :lastname, :code, :now)");
					$stmt->execute(['email'=>$email, 'password'=>$password, 'firstname'=>$firstname, 'lastname'=>$lastname, 'code'=>$code, 'now'=>$now]);
					$userid = $conn->lastInsertId();

					$message = "
						<h2>Thank you for Registering.</h2>
						<p>Your Account:</p>
						<p>Email: ".$email."</p>
						<p>Password: ".$_POST['password']."</p>
						<p>Please click the link below to activate your account.</p>
						<a href='http://localhost/o_shop/o_shop/activate.php?code=".$code."&user=".$userid."'>Activate Account</a>
					";


				   


				}
				catch(PDOException $e){
					$_SESSION['error'] = $e->getMessage();
					header('location: register.php');
				}

				$pdo->close();

			}

		}

	}
	else{
		$_SESSION['error'] = 'Fill up signup form first';
		header('location: signup.php');
	}

?>

<!DOCTYPE html>
<html>
<body>

<h1><center>Register Successfully!!</center></h1>
<p><center>Wait for Your Account Activation.</center></p>
<p><center>Back To</center></p>
<center><a href="index.php">Home</a><br></center>


</body>
</html>