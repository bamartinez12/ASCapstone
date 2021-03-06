<?php

error_reporting(E_ALL ^ E_NOTICE);

session_start();


require_once('models/db.php');
require_once('models/render.php');
require_once('models/users.php');

if (isset($_REQUEST['action']))
{
	$action = $_REQUEST['action'];
}
else
{
	$action = 'login'; // default page
}

switch ($action) {

	// LOGIN
	case 'login':
	$doc_title = "Login";
	$_SESSION['newPassword'] = false;
	echo $_SESSION['newPassword'];
	include 'views/login.php';
	break;

	// VALIDATE LOGIN
	case 'validateLogin':
	$doc_title = "Login";
	require_once 'models/login.php';
	require_once 'models/orders.php';
	require_once 'models/products.php';
	$username = $_REQUEST['User'];
	$password = $_REQUEST['Pass'];
	$userInfo = getUserFromUsername($username);
	if (verifyPassword($username, $password) == false) {
		$_SESSION['loginFailed'] = true;
		include 'views/login.php';
	}
	else {
		$user = getUserFromUsername($username);
		// SAVE USER
		$_SESSION['PermissionLevel'] = $user['PermissionLevel'];
		$_SESSION['userHash'] = hash('haval256,4', 'The quick brown fox jumped over the lazy dog.') . hash('ripemd320', $username . $password) ;
		$_SESSION['UserID'] = $user['UserID'];
		$_SESSION['Username'] = $user['Username'];
		$_SESSION['Password'] = $user['Password'];
		$_SESSION['FirstName'] = $user['FirstName'];
		$_SESSION['LastName'] = $user['LastName'];
		$_SESSION['Company'] = $user['Company'];
		if(hasOrder($_SESSION['UserID']) === 1 || $_SESSION['PermissionLevel'] === 2 || $_SESSION['PermissionLevel'] === 1) {
			$_SESSION['ExistingUser'] = 1;
		}
		$orders = getUserOrders($_SESSION['UserID']);
		include 'views/dashboard.php';
	}
	break;

	// CONTACT US / REPORT ISSUE

	case 'issue':
	$doc_title = "Report Issue";
	include_once('views/contact.php');
	break;

	// CREATE ACCOUNT
	case 'createAccount':
	$doc_title = "Create Account";
	require_once 'models/users.php';
	include 'views/createAccount.php';
	break;

	// ADD ACCOUNT
	case 'addAccount':
	$doc_title = "New User";
	require_once 'models/addAccount.php';
	require_once 'models/login.php';
	if(validateNewUser($_REQUEST) === true) {
		// Get username
		$_REQUEST['PermissionLevel'] = 3;
		addUser($_REQUEST);
		$user = getUserFromUsername($_REQUEST['Username']);
		$_SESSION['PermissionLevel'] = $_REQUEST['PermissionLevel'];
		$_SESSION['UserID'] = $user['UserID'];
		$_SESSION['Username'] = $user['Username'];
		$_SESSION['Password'] = $user['Password'];
		$_SESSION['FirstName'] = $user['FirstName'];
		$_SESSION['LastName'] = $user['LastName'];
		$_SESSION['Company'] = $user['Company'];
		$_SESSION['userHash'] = hash('haval256,4', 'The quick brown fox jumped over the lazy dog.') . hash('ripemd320', $username . $password) ;
		include 'views/dashboard.php';
	} else {
		$error = "Please fill out the required fields indicated with *";
		include 'views/createAccount.php';
	}
	break;

	// FORGOT PASSWORD
	case 'forgotPassword':
	$doc_title = "Password Recovery";
	require_once 'models/forgotPassword.php';
	include 'views/forgotpassword.php';
	break;

	// SECURITY QUESTIONS
	case 'securityQuestions':
	$doc_title = "Security Questions";
	require_once 'models/forgotPassword.php';
	$email = $_REQUEST['email'];
	$_SESSION['Email'] = $email;	
	if (getUserfromEmail($_SESSION['Email']) == "") {
		include 'views/forgotPassword.php';
	} else {
		$question = askSecurityQuestion($email);
		$_SESSION['question'] = $question['question'];
		$_SESSION['correctAnswer'] = $question['answer'];
		include 'views/securityQuestions.php';
	}
	break;

	// PASSWORD RESET
	case 'passwordReset':
	$doc_title = "Password Reset";
	require_once 'models/forgotPassword.php';
	$userAnswer = $_REQUEST['userAnswer'];
	$correctAnswer = $_SESSION['correctAnswer'];
	if(verifyAnswer($userAnswer, $correctAnswer) == false) {
		include 'views/securityQuestions.php';
	} else {
		include 'views/passwordReset.php';
	}
	break;

	// VALIDATE NEW PASSWORD
	case 'validateNewPassword':
	$doc_title = "Password Validation";
	require_once 'models/forgotPassword.php';
	$password1 = $_REQUEST['password1'];
	$password2 = $_REQUEST['password2'];
	if($password1 === $password2) {
		updatePassword($_SESSION['Email'], $password1);
		$_SESSION['newPassword'] = true;
		include 'views/login.php';
	} else {
		include 'views/passwordReset.php';	
	}
	break;

	// DASHBOARD
	case 'dashboard':
	$doc_title = "DashBoard";
	require_once 'models/login.php';
	require_once 'models/dashboard.php';
	require_once 'models/products.php';
	require_once 'models/orders.php';
	if (!isset($_SESSION['Username']) && !isset($_SESSION['Password'])) {		
		include 'views/login.php';
	} else {
		$details = array();
		$user = getUserFromUsername($_SESSION['Username']);		
		if(hasOrder($_SESSION['UserID']) === 1 || $_SESSION['PermissionLevel'] === 2 || $_SESSION['PermissionLevel'] === 1) {
			$_SESSION['ExistingUser'] = 1;
		}
		
		if($_SESSION['PermissionLevel'] === 1)
                    {
                        $orders = getAllOrders();
                
                    }
                    
                    //*******************************
                    //WIP Mike 3-7
                    
                else if ($_SESSION['PermissionLevel'] === 2)
                {
                    $orderDetailIDs = getOrderDetailFromTaskTable($_SESSION['UserID']);
                    foreach($orderDetailIDs as $orderDetailID)
                    {
                        $orders = getOrderByOrderDetail($orderDetailID);
                    }
                 
                }
                
                    //**********************************
                else
                {
                    $orders = getUserOrders($_SESSION['UserID']);
                }
                        include 'views/dashboard.php';
	}

	break;

	// NEW ORDER
	case 'newOrder':
	$doc_title = "New Order";
	require_once 'models/orders.php';
	require_once 'models/products.php';
	if(isset($_SESSION['UserID'])) {
		$_SESSION['editStatus'] = 0;
		$products = getAllProducts();
		$employees = getAllEmployees();
		include 'views/order.php';
	} else {
		render_error('Something went wrong.');
	}
	break;

	// PLACE ORDER
	case 'placeOrder':
	$doc_title = "Place Order";
	require_once 'models/orders.php';
	if(isset($_REQUEST['submit']) && isset($_SESSION['userHash'])) {

		// Get product array (id, price)
		$product = $_REQUEST['ProductID'];
		// Convert to php array
		$productArray = explode(',', $product);
		// grab ID (first key)
		$productID = array_slice($productArray, 0, 1);
		// Set REQUEST['id'] to product ID from array
		$_REQUEST['ProductID'] = implode($productID);
		$_REQUEST['PricePaid'] = str_replace("$", "", $_REQUEST['PricePaid']);
		
		// add order
		addOrder($_REQUEST);
		// Cache Order data
		$orderResponse = 'Thank you for placing your order.';
		if(hasOrder($_SESSION['UserID']) === 1 || $_SESSION['PermissionLevel'] === 2 || $_SESSION['PermissionLevel'] === 1) {
			$_SESSION['ExistingUser'] = 1;
		}

		include 'views/dashboard.php';
	} else {
		render_error('Something went wrong.');
	}
	break;

	// EDIT ORDER
	case 'editOrder':
	$doc_title = "Edit Existing Order";
	require_once 'models/orders.php';
	require_once 'models/products.php';
        require_once 'models/comments.php';
	if(isset($_SESSION['UserID'])) {
		$_SESSION['editStatus'] = 1;
		$orderDetailID = $_REQUEST['OrderDetailID'];
		$products = getAllProducts();
                
                //Mike 3-7
                $comments = getComment($orderDetailID);
                //end Mike 3-7
		$order = getOrderByOrderDetail($orderDetailID);

		include 'views/order.php';
	} else {
		render_error('Something went wrong.');
	}
	break;

	// ALL ORDERS
	case 'allOrders':
	$doc_title = "Orders";
	require_once 'models/orders.php';
	if(isset($_SESSION['UserID'])) {
		include 'views/allOrders.php';
	} else {
		render_error('Something went wrong.');
	}
	break;

	// LOGOUT
	case 'logOut':
	$doc_title = "Log Out";
	session_start();
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(),'',0,'/');
    session_regenerate_id(true);
	include 'views/login.php';
	break;

	// ----- EMPLOYEE ----- //
	case 'orderEmployee':
	$doc_title = "Order";
	include 'views/order-employee.php';
	break;

	// UNKNOWN ACTION
	default:
	$doc_title = "Unknown";
		render_error('Unknown request.');
	break;
}

