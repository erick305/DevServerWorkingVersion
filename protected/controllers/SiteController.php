<?php

class SiteController extends Controller
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'
		$this->render('index');
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$name='=?UTF-8?B?'.base64_encode($model->name).'?=';
				$subject='=?UTF-8?B?'.base64_encode($model->subject).'?=';
				$headers="From: $name <{$model->email}>\r\n".
					"Reply-To: {$model->email}\r\n".
					"MIME-Version: 1.0\r\n".
					"Content-type: text/plain; charset=UTF-8";

				mail(Yii::app()->params['adminEmail'],$subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		$model=new LoginForm;

		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$model->attributes=$_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if($model->validate() )
			{	
				$username = $model->username;
				$user = User::model()->find("username=:username",array(':username'=>$username));
				if ($user->disable != 1)
				{
					$model->login();
					if (Yii::app()->user->returnUrl == "/JobFair/index.php") {
						if ($user->isAStudent()) {
							$this->redirect("/JobFair/index.php/home/studenthome");
						} elseif ($user->isAEmployer()) {
							$this->redirect("/JobFair/index.php/home/employerhome");
						} else {
							$this->redirect("/JobFair/index.php/home/adminhome");
						}
					} else {
						$this->redirect(Yii::app()->user->returnUrl);
					}
				} else {
					$this->redirect("/JobFair/index.php/site/page?view=disableUser");
				}
			}
		} 
		// display the login form
		$this->render('login',array('model'=>$model));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	function genRandomString($length = 10) {
		$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		$string = "";
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
	
		return $string;
	}
	
	
	public function actionForgotPassword()
	{
		if(isset($_POST['User']))
		{
			$email = $_POST['User']['email'];
			$model = User::model()->find("email=:email",array(':email'=>$email));
			if ($model == null){
				$error = 'Email does not exist in our records';
				$this->render('forgotPassword', array('error'=>$error));
				return;
			}
			$password = $this->genRandomString(10);
			$hasher = new PasswordHash(8, false);
			$model->password = $hasher->HashPassword($password);
			$model->save(false);
            $link = CHtml::link('here', 'http://'. Yii::app()->request->getServerName()  . '/JobFair/' );
            $message = '<br/>Username: '. $model->username .'<br/>Password: '. $password . '<br/>Login: '. $link;
            User::sendEmail($email, "Your new password", "Your password has been reset", $message);
			//User::sendEmailWithNewPassword($email, $password, $model->username);
			$error = 'Email has been sent';
			$this->render('forgotPassword', array('error'=>$error));
			return;
		}
	
	$error = '';
	$this->render('forgotPassword', array('error'=>$error));
	
	}
	
}