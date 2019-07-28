<?php 
 /**
 * @category   sms sender
 * @author   Hosein_Beheshti
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use mysqli;
use Thread;

/** 
* DatabaseController is a class that include 
* connect() and report() functions
* for connect to database and work with it
*/

class DatabaseController extends Controller
{
	//a constructor for DatabaseController class
	//that create a database and a table if there is not exist
	public function __construct(){
		//Our database specifications
		$servername = "localhost";
		$username = "root";
		$password = "";
		$databaseName = "db_sms";


		// Create connection
		$conn = new mysqli($servername, $username, $password);
		// Check connection
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		} 
		//Create database
		$sql = "CREATE DATABASE db_sms CHARACTER SET utf8 COLLATE utf8_general_ci";
		if ($conn->query($sql) === TRUE) {
		    // echo "database created successfully";
		    $conn->close();

		} else {
		    // echo "Error creating database: " . $conn->error;
		    $conn->close();
		}
		// Create connection
		$conn = new mysqli($servername , $username , $password , $databaseName );
		// Check connection
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		} 

		// Create table
		$sql = "CREATE TABLE tbl_sms (
			id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
			mobile BIGINT(20) UNSIGNED	 NOT NULL,
			message VARCHAR(1000)  NOT NULL,
			serverstatus INT(1) NOT NULL
			)";
		if ($conn->query($sql) === TRUE) {
		   //  echo "Table created successfully";
		} else {
		  //  echo "Error creating table: " . $conn->error;
		}
	}
	/**
	* this function connect to our database
	*
	* @param $servername 
	* @param $username
	* @param $password
	* @param $databaseName
	* @return $conn an object
	*/
	public function connect($servername , $username , $password , $databaseName){

	$conn = new mysqli($servername , $username , $password , $databaseName );

	if($conn->connect_error){
		die(" connection failed " . $conn->connect_error);
	}
	else{
		//echo "connected to database successfully"
	}
	return $conn;
	}

	/**
	* this function insert datas to database
	* @param $number phone number it is an integer 
	* @param $message test message it is string
	* @param $servers server status it is an integer
	*/
	public function insert($number , $message , $servers){
	$conn = $this->connect("localhost" , "root" , "" , "db_sms");
	$sql = "INSERT INTO tbl_sms( mobile , message ,serverstatus ) VALUES($number, '$message' , $servers)";
	if ($conn->query($sql) === TRUE){
		//echo " inserted to database ";

	} else{
		//echo " can not insert ";
	}
	$conn->close();

	return new Response();
	}

    /**
     * @Route("/report")
     *
     * this function get datas from database and returns a html page to show reports
	 * @return a html page
     */  
    public function report()
    {
    //call connect function
	$conn = $this->connect("localhost" , "root" , "" , "db_sms");

	//select from database
	$sql = "SELECT * FROM tbl_sms";
    if ($result = $conn->query($sql)){
    	$last_id = mysqli_num_rows($result);
    }
    //number used from API_1
    $serverone = 0 ;
    //number used from API_2
    $servertwo = 0 ;
	//API_1 error percentage
	$serveronefaults = 0;
	//API_2 error percentage
    $servertwofaults = 0;

    //select datas from database
    $sql = "SELECT * FROM tbl_sms ";
    if ($result = $conn->query($sql)){
    	while ($row = $result->fetch_assoc()) {
    		if ($row['serverstatus'] == 1 )
    			$serverone++;
    		else if($row['serverstatus'] == 2){
    			$servertwo++;
    			$serveronefaults++;
    		}
    		else if($row['serverstatus'] == 0){
    			$servertwofaults++;
    			$serveronefaults++;
    		}
    	}
    }
    //calculate percentage
    $serveronefaults = 100 * $serveronefaults/($serverone + $serveronefaults);
    $servertwofaults = 100 * $servertwofaults/($servertwofaults + $servertwo);

    //select data from database and use of GROUP BY to get 10 most used phone numbers
    $sql = "SELECT mobile,COUNT(message)
			FROM tbl_sms  
			GROUP BY mobile 
			";

	$number_arr = array();
	$topNumbers = array();
	if ($result = $conn->query($sql)){
		
		while ($row = $result->fetch_assoc()) {
			$number_arr += [$row['mobile'] => $row['COUNT(message)']];
			}	
		arsort($number_arr);

    }

    $counter =0;
	foreach ($number_arr as $key => $value) {
		$topNumbers[$counter] = array($key,$value);
		    $counter++;
	    if ($counter>9){
	    	break;
	    }
    }
    // var_dump($topNumbers);

	$conn->close();

	 //render report page and passing variables to show
	 return $this->render('view/report.html.twig',['last_id' => $last_id , 'serverone' => $serverone , 'servertwo' => $servertwo 
	 	, 'serveronefaults' => $serveronefaults , 'servertwofaults' => $servertwofaults , 'topnumbers' => $topNumbers ]);
  	} 
  	
 	



}
 ?>